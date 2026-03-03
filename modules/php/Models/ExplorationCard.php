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
        //keep json as short as possible by removing static datas (we can get it with material sent at page load)
        unset($data['contracts']);
        unset($data['symbol']);
        unset($data['action']);
        return $data;
    }
 
}
