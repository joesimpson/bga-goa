<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Helpers\DB_Model;

/*
 * Tile: all utility functions concerning a Tile
 */

class Tile extends DB_Model
{
  protected $table = 'tiles';
  protected $primary = 'tile_id';
  protected $attributes = [
    'id' => ['tile_id', 'int'],
    'state' => ['tile_state', 'int'],
    'location' => 'tile_location',
    //startLocation may be used to reset all tiles when new session starts
    'startLocation' => 'tile_start_location',
    'pId' => ['player_id', 'int'],
    'type' => ['type', 'int'],

    'row' => ['tile_row', 'int'],
    'col' => ['tile_col', 'int'],
  ];
   
  public function __construct($row, $datas)
  {
    parent::__construct($row);
    foreach ($datas as $attribute => $value) {
      $this->$attribute = $value;
    }
  }

  public function getUiData()
  {
    $data = parent::getUiData();
    //Remove unused datas on client side :
    unset($data['startLocation']);
    return $data;
  }

}
