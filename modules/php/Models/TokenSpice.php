<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Managers\Tokens;

class TokenSpice extends Token
{  
    protected $staticAttributes = [
        ['spice', 'int'],
    ];

    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TOKEN_SUBTTYPE_SPICE;
        return $data;
    }
   
}
