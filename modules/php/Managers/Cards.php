<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
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
  public static function getUiData($currentPlayerId): array
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
  
  public static function getByType(int $cardType): Collection
  {
    return self::DB()
      ->where('type', $cardType)
      ->get();
  }
   
  public static function getExplorationDiscardPile(): Collection
  {
    return self::DB()
      ->where( 'subType', CARD_SUBTTYPE_EXPLORATION)
      ->where(static::$prefix . 'location', CARD_LOCATION_EXP_DISCARD)
      ->get();
  }

  public static function getAllDeckSizes(): array
  {
    return [
            'exploration' => Cards::countInExplorationDeck(),
        ];
  }
  public static function countInExplorationDeck() : int
  {
    return self::DB()
      ->where( 'subType', CARD_SUBTTYPE_EXPLORATION)
      ->where(static::$prefix . 'location', CARD_LOCATION_EXP_DECK)
      ->count();
  }
  public static function getExplorationHand(int $pId) : Collection
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
    $progressMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_EXP,$playerId);
    $gains = PROGRESS_GAINS_EXPLO[$progressMarker->getRow() - 1];
    $nbMaxInHand = $gains['max'];
    $delta = $gains['plus'];
    
    {
      //If we want to forbid drawing cards when already at max 
      //$nbCardsInHand = Cards::countInExplorationHand($playerId);
      //$possibleDelta = $nbMaxInHand - $nbCardsInHand;
      //return max( 0, min($delta, $possibleDelta));
    }
    {
      //If we allow to draw cards BEFORE discarding to reach max limit
      return $delta;
    }
  }
  
  public static function maxExplorationToKeep(Player $player) :int
  {
    $playerId = $player->getId();
    $progressMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_EXP,$playerId);
    $gains = PROGRESS_GAINS_EXPLO[$progressMarker->getRow() - 1];
    return $gains['max'];
  }

  public static function getPossibleToDiscard(Player $player) : Collection
  {
    //We suppose we always may discard our exploration cards
    return Cards::getExplorationHand($player->getId());
  }
  

  /**
   * @param Player $player
   * @param array &$args : this function may add some possible actions into it
   * @param array $actionsWhiteList : optional list of card actions to keep
   */
  public static function getPossibleToPlay(Player $player, array &$args, array|null $actionsWhiteList = null) : Collection
  {
    $cardsPlayed = $player->getCardsPlayed();
    $playerSpices = Tokens::getPlayerSpices($player->getId());

    $hand = Cards::getExplorationHand($player->getId());
    return $hand->filter(function($card) use ($cardsPlayed, $actionsWhiteList){
      if(isset($cardsPlayed) && count($cardsPlayed) >= NB_MAX_CARDS_PER_TURN) return false;
      if(isset($actionsWhiteList) && !in_array($card->getAction(),$actionsWhiteList)) return false;
      return true;
    })
    ->map(function($card) use ($player, $playerSpices, &$args){
      $playDatas = [];
      $possiblePlay = false;
      $actionPlayed = $player->isActionPlayed();
      switch($card->getAction()){
        case CARD_ACTION_PROGRESS_NO_SPICE: 
            $progressTracks = Tokens::getUpgradableProgressMarkers($player, false, true);
            $possiblePlay = count($progressTracks)>0;
            $playDatas['progressTracks'] = $progressTracks;
          //not possible if player already performed an action in this round
          if($actionPlayed) $possiblePlay = false;
          break;
        case CARD_ACTION_PROGRESS_NO_SHIP:
            $progressTracks = Tokens::getUpgradableProgressMarkers($player, true, false);
            $possiblePlay = count($progressTracks)>0;
            $playDatas['progressTracks'] = $progressTracks;
          //not possible if player already performed an action in this round
          if($actionPlayed) $possiblePlay = false;
          break;
        case CARD_ACTION_HARVEST_AND_CARDS:
          //not possible if player already performed an action in this round
          if($actionPlayed) $possiblePlay = false;
          else if(isset($args['harvestSpots'])){
            $possiblePlay = (count($args['harvestSpots']) > 0);
            if($possiblePlay){
              $playDatas['harvest'] = [
                'countAction' => true,
                'spots' => $args['harvestSpots'],
                //in order to not let players loose ships/contracts, set min to max
                'min' => $args['playerBaseHarvest'],
                'max' => $args['playerBaseHarvest'],
                'others' => [ACTION_SHIP, ACTION_CONTRACT],
              ];
            }
          } 
          break;
        case CARD_ACTION_OUTPOST_EXTRA_CONTRACTS:
          /* REPLACED by a rule to play the Card AFTER seeing the result of FOUND Outpost{ 
            
          // Not possible if the arg outpostsToFound is not set for playerTurn
          $playDatas['outpostsToFound'] = Tiles::getFoundableOutposts($player)->getIds();
          if(isset($playDatas['outpostsToFound'])){
            $outpostsToFound = $playDatas['outpostsToFound'];
            $possiblePlay = (count($outpostsToFound)>0);
            $playDatas['playerBaseContracts'] = Players::getFoundBaseContracts($player);
            $playDatas['extraDraw'] = 1;
          }
          //not possible if player already performed an action in this round
          if($actionPlayed) $possiblePlay = false;

          }*/
          {
            $effectsToFound = Effects::getEffectToResolveByType(EFFECT_TYPE_FOUND_TILE,$player->getId());
            $possiblePlay = ($effectsToFound->count()>0);
          }
          break;
        case CARD_ACTION_RESSOURCES_TO_COINS:
          if($playerSpices->count()>0){
            $possiblePlay = true;
            $playDatas['spendSpices'] = [
              'min' => 1, 
              'max' => 4, 
              'forCoins' => true, 
              't_ids' => $playerSpices->getIds(),
            ];
            $args['possibleActions'][] = 'actSpendSpices';
          }
          break;
        case CARD_ACTION_2_SPICES:
          if(!isset($args['harvestSpots'])){
            $harvestSpots = Tiles::getPlayerAvailableSpotsForSpices($player);
          }
          else {
            $harvestSpots = $args['harvestSpots'];
          }
          $possiblePlay = count($harvestSpots) > 0;
          if($possiblePlay){
            $playDatas['harvest'] = [
              'countAction' => false,
              'spots' => $harvestSpots,
              'min' => 1,
              'max' => 2,
            ];
            $args['possibleActions'][] = 'actHarvest';
          }
          break;
        case CARD_ACTION_PROGRESS_WITH_COINS:
          $progressTracks = Tokens::getUpgradableProgressMarkersWithCoins($player);
          $possiblePlay = count($progressTracks)>0;
          $playDatas['progressTracks'] = $progressTracks;
          if($possiblePlay) $args['possibleActions'][] = 'actProgress';
          break;
        case CARD_ACTION_2_CONTRACTS:
        case CARD_ACTION_2_SHIPS:
        case CARD_ACTION_5_COINS:
        default:
          $possiblePlay = true;
          break;
      }
      return [
        'id' => $card->getId(),
        'action' => $card->getAction(),
        'play_datas' => $playDatas,
        'possiblePlay' => $possiblePlay,
      ];
    })
    ->filter(function($map) {
        return $map['possiblePlay'];
    })
    ;
  }
  
  public static function getPrivateDatas(int $pId) : array
  {
    return [ 
      'hand' => Cards::getExplorationHand($pId)->ui(),
    ];
  }
  
  public static function drawExplorationCardsToPlayer(Player $player,int $nb, bool &$doCheckpoint) : Collection
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
    //we don't know here which state to save, and the action may be unfinished here
    //Log::addStateCheckpoint($nextStateClass);
    $doCheckpoint = true;
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
   
  /**
   * @param Player &$player : who is playing the card
   * @param int $cardId : which card is played
   * @param bool &$doCheckpoint (auto updated arg) : whether the card action leads to a need for a checkpoint
   */
  public static function playExploration(
    Player &$player,
    int $cardId,
    bool &$doCheckpoint, 
  ): ExplorationCard
  { 
    $card = Cards::get($cardId);
    
    self::insertOnTop($cardId,CARD_LOCATION_EXP_DISCARD);
    $card = Cards::get($cardId);
    $card->setPId(null);

    Players::registerCardPlayed($player,$cardId);
    
    Stats::inc("playedExplorations", $player->getId());

    //NOTIFY 
    Notifications::playExploration( $player, $card);
    
    switch($card->getAction()){
      case CARD_ACTION_PROGRESS_NO_SPICE: //done in actProgress
        break;
      case CARD_ACTION_PROGRESS_NO_SHIP: //done in actProgress
        break;
      case CARD_ACTION_HARVEST_AND_CARDS: //done in actHarvest
        break;
      case CARD_ACTION_OUTPOST_EXTRA_CONTRACTS:
        $cards = Cards::drawAndDiscardExploration($player, 1);
        $sum = 0;
        foreach($cards as $card){
          $sum += $card->getContracts();
        }
        Notifications::resultFoundOutpost($player, $sum);
        $doCheckpoint = true;
        Utils::refreshFoundTileEffectWithContracts($player->getId(), $sum);
        break;
      case CARD_ACTION_RESSOURCES_TO_COINS: //done in actSpendSpices
        break;
      case CARD_ACTION_2_SPICES: //done in actHarvest
        break;
      case CARD_ACTION_2_CONTRACTS:
        Players::giveContracts($player, 2);
        break;
      case CARD_ACTION_2_SHIPS:
        Players::giveShips($player, 2);
        break;
      case CARD_ACTION_5_COINS:
        Players::giveMoney($player, 5);
        break;
      case CARD_ACTION_PROGRESS_WITH_COINS: //done in actProgress
        break;
      default:
        break;
    }
    
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
  
  public static function setupSession(int $session)
  {
    
    // MOVE ALL TO DECK
    self::DB()
      ->where('subtype', CARD_SUBTTYPE_EXPLORATION)
      ->update([static::$prefix .'location' => CARD_LOCATION_EXP_DECK])
      ->run();
    Notifications::discardAllExploration();

    self::shuffle(CARD_LOCATION_EXP_DECK);
    $nbDeck = Cards::countInExplorationDeck();
    Notifications::reshuffleExplorationDeck(null, $nbDeck );
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
