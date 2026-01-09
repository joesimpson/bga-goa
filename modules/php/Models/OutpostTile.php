<?php

namespace Bga\Games\Goa\Models;

class OutpostTile extends Tile
{  

    protected $staticAttributes = [
        ['colony', 'int'],
    ];

    public function __construct($row, $datas)
    {
        parent::__construct($row, $datas);
    } 
    
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TILE_SUBTTYPE_OUTPOST;
        return $data;
    }
 
}
