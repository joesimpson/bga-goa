<?php

namespace Bga\Games\Goa\Models;

class BonanzaTile extends MarketTile
{  

    public function __construct($row, $datas)
    {
        parent::__construct($row, $datas);
    } 
    
    public function getUiData()
    {
        $data = parent::getUiData();
        if($this->getLocation() == TILE_LOCATION_BOARD_MARKET){
            $data['type'] = BONANZA_TILE_BACK;//HIDE REAL TYPE if tile is still in market
        }
        return $data;
    }
 
}
