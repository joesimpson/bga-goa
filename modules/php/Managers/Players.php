<?php

namespace Bga\Games\Goa\Managers;

use Bga\GameFramework\Notify;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Exceptions\PrematureStateChangeException;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Models\AutomaPlayer;
use Bga\Games\Goa\Models\Player;
use Bga\Games\Goa\Models\Tile;
use Bga\Games\Goa\States\PlayerChoice;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */

class Players extends \Bga\Games\Goa\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \Bga\Games\Goa\Models\Player($row);
  }

  /**
   * @param array $players
   * @return Collection $players
   */
  public static function setupNewGame($players, $options)
  {
    // Create players
    $gameInfos = Game::get()->getGameinfos();
    $colors = $gameInfos['player_colors'];
    shuffle($colors);//Shuffle for cases where color matters
    $query = self::DB()->multipleInsert(['player_id', 'player_color', 'player_name', 'money']);

    $query_values = [];
    $initialMoney = 0; // will be set by each session

    foreach ($players as $player_id => $player) {
      // Now you can access both $player_id and $player array
      $color = array_shift($colors);
      $query_values[] = [
          $player_id,
          $color,
          addslashes($player["player_name"]),
          $initialMoney,
      ];
    }
    $query->values($query_values);

    $playersObjects = self::getAll();
    if($gameInfos['favorite_colors_support'] ){
      Game::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);

      //Reload DAO 
      $playersObjects = self::getAll();
      foreach ($playersObjects as $pId => $player) {
        //$color = Players::getColor($pId);
        $color = $player->getColor(); 
      }
    }
    Game::get()->reloadPlayersBasicInfos();

    $assignedColors = $playersObjects->map( function ($player) { return $player->getColor();})->toArray();
    
    if(Globals::getOptionAutomaDifficulty() != OPTION_AUTOMA_DIFFICULTY_OFF){
      //$automa_color = array_shift($colors);
      //!\ beware of reassignment based on player prefs -> make sure we don't reuse a player color if possible
      foreach($colors as $color){
        if(in_array($color,$assignedColors)){
          continue;
        }
        $automa_color = $color;
        break;
      }
      Globals::setAutomaColor($automa_color);
      Notifications::automaColor($automa_color, Utils::getAutomaPId());
    }

    //Init first player
    if(!array_key_exists("DEBUG_SETUP",$options)){
      $player = Players::get(Globals::getFirstPlayer());
      Players::changeActive($player->getId());
      $player->giveExtraTime();
    }

    return $playersObjects;
  }
 
  /**
   * Setup new player turn
   */
  public static function setupNewTurn($player)
  {
    //Stats::inc("turns_number_p",$player);
  }
  
  public static function setupSession(int $session)
  {
    $firstPlayerId = Globals::getFirstPlayer();

    if($session > 1){
      //CLEAN PREVIOUS SESSIONS
      self::DB()
        ->update(['money' =>0, 'ships' => 0, 'contracts' => 0, 'compasses' => 0])
        ->run();
      //NOTIF reset money and currencies
      Notifications::resetCurrencies();
    }
    //refreshed datas
    $players = Players::getAll();
    $nbPlayers = $players->count();

    /**
     * Game rule : starting player is determined by randomness of cards : 
     * 
     * The first player draws the top Exploration Card. The  other players then do the same in clockwise order until a player reveals a card with the Elephant symbol.
     */
    $elephantFound = false;
    while(!$elephantFound && $nbPlayers > 1){
      if(!isset($player)) $player = $players[$firstPlayerId];
      else $player = Players::getNextPlayer($player->getId());
      $cards = Cards::drawAndDiscardExploration($player, 1);
      $card = $cards->first();
      if(isset($card) && $card->getSymbol() === CARD_SYMBOL_ELEPHANT ){
        Globals::setFirstPlayer($firstPlayerId);
        $elephantFound = true;
        break;
      }
    }
    
    $firstPlayer = $players[$firstPlayerId];
    Tokens::giveFirstPlayerToken($firstPlayer);

    foreach($players as $pId => &$player){
      if($pId == $firstPlayerId){
        $initialMoney = NB_INITIAL_MONEY_FIRST;
      } else {
        $initialMoney = NB_INITIAL_MONEY_OTHERS;
      }
      Players::giveMoney($player, $initialMoney);
      Players::giveShips($player, NB_INITIAL_SHIPS);
      Players::giveContracts($player, NB_INITIAL_CONTRACTS);
    }
    
    $turnOrder = Players::getTurnOrder($firstPlayerId);
    foreach ($turnOrder as $key => $orderPId) {
      Stats::set("turnOrder", $orderPId, $key + 1);
    }
    //DEBUG 
    //Notifications::message(json_encode($turnOrder), ['$turnOrder'=> $turnOrder]);
  }

  public static function resetBidPhase()
  {
    self::DB()
      ->update(['passed' => 0])
      ->run();
      //TODO JSA AUTOMA
  }
  public static function resetActionsPhase()
  {
    self::DB()
      ->update(['nbActions' => 0, 'passed' => 0, 'actionPlayed' => 0, 'cardsPlayed' =>null])
      ->run();
    $automa = Players::automaPlayer();
    if(isset($automa)){
      $automa->setNbActions(0);
      $automa->setPassed(false);
      $automa->setActionPlayed(false);
    }
  }
  /**
   * Save the information that the player played one of their authorized actions
   */
  public static function registerNewAction(Player $player, string $serverAction)
  {
    if($player->getNbActions() >= NB_MIN_ACTIONS_PER_ROUND){
        Players::giveExtraActionCards($player,-1);
    }
    $player->incNbActions();
    $player->setActionPlayed(true);
    $action_type = Utils::getActionTypeFromServerAction($serverAction);
    Notifications::playAction($player, $action_type);
    Stats::registerNewAction($player, $action_type);
  }
  /**
   * Save the information that the player played one of their card
   */
  public static function registerCardPlayed(Player $player, int $card_id)
  {
    $cardsPlayed = $player->getCardsPlayed();
    $cardsPlayed[] = $card_id;
    $player->setCardsPlayed($cardsPlayed);
  }

  /**
   * @param int $pId
   * @param int $score score to add to current score
   */
  public static function incPlayerScore(int $pId, int $score)
  {
    Game::get()->trace("incPlayerScore($pId, $score)");

    return self::DB()
      ->inc(['player_score' => $score])
      ->wherePlayer($pId)
      ->run();
  }

  public static function getActiveId()
  {
    return Game::get()->getActivePlayerId();
  }

  public static function getCurrentId($bReturnNullIfNotLogged = false)
  {
    return (int) Game::get()->getCurrentPlayerId($bReturnNullIfNotLogged);
  }

  public static function getAll()
  {
    return self::DB()->get(false);
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public static function get(int|null $pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    if($pId == AUTOMA_PLAYER_ID) return self::automaPlayer();
    return self::DB()
      ->where($pId)
      ->getSingle();
  }

  /**
   * @return Player
   */
  public static function getActive()
  {
    return self::get();
  }

  /**
   * @return Player
   */
  public static function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public static function getNextId($player = null)
  {
    $player = $player ?? Players::getCurrent();
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    $firstPlayerIdInTable = array_key_first($table);
    if(AUTOMA_PLAYER_ID == $pId) {
      return $firstPlayerIdInTable;
    }
    $nextPid = $table[$pId];


    $automa_id = Utils::getAutomaPId();
    $automaIsActive = Globals::isAutomaActive();
    if(isset($automa_id) && !$automaIsActive && $nextPid == $firstPlayerIdInTable ){
      return $automa_id;
    }
    return $nextPid;
  }
  
  /**
   * @param int $player_id
   * @param array $tried_pid
   * @return Player, or null if no one found
   */
  public static function getNextPlayerWithActions(int $player_id, array &$tried_pid = []) : Player |null
  {
    Game::get()->trace("getNextPlayerWithActions($player_id, ".json_encode($tried_pid).")");
    $nextPlayer = Players::getNextPlayer($player_id);
    if(in_array($nextPlayer->getId(), $tried_pid) ) return null;
    if(isset($nextPlayer) 
      && $nextPlayer->isPassed() != 1 
      && ($nextPlayer->getNbActions() <NB_MIN_ACTIONS_PER_ROUND || $nextPlayer->getCompasses() > 0 )
    ){
      return $nextPlayer;
    }
    $tried_pid[] = $nextPlayer->getId();
    return self::getNextPlayerWithActions($nextPlayer->getId(), $tried_pid);
  }
  
  /**
   * @param int $player_id
   * @return Player player playing after $player_id
   */
  public static function getNextPlayer(int $player_id) : Player
  {
    $nextPlayer_id = Players::getNextId($player_id);
    if(AUTOMA_PLAYER_ID == $nextPlayer_id) return Players::automaPlayer();
    return Players::get($nextPlayer_id);
  }

  public static function automaPlayer() : AutomaPlayer {
    return new AutomaPlayer([
      'player_id'     => AUTOMA_PLAYER_ID, 
      'player_name'   => Utils::getAutomaName(), 
      'player_color'  => Globals::getAutomaColor(),
      'nbActions'     => Globals::getAutomaNbActions(),
      'passed'        => Globals::isAutomaPassed(),
      'actionPlayed'  => Globals::isAutomaActionPlayed(),
      ]);
  }

  /*
   * Return the number of players
   */
  public static function count()
  {
    return self::DB()->count();
  }

  /*
   * getUiData : get all ui data of all players
   */
  public static function getUiData($pId)
  {
    return self::getAll()
      ->map(function ($player) use ($pId) {
        return $player->getUiData($pId);
      })
      ->toAssoc();
  }

  /**
   * Get current turn order according to first player variable
   */
  public static function getTurnOrder($firstPlayer = null)
  {
    $firstPlayer = $firstPlayer ?? Globals::getFirstPlayer();
    $order = [];
    $p = $firstPlayer;
    do {
      $order[] = $p;
      $p = self::getNextId($p);
    } while ($p != $firstPlayer);
    return $order;
  }

  /**
   * This allow to change active player
   */
  public static function changeActive(int $pId)
  {
    Game::get()->gamestate->changeActivePlayer($pId);
  }

  /**
   * Sets player datas related to turn number $turn
   * @param array $player_ids
   * @param int $turn
   */
  public static function startTurn(array $player_ids,int $turn)
  {
    foreach($player_ids as $player_id){
      $player = self::get($player_id);
      $player->startTurn($turn);
    }
  }

  // ----------------------------------------------------------------------------
  // ----------------------------------------------------------------------------
  public static function getAllHarvestBaseGain() : array
  {
    $players = Players::getAll();
    return $players->map(function($p) { return Players::getHarvestBaseGain($p);})->toAssoc();
  }
  public static function getAllFoundBaseContracts() : array
  {
    $players = Players::getAll();
    return $players->map(function($p) { return Players::getFoundBaseContracts($p);})->toAssoc();
  }
  public static function getHarvestBaseGain(Player $player) : int
  {
    $outpostMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_HARVEST, $player->getId());
    return PROGRESS_GAINS_HARVEST[$outpostMarker->getRow() - 1];
  }
  public static function getFoundBaseContracts(Player $player) : int
  {
    $outpostMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, $player->getId());
    return PROGRESS_GAINS_OUTPOST[$outpostMarker->getRow() - 1];
  }
  public static function giveContracts(Player &$player, int $nb = 1)
  {
    if($player->getId() == AUTOMA_PLAYER_ID) return;
    if($nb ==0) return;
    $player->incContracts($nb);
    if($nb > 0) Stats::inc( "totalContracts", $player->getId(), $nb);
    Notifications::getContracts($player,$nb);
  }
  public static function giveShips(Player &$player, int $nb = 1)
  {
    if($player->getId() == AUTOMA_PLAYER_ID) return;
    if($nb ==0) return;
    $player->incShips($nb);
    if($nb > 0) Stats::inc( "totalShips", $player->getId(), $nb);
    Notifications::getShips($player,$nb);
  }
  public static function giveMoney(Player &$player, int $nb = 1, bool $sendNotif = true)
  {
    if($player->getId() == AUTOMA_PLAYER_ID) return;
    if($nb == 0) return;
    $player->incMoney($nb);
    if($nb > 0) Stats::inc( "totalMoney", $player->getId(), $nb);
    if($sendNotif) Notifications::getMoney($player,$nb);
  }
  public static function giveExtraActionCards(Player &$player, int $nb = 1)
  {
    if($player->getId() == AUTOMA_PLAYER_ID) return;
    if($nb == 0) return;
    $player->incCompasses($nb);
    if($nb > 0) Stats::inc( "totalCompasses", $player->getId(), $nb);
    Notifications::getCompass($player,$nb);
  }

  /**
   * @throws PrematureStateChangeException when caller will need to go to another state than default one
   * */
  public static function directGain(Player &$player, int $gainType, int $gainQuantity, Tile $tile, bool &$doCheckpoint)
  {
    switch($gainType){
      // gains from tiles with MARKET_TYPE_ACTION_DIRECT
      case ACTION_CONTRACT:
        Players::giveContracts($player, $gainQuantity);
        break;
      case ACTION_SHIP:
        Players::giveShips($player, $gainQuantity);
        break;
      case ACTION_COMPASS:
        Players::giveExtraActionCards($player, $gainQuantity);
        break;
      case ACTION_EXPLORATION:
        Cards::drawExplorationCardsToPlayer($player, $gainQuantity, $doCheckpoint);
        break;
      case ACTION_SHIPS_OR_CONTRACTS: 
        //Save datas for next state
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_ACTION, $gainQuantity, [ACTION_SHIP, ACTION_CONTRACT]);
        throw new PrematureStateChangeException (1,PlayerChoice::class,$player);
      case ACTION_FAVOR: 
        $tilesToChoose = Tiles::getTilesForFavor()->map(function ($tile) {
          return $tile->getType();
        })->getIds();
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_TILE, $gainQuantity, $tilesToChoose);
        throw new PrematureStateChangeException (2,PlayerChoice::class,$player);
        break;
      default: break;
    }
  }

}
