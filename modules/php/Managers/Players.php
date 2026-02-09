<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Game;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Exceptions\PrematureStateChangeException;
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
    $query = self::DB()->multipleInsert(['player_id', 'player_color', 'player_canal', 'player_name', 'player_avatar','money']);

    $query_values = [];
    $initialMoney = 10;

    $firstPlayer = Globals::getFirstPlayer();

    foreach ($players as $player_id => $player) {
      if($player_id == $firstPlayer){
        $initialMoney = 5;
      }
      // Now you can access both $player_id and $player array
      $color = array_shift($colors);
      $query_values[] = [
          $player_id,
          $color,
          $player["player_canal"],
          addslashes($player["player_name"]),
          addslashes($player["player_avatar"]),
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
    Stats::inc("turns_number_p",$player);
  }

  public static function resetBidPhase()
  {
    self::DB()
      ->update(['passed' => 0])
      ->run();
  }
  public static function resetActionsPhase()
  {
    self::DB()
      ->update(['nbActions' => 0, 'passed' => 0, 'actionPlayed' => 0, 'cardsPlayed' =>null])
      ->run();
  }
  /**
   * Save the information that the player played one of their authorized actions
   */
  public static function registerNewAction(Player $player)
  {
    if($player->getNbActions() >= NB_MIN_ACTIONS_PER_ROUND){
        Players::giveExtraActionCards($player,-1);
    }
    $player->incNbActions();
    $player->setActionPlayed(true);
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
    return $table[$pId];
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
    return Players::get($nextPlayer_id);
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

  public static function getFoundBaseContracts(Player $player) : int
  {
    $outpostMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, $player->getId());
    return PROGRESS_GAINS_OUTPOST[$outpostMarker->getRow() - 1];
  }
  public static function giveContracts(Player &$player, int $nb = 1)
  {
    if($nb ==0) return;
    $player->incContracts($nb);
    Notifications::getContracts($player,$nb);
  }
  public static function giveShips(Player &$player, int $nb = 1)
  {
    $player->incShips($nb);
    Notifications::getShips($player,$nb);
  }
  public static function giveMoney(Player &$player, int $nb = 1)
  {
    $player->incMoney($nb);
    Notifications::getMoney($player,$nb);
  }
  public static function giveExtraActionCards(Player &$player, int $nb = 1)
  {
    $player->incCompasses($nb);
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
        Globals::addChoiceTile($tile,$gainType, $gainQuantity, [ACTION_SHIP, ACTION_CONTRACT]);
        throw new PrematureStateChangeException (1,PlayerChoice::class,$player);
      case ACTION_FAVOR: 
        $tilesToChoose = Tiles::getTilesForFavor()->map(function ($tile) {
          return $tile->getType();
        })->toAssoc();
        Globals::addChoiceTile($tile,$gainType, $gainQuantity, $tilesToChoose,CHOICE_PICK_TILE);
        throw new PrematureStateChangeException (2,PlayerChoice::class,$player);
        break;
      default: break;
    }
  }

}
