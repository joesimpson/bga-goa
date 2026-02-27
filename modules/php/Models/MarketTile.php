<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Managers\Tiles;

class MarketTile extends Tile
{  

    protected $staticAttributes = [
        ['play_type', 'int'],
        'spice_slots', //array of ints
        'actions', //array of ints
        ['end_score', 'int'],
        ['name', 'string'],
    ];

    public function __construct($row, $datas)
    {
        parent::__construct($row, $datas);
    } 
    
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TILE_SUBTTYPE_MARKET;
        unset($data['name']);
        return $data;
    }

    public function alreadyPlayed()
    {
        return $this->getState() === TILE_STATE_PLAYED;
    }
    
    /*
    public function getName() : string{

        $allTypes = Tiles::getMarketTilesTypes();
        if(!in_array($this->getType(), array_keys($allTypes))){
            return '';
        }
        return $allTypes[$this->getType()]['name'];
    }*/
 
}
