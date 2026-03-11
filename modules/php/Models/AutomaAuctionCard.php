<?php

namespace Bga\Games\Goa\Models;

class AutomaAuctionCard extends Card
{  
    protected $staticAttributes = [
        ['token_type', 'int'],
        ['tile_priority', 'int'],
        ['start', 'int'],
        ['direction', 'int'],
        ['auction_money', 'int'],
    ];

    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = CARD_SUBTTYPE_AUTOMA_AUCTION;
        //TODO JSA keep json as short as possible by removing static datas (we can get it with material sent at page load)
        return $data;
    }
 
}
