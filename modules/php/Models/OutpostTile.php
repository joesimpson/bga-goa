<?php

namespace Bga\Games\Goa\Models;

class OutpostTile extends Tile
{  

    protected $staticAttributes = [
        ['colony', 'int'],
        'spice_slots', //array of ints
    ];

    public function __construct($row, $datas)
    {
        parent::__construct($row, $datas);
    } 
    
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TILE_SUBTTYPE_OUTPOST;
        unset($data['row']);
        unset($data['col']);
        return $data;
    }
 
}
