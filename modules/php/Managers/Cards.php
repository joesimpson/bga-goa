<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\ExplorationCard;
use Bga\Games\Goa\Models\Player;
use InvalidArgumentException;

/* Class to manage all the cards */

class Cards extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type', 'subtype'];
  protected static $autoreshuffle = true;
  protected static $autoreshuffleCustom = [CARD_LOCATION_EXP_DECK => CARD_LOCATION_EXP_DISCARD];

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $subtype = isset($row['subtype']) ? $row['subtype'] : null;
    switch ($subtype) {
      case CARD_SUBTTYPE_EXPLORATION:
        $data = self::getExplorationCardsTypes()[$type];
        return new ExplorationCard($row, $data);
    }
    throw new InvalidArgumentException("Card $type, $subtype");
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all cards visible by this player
   */
  public static function getUiData($currentPlayerId)
  {
    //$privateCards = self::getExplorationHand($currentPlayerId);

    return 
      self::getExplorationDiscardPile()
      //->merge($privateCards)
      ->map(function ($card) {
        return $card->getUiData();
      })
      ->toArray();
  } 
  
  public static function getByType(int $cardType)
  {
    return self::DB()
      ->where('type', $cardType)
      ->get();
  }
   
  public static function getExplorationDiscardPile()
  {
    return self::DB()
      ->where( 'subType', CARD_SUBTTYPE_EXPLORATION)
      ->where(static::$prefix . 'location', CARD_LOCATION_EXP_DISCARD)
      ->get();
  }

  public static function getAllDeckSizes()
  {
    return [
            'exploration' => Cards::countInExplorationDeck(),
        ];
  }
  public static function countInExplorationDeck()
  {
    return self::DB()
      ->where( 'subType', CARD_SUBTTYPE_EXPLORATION)
      ->where(static::$prefix . 'location', CARD_LOCATION_EXP_DECK)
      ->count();
  }
  public static function getExplorationHand(int $pId)
  {
    return self::DB()
      ->where( 'subType', CARD_SUBTTYPE_EXPLORATION)
      ->where(static::$prefix . 'location', CARD_LOCATION_HAND)
      ->wherePlayer($pId)
      ->get();
  }
  public static function countInExplorationHand(int $playerId) : int
  {
    return self::DB()
      ->where( 'subType', CARD_SUBTTYPE_EXPLORATION)
      ->where(static::$prefix . 'location', CARD_LOCATION_HAND)
      ->wherePlayer($playerId)
      ->count();
  }
  
  public static function maxExplorationToDraw(Player $player) :int
  {
    $playerId = $player->getId();
    $nbCardsInHand = Cards::countInExplorationHand($playerId);
    $progressMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_EXP,$playerId);
    $gains = PROGRESS_GAINS_EXPLO[$progressMarker->getRow() - 1];
    $nbMaxInHand = $gains['max'];
    $delta = $gains['plus'];
    $possibleDelta = $nbMaxInHand - $nbCardsInHand;
    return max( 0, min($delta, $possibleDelta));
  }

  public static function getPossibleToDiscard(Player $player) : Collection
  {
    //We suppose we always may discard our exploration cards
    return Cards::getExplorationHand($player->getId());
  }
  
  public static function getPossibleToPlay(Player $player) : Collection
  {
    //TODO JSA : filter cards to play
    $hand = Cards::getExplorationHand($player->getId());
    return $hand;
  }
  
  public static function getPrivateDatas(int $pId)
  {
    return [ 
      'hand' => Cards::getExplorationHand($pId)->ui(),
    ];
  }
  
  public static function drawExplorationCardsToPlayer(Player $player,int $nb)
  {
    Game::get()->trace("drawExplorationCardsToPlayer($nb)");
    //$nbDiscardBefore = Cards::countInLocation(CARD_LOCATION_EXP_DISCARD);
    $nbDeckBefore = Cards::countInExplorationDeck();

    $cards = Cards::pickForLocation($nb, CARD_LOCATION_EXP_DECK,CARD_LOCATION_HAND,);
    
    //$nbDiscard = Cards::countInLocation(CARD_LOCATION_EXP_DISCARD);
    if($nbDeckBefore< $nb){
      $nbDeck = Cards::countInExplorationDeck();
      //notify deck size BEFORE drawing, because we have an animation after
      $nbDeckRightAfterShuffle = $nbDeck + max($nb,$nbDeckBefore);
      Notifications::reshuffleExplorationDeck($player, $nbDeckRightAfterShuffle );
    }

    foreach($cards as $card){
      $card->setPId($player->getId());
      $card->setLocation(CARD_LOCATION_HAND);
    }
     
    Notifications::giveExplorationCardsToPlayer($player,$cards);
    //TODO JSA CHECKPOINT !
    return $cards;
  }
  public static function drawAndDiscardExploration(Player $player,int $nb): Collection
  {
    $nbDeckBefore = Cards::countInExplorationDeck();

    $cards = Cards::pickForLocation($nb, CARD_LOCATION_EXP_DECK,CARD_LOCATION_EXP_DISCARD,);

    if($nbDeckBefore< $nb){
      $nbDeck = Cards::countInExplorationDeck();
      //notify deck size BEFORE drawing, because we have an animation after
      $nbDeckRightAfterShuffle = $nbDeck + max($nb,$nbDeckBefore);
      Notifications::reshuffleExplorationDeck($player, $nbDeckRightAfterShuffle );
    }

    foreach($cards as &$card){
      //insertOnTop to keep order of discarded cards on pile
      Cards::insertOnTop($card->getId(),$card->getLocation());
      $card = Cards::get($card->getId());//refresh object
      Notifications::drawAndDiscardExploration($player->getId(), $card);
    }
    return $cards;
  }
  
  public static function discardExploration(Player $player,int $cardId): ExplorationCard
  {
    self::insertOnTop($cardId,CARD_LOCATION_EXP_DISCARD);
    $card = Cards::get($cardId);
    //$card->setLocation(CARD_LOCATION_EXP_DISCARD);
    $card->setPId(null);

    Notifications::discardExploration( $player, $card);
 
    return $card;
  }
   
  ///////////////////////////////////////////////////////////////////////////////////////
   
  /** Creation of the cards
   * @param Collection $players
   */
  public static function setupNewGame($players, $options)
  {
    $cards = [];

    foreach (self::getExplorationCardsTypes() as $type => $card) {
      $cards[] = [
        'location' => CARD_LOCATION_EXP_DECK,
        'type' => $type,
        'subtype' => CARD_SUBTTYPE_EXPLORATION,
        'nbr' => 1,
      ];
    } 

    self::create($cards);
    self::shuffle(CARD_LOCATION_EXP_DECK);

  }
  
  /**
   * @return array of all the different types of Exploration Cards
   */
  public static function getExplorationCardsTypes(): array
  { 
    $f = function ($t) {
      return [
        'contracts' => $t[0],
        'symbol' => $t[1],
        'action' => $t[2],
      ];
    };
    return [
      //definition in the same order as in the sprite 'exp_cards.jpg' :
      2 =>  $f([1,   CARD_SYMBOL_PALMTREE,  CARD_ACTION_PROGRESS_NO_SPICE, ]), 
      3 =>  $f([2,   CARD_SYMBOL_PALMTREE,  CARD_ACTION_PROGRESS_NO_SHIP, ]), 
      4 =>  $f([1,   CARD_SYMBOL_PALMTREE,  CARD_ACTION_RESSOURCES_TO_COINS, ]), 
      5 =>  $f([3,   CARD_SYMBOL_PALMTREE,  CARD_ACTION_OUTPOST_EXTRA_CONTRACTS, ]), 
      6 =>  $f([2,   CARD_SYMBOL_PALMTREE,  CARD_ACTION_2_SPICES, ]), 
      7 =>  $f([1,   CARD_SYMBOL_ELEPHANT,  CARD_ACTION_2_CONTRACTS, ]), 
      8 =>  $f([1,   CARD_SYMBOL_SHELL,     CARD_ACTION_2_SHIPS, ]), 
      9 =>  $f([2,   CARD_SYMBOL_SHELL,     CARD_ACTION_5_COINS, ]), 
      10 => $f([2,   CARD_SYMBOL_SHELL,     CARD_ACTION_2_SPICES, ]), 
      11 => $f([3,   CARD_SYMBOL_SHELL,     CARD_ACTION_PROGRESS_NO_SHIP, ]), 
      12 => $f([2,   CARD_SYMBOL_SHELL,     CARD_ACTION_PROGRESS_WITH_COINS, ]), 
      13 => $f([3,   CARD_SYMBOL_ELEPHANT,  CARD_ACTION_HARVEST_AND_CARDS, ]), 
      14 => $f([3,   CARD_SYMBOL_STATUE,    CARD_ACTION_5_COINS, ]), 
      15 => $f([3,   CARD_SYMBOL_STATUE,    CARD_ACTION_RESSOURCES_TO_COINS, ]), 
      16 => $f([3,   CARD_SYMBOL_STATUE,    CARD_ACTION_2_SPICES, ]), 
      17 => $f([1,   CARD_SYMBOL_STATUE,    CARD_ACTION_OUTPOST_EXTRA_CONTRACTS, ]), 
      18 => $f([2,   CARD_SYMBOL_STATUE,    CARD_ACTION_PROGRESS_NO_SHIP, ]), 
      19 => $f([2,   CARD_SYMBOL_ELEPHANT,  CARD_ACTION_2_SHIPS, ]), 
      20 => $f([3,   CARD_SYMBOL_URN,       CARD_ACTION_2_SHIPS, ]), 
      21 => $f([2,   CARD_SYMBOL_URN,       CARD_ACTION_2_CONTRACTS, ]), 
      22 => $f([3,   CARD_SYMBOL_URN,       CARD_ACTION_PROGRESS_WITH_COINS, ]), 
      23 => $f([2,   CARD_SYMBOL_URN,       CARD_ACTION_OUTPOST_EXTRA_CONTRACTS, ]), 
      24 => $f([2,   CARD_SYMBOL_URN,       CARD_ACTION_HARVEST_AND_CARDS, ]), 
      25 => $f([2,   CARD_SYMBOL_ELEPHANT,  CARD_ACTION_PROGRESS_NO_SPICE, ]), 
      26 => $f([2,   CARD_SYMBOL_TIGER,     CARD_ACTION_HARVEST_AND_CARDS, ]), 
      27 => $f([2,   CARD_SYMBOL_TIGER,     CARD_ACTION_RESSOURCES_TO_COINS, ]), 
      28 => $f([3,   CARD_SYMBOL_TIGER,     CARD_ACTION_2_CONTRACTS, ]), 
      29 => $f([3,   CARD_SYMBOL_TIGER,     CARD_ACTION_PROGRESS_NO_SPICE, ]), 
      30 => $f([1,   CARD_SYMBOL_TIGER,     CARD_ACTION_PROGRESS_WITH_COINS, ]),
      31 => $f([1,   CARD_SYMBOL_ELEPHANT,  CARD_ACTION_5_COINS, ]),  


    ];
  }
}
