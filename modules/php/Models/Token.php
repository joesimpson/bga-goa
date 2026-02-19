<?php

namespace Bga\Games\Goa\Models;


/*
 * Token: all utility functions concerning a Token
 */

class Token extends \Bga\Games\Goa\Helpers\DB_Model
{
  protected $table = 'tokens';
  protected $primary = 'token_id';
  protected $attributes = [
    'id' => ['token_id', 'int'],
    'state' => ['token_state', 'int'],
    'location' => 'token_location',
    'pId' => ['player_id', 'int'],
    'type' => ['type', 'int'],
    
    'row' => ['token_row', 'int'],
    'col' => ['token_col', 'int'],
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
    return $data;
  }

   
}
