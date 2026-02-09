<?php

namespace Bga\Games\Goa\Core;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Models\ExplorationCard;
use Bga\Games\Goa\Models\OutpostTile;
use Bga\Games\Goa\Models\Player;
use Bga\Games\Goa\Models\ProgressMarker;
use Bga\Games\Goa\Models\Tile;
use Bga\Games\Goa\Models\Token;

class Notifications
{ 
  
  public static function newSession(int $session)
  {
    $msg = clienttranslate('Session ${n} begins');

    self::notifyAll('newSession', $msg, [
      'n' => $session,
    ]);
  }
  public static function setupMarket(int $phase, Collection $tiles)
  {
    self::notifyAll("setupMarket", clienttranslate('The market board is filled with tiles'), [
        "phase" => $phase,
        "tiles" => $tiles->ui(),
        "preserve" => ['tiles'],
    ]);
  }

  public static function newRound(int $round)
  {
    $msg = clienttranslate('Round ${n} begins');

    self::notifyAll('newRound', $msg, [
      'n' => $round,
    ]);
  }
  
  public static function newRoundStep(int $roundStep, string $stepMessage)
  {
    $msg = clienttranslate('Round step ${n} begins : ${stepMessage}');

    self::notifyAll('newRoundStep', $msg, [
      'n' => $roundStep,
      'stepMessage' => $stepMessage,
      "i18n" => ['stepMessage'],
    ]);
  }

  public static function newTurn(int $turn)
  {
    //$msg = clienttranslate('Actions turn ${n} begins');
    $msg = '';

    self::notifyAll('newTurn', $msg, [
      'n' => $turn,
    ]);
  }

  public static function giveFirstPlayerToken(Player $player, Token $token)
  {
    self::notifyAll("giveFirstPlayerToken", clienttranslate('${player_name} gets the ${token_icon} marker and becomes the starting player'), [
        "player" => $player,
        "token" => $token->getUiData(),
        "token_icon" => '',
        'preserve'=>['token'],
    ]);
  }

  public static function auctionPlaced(Player $player, int $row, int $col, Token $token)
  {
    //${player_name} places the auction marker ${token_icon} on the market (row ${row}, column ${col}) 
    //Change message for less verbose because the token is visible in the grid
    self::notifyAll("auctionPlaced", clienttranslate('${player_name} places the auction marker ${token_icon} on the market'), [
        "player" => $player,
        "row" => $row,
        "col" => $col,
        "token" => $token->getUiData(),
        "token_icon" => '',
        'preserve'=>['token'],
    ]);
  }
  
  public static function newBid(Player $player, int $previousBid, int $nextBid,)
  {
    self::notifyAll("newBid", clienttranslate('${player_name} raises the bid from ${a} ${icon_money} to ${b} ${icon_money}'), [
        "player" => $player,
        "a" => $previousBid,
        "b" => $nextBid,
        "icon_money" => '',
    ]);
  }
  
  public static function passBid(Player $player, )
  {
    self::notifyAll("passBid", clienttranslate('${player_name} passes'), [
        "player" => $player,
    ]);
  }
  
  public static function passAction(Player $player, )
  {
    self::notifyAll("passAction", clienttranslate('${player_name} passes'), [
        "player" => $player,
    ]);
  }

  public static function winsAuction(Player $playerWins, int $bid, Player|null $player2Auctioneer = null )
  {
    if(isset($player2Auctioneer)){
      self::notifyAll("winsAuction", clienttranslate('${player_name} wins the auction and gives ${n} ${icon_money} to ${player_name2}'), [
          "player" => $playerWins,
          "player2" => $player2Auctioneer,
          "n" => $bid,
          "icon_money" => clienttranslate('Pardaos'),
          "i18n" => ['icon_money'],
      ]);
    } else {
      self::notifyAll("winsAuctionBank", clienttranslate('${player_name} wins the auction and gives ${n} ${icon_money} to the bank'), [
          "player" => $playerWins,
          "n" => $bid,
          "icon_money" => clienttranslate('Pardaos'),
          "i18n" => ['icon_money'],
      ]);
    }
  }
  
  public static function auctionMarkerBack(Player $player, Token $token)
  {
    self::notifyAll("auctionMarkerBack", clienttranslate('${player_name} takes back the auction marker ${token_icon}'), [
        "player" => $player,
        "token" => $token->getUiData(),
        "token_icon" => '',
        'preserve'=>['token'],
    ]);
  }
  
  public static function getContracts(Player $player, int $n)
  {
    self::notifyAll("getContracts", clienttranslate('${player_name} gets ${n} ${icon_contracts}'), [
        "player" => $player,
        "n" => $n,
        "icon_contracts" => clienttranslate('Contracts'),
    ]);
  }
  
