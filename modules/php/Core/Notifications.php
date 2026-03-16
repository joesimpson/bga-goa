<?php

namespace Bga\Games\Goa\Core;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Models\AuctionMarker;
use Bga\Games\Goa\Models\AutomaActionCard;
use Bga\Games\Goa\Models\AutomaAuctionCard;
use Bga\Games\Goa\Models\ExplorationCard;
use Bga\Games\Goa\Models\MarketTile;
use Bga\Games\Goa\Models\MoneyCard;
use Bga\Games\Goa\Models\MoneyCardType;
use Bga\Games\Goa\Models\OutpostTile;
use Bga\Games\Goa\Models\Player;
use Bga\Games\Goa\Models\ProgressMarker;
use Bga\Games\Goa\Models\Tile;
use Bga\Games\Goa\Models\Token;
use Bga\Games\Goa\Models\TokenSpice;

class Notifications
{ 
  
  public static function automaColor(string $color, int $automa_id)
  {
    self::notifyAll('automaColor',  clienttranslate('${automa_name} will play with the next available color'), [
      'automa_name' => Utils::getAutomaName(),
      'automa_color' => $color,
      'automa_id' => $automa_id,
      'i18n' => ['automa_name'],
      'preserve' => ['automa_color'],
    ]);
  }
  public static function initAutomaMoney(Collection $cards)
  {
    $cardsNames = $cards->map(function($card) {return $card->getValue();})->toArray();

    $level = Globals::getOptionAutomaDifficulty();
    self::notifyAll('initAutomaMoney',  clienttranslate('Level ${n} : ${automa_name} will play with ${x} Pardau cards (${money_cards})'), [
      'automa_name' => Utils::getAutomaName(),
      'automa_color' => Globals::getAutomaColor(),
      'i18n' => ['automa_name'],
      'preserve' => ['automa_color','cards'],
      'n' => $level,
      'x' => $cards->count(),
      'cards' => $cards->ui(),
      'money_cards' => $cardsNames,
      'separator' => ['money_cards' => ','],
    ]);
  }
  
  public static function initAutomaAuctionCards(Collection $cards)
  {
    self::notifyAll('initAutomaAuctionCards',  clienttranslate('${automa_name} will play with ${n} Auction cards'), [
      'automa_name' => Utils::getAutomaName(),
      'automa_color' => Globals::getAutomaColor(),
      'i18n' => ['automa_name'],
      'preserve' => ['automa_color','cards'],
      'n' => $cards->count(),
      'cards' => $cards->ui(),
    ]);
  }
  
  public static function initAutomaActionCards(Collection $cards)
  {
    self::notifyAll('initAutomaActionCards',  clienttranslate('${automa_name} will play with ${n} Action cards'), [
      'automa_name' => Utils::getAutomaName(),
      'automa_color' => Globals::getAutomaColor(),
      'i18n' => ['automa_name'],
      'preserve' => ['automa_color','cards'],
      'n' => $cards->count(),
      'cards' => $cards->ui(),
    ]);
  }

  public static function newSession(int $session)
  {
    $msg = '';
    if($session > 1){
      //visible message only when game with multiples sessions
      $msg = clienttranslate('Session ${n} begins');
    }

    self::notifyAll('newSession', $msg, [
      'n' => $session,
    ]);
  }
  public static function resetCurrencies()
  {
    self::notifyAll('resetCurrencies',  '', [
    ]);
  }
  public static function setupMarket(int $phase, Collection $tiles, Collection $boxTiles)
  {
    self::notifyAll("setupMarket", clienttranslate('The market board is filled with tiles'), [
        "phase" => $phase,
        "tiles" => $tiles->ui(),
        "boxTiles" => $boxTiles->uiAssoc(),
        "preserve" => ['tiles', 'boxTiles'],
    ]);
  }

  public static function newRound(int $round)
  {
    self::logsLineSeparator();

    $msg = clienttranslate('Round ${n} begins');

    self::notifyAll('newRound', $msg, [
      'n' => $round,
    ]);
  }

  public static function endRound(int $round)
  {
    $msg = clienttranslate('Round ${n} ends');

    self::notifyAll('endRound', $msg, [
      'n' => $round,
    ]);
    //Not needed if separator before newRound
    //self::logsLineSeparator();
  }
  
  public static function logsLineSeparator()
  {
    self::notifyAll('lineSeparator', '<hr>', [
    ]);
  }
  
