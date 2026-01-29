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
    

    /**
     * @return int id of tile if this spice is on a tile, null otherwise
     */
    public function getTileId() : int |null
    {
        if (preg_match("/^" . TOKEN_LOCATION_TILE . "(?P<tile>\d+)$/", $this->getLocation(), $matches) == 1) {
            return $matches['tile'];
        }
        return null;
    }
   
}
