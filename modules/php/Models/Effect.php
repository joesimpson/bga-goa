<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Managers\Effects;

/*
 * Effect: all utility functions concerning an Effect to be resolved
 */

class Effect extends \Bga\Games\Goa\Helpers\DB_Model
{
  protected $table = 'effects';
  protected $primary = 'effect_id';
  protected $attributes = [
    'id' => ['effect_id', 'int'],
    'parentId' => ['effect_parent_id', 'int'],
    'state' => ['effect_state', 'int'],
    'pId' => ['player_id', 'int'],
    'type' => ['effect_type', 'int'],
    'location' => 'effect_location',
    
    'quantity' => ['effect_qty', 'int'],
    'sourceTile' => ['effect_source_tile', 'int'],
    'sourceAction' => ['effect_source_action', 'int'],
    'result' => ['effect_result', 'obj'],
    
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
    $parent = $this->getParentId();
    if(isset($parent) ){
      $otherEffect = Effects::get($parent);
      if(isset($otherEffect)){
        $data['parent'] = $otherEffect->getUiData();
      }
    }
    return $data;
  }

  public function addChoiceInResult(int|string $choice) : array
  {
    $result = $this->getResult();
    if(!isset($result)) $result = [];
    $result[] = $choice;
    $this->setResult($result);

    return $result;
  }
   
}
