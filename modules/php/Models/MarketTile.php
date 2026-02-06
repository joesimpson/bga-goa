<?php

namespace Bga\Games\Goa\Models;

class MarketTile extends Tile
{  

    protected $staticAttributes = [
        ['play_type', 'int'],
        'spice_slots', //array of ints
        'actions', //array of ints
        ['end_score', 'int'],
    ];

    public function __construct($row, $datas)
    {
        parent::__construct($row, $datas);
    } 
    
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TILE_SUBTTYPE_MARKET;
        return $data;
    }

    public function alreadyPlayed()
    {
        return $this->getState() === TILE_STATE_PLAYED;
    }
 
}
