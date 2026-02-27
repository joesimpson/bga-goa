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
        //$data['name'] = $this->getGoodsName();
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
    
    public function getGoodsName(){
        switch($this->getSpice()){
            case SPICE_WHITE:    return clienttranslate('Coconut Coir');
            case SPICE_YELLOW:   return clienttranslate('Turmeric');
            case SPICE_BLACK:    return clienttranslate('Pepper');
            case SPICE_PINK:     return clienttranslate('Silk');
            case SPICE_GREEN:    return clienttranslate('Dried fish');
            default: return '';
        }
    }
   
}
