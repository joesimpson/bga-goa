<?php

namespace Bga\Games\Goa\Models;

/**
 * State of the token will be used to track progression
 */
class ProgressMarker extends Token
{  
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TOKEN_SUBTTYPE_AUCTION_MARKER;
        return $data;
    }
   
}
