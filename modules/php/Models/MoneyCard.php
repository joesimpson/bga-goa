<?php

namespace Bga\Games\Goa\Models;

class MoneyCard extends Card
{  
    protected $staticAttributes = [
        ['value', 'int'],
    ];

    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = CARD_SUBTTYPE_MONEY;
        return $data;
    }
 
}
