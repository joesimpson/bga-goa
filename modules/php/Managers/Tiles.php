<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Helpers\Collection;
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
  protected static $customFields = ['player_id', 'type', 'subtype'];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $subtype = isset($row['subtype']) ? $row['subtype'] : null;
    switch ($subtype) {
      case TILE_SUBTTYPE_OUTPOST:
        $data = self::getOutpostTilesTypes()[$type];
        return new OutpostTile($row, $data);
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
      self::getInLocation(TILE_LOCATION_BOARD)
      ->merge(self::getInLocation(TILE_LOCATION_PLAYER_BOARD))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOLLAM    ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOCHI     ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_MADRAS    ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOZHIKODE ]))
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
    return self::getFilteredQuery($player_id,TILE_LOCATION_BOARD)->get();
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

  ///////////////////////////////////////////////////////////////////////////////
  
  /** Creation of the Tiles */
  public static function setupNewGame($players, $options)
  {
    $Tiles = [];

    $outposts = self::getOutpostTilesTypes();
    
    foreach ($outposts as $type => $outpost) {
        $Tiles[] = [
          'location' => TILE_LOCATION_BOARD_OUTPOSTS[$outpost['colony']],
          'type' => $type,
          'subtype' => TILE_SUBTTYPE_OUTPOST,
          'nbr' => $outpost['nbr'],
        ];
    }

    self::create($Tiles);
  }
   
  /**
   * @return array of all the different types of Tiles
   */
  public static function getOutpostTilesTypes()
  {
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
      
      6 => $f([ 4, COLONY_KOCHI, [ ALL_SPICES,   ]  ]), 
      
      7 => $f([ 1,  COLONY_MADRAS, [ [SPICE_WHITE, SPICE_YELLOW], [SPICE_WHITE, SPICE_YELLOW]], ]), 
      8 => $f([ 1,  COLONY_MADRAS, [ [SPICE_BLACK, SPICE_PINK], [SPICE_BLACK, SPICE_PINK]],     ]), 
      9 => $f([ 1,  COLONY_MADRAS, [ [SPICE_GREEN, SPICE_WHITE], [SPICE_GREEN, SPICE_YELLOW]], ]), 
      10 => $f([ 1, COLONY_MADRAS,  [ [SPICE_PINK, SPICE_GREEN], [SPICE_PINK, SPICE_GREEN]],     ]), 
      11 => $f([ 1, COLONY_MADRAS,  [ [SPICE_YELLOW, SPICE_BLACK], [SPICE_YELLOW, SPICE_BLACK]], ]), 
      
      12 => $f([ 4, COLONY_KOZHIKODE, [ ALL_SPICES, ALL_SPICES,   ]  ]), 
    ];
  }
}
