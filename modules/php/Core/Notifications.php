<?php

namespace Bga\Games\Goa\Core;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Models\ExplorationCard;
use Bga\Games\Goa\Models\Player;
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
    $msg = clienttranslate('Actions turn ${n} begins');

    self::notifyAll('newTurn', $msg, [
      'n' => $turn,
    ]);
  }

  /**
   */
  public static function auctionPlaced(Player $player, int $row, int $col, Token $token)
  {
    self::notifyAll("auctionPlaced", clienttranslate('${player_name} places the auction marker ${token_icon} on the market (row ${row}, column ${col})'), [
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
  public static function giveExplorationCardsToPlayer(Player $player, Collection $cards)
  {
    $privateDatas[$player->getId()] = new NotificationMessage('', [
            "cards" => $cards->ui(),
        ]);
        
    self::notifyAll("giveExplorationCardsToPlayer", clienttranslate('${player_name} draws ${n} exploration cards'), [
        "player" => $player,
        "n" => $cards->count(),
        '_private' => $privateDatas,
    ]);
  }
  
  /**
   * @param Player $player
   * @param int $points
   * @param string $msg (optional) Message to overwrite default
   * @param string $n2 (optional) Second number argument used in $msg
   */
  public static function addPoints(Player $player,int $points, ?string $msg = null, ?int $n2 =null){
    // Commented because unused in this game, and we don't need to translate that message
    //if(!isset($msg)) $msg = clienttranslate('${player_name} scores ${n} points');
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
    $msg = '';

    self::notifyAll('newSpiceOnTile', $msg, [
      'player' => $player,
      'token' => $token->getUiData(),
      'tile_id' => $toTile->getId(),
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