  public static function newRoundStep(int $roundStep, string $stepMessage)
  {
    self::logsLineSeparator();

    $msg = clienttranslate('${step_icon} Round step ${n} begins : ${stepMessage}');

    self::notifyAll('newRoundStep', $msg, [
      'n' => $roundStep,
      'stepMessage' => $stepMessage,
      'step_icon' => '',
      "i18n" => ['stepMessage'],
    ]);
  }

  public static function resetActionsPhase($reusableTilesIds, int $newState)
  {
    $msg = '';
    self::notifyAll('resetActionsPhase', $msg, [
      'newState' => $newState,
      'tiles_ids' => $reusableTilesIds,
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
    if($nextBid == $previousBid){
      return;//SILENT
    }
    if($nextBid > $previousBid){
      $msg = clienttranslate('${player_name} raises the bid from ${a} ${icon_money} to ${b} ${icon_money}');
    }
    else {
      $msg = clienttranslate('${player_name} decreases the bid from ${a} ${icon_money} to ${b} ${icon_money}');
    }
    self::notifyAll("newBid", $msg, [
        "player" => $player,
        "a" => $previousBid,
        "b" => $nextBid,
        "icon_money" => '',
    ]);
  }
  
  public static function passBid(Player $player, AuctionMarker $token )
  {
    self::notifyAll("passBid", clienttranslate('${player_name} passes the auction ${token_icon}'), [
        "player" => $player,
        "token" => $token->getUiData(),
        "token_icon" => '',
        'preserve'=>['token'],
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
  
  public static function winsAuctionWithCard(Player $playerWins, int $bid, MoneyCard $card, Player|null $player2Auctioneer = null )
  {
    if(isset($player2Auctioneer)){
      self::notifyAll("winsAuction", clienttranslate('${player_name} wins the auction and gives ${n} ${icon_money} to ${player_name2}'), [
        "player" => $playerWins,
        "player2" => $player2Auctioneer,
        "n" => $bid,
        "card" => $card,
        "icon_money" => clienttranslate('Pardaos'),
        "i18n" => ['icon_money'],
        "preserve" => ['card'],
        ]);
    }
    else {
      self::notifyAll("winsAuctionBank", clienttranslate('${player_name} wins the auction and gives ${n} ${icon_money} to the bank'), [
        "player" => $playerWins,
        "n" => $bid,
        "card" => $card,
        "icon_money" => clienttranslate('Pardaos'),
        "i18n" => ['icon_money'],
        "preserve" => ['card'],
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
    if($n >= 0){
      self::notifyAll("getContracts", clienttranslate('${player_name} gets ${n} ${icon_contracts}'), [
        "player" => $player,
        "n" => $n,
        "icon_contracts" => clienttranslate('Contracts'),
      ]);
    }
    else {
      self::notifyAll("spendContracts", clienttranslate('${player_name} spends ${n} ${icon_contracts}'), [
        "player" => $player,
        "n" => -$n,
        "icon_contracts" => clienttranslate('Contracts'),
      ]);
    }
  }
  
  public static function getShips(Player $player, int $n)
  {
    if($n >= 0){
      self::notifyAll("getShips", clienttranslate('${player_name} gets ${n} ${icon_ships}'), [
          "player" => $player,
          "n" => $n,
          "icon_ships" => clienttranslate('Ships'),
      ]);
    }
    else {
      self::notifyAll("spendShips", clienttranslate('${player_name} spends ${n} ${icon_ships}'), [
          "player" => $player,
          "n" => -$n,
          "icon_ships" => clienttranslate('Ships'),
      ]);
    }
  }
  public static function getCompass(Player $player, int $n)
  {
    if($n >= 0){
      self::notifyAll("getCompass", clienttranslate('${player_name} gets ${n} ${icon_compass}'), [
          "player" => $player,
          "n" => $n,
          "icon_compass" => clienttranslate('Compass cards'),
      ]);
    }
    else {
      self::notifyAll("spendCompass", clienttranslate('${player_name} spends ${n} ${icon_compass}'), [
          "player" => $player,
          "n" => -$n,
          "icon_compass" => clienttranslate('Compass cards'),
      ]);
    }
  }
  public static function giveTile(Player $player, Tile $tile)
  {
    $notifId = "giveTile";
    $tile_name = '';
    if( $tile instanceof MarketTile ) $tile_name = $tile->getName();

    //if($tile instanceof OutpostTile) $notifId = "giveOutpost";
    self::notifyAll($notifId, clienttranslate('${player_name} gets the tile ${tile_icon}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => $tile_name,
        'preserve'=>['tile'],
        'i18n'=> ['tile_icon'],
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
    $tile_name = '';
    if( $tile instanceof MarketTile ) $tile_name = $tile->getName();
    if( $tile instanceof OutpostTile ) $tile_name = Utils::getColonyName($tile->getColony());
    self::notifyAll("discardTile", clienttranslate('${player_name} discards the tile ${tile_icon} ${tile_name}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        'preserve'=>['tile'],
        "tile_name" => $tile_name,
        'i18n'=> ['tile_name'],
    ]);
  }
  
  public static function playTile(Player $player, MarketTile $tile)
  {
    self::notifyAll("playTile", clienttranslate('${player_name} plays the tile ${tile_icon} (${tile_name})'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        'preserve'=>['tile'],
        "tile_name" => $tile->getName(),
        'i18n'=> ['tile_name'],
    ]);
  }
  public static function placeTile(Player $player, MarketTile $tile)
  {
    self::notifyAll("placeTile", clienttranslate('${player_name} places the tile ${tile_icon} (${tile_name}) at row ${row}/column ${col}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        "row" => $tile->getRow(),
        "col" => $tile->getCol(),
        'preserve'=>['tile'],
        "tile_name" => $tile->getName(),
        'i18n'=> ['tile_name'],
    ]);
  }
  
  public static function tryFoundOutpost(Player $player, OutpostTile $tile, int $nbContracts)
  {
    $colonyName = Utils::getColonyName($tile->getColony());
    self::notifyAll("tryFoundOutpost", clienttranslate('${player_name} tries to establish the ${outpostname} outpost ${tile_icon} which requires ${n} ${icon_contracts}'), [
        "player" => $player,
        "tile" => $tile->getUiData(),
        "tile_icon" => '',
        "n" => $nbContracts,
        "outpostname" => ($colonyName),
        "icon_contracts" => clienttranslate('Contracts'),
        'preserve'=>['tile'],
        'i18n'=>['outpostname'],
    ]);
  }
  
  public static function resultDrawNCards(Player $player, int $nbContracts)
  {
    self::notifyAll("resultDrawNCards", clienttranslate('Cards reveal ${n} ${icon_contracts}'), [
        "player" => $player,
        "n" => $nbContracts,
        "icon_contracts" => clienttranslate('Contracts'),
    ]);
  }
  public static function resultFoundOutpost(Player $player, int $nbContracts)
  {
    self::notifyAll("resultFoundOutpost", clienttranslate('Cards reveal ${n} ${icon_contracts} to establish the outpost'), [
        "player" => $player,
        "n" => $nbContracts,
        "icon_contracts" => clienttranslate('Contracts'),
    ]);
  }
  public static function abandonOutpost(Player $player, OutpostTile $tile)
  {
    self::notifyAll("abandonOutpost", clienttranslate('${player_name} chooses not to establish the ${outpostname} outpost'), [
        "player" => $player,
        "outpostname" => Utils::getColonyName($tile->getColony()),
        'i18n'=>['outpostname'],
    ]);
  }
  public static function foundOutpost(Player $player, OutpostTile $tile)
  {
    self::notifyAll("foundOutpost", clienttranslate('${player_name} establishes the ${outpostname} outpost'), [
        "player" => $player,
        "outpostname" => Utils::getColonyName($tile->getColony()),
        'i18n'=>['outpostname'],
    ]);
  }

  public static function drawAndDiscardExploration(int $playerId, ExplorationCard $card)
  {
    $symbolType = $card->getSymbol();

    self::notifyAll("drawAndDiscardExploration", clienttranslate('${player_name} draws and discard ${card_icon} (${icon_symbol})'), [
       "player_id" => $playerId,
        "player_name" => Game::get()->getPlayerNameById($playerId),
        "card" => $card->getUiData(),
        "card_id" => $card->getId(),
        "card_icon" => '',
        'symbolType' => $symbolType,
        'icon_symbol' => CARD_SYMBOLS[$symbolType],
        'i18n' => ['icon_symbol'],
        'preserve'=>['card', 'symbolType'],
    ]);
  }

  public static function discardExploration(Player $player, ExplorationCard $card)
  {
    $symbolType = $card->getSymbol();
    self::notifyAll("discardExploration", clienttranslate('${player_name} discards ${card_icon} (${icon_symbol}) from his hand'), [
       "player" => $player,
        "card" => $card->getUiData(),
        "card_id" => $card->getId(),
        "card_icon" => '',
        'symbolType' => $symbolType,
        'icon_symbol' => CARD_SYMBOLS[$symbolType],
        'i18n' => ['icon_symbol'],
        'preserve'=>['card', 'symbolType'],
    ]);
  }
  
  public static function playExploration(Player $player, ExplorationCard $card)
  {
    $symbolType = $card->getSymbol();
    //keep same id as discardExploration to keep same UI behavior for now
    self::notifyAll("discardExploration", clienttranslate('${player_name} plays ${card_icon} (${icon_symbol}) from his hand'), [
       "player" => $player,
        "card" => $card->getUiData(),
        "card_id" => $card->getId(),
        "card_icon" => '',
        'symbolType' => $symbolType,
        'icon_symbol' => CARD_SYMBOLS[$symbolType],
        'i18n' => ['icon_symbol'],
        'preserve'=>['card', 'symbolType'],
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

  public static function cleanHandStart(Player $player, int $limit)
  {
    self::notifyAll("cleanHandStart", clienttranslate('${player_name} exceeds his hand limit (${n}) and must play and/or discard Exploration cards'), [
        "player" => $player,
        "n" => $limit,
    ]);
  }
  
  public static function cleanHandEnd(Player $player, int $handSize)
  {
    self::notifyAll("cleanHandEnd", clienttranslate('${player_name} now reaches his hand limit (${n})'), [
        "player" => $player,
        "n" => $handSize,
    ]);
  }
  
  public static function giveAuctionCardToAutoma( AutomaAuctionCard $card)
  {
    $message = clienttranslate('${automa_name} draws the next auction card ${auction_card_icon}');
        
    self::notifyAll("giveAuctionCardToAutoma", $message, [
        'automa_name' => Utils::getAutomaName(),
        'automa_color' => Globals::getAutomaColor(),
        'card' => $card->getUiData(),
        'i18n' => ['automa_name'],
        'preserve'=>['card', 'automa_color', ],
        "auction_card_icon" => '',//TODO JSA icon on client side
    ]);
  }

  public static function giveActionCardToAutoma( AutomaActionCard $card)
  {
    $message = clienttranslate('${automa_name} draws the next action card ${action_card_icon}');
        
    self::notifyAll("giveActionCardToAutoma", $message, [
        'automa_name' => Utils::getAutomaName(),
        'automa_color' => Globals::getAutomaColor(),
        'card' => $card->getUiData(),
        'i18n' => ['automa_name'],
        'preserve'=>['card', 'automa_color', ],
        "action_card_icon" => '',//TODO JSA icon on client side
    ]);
  }
  
  public static function automaDrawMoney( MoneyCard $card)
  {
    $message = clienttranslate('${automa_name} draws the next money card : ${n} ${icon_money}');
        
    self::notifyAll("automaDrawMoney", $message, [
        'automa_name' => Utils::getAutomaName(),
        'automa_color' => Globals::getAutomaColor(),
        'card' => $card->getUiData(),
        'i18n' => ['automa_name', 'icon_money' ],
        'preserve'=>['card', 'automa_color', ],
        "n" => $card->getValue(),
        "icon_money" => clienttranslate('Pardaos'),
    ]);
  }
  public static function automaGetsPlayerMoney(Collection $cards, Player $automata,int $money,Player $fromPlayer)
  {
    $message = clienttranslate('${player_name} places ${x} Pardau cards on ${automa_name} discard pile (${money_cards})');
        
    $cardsNames = $cards->map(function($card) {return $card->getValue();})->toArray();
    self::notifyAll("automaGetsPlayerMoney", $message, [
      'cards' => $cards->ui(),
      'x' => $cards->count(),
      'player' => $fromPlayer,
      'automa_name' => $automata->getName(),
      'automa_color' => $automata->getColor(),
      'i18n' => ['automa_name'],
      'preserve'=>['automa_color', 'cards',  ],
      'money_cards' => $cardsNames,
      'separator' => ['money_cards' => ','],
    ]);
  }
  
  public static function automaGetsBankMoney(Collection $cards, Player $automata,)
  {
    $message = clienttranslate('The Bank places ${x} Pardau cards on ${automa_name} discard pile : ${money_cards} ${icon_money}');
        
    $cardsNames = $cards->map(function($card) {return $card->getValue();})->toArray();
    self::notifyAll("automaGetsBankMoney", $message, [
      'cards' => $cards->ui(),
      'x' => $cards->count(),
      'automa_name' => $automata->getName(),
      'automa_color' => $automata->getColor(),
      "icon_money" => clienttranslate('Pardaos'),
      'i18n' => ['automa_name', 'icon_money'],
      'preserve'=>['automa_color', 'cards',  ],
      'money_cards' => $cardsNames,
      'separator' => ['money_cards' => ','],
    ]);
  }
  
  public static function automaDontGetBankMoney( Player $automata, MoneyCardType $cardType,)
  {
    $message = clienttranslate('${automa_name} does not receive anything');
    self::notifyAll("automaDontGetBankMoney", $message, [
      'automa_name' => $automata->getName(),
      'automa_color' => $automata->getColor(),
      'i18n' => ['automa_name'],
      'preserve'=>['automa_color', ],
    ]);
  }
  
  public static function automaDiscardMoney( MoneyCard $card)
  {
    $message = clienttranslate('${automa_name} discards the auctioned money card : ${n} ${icon_money}');
        
    self::notifyAll("automaDiscardMoney", $message, [
        'automa_name' => Utils::getAutomaName(),
        'automa_color' => Globals::getAutomaColor(),
        'card' => $card->getUiData(),
        'i18n' => ['automa_name', 'icon_money' ],
        'preserve'=>['card', 'automa_color', ],
        "n" => $card->getValue(),
        "icon_money" => clienttranslate('Pardaos'),
    ]);
  }
  
  public static function automaBankMoney( MoneyCard $card)
  {
    $message = clienttranslate('${automa_name} pays the auctioned money card : ${n} ${icon_money}');
        
    self::notifyAll("automaBankMoney", $message, [
        'automa_name' => Utils::getAutomaName(),
        'automa_color' => Globals::getAutomaColor(),
        'card' => $card->getUiData(),
        'i18n' => ['automa_name', 'icon_money' ],
        'preserve'=>['card', 'automa_color', ],
        "n" => $card->getValue(),
        "icon_money" => clienttranslate('Pardaos'),
    ]);
  }
  
  public static function getMoney(Player $player, int $amount, )
  {
    if($amount >= 0){
      self::notifyAll("getMoney", clienttranslate('${player_name} gets ${n} ${icon_money}'), [
          "player" => $player,
          "n" => $amount,
          "icon_money" => clienttranslate('Pardaos'),
          "i18n" => ['icon_money'],
      ]);
    }
    else {
      self::notifyAll("spendMoney", clienttranslate('${player_name} spends ${n} ${icon_money}'), [
          "player" => $player,
          "n" => -$amount,
          "icon_money" => clienttranslate('Pardaos'),
          "i18n" => ['icon_money'],
      ]);
    }
  }
  
  public static function revealMoney(Player $player, int $amount, )
  {
    self::notifyAll("revealMoney", clienttranslate('${player_name} has ${n} ${icon_money}'), [
        "player" => $player,
        "n" => $amount,
        "icon_money" => clienttranslate('Pardaos'),
        "i18n" => ['icon_money'],
    ]);
  }

  public static function espionage(Player $player, Player $opponent, int $track)
  {
    $action_type = Utils::getActionType($track);
    self::notifyAll("espionage", clienttranslate('${player_name} spies ${player_name2} board to use track #${n} : ${action_icon}'), [
        "player" => $player,
        "player2" => $opponent,
        "n" => $track,
        "action_type" => $action_type,
        "action_icon" => '',
        'preserve'=>['action_type'],
    ]);
  }
  
  public static function progressBonusFirst(Player $player, int $n)
  {
    self::notifyAll("progressBonusFirst", clienttranslate('${player_name} receives a bonus for being the first one to reach progress row ${n}'), [
      "player" => $player,
      "n" => $n,
    ]);
  }
  
  public static function progressBonusAllColumns(Player $player, int $n)
  {
    self::notifyAll("progressCol", clienttranslate('${player_name} receives a bonus for having all progress markers at row ${n} or below'), [
      "player" => $player,
      "n" => $n,
    ]);
  }

  public static function progress(Player $player, ProgressMarker $token)
  {
    $track = $token->getTrackIndex();
    $action_type = Utils::getActionType($track);

    self::notifyAll("progress", clienttranslate('${player_name} progresses on track #${n} : ${action_icon}'), [
        "player" => $player,
        "n" => $track,
        "token" => $token->getUiData(),
        "action_type" => $action_type,
        "action_icon" => '',
        'preserve'=>['token', 'action_type'],
    ]);
  }
  
  public static function playAction(Player $player, int $action_type)
  {

    $action_name = '';
    switch($action_type){
      case ACTION_TYPE_PROGRESS   : $action_name = clienttranslate("Progress"); break;
      case ACTION_TYPE_BUILD_SHIPS: $action_name = clienttranslate("Build Ships"); break;
      case ACTION_TYPE_PRODUCE    : $action_name = clienttranslate("Produce"); break;
      case ACTION_TYPE_TAXES      : $action_name = clienttranslate("Collect Taxes"); break;
      case ACTION_TYPE_EXP        : $action_name = clienttranslate("Explore"); break;
      case ACTION_TYPE_FOUND      : $action_name = clienttranslate("Establish an Outpost"); break;
    }

    if($action_name == '') return;

    self::notifyAll("playAction", clienttranslate('${player_name} plays an action ${icon_action} : ${action_name}'), [
        "player" => $player,
        "action_name" => $action_name,
        "action_type" => $action_type,
        "icon_action" => '',
        'i18n'=>['action_name'],
        'preserve'=>['action_type'],
    ]);
  }
  
  public static function automaPlayAction(Player $player)
  {
    self::notifyAll("playAction", clienttranslate('${player_name} perform an action ${icon_action}'), [
        "player" => $player,
        "icon_action" => '',
    ]);
  }
  
  public static function computeScoring(int $session)
  {
    self::notifyAll("computeScoring", clienttranslate('Computing scores...'), [
      'n' => $session,
    ]);
  }
  /**
   * @param Player $player
   * @param int $points
   * @param string $msg (optional) Message to overwrite default
   * @param string $n2 (optional) Second number argument used in $msg
   */
  public static function addPoints(Player $player,int $points, ?string $msg = null, ?int $n2 =null){
    //Keep it commented if not necessary to translate it in this game
    //if(!isset($msg)) $msg = clienttranslate('${player_name} scores ${n} points');
    $args = [ 
      'player' => $player,
      'n' => $points,
    ];
    if(isset($n2)) $args['n2'] = $n2;
    self::notifyAll('addPoints',$msg, $args, );
  }
  
  public static function addPointsWithTile(Player $player,int $points, Tile $tile){
    $msg = clienttranslate('${player_name} scores ${n} points with the tile ${tile_icon}');
    $args = [ 
      'player' => $player,
      'n' => $points,
      "tile" => $tile->getUiData(),
      "tile_icon" => '',
      'preserve'=>['tile'],
    ];
    self::notifyAll('addPointsWithTile',$msg, $args, );
  }
  
  public static function addPointsWithMoney(Player $player,int $points,int $money){
    $msg = clienttranslate('${player_name} scores ${n} points for having the most Pardaos : ${n2} ${icon_money}');
    $args = [ 
      'player' => $player,
      'n' => $points,
      'n2' => $money,
      'icon_money' => clienttranslate('Pardaos'),
      'i18n' => ['icon_money'],
    ];
    self::notifyAll('addPointsWithMoney',$msg, $args, );
  }
  
  public static function addPointsWithSymbols(Player $player,int $points,int $nbSymbols, int $symbolType){
    $msg = clienttranslate('${player_name} scores ${n} points with ${n2} ${icon_symbol} symbols');
    $args = [ 
      'player' => $player,
      'n' => $points,
      'n2' => $nbSymbols,
      'symbolType' => $symbolType,
      'icon_symbol' => CARD_SYMBOLS[$symbolType],
      'i18n' => ['icon_symbol'],
      'preserve'=>['symbolType'],
    ];
    self::notifyAll('addPointsWithSymbols',$msg, $args, );
  }
  
  public static function addPointsWithProgress(Player $player,int $points){
    $msg = clienttranslate('${player_name} scores ${n} points with progress markers');
    $args = [ 
      'player' => $player,
      'n' => $points,
      'scoringType' => SCORING_PROGRESS,
      'preserve'=>['scoringType'],
    ];
    self::notifyAll('addPoints',$msg, $args, );
  }
  
  public static function addPointsWithOutposts(Player $player,int $points, int $nbTiles){
    $msg = clienttranslate('${player_name} scores ${n} points with ${n2} outposts');
    $args = [ 
      'player' => $player,
      'n' => $points,
      'n2' => $nbTiles,
      'scoringType' => SCORING_OUTPOSTS,
      'preserve'=>['scoringType'],
    ];
    self::notifyAll('addPoints',$msg, $args, );
  }
    /**
   * @param Player $player
   * @param TokenSpice $token
   * @param Tile $toTile
   */
  public static function newSpiceOnTile(Player $player,TokenSpice $token,Tile $toTile)
  {
    $msg = clienttranslate('${player_name} places a ${token_icon} on their tile');

    self::notifyAll('newSpiceOnTile', $msg, [
      'player' => $player,
      'token' => $token->getUiData(),
      //'tile_id' => $toTile->getId(),
      'token_icon' => $token->getGoodsName(),
      'preserve'=>['token'],
      'i18n' => ['token_icon'],
    ]);
  }
  
    /**
   * @param Player $player
   * @param Collection $tokens
   */
  public static function newSpices(Player $player,Collection $tokens)
  {
    $nTokens = $tokens->count();
    if($nTokens == 0) return;
    $sortedTokens = $tokens->ui();
    usort($sortedTokens, function ($a,$b)  {
      return $a["spice"] <=>  $b["spice"];
    });
    $sortedTokensNames = $tokens->map(function($token) {return $token->getGoodsName();})->toArray();
    $msg = clienttranslate('${player_name} places ${n} Goods tokens ${token_icons} on their tiles');
    self::notifyAll('newSpices', $msg, [
      'player' => $player,
      'n' => $nTokens,
      'tokens' => $sortedTokens,
      'token_icons' => $sortedTokensNames,
      'preserve'=>['tokens',],

      'i18n' => ['token_icons'],
      'separator' => ['token_icons' => ' '],
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
    $sortedTokens = $tokens->ui();
    usort($sortedTokens, function ($a,$b)  {
      return $a["spice"] <=>  $b["spice"];
    });
    $sortedTokensNames = $tokens->map(function($token) {return $token->getGoodsName();})->toArray();
    $msg = clienttranslate('${player_name} removes ${n} Goods tokens ${token_icons} from their tiles');
    self::notifyAll('removeSpices', $msg, [
      'player' => $player,
      'n' => $nTokens,
      'tokens' => $sortedTokens,
      'token_icons' => $sortedTokensNames,
      'preserve'=>['tokens',],

      'i18n' => ['token_icons'],
      'separator' => ['token_icons' => ' '],
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
    $sortedTokens = $tokens->ui();
    usort($sortedTokens, function ($a,$b)  {
      return $a["spice"] <=>  $b["spice"];
    });
    $sortedTokensNames = $tokens->map(function($token) {return $token->getGoodsName();})->toArray();
    
    $msg = clienttranslate('${player_name} removes ${n} Goods tokens ${token_icons} from their tiles');

    self::notifyAll('removeTileSpices', $msg, [
      'player' => $player,
      'n' => $nTokens,
      'tokens' => $sortedTokens,
      'token_icons' => $sortedTokensNames,
      'tile_id' => $tile->getId(),
      'preserve'=>['tokens','tile_id'],
      'i18n' => ['token_icons'],
      'separator' => ['token_icons' => ' '],
    ]);
  }

  
  public static function discardAllExploration()
  {
    self::notifyAll('discardAllExploration', '', [
    ]);
  }
  public static function reshuffleExplorationDeck(Player|null $player, int $deck_size)
  {
    $msg = clienttranslate('Exploration deck is reshuffled');

    self::notifyAll('reshuffleExplorationDeck', $msg, [
      'deck_size' => $deck_size,
    ]);
  }
  
  public static function reshuffleAutomaMoneyDeck(int $deck_size)
  {
    $msg = clienttranslate('${automa_name} money deck is reshuffled');

    self::notifyAll('reshuffleAutomaMoneyDeck', $msg, [
      'deck_size' => $deck_size,
      'automa_name' => Utils::getAutomaName(),
      'automa_color' => Globals::getAutomaColor(),
      'i18n' => ['automa_name'],
      'preserve'=>[ 'automa_color', ],
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
      $data['preserve'][] = 'player_id2';
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
