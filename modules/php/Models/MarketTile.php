<?php

namespace Bga\Games\Goa\Models;

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
        //keep json as short as possible by removing static datas (we can get it with material sent at page load)
        unset($data['play_type']);
        unset($data['spice_slots']);
        unset($data['actions']);
        unset($data['end_score']);
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
