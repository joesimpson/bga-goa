<?php

namespace Bga\Games\Goa\Models;

class TokenSpice extends Token
{  
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TOKEN_SUBTTYPE_SPICE;
        return $data;
    }
   
}
