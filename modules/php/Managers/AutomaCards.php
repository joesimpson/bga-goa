<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\AutomaAuctionDirectionType;
use Bga\Games\Goa\Models\AutomaAuctionStartType;
use Bga\Games\Goa\Models\CardLocation;
use Bga\Games\Goa\Models\MarketPlayType;

/* Class to manage all the Automa cards */

class AutomaCards extends Cards
{
    
    /**
     * @param int $currentPlayerId Id of current player loading the game
     * @return array all cards visible by this player
     */
    public static function getUiData($currentPlayerId): array
    {
        //TODO JSA check phase
        //TODO JSA !! getTopOf cannot be merged if null
        return self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_1_BOARD->value)
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_2_BOARD->value))
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_3_BOARD->value))
        //TODO JSA REMOVE DECK CONTENT AFTER TEST
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value))
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value))
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value))
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value))
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value))
            ->merge(self::getInLocation(CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value))
            ->map(function ($card) {
                return $card->getUiData();
            })
            ->toArray();
    } 

    public static function getAuctionDeckForTokenType(int $token_type, int $phase) : CardLocation
    {
        switch($phase){
            case 1: //DAY
            default: 
                switch($token_type){
                    case TOKEN_TYPE_AUCTION_MARKER_1: 
                    default: return CardLocation::AUTOMA_AUCTION_DAY_1_DECK; 
                    case TOKEN_TYPE_AUCTION_MARKER_2: 
                        return CardLocation::AUTOMA_AUCTION_DAY_2_DECK; 
                    case TOKEN_TYPE_AUCTION_MARKER_3: 
                        return CardLocation::AUTOMA_AUCTION_DAY_3_DECK; 
                }
            case 2: //NIGHT
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


  ///////////////////////////////////////////////////////////////////////////////////////
   
  /** Creation of the cards
   * @param Collection $players
   */
  public static function setupNewGame($players, $options)
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

    self::create($cards);
    self::shuffle(CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value);
    self::shuffle(CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value);
    self::shuffle(CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value);
    self::shuffle(CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value);
    self::shuffle(CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value);
    self::shuffle(CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value);
    self::shuffle(CardLocation::AUTOMA_ACTION_DECK->value);

  }
  
  public static function setupSession(int $session)
  {
    //TODO JSA CAMPAIGN SOLO
    
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
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          3,
      ]), 
      6 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          2,
      ]), 
      7 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          1,
      ]), 
      8 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
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
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          3,
      ]), 
      14 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          2,
      ]), 
      15 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::DEPOT, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          1,
      ]), 
      16 =>  $f([
          1, 
          0, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE], MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          5,
      ]), 
      
      //NIGHT CARDS :
      21 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::BOTTOM_LEFT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          2,   
      ]), 
      22 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   MarketPlayType::DEPOT,MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          3,   
      ]), 
      23 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          4,   
      ]), 
      24 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_1], 
          [  MarketPlayType::SCORING, MarketPlayType::DEPOT,[ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
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
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          2,   
      ]), 
      30 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [ [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],   MarketPlayType::DEPOT,MarketPlayType::SCORING, MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::TOP_LEFT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          3,   
      ]), 
      31 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::SCORING, [ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, MarketPlayType::DEPOT,],
          AutomaAuctionStartType::TOP_RIGHT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
          4,   
      ]), 
      32 =>  $f([
          0, 
          1, 
          [TOKEN_TYPE_AUCTION_MARKER_2, TOKEN_TYPE_AUCTION_MARKER_3], 
          [  MarketPlayType::SCORING, MarketPlayType::DEPOT,[ MarketPlayType::ACTION_REUSEABLE, MarketPlayType::ACTION_ONCE],  MarketPlayType::ACTION_DIRECT, ],
          AutomaAuctionStartType::BOTTOM_RIGHT,
          AutomaAuctionDirectionType::COUNTER_LOCKWISE,
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
    return [

    ];
  }

}
