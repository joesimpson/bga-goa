<?php

namespace Bga\Games\Goa\Models;

class AutomaActionCard extends Card
{  
    protected $staticAttributes = [
        ['not_first', 'int'],
        ['when_first', 'int'],
    ];

    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = CARD_SUBTTYPE_AUTOMA_ACTION;
        //keep json as short as possible by removing static datas (we can get it with material sent at page load)
        unset($data['not_first']);
        unset($data['when_first']);
        return $data;
    }
 
}
