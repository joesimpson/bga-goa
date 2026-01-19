<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\MarketTile;
use Bga\Games\Goa\Models\OutpostTile;
use Bga\Games\Goa\Models\Tile;
use InvalidArgumentException;

/* Class to manage all the Tiles */

class Tiles extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'tiles';
  protected static $prefix = 'tile_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type', 'subtype', 'row', 'col'];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $subtype = isset($row['subtype']) ? $row['subtype'] : null;
    switch ($subtype) {
      case TILE_SUBTTYPE_OUTPOST:
        $data = self::getOutpostTilesTypes()[$type];
        return new OutpostTile($row, $data);
      case TILE_SUBTTYPE_MARKET:
        $data = self::getMarketTilesTypes()[$type];
        return new MarketTile($row, $data);
    }

    throw new InvalidArgumentException("Tile $type, $subtype");
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all Tiles visible by this player
   */
  public static function getUiData($currentPlayerId)
  {

    return 
      self::getInLocation(TILE_LOCATION_BOARD_MARKET)
      ->merge(self::getInLocation(TILE_LOCATION_PLAYER_BOARD))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOLLAM    ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOCHI     ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_MADRAS    ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOZHIKODE ]))
      
      //for debug :
      ->merge(self::getInLocation(TILE_LOCATION_DECK_A))
      ->merge(self::getInLocation(TILE_LOCATION_DECK_B))

      ->map(function ($tile) {
        return $tile->getUiData();
      })
      ->toArray();
  } 
 
  /**
   * Return all BOARD Tiles 
   * @param int $player_id (Optional) filter player Tiles
   * @return Collection
   */
  public static function getBoardTiles($player_id = null)
  {
    return self::getFilteredQuery($player_id,TILE_LOCATION_BOARD_MARKET)->get();
  }

  /**
   * @param int $pId
   * @param string $location (optional)
   * @return int number of ALL Tiles owned by that player and in that $location,
   *   or ALL Tiles owned by that player if location not given
   */
  public static function countPlayerTiles($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location)->count();
  }
  
  public static function getByType(int $cardType, int $cardSubtype)
  {
    return self::DB()
      ->where('type', $cardType)
      ->where('subtype', $cardSubtype)
      ->get();
  }

  ///////////////////////////////////////////////////////////////////////////////
  
  /** Creation of the Tiles */
  public static function setupNewGame($players, $options)
  {
    $tiles = [];

    $outposts = self::getOutpostTilesTypes();
    
    foreach ($outposts as $type => $outpost) {
        $tiles[] = [
          'location' => TILE_LOCATION_BOARD_OUTPOSTS[$outpost['colony']],
          'type' => $type,
          'subtype' => TILE_SUBTTYPE_OUTPOST,
          'nbr' => $outpost['nbr'],
        ];
    }

    $markets = self::getMarketTilesTypes();
    foreach ($markets as $type => $market) {
        if($market['nbr_A']>0){
          $tiles[] = [
            'location' => TILE_LOCATION_DECK_A,
            'type' => $type,
            'subtype' => TILE_SUBTTYPE_MARKET,
            'nbr' => $market['nbr_A'],
          ];
        }
        if($market['nbr_B']>0){
          $tiles[] = [
            'location' => TILE_LOCATION_DECK_B,
            'type' => $type,
            'subtype' => TILE_SUBTTYPE_MARKET,
            'nbr' => $market['nbr_B'],
          ];
        }
    }

    self::create($tiles);
    self::shuffle(TILE_LOCATION_DECK_A);
    self::shuffle(TILE_LOCATION_DECK_B);

    //TODO JSA move it in other state for each round / phase:
    $tiles = self::pickForLocation(4,TILE_LOCATION_DECK_A,TILE_LOCATION_BOX);
    //TODO JSA if more than 2 plantations, pick and replace ...

    $deckPhase = self::getInLocationOrdered(TILE_LOCATION_DECK_A);
    $counter = 0;
    foreach($deckPhase as $tile_id => $tile){
      $tile->setLocation(TILE_LOCATION_BOARD_MARKET);
      $tile->setRow(1 + intval($counter / MARKET_GRID_SIZE) );
      $tile->setCol(1 + $counter - ($tile->getRow()-1)*MARKET_GRID_SIZE );
      $counter++;
    }



  }
   
  /**
   * @return array of all the different types of Outpost Tiles
   */
  public static function getOutpostTilesTypes()
  {
    //define the array keys for each entry in the next array
    $f = function ($t) {
      return [
        'nbr' => $t[0],
        'colony' => $t[1],
        'spice_slots' => $t[2],
      ];
    };
    return [
      1 => $f([ 1, COLONY_KOLLAM, [ [SPICE_WHITE, SPICE_YELLOW],   ]  ]), 
      2 => $f([ 1, COLONY_KOLLAM, [ [SPICE_BLACK, SPICE_PINK],     ]  ]), 
      3 => $f([ 1, COLONY_KOLLAM, [ [SPICE_GREEN, SPICE_WHITE],     ]  ]), 
      4 => $f([ 1, COLONY_KOLLAM, [ [SPICE_PINK, SPICE_GREEN],     ]  ]), 
      5 => $f([ 1, COLONY_KOLLAM, [ [SPICE_YELLOW, SPICE_BLACK],     ]  ]), 
      
      6 => $f([ 4, COLONY_KOCHI, [ SPICES_ALL,   ]  ]), 
      
      7 => $f([ 1,  COLONY_MADRAS, [ [SPICE_WHITE, SPICE_YELLOW], [SPICE_WHITE, SPICE_YELLOW]], ]), 
      8 => $f([ 1,  COLONY_MADRAS, [ [SPICE_BLACK, SPICE_PINK], [SPICE_BLACK, SPICE_PINK]],     ]), 
      9 => $f([ 1,  COLONY_MADRAS, [ [SPICE_GREEN, SPICE_WHITE], [SPICE_GREEN, SPICE_WHITE]], ]), 
      10 => $f([ 1, COLONY_MADRAS,  [ [SPICE_PINK, SPICE_GREEN], [SPICE_PINK, SPICE_GREEN]],     ]), 
      11 => $f([ 1, COLONY_MADRAS,  [ [SPICE_YELLOW, SPICE_BLACK], [SPICE_YELLOW, SPICE_BLACK]], ]), 
      
      12 => $f([ 4, COLONY_KOZHIKODE, [ SPICES_ALL, SPICES_ALL,   ]  ]), 
    ];
  }
  
  /**
   * @return array of all the different types of Market Tiles
   */
  public static function getMarketTilesTypes()
  {
    //define the array keys for each entry in the next array
    $f = function ($t) {
      return [
        //Number of same in deck A
        'nbr_A' => $t[0],
        //Number of same in deck B
        'nbr_B' => $t[1],
        'play_type' => $t[2],
        'spice_slots' => $t[3],
        'actions' => $t[4],
        'end_score' => $t[5],
      ];
    };

    return [
      //I define the tiles in the same order as I made the sprite "market.jpg" :
      2 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICES_ALL, ], [], 0, ]),  
      3 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_WHITE,  ], [], 1, ]),  
      4 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_YELLOW, ], [], 1, ]),  
      5 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_GREEN,  ], [], 1, ]),  
      6 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_BLACK,  ], [], 1, ]),  
      7 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_PINK,   ], [], 1, ]),  
      
      8 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_SPICE => SPICES_ALL ], 0, ]),
      9 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_CONTRACT => 1 ], 0, ]),
      
      10 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_WHITE,  SPICE_WHITE   ], [], 0, ]),  
      11 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_YELLOW, SPICE_YELLOW, ], [], 0, ]),  
      12 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_GREEN,  SPICE_GREEN,  ], [], 0, ]),  
      13 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_BLACK,  SPICE_BLACK,  ], [], 0, ]),  
      14 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_PINK,   SPICE_PINK,   ], [], 0, ]),  

      15 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_SHIP => 1 ], 0, ]),
      16 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_MONEY => 3 ], 0, ]),

      17 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_WHITE,  SPICE_WHITE,  SPICE_WHITE   ], [], 0, ]),  
      18 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_YELLOW, SPICE_YELLOW, SPICE_YELLOW, ], [], 0, ]),  
      19 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_GREEN,  SPICE_GREEN,  SPICE_GREEN,  ], [], 0, ]),  
      20 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_BLACK,  SPICE_BLACK,  SPICE_BLACK,  ], [], 0, ]),  
      21 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_PINK,   SPICE_PINK,   SPICE_PINK,   ], [], 0, ]),  

      
      23 => $f([ 1, 2, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_CONTRACT => 1,  ACTION_COMPASS => 1, ACTION_SHIP => 1, ], 0, ]),
      24 => $f([ 1, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_CONTRACT => 4, ], 0, ]),
      25 => $f([ 1, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_SHIP => 4, ], 0, ]),
      26 => $f([ 2, 2, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_SHIPS_OR_CONTRACTS => 3, ], 0, ]),
      27 => $f([ 1, 2, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_COMPASS => 2, ], 0, ]),
      28 => $f([ 1, 0, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_EXPLORATION => 2, ], 0, ]),
      29 => $f([ 0, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_EXPLORATION => 3, ], 0, ]),
      30 => $f([ 0, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_FAVOR => 1 ], 0, ]),

      31 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_SWAP => 1 ], 0, ]),
      32 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_RESUPPLY => 1 ], 0, ]),
      33 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_HARVEST => 1 ], 0, ]),
      34 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_ESPIONAGE => 1 ], 0, ]),
      35 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_VICEKING => 1 ], 0, ]),
      
      36 => $f([ 0, 1, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_EXPLORATION => 1, ], 0, ]),
      
      37 => $f([ 1, 0, MARKET_TYPE_SCORING, [], [], 0, ]),
      38 => $f([ 1, 1, MARKET_TYPE_SCORING, [], [], 0, ]),
      39 => $f([ 0, 1, MARKET_TYPE_SCORING, [], [], 2, ]),
      40 => $f([ 0, 1, MARKET_TYPE_SCORING, [], [], 3, ]),

    ];
  }
}
