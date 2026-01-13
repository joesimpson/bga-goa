<?php

namespace Bga\Games\Goa\Models;

class ExplorationCard extends Card
{  
    
    protected $staticAttributes = [
        ['contracts', 'int'],
        ['symbol', 'int'],
        ['action', 'string'],
    ];

    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = CARD_SUBTTYPE_EXPLORATION;
        return $data;
    }
 
}
