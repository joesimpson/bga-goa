<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Models\AuctionMarker;
use Bga\Games\Goa\Models\AutomaActionCard;
use Bga\Games\Goa\Models\AutomaActionType;
use Bga\Games\Goa\Models\AutomaAuctionCard;
use Bga\Games\Goa\Models\AutomaAuctionDirectionType;
use Bga\Games\Goa\Models\AutomaAuctionStartType;
use Bga\Games\Goa\Models\CardLocation;
use Bga\Games\Goa\Models\GamePhase;
use Bga\Games\Goa\Models\MarketPlayType;
use Bga\Games\Goa\Models\MoneyCardType;

/* Class to manage all the Automa cards */

class AutomaCards extends Cards
{
    
    /**
     * @param int $currentPlayerId Id of current player loading the game
     * @return array all cards visible by this player
     */
    public static function getUiData(int $currentPlayerId): array
    {
        $phase = Utils::getRoundPhase();
        $phaseCards = self::getInLocationOrdered(CardLocation::AUTOMA_AUCTION_DAY_1_BOARD->value)
            ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_AUCTION_DAY_2_BOARD->value))
            ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_AUCTION_DAY_3_BOARD->value))
            ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_ACTION_BOARD_A->value));

        if($phase == GamePhase::NIGHT){
            $phaseCards = self::getInLocationOrdered(CardLocation::AUTOMA_AUCTION_NIGHT_1_BOARD->value)
                ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_AUCTION_NIGHT_2_BOARD->value))
                ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_AUCTION_NIGHT_3_BOARD->value))
                ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_ACTION_BOARD_B->value));
        }
        return $phaseCards
            ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_MONEY_DISCARD->value))
            ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_MONEY_AUCTION_1->value))
            ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_MONEY_AUCTION_2->value))
            ->merge(self::getInLocationOrdered(CardLocation::AUTOMA_MONEY_AUCTION_3->value))
        //REMOVE DECK CONTENT AFTER TEST -> replace by decksize
            //->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_ACTION_DECK_A->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_ACTION_DECK_B->value))
            //->merge(self::getInLocation(CardLocation::AUTOMA_MONEY_DECK->value))
            ->ui();
    } 

    public static function getAuctionDeckForTokenType(int $token_type, int $phase) : CardLocation
    {
        switch($phase){
            case GamePhase::DAY->value:
            default: 
                switch($token_type){
                    case TOKEN_TYPE_AUCTION_MARKER_1: 
                    default: return CardLocation::AUTOMA_AUCTION_DAY_1_DECK; 
                    case TOKEN_TYPE_AUCTION_MARKER_2: 
                        return CardLocation::AUTOMA_AUCTION_DAY_2_DECK; 
                    case TOKEN_TYPE_AUCTION_MARKER_3: 
                        return CardLocation::AUTOMA_AUCTION_DAY_3_DECK; 
                }
            case GamePhase::NIGHT->value:
                switch($token_type){
                    case TOKEN_TYPE_AUCTION_MARKER_1: 
                    default: return CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK; 
                    case TOKEN_TYPE_AUCTION_MARKER_2: 
                        return CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK; 
                    case TOKEN_TYPE_AUCTION_MARKER_3: 
                        return CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK; 
                }
        }
    }

    public static function countAutomaAuctionDeckSizes(GamePhase $roundPhase) : array
    {
        if($roundPhase == GamePhase::NIGHT){
            //NIGHT
            return [
            1 => self::DB()
                    ->where('subType', CARD_SUBTTYPE_AUTOMA_AUCTION)
                    ->where(static::$prefix . 'location', CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value)
                    ->count(),
            2 => self::DB()
                    ->where('subType', CARD_SUBTTYPE_AUTOMA_AUCTION)
                    ->where(static::$prefix . 'location', CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value)
                    ->count(),
            3 => self::DB()
                    ->where('subType', CARD_SUBTTYPE_AUTOMA_AUCTION)
                    ->where(static::$prefix . 'location', CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value)
                    ->count(),
            ];
        }
        return [
            1 => self::DB()
                ->where('subType', CARD_SUBTTYPE_AUTOMA_AUCTION)
                ->where(static::$prefix . 'location', CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value)
                ->count(),
            2 => self::DB()
                ->where('subType', CARD_SUBTTYPE_AUTOMA_AUCTION)
                ->where(static::$prefix . 'location', CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value)
                ->count(),
            3 => self::DB()
                ->where('subType', CARD_SUBTTYPE_AUTOMA_AUCTION)
                ->where(static::$prefix . 'location', CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value)
                ->count(),

        ];
    }
    
    public static function countAutomaActionSize(string $deckLocation) : int
    {
        return self::DB()
                ->where('subType', CARD_SUBTTYPE_AUTOMA_ACTION)
                ->where(static::$prefix . 'location', $deckLocation)
                ->count();
    }

    public static function countAutomaActionDeckSize(GamePhase $roundPhase) : int
    {
        if($roundPhase == GamePhase::NIGHT){
            //NIGHT
            return AutomaCards::countAutomaActionSize(CardLocation::AUTOMA_ACTION_DECK_B->value);
        }
        return AutomaCards::countAutomaActionSize(CardLocation::AUTOMA_ACTION_DECK_A->value);
    }
    
    public static function getAuctionBoardForTokenType(int $token_type) : CardLocation |null
    {
        $roundPhase = Utils::getRoundPhase();
        if($roundPhase == GamePhase::NIGHT){
            switch($token_type){
                case TOKEN_TYPE_AUCTION_MARKER_1:
                    return CardLocation::AUTOMA_AUCTION_NIGHT_1_BOARD;
                case TOKEN_TYPE_AUCTION_MARKER_2:
                    return  CardLocation::AUTOMA_AUCTION_NIGHT_2_BOARD;
                case TOKEN_TYPE_AUCTION_MARKER_3: 
                    return  CardLocation::AUTOMA_AUCTION_NIGHT_3_BOARD;
            }
        }
        else {
            //DAY
            switch($token_type){
                case TOKEN_TYPE_AUCTION_MARKER_1:
                    return  CardLocation::AUTOMA_AUCTION_DAY_1_BOARD;
                case TOKEN_TYPE_AUCTION_MARKER_2:
                    return  CardLocation::AUTOMA_AUCTION_DAY_2_BOARD;
                case TOKEN_TYPE_AUCTION_MARKER_3: 
                    return CardLocation::AUTOMA_AUCTION_DAY_3_BOARD;
            }
        }
        return null;
    }
    
    public static function giveAuctionCardToAutoma(AuctionMarker $token) : AutomaAuctionCard
    {
        $roundPhase = Utils::getRoundPhase();
        $toDeck = AutomaCards::getAuctionBoardForTokenType($token->getType())->value;
        if($roundPhase == GamePhase::NIGHT){
            //NIGHT
            $fromDeck = AutomaCards::getAuctionDeckForTokenType($token->getType(), 2)->value;
        }
        else {
            $fromDeck = AutomaCards::getAuctionDeckForTokenType($token->getType(), 1)->value;
            //DAY
        }
        $auctionCard = AutomaCards::getTopOf($fromDeck);
        AutomaCards::insertOnTop($auctionCard->getId(), $toDeck);
        $auctionCard->setLocation($toDeck);
        Notifications::giveAuctionCardToAutoma($auctionCard);
        return $auctionCard;
    }
    
    public static function giveActionCardToAutoma() : AutomaActionCard
    {
        $roundPhase = Utils::getRoundPhase();
        if($roundPhase == GamePhase::NIGHT){
            //NIGHT
            $fromDeck = CardLocation::AUTOMA_ACTION_DECK_B->value;
            $toDeck = CardLocation::AUTOMA_ACTION_BOARD_B->value;
        }
        else {
            //DAY
            $fromDeck = CardLocation::AUTOMA_ACTION_DECK_A->value;
            $toDeck = CardLocation::AUTOMA_ACTION_BOARD_A->value;
        }
        $deckSize = AutomaCards::countAutomaActionDeckSize($roundPhase);
        if($deckSize == 0){
            //if fromDeck is empty, reshuffle (PDF RULE is not clear)
            AutomaCards::reshuffleAutomaActionDeck($toDeck, $fromDeck);
        }
        $actionCard = AutomaCards::getTopOf($fromDeck);
        AutomaCards::insertOnTop($actionCard->getId(), $toDeck);
        $actionCard->setLocation($toDeck);
        Notifications::giveActionCardToAutoma($actionCard);
        return $actionCard;
    }
    /**
     * @param string $fromDeck specifying the deck clear
     * @param string $toDeck specifying the deck to reshuffle
     * @return int decksize after reshuffle
     */
    public static function reshuffleAutomaActionDeck(string $fromDeck,string $toDeck) : int
    {
        self::moveAllInLocation( $fromDeck, $toDeck, );
        self::shuffle($toDeck);
        $decksize = AutomaCards::countAutomaActionSize($toDeck);
        Notifications::reshuffleAutomaActionDeck($decksize );
        return $decksize;
    }

  ///////////////////////////////////////////////////////////////////////////////////////
   
  /** Creation of the cards
   * @param Collection $players
   */
  public static function setupNewGame($players, $options)
  {
    //Create cards on first session...
    //self::createCards();
  }
  
  public static function createCards()
  {
    $difficulty = Globals::getOptionAutomaDifficulty();
    if(OPTION_AUTOMA_DIFFICULTY_OFF == $difficulty){
        //NO AUTOMA
        return;
    }

    $cards = [];
    $auctions = self::getAutomaAuctionCardsTypes();
    foreach ($auctions as $type => $card) {
        if($card['nbr_A']>0){//DAY
            foreach($card['token_types'] as $token_type){
                $location = AutomaCards::getAuctionDeckForTokenType($token_type, 1)->value;
                $cards[] = [
                    'type' => $type,
                    'subtype' => CARD_SUBTTYPE_AUTOMA_AUCTION,
                    'location' => $location,
                    'nbr' => $card['nbr_A'],
                ];
            }
        }
        if($card['nbr_B']>0){//NIGHT
            foreach($card['token_types'] as $token_type){
                $location = AutomaCards::getAuctionDeckForTokenType($token_type, 2)->value;
                $cards[] = [
                    'type' => $type,
                    'subtype' => CARD_SUBTTYPE_AUTOMA_AUCTION,
                    'location' => $location,
                    'nbr' => $card['nbr_B'],
                ];
            }
        }
    }
    
    $actions = self::getAutomaActionCardsTypes();
    foreach ($actions as $type => $card) {
        if($card['nbr_A']>0){//DAY
            $cards[] = [
                'type' => $type,
                'subtype' => CARD_SUBTTYPE_AUTOMA_ACTION,
                'location' => CardLocation::AUTOMA_ACTION_DECK_A->value,
                'nbr' => $card['nbr_A'],
            ];
        }
        if($card['nbr_B']>0){//NIGHT
            $cards[] = [
                'type' => $type,
                'subtype' => CARD_SUBTTYPE_AUTOMA_ACTION,
                'location' => CardLocation::AUTOMA_ACTION_DECK_B->value,
                'nbr' => $card['nbr_B'],
            ];
        }
    }

    self::create($cards);
  }

  public static function setupSession(int $session)
  {
    $difficulty = Globals::getOptionAutomaDifficulty();
    switch($difficulty){
        case OPTION_AUTOMA_DIFFICULTY_OFF: return;
        case OPTION_AUTOMA_DIFFICULTY_1:
            MoneyCards::initAutoma([
                MoneyCardType::MONEY_1->value => 5,
                MoneyCardType::MONEY_2->value => 3,
                MoneyCardType::MONEY_5->value => 1,
                MoneyCardType::MONEY_10->value => 0,
            ]);
            break;
        case OPTION_AUTOMA_DIFFICULTY_2:
            MoneyCards::initAutoma([
                MoneyCardType::MONEY_1->value => 4,
                MoneyCardType::MONEY_2->value => 3,
                MoneyCardType::MONEY_5->value => 2,
                MoneyCardType::MONEY_10->value => 0,
            ]);
            break;
        case OPTION_AUTOMA_DIFFICULTY_3:
            MoneyCards::initAutoma([
                MoneyCardType::MONEY_1->value => 2,
                MoneyCardType::MONEY_2->value => 3,
                MoneyCardType::MONEY_5->value => 2,
                MoneyCardType::MONEY_10->value => 1,
            ]);
            break;
        default: return;
    }

    if($session == 1){
        //Create cards
        self::createCards();
    }
    else {
        //Reuse cards
        
        //Notifications::discardAllAuctions();

        // MOVE ALL TO DECK
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_AUCTION_DAY_1_BOARD->value,   CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value,  );
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_AUCTION_DAY_2_BOARD->value,   CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value,  );
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_AUCTION_DAY_3_BOARD->value,   CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value,  );
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_1_BOARD->value, CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value,);
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_2_BOARD->value, CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value,);
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_3_BOARD->value, CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value,);
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_ACTION_BOARD_A ->value,     CardLocation::AUTOMA_ACTION_DECK_A->value,        );
        AutomaCards::moveAllInLocation(CardLocation::AUTOMA_ACTION_BOARD_B ->value,   CardLocation::AUTOMA_ACTION_DECK_B->value,        );
    }

    AutomaCards::shuffle(CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value);
    AutomaCards::shuffle(CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value);
    AutomaCards::shuffle(CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value);
    AutomaCards::shuffle(CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value);
    AutomaCards::shuffle(CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value);
    AutomaCards::shuffle(CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value);
    AutomaCards::shuffle(CardLocation::AUTOMA_ACTION_DECK_A->value);
    AutomaCards::shuffle(CardLocation::AUTOMA_ACTION_DECK_B->value);
    
    AutomaCards::setupPhase(GamePhase::DAY);
    
  }
  
  public static function setupPhase(GamePhase $phase)
  { 
    switch($phase) {
        case GamePhase::DAY:
            $auctionCards = self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value)
                    ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value))
                    ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value));
            Notifications::initAutomaAuctionCards($auctionCards);
            $actionCards = self::getInLocation(CardLocation::AUTOMA_ACTION_DECK_A->value);
            Notifications::initAutomaActionCards($actionCards);
            break;
        case GamePhase::NIGHT:
            $auctionCards = self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value)
                    ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value))
                    ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value));
            Notifications::initAutomaAuctionCards($auctionCards);
            $actionCards = self::getInLocation(CardLocation::AUTOMA_ACTION_DECK_B->value);
            Notifications::initAutomaActionCards($actionCards);
            break;
    }

  }
  /**
   * @return array of all the different types of Automa AUCTION Cards
   */
  public static function getAutomaAuctionCardsTypes(): array
  { 
    $f = function ($t) {
      return [
        //Number of same in deck A
        'nbr_A' => $t[0],
        //Number of same in deck B
        'nbr_B' => $t[1],
        'token_types'   => $t[2],//will determine location in decks
        'tile_priority' => $t[3],
        'start'         => $t[4],
        'direction'     => $t[5],
        'auction_money' => $t[6],
      ];
    };
    return [
      //definition in the same order as in the sprite 'solo_cards.jpg' :
      1 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE] ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          2,   
      ]), 
      2 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE] ],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          1,   
      ]), 
      3 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          2,   
      ]), 
      4 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ MarketPlayType::ACTION_DIRECT,  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::DEPOT, MarketPlayType::SCORING, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          1,   
      ]), 
      5 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ MarketPlayType::DEPOT, MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT,  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  ],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          3,
      ]), 
      6 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          2,
      ]), 
      7 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          1,
      ]), 
      8 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          5,
      ]), 
        
      9 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE] ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          2,   
      ]), 
      10 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE] ],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          1,   
      ]), 
      11 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          2,   
      ]), 
      12 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ MarketPlayType::ACTION_DIRECT,  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::DEPOT, MarketPlayType::SCORING, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          1,   
      ]), 
      13 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ MarketPlayType::DEPOT, MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT,  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  ],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          3,
      ]), 
      14 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          2,
      ]), 
      15 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          1,
      ]), 
      16 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          5,
      ]), 
      
      //NIGHT CARDS :
      21 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          2,   
      ]), 
      22 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   MarketPlayType::DEPOT,MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          3,   
      ]), 
      23 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          4,   
      ]), 
      24 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::SCORING, MarketPlayType::DEPOT,[ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          5,   
      ]), 
      25 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          3,   
      ]), 
      26 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          4,   
      ]), 
      27 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::DEPOT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          5,   
      ]), 
      28 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   ],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          2,   
      ]), 
      
      29 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          2,   
      ]), 
      30 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   MarketPlayType::DEPOT,MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          3,   
      ]), 
      31 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          4,   
      ]), 
      32 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::SCORING, MarketPlayType::DEPOT,[ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_CLOCKWISE,
          5,   
      ]), 
      33 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          3,   
      ]), 
      34 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          4,   
      ]), 
      35 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::DEPOT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::CLOCKWISE,
          5,   
      ]), 
      36 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::DEPOT, MarketPlayType::ACTION_DIRECT, MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   ],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::CLOCKWISE,
          2,   
      ]), 
      
      
    ];
  }

  
  /**
   * @return array of all the different types of Automa ACTION Cards
   */
  public static function getAutomaActionCardsTypes(): array
  { 
    $f = function ($t) {
      return [
        //Number of same in deck A
        'nbr_A'         => $t[0],
        //Number of same in deck B
        'nbr_B'         => $t[1],
        'not_first'     => $t[2],
        'when_first'    => $t[3],
      ];
    };
    return [
      //definition in the same order as in the sprite 'solo_cards.jpg' :
      41 =>  $f([ 1, 0, AutomaActionType::GAIN_MONEY_1,   AutomaActionType::GAIN_MONEY_2, ]), 
      42 =>  $f([ 3, 0, AutomaActionType::GAIN_MONEY_2,   AutomaActionType::GAIN_MONEY_5, ]), 
      43 =>  $f([ 2, 0, AutomaActionType::NOGAIN_MONEY_5, AutomaActionType::GAIN_MONEY_5, ]), 
      44 =>  $f([ 1, 0, AutomaActionType::GAIN_MONEY_1,   AutomaActionType::DISCARD_2_KOLLAM, ]), 
      45 =>  $f([ 1, 0, AutomaActionType::GAIN_MONEY_5,   AutomaActionType::DISCARD_2_MADRAS, ]), 

      46 =>  $f([ 0, 3, AutomaActionType::GAIN_MONEY_2,   AutomaActionType::GAIN_MONEY_5, ]), 
      47 =>  $f([ 0, 1, AutomaActionType::GAIN_MONEY_5,   AutomaActionType::GAIN_MONEY_10, ]), 
      48 =>  $f([ 0, 2, AutomaActionType::NOGAIN_MONEY_5, AutomaActionType::GAIN_MONEY_5, ]), 
      49 =>  $f([ 0, 1, AutomaActionType::NOGAIN_MONEY_10,AutomaActionType::GAIN_MONEY_10, ]), 
      50 =>  $f([ 0, 1, AutomaActionType::NOGAIN_MONEY_2, AutomaActionType::GAIN_MONEY_2, ]), 

    ];
  }

}
