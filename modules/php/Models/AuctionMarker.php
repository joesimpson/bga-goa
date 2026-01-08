<?php

namespace Bga\Games\Goa\Models;

class AuctionMarker extends Token
{  
    
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TOKEN_SUBTTYPE_AUCTION_MARKER;
        $data['auctionOrder'] = $this->getAuctionOrder();
        return $data;
    }

    public function getAuctionOrder() : int{
        switch($this->getType()){
            case TOKEN_TYPE_AUCTION_MARKER_1: return 1;
            case TOKEN_TYPE_AUCTION_MARKER_2: return 2;
            case TOKEN_TYPE_AUCTION_MARKER_3: return 3;
            case TOKEN_TYPE_AUCTION_MARKER_4: return 4;
            case TOKEN_TYPE_AUCTION_MARKER_5: return 5;
            default:
                return 1;
        }
    }
   
}