  public static function getShips(Player $player, int $n)
  {
    self::notifyAll("getShips", clienttranslate('${player_name} gets ${n} ${icon_ships}'), [
        "player" => $player,
        "n" => $n,
        "icon_ships" => clienttranslate('Ships'),
    ]);
  }
  public static function getCompass(Player $player, int $n)
  {
    self::notifyAll("getCompass", clienttranslate('${player_name} gets ${n} ${icon_compass}'), [
        "player" => $player,
        "n" => $n,
        "icon_compass" => clienttranslate('Compass cards'),
    ]);
  }
  public static function giveTile(Player $player, Tile $tile)
  {
    $notifId = "giveTile";
    //if($tile instanceof OutpostTile) $notifId = "giveOutpost";
    self::notifyAll($notifId, clienttranslate('${player_name} gets the tile ${tile_icon}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        'preserve'=>['tile'],
    ]);
  }
  public static function replaceTile(Player $player, Tile $tile)
  {
    self::notifyAll("replaceTile", clienttranslate('${player_name} replaces the tile ${tile_icon}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        'preserve'=>['tile'],
    ]);
  }

  public static function discardTile(Player $player, Tile $tile)
  {
    self::notifyAll("discardTile", clienttranslate('${player_name} discards the tile ${tile_icon}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        'preserve'=>['tile'],
    ]);
  }
  
  public static function playTile(Player $player, Tile $tile)
  {
    self::notifyAll("playTile", clienttranslate('${player_name} plays the tile ${tile_icon}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        'preserve'=>['tile'],
    ]);
  }
  
  public static function tryFoundOutpost(Player $player, Tile $tile, int $nbContracts)
  {
    $colonyName = Utils::getColonyName($tile->getColony());
    self::notifyAll("tryFoundOutpost", clienttranslate('${player_name} tries to found the ${name} outpost ${tile_icon} which requires ${n} ${icon_contracts}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        "n" => $nbContracts,
        "name" => ($colonyName),
        "icon_contracts" => clienttranslate('Contracts'),
        'preserve'=>['tile'],
        'i18n'=>['name'],
    ]);
  }
  
  public static function resultFoundOutpost(Player $player, int $nbContracts)
  {
    self::notifyAll("resultFoundOutpost", clienttranslate('Cards revealed ${n} ${icon_contracts} to found the outpost'), [
        "player" => $player,
        "n" => $nbContracts,
        "icon_contracts" => clienttranslate('Contracts'),
    ]);
  }
  public static function abandonOutpost(Player $player, Tile $tile)
  {
    self::notifyAll("abandonOutpost", clienttranslate('${player_name} chooses not to found the outpost'), [
        "player" => $player,
    ]);
  }

  public static function drawAndDiscardExploration(int $playerId, ExplorationCard $card)
  {
    self::notifyAll("drawAndDiscardExploration", clienttranslate('${player_name} draws and discard ${card_icon}'), [
       "player_id" => $playerId,
        "player_name" => Game::get()->getPlayerNameById($playerId),
        "card" => $card->getUiData(),
        "card_id" => $card->getId(),
        "card_icon" => '',
        'preserve'=>['card'],
    ]);
  }

  public static function discardExploration(Player $player, ExplorationCard $card)
  {
    self::notifyAll("discardExploration", clienttranslate('${player_name} discards ${card_icon} from his hand'), [
       "player" => $player,
        "card" => $card->getUiData(),
        "card_id" => $card->getId(),
        "card_icon" => '',
        'preserve'=>['card'],
    ]);
  }
  
  public static function playExploration(Player $player, ExplorationCard $card)
  {
    //keep same id as discardExploration to keep same UI behavior for now
    self::notifyAll("discardExploration", clienttranslate('${player_name} plays ${card_icon} from his hand'), [
       "player" => $player,
        "card" => $card->getUiData(),
        "card_id" => $card->getId(),
        "card_icon" => '',
        'preserve'=>['card'],
    ]);
  }
  public static function giveExplorationCardsToPlayer(Player $player, Collection $cards)
  {
    $message = clienttranslate('${player_name} draws ${n} exploration cards ${icon_explo}');
    $privateDatas[$player->getId()] = new NotificationMessage($message, [
            "cards" => $cards->ui(),
        ]);
        
    self::notifyAll("giveExplorationCardsToPlayer", $message, [
        "player" => $player,
        "n" => $cards->count(),
        "icon_explo" => '',
        '_private' => $privateDatas,
    ]);
  }
  
  public static function getMoney(Player $player, int $amount, )
  {
    self::notifyAll("getMoney", clienttranslate('${player_name} gets ${n} ${icon_money}'), [
        "player" => $player,
        "n" => $amount,
        "icon_money" => clienttranslate('Pardaos'),
        "i18n" => ['icon_money'],
    ]);
  }

  
  public static function progress(Player $player, ProgressMarker $token)
  {
    self::notifyAll("progress", clienttranslate('${player_name} progresses on track #${n}'), [
        "player" => $player,
        "n" => $token->getTrackIndex(),
        "token" => $token->getUiData(),
        'preserve'=>['token'],
    ]);
  }
  
