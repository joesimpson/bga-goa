<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;

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
        // keep json as short as possible by removing static datas (we can get it with material sent at page load)
        unset($data['token_type']);
        unset($data['tile_priority']);
        unset($data['start']);
        unset($data['direction']);
        unset($data['auction_money']);
        return $data;
    }

    public function play(AuctionMarker $token)
    {
        if(isset($token)) {
            $pAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token);

            $spot = $this->findAuctionSpotByPriority($pAuctionableSpots);

            $row = $spot['row'];
            $col = $spot['col'];
            $auctionType = $token->getType();
            /*
            switch($this->getLocation()){
                case CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value:
                case CardLocation::AUTOMA_AUCTION_NIGHT_1_DECK->value:
                case CardLocation::AUTOMA_AUCTION_DAY_1_BOARD->value:
                case CardLocation::AUTOMA_AUCTION_NIGHT_1_BOARD->value:
                    $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
                    break;
                case CardLocation::AUTOMA_AUCTION_DAY_2_DECK->value:
                case CardLocation::AUTOMA_AUCTION_NIGHT_2_DECK->value:
                case CardLocation::AUTOMA_AUCTION_DAY_2_BOARD->value:
                case CardLocation::AUTOMA_AUCTION_NIGHT_2_BOARD->value:
                    $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
                    break;
                case CardLocation::AUTOMA_AUCTION_DAY_3_DECK->value:
                case CardLocation::AUTOMA_AUCTION_NIGHT_3_DECK->value:
                case CardLocation::AUTOMA_AUCTION_DAY_3_BOARD->value:
                case CardLocation::AUTOMA_AUCTION_NIGHT_3_BOARD->value:
                    $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
                    break;
                default: 
                    $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
                    break;
            }
            */
            
            GridUtils::placeAuction(Players::automaPlayer(),  $row, $col, $auctionType);

        }

    }
    
    public function findAuctionSpotByPriority(array $pAuctionableSpots)
    {
        $boardTiles = Tiles::getBoardTiles();
        foreach($this->getTile_priority() as $marketPlayType){
            //RULE 1 LOOP: If no valid placement is possible for that tile type, proceed to the next tile type in the listed order and repeat until a valid placement is found.
            Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : auction card ".json_encode($this)." search play type ".json_encode($marketPlayType));
            //TODO JSA RULE 2: Starting from the location indicated on the card, follow the specified direction (clockwise or counterclockwise) until you reach a tile of the targeted type.

            foreach($pAuctionableSpots as $auctionableSpot){
                $tmp_row = $auctionableSpot['row'];
                $tmp_col = $auctionableSpot['col'];
                $matchTileOnSpot = $boardTiles->filter(function ($tile) use($tmp_row,$tmp_col, $marketPlayType){
                    if($tmp_row == $tile->getRow() && $tmp_col == $tile->getCol()){
                        Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : [{$tile->getRow()},{$tile->getCol()}] found play type ".json_encode($tile->getPlay_type()));
                        if(is_array($marketPlayType)) {
                            $expectedPlayTypes = array_map(function ($v){return $v->value;},$marketPlayType);
                            return in_array($tile->getPlay_type(),$expectedPlayTypes);
                        }
                        return $marketPlayType->value == $tile->getPlay_type() ;
                    }
                    return false;
                })->first();
                if(isset($matchTileOnSpot)){
                    return $auctionableSpot;
                }
            }
        }
        
        return $pAuctionableSpots[0];
    }

 
}
