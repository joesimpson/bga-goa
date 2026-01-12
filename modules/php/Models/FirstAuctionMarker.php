<?php

namespace Bga\Games\Goa\Models;

class FirstAuctionMarker extends Token
{  
    
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER;
        return $data;
    }
   
}