  /**
   * @param Player $player
   * @param int $points
   * @param string $msg (optional) Message to overwrite default
   * @param string $n2 (optional) Second number argument used in $msg
   */
  public static function addPoints(Player $player,int $points, ?string $msg = null, ?int $n2 =null){
    if(!isset($msg)) $msg = clienttranslate('${player_name} scores ${n} points');
    $args = [ 
      'player' => $player,
      'n' => $points,
    ];
    if(isset($n2)) $args['n2'] = $n2;
    self::notifyAll('addPoints',$msg, $args, );
  }

  
    /**
   * @param Player $player
   * @param Token $token
   * @param Tile $toTile
   */
  public static function newSpiceOnTile(Player $player,Token $token,Tile $toTile)
  {
    $msg = clienttranslate('${player_name} places a ${token_icon} on their tile');

    self::notifyAll('newSpiceOnTile', $msg, [
      'player' => $player,
      'token' => $token->getUiData(),
      //'tile_id' => $toTile->getId(),
      'token_icon' => clienttranslate('Spice'),
      'preserve'=>['token'],
    ]);
  }
  
    /**
   * @param Player $player
   * @param Collection $tokens
   */
  public static function removeSpices(Player $player,Collection $tokens)
  {
    $nTokens = $tokens->count();
    if($nTokens == 0) return;
    $msg = clienttranslate('${player_name} removes ${n} spices tokens from their tiles');
    //TODO JSA ICONS
    self::notifyAll('removeSpices', $msg, [
      'player' => $player,
      'n' => $nTokens,
      'tokens' => $tokens->ui(),
      'preserve'=>['tokens',],
    ]);
  }

    /**
   * @param Player $player
   * @param Tile $tile
   * @param Collection $tokens
   */
  public static function removeTileSpices(Player $player,Tile $tile, Collection $tokens)
  {
    $nTokens = $tokens->count();
    if($nTokens == 0) return;
    $msg = clienttranslate('${player_name} removes ${n} spices tokens from their tile');

    self::notifyAll('removeTileSpices', $msg, [
      'player' => $player,
      'n' => $nTokens,
      'tokens' => $tokens->ui(),
      'tile_id' => $tile->getId(),
      'preserve'=>['tokens','tile_id'],
    ]);
  }

  
  public static function reshuffleExplorationDeck(Player $player, int $deck_size)
  {
    self::notifyAll('reshuffleExplorationDeck', '', [
      'deck_size' => $deck_size,
    ]);
  }
  
  /**
   */  
  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    Game::get()->notify->all($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notify->player($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  /**
   *  Empty notif to send after an action, to let framework works & refresh ui
   * (Usually not needed if we send another notif or if we change state of a player)
   * */
  public static function emptyNotif(){
    self::notifyAll('e','',[],);
  }
  /*********************
   **** UPDATE ARGS ****
   *********************/

  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      //for playername_wrapper
      $data['player_color'] = $data['player']->getColor();
      if (!isset($data['preserve'])) {
        $data['preserve'] = [];
      }
      $data['preserve'][] = 'player_color';

      unset($data['player']);
    }
    if (isset($data['player2'])) {
      $data['player_name2'] = $data['player2']->getName();
      $data['player_id2'] = $data['player2']->getId();
      //for playername_wrapper
      $data['player_color2'] = $data['player2']->getColor();
      if (!isset($data['preserve'])) {
        $data['preserve'] = [];
      }
      $data['preserve'][] = 'player_color2';
      unset($data['player2']);
    }
    else {
      unset($data['player2']);
    }
    /* not used in this game for now
    
    if (isset($data['player3'])) {
      $data['player_name3'] = $data['player3']->getName();
      $data['player_id3'] = $data['player3']->getId();
      unset($data['player3']);
    }
    */
  }
  
  /************************************
   **** UPDATES after confirm/undo ****
   ***********************************/
  
  public static function refreshUI($datas)
  {
    // Keep only the things from getAllDatas that matters (changed during the player actions)
    //$privateHand = $datas['cards']['hand'];
    $players = $datas['players'];
    $gameDatas = [
      'players' => $players,
      'cards' => $datas['cards'],
      'tiles' => $datas['tiles'],
      'tokens' => $datas['tokens'],
    ];
    unset($gameDatas['cards']['private']);

    $privateDatas = [];
    foreach ($players as $pid => $player) {
      $privateDatas[$pid] = new NotificationMessage('', [
            'private' => Cards::getPrivateDatas($pid),
          ]);
    }

    self::notifyAll('refreshUI', '', [
      'datas' => $gameDatas,
      '_private' => $privateDatas,
    ]);
  }
  
  /**
   * @param Player $player
   * @param array $notifIds
   */
  public static function clearTurn($player, $notifIds)
  {
    self::notifyAll('clearTurn', '', [
      'player' => $player,
      'notifIds' => $notifIds,
    ]);
  }
  
  /**
   * @param Player $player
   * @param int $stepId
   */
  public static function undoStep($player, $stepId)
  {
    self::notifyAll('undoStep', clienttranslate('${player_name} undoes their action'), [
      'player' => $player,
    ]);
  }
  /**
   * @param Player $player
   */
  public static function restartTurn($player)
  {
    self::notifyAll('restartTurn', clienttranslate('${player_name} restarts their turn'), [
      'player' => $player,
    ]);
  }

}
