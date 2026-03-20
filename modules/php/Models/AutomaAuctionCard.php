<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Managers\MoneyCards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;

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

    /**
     * The Automata plays this card to place this auction token
     */
    public function play(AuctionMarker $token)
    {
        if(isset($token)) {
            $boardTiles = Tiles::getBoardTiles();

            $auctionType = $token->getType();
            $boardTokens = Tokens::getBoardTokens();
            $spot = $this->findAuctionSpotByPriority($token, $boardTiles, $boardTokens, );

            $row = $spot['row'];
            $col = $spot['col'];
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
    
    public function sortAuctionSpotsFromStart(int $auctionType,array $pAuctionableSpots, Collection $boardTokens,) :array
    {
        $auctionableSpots = [];

        $startSpot = $this->getStart();
        $dir = $this->getDirection();

        switch($auctionType){
            default:
            case TOKEN_TYPE_AUCTION_MARKER_1:
                $orderedSpots = GridUtils::listOrderedSpots_SnailAroundBoard($startSpot, $dir);
                break;
            case TOKEN_TYPE_AUCTION_MARKER_2:
            case TOKEN_TYPE_AUCTION_MARKER_3:
                //RULE : For Levels and , start adjacent to the position where the previous marker was placed.
                $previousMarker = Tokens::findPreviousAuctionMarker($auctionType, $boardTokens);
                $orderedSpots = GridUtils::listOrderedSpotsAround($previousMarker->getRow(), $previousMarker->getCol(), $startSpot, $dir);
                foreach($orderedSpots as $spot){
                    if(in_array($spot,$pAuctionableSpots)){
                        $auctionableSpots[] = $spot;
                    }
                }
                if(count($auctionableSpots) == 0 ){
                    //if no possible spot around, do default list
                    $orderedSpots = GridUtils::listOrderedSpots_SnailAroundBoard($startSpot, $dir);
                }
                break;
        }
        foreach($orderedSpots as $spot){
            if(in_array($spot,$pAuctionableSpots)){
                $auctionableSpots[] = $spot;
            }
        }

        //Game::get()->trace(__CLASS__.".".__FUNCTION__.": sorted auctionableSpots =".json_encode($auctionableSpots));
        return $auctionableSpots;
    }
    
    /**
     * @return array as ['row' => 2, 'col'=>4]
     */
    public function findAuctionSpotByPriority(AuctionMarker $token, Collection $boardTiles, Collection $boardTokens,) : array
    {
        
        $auctionType = $token->getType();
        $auctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles, $boardTokens,);
        $pAuctionableSpots = $this->sortAuctionSpotsFromStart($auctionType,$auctionableSpots, $boardTokens,);  

        $isEmptySpot = function ($row,$col) use ($boardTiles){
            return $boardTiles->filter(function ($tile) use($row,$col){
                return $row == $tile->getRow() && $col == $tile->getCol() ;
            })->count() == 0;
        };

        foreach($this->getTile_priority() as $marketPlayType){
            //RULE 1 LOOP: If no valid placement is possible for that tile type, proceed to the next tile type in the listed order and repeat until a valid placement is found.
            Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : auction card ".json_encode($this->getType())." search play type ".json_encode($marketPlayType));
            foreach($pAuctionableSpots as $auctionableSpot){
                $tmp_row = $auctionableSpot['row'];
                $tmp_col = $auctionableSpot['col'];
                $matchNearTileOnSpot = $boardTiles->filter(function ($tile) use($tmp_row,$tmp_col, $marketPlayType){
                    if( GridUtils::isAdjacentCoord($tmp_row ,$tmp_col, $tile->getRow(), $tile->getCol())){
                        if(is_array($marketPlayType)) {
                            $expectedPlayTypes = array_map(function ($v){return $v->value;},$marketPlayType);
                            return in_array($tile->getPlay_type(),$expectedPlayTypes);
                        }
                        return $marketPlayType->value == $tile->getPlay_type() ;
                    }
                    return false;
                })->first();
                $matchTileOnSpot = $boardTiles->filter(function ($tile) use($tmp_row,$tmp_col, $marketPlayType){
                    if($tmp_row == $tile->getRow() && $tmp_col == $tile->getCol()){
                        //Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : [{$tile->getRow()},{$tile->getCol()}] found play type ".json_encode($tile->getPlay_type()));
                        if(is_array($marketPlayType)) {
                            $expectedPlayTypes = array_map(function ($v){return $v->value;},$marketPlayType);
                            return in_array($tile->getPlay_type(),$expectedPlayTypes);
                        }
                        return $marketPlayType->value == $tile->getPlay_type() ;
                    }
                    return false;
                })->first();
                
                if($auctionType == TOKEN_TYPE_AUCTION_MARKER_1 && isset($matchNearTileOnSpot) && $isEmptySpot($tmp_row,$tmp_col)){
                    return $auctionableSpot;
                }
                if($auctionType != TOKEN_TYPE_AUCTION_MARKER_1 && isset($matchTileOnSpot)){
                    return $auctionableSpot;
                }
            }
        }
        if(count($pAuctionableSpots) ==0 ) return $auctionableSpots[0];//SHOULD NOT HAPPEN
        Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : NOT FOUND use first possible spot ".json_encode($pAuctionableSpots[0]));
        return $pAuctionableSpots[0];
    }

    
    /**
     * The Automata plays this card to BID on this auction token
     */
    public function bid(AuctionMarker $token)
    {
        $currentBid = Globals::getCurrentBid();

        $bid = $this->getAuction_money();
        //RULE : Auction money + draw 1 MONEY CARD
        $moneyCard = MoneyCards::drawAutoma($token->getType());
        if(isset($moneyCard)){
            $bid += $moneyCard->getValue();
        }
        if($bid >= $currentBid - 1 ){
            $nextBid = $bid;
            Globals::setCurrentBid($nextBid);
            Globals::setHighBidPlayer(AUTOMA_PLAYER_ID);
            Notifications::newBid(Players::automaPlayer(), $currentBid, $nextBid);
        }
        else {
            //IF automa bid < current BID - 1, the automa passes
            $automa = Players::automaPlayer();
            $automa->setPassed(true);
            Notifications::passBid($automa, $token);
        }

    }
 
}
