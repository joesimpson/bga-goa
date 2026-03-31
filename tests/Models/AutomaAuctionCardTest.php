<?php

declare(strict_types=1);

namespace Tests\Models;

use Bga\GameFramework\GameMock;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Managers\AutomaCards;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\Models\AuctionMarker;
use Bga\Games\Goa\Models\AutomaAuctionCard;
use Bga\Games\Goa\Models\MarketTile;
use PHPUnit\Framework\TestCase;

final class AutomaAuctionCardTest extends TestCase
{

    private function createGameMock()
    {
        $mock = new GameMock();
        return $mock;
    }

    
    private function changeTestTileType(Collection &$boardTiles, int $index, int $type)
    {
        $source = $boardTiles[$index];
        $boardTiles[$index] = new MarketTile([
            'tile_id'=>$source->getId(), 
            'tile_row'=> $source->getRow(), 
            'tile_col'=> $source->getCol(),
            ],Tiles::getMarketTilesTypes()[$type]);
    }
    private function prepareBoardTiles() : Collection
    {
        $boardTiles = new Collection();
            
        $addTile = function ($id,$type, $row, $col,) use (&$boardTiles) {
            $boardTiles[$id] = new MarketTile(['tile_id'=>$id, 'tile_row'=> $row, 'tile_col'=> $col,'row'=> $row, 'col'=> $col],Tiles::getMarketTilesTypes()[$type]);
        };
        // Real use case taken from DB
        // SELECT tile_id,type, tile_row, tile_col FROM `tiles` where tile_location='market' ORDER BY `tiles`.`tile_row` ASC, `tile_col` ASC;
        // Call debug function DebugTrait.debug_UnitTestTiles() to see it in Interface
        $addTile(1, 37, 1,4);
        $addTile(2, 3,  2,1);
        $addTile(3, 11, 2,3);
        $addTile(4, 28, 2,4);
        $addTile(5, 26, 3,1);
        $addTile(6, 13, 3,2);
        $addTile(7 , 14, 3,3);
        $addTile(8 , 7, 3,4);
        $addTile(9 , 11, 3,5);
        $addTile(10, 27, 4,1);
        $addTile(11, 8, 4,2);
        $addTile(12, 14, 4,3);
        //$addTile(13, 15, 4,4);
        //$addTile(14, 2, 4,5);
        $addTile(15, 24, 5,1);
        $addTile(16, 4, 5,2);
        $addTile(17, 25, 5,3);
        //$addTile(18, 5, 5,4);
        $addTile(19, 13, 5,5);

        return $boardTiles;
    }
    
    private function prepareBoardTilesNight() : Collection
    {
        $boardTiles = new Collection();
            
        $addTile = function ($id,$type, $row, $col,) use (&$boardTiles) {
            $boardTiles[$id] = new MarketTile([
                'location'=> TILE_LOCATION_BOARD_MARKET,
                'tile_start_location' => 'test',
                'type' => $type,
                'subtype' => TILE_SUBTTYPE_MARKET,
                'nbr' => 1,
                'tile_id'=>$id, 'tile_row'=> $row, 'tile_col'=> $col,'row'=> $row, 'col'=> $col],Tiles::getMarketTilesTypes()[$type]);
        };
        // Real use case taken from DB
        // SELECT tile_id,type, tile_row, tile_col FROM `tiles` where tile_location='market' ORDER BY `tiles`.`tile_row` ASC, `tile_col` ASC;
        // Call debug function DebugTrait.debug_UnitTestTiles() to see it in Interface
        $addTile(1,  27, 1, 1);
        $addTile(2,  27, 1, 2);
        $addTile(3,  20, 1, 3);
        $addTile(4,  23, 1, 4);
        $addTile(5,  19, 1, 5);
        $addTile(6,  33, 2, 1);
        $addTile(7 , 24, 2, 2);
        $addTile(8 , 31, 2, 3);
        $addTile(9 , 10, 2, 4);
        $addTile(10, 21, 2, 5);
        $addTile(11, 26, 3, 1);
        $addTile(12, 23, 3, 2);
        $addTile(13, 38, 3, 3);
        $addTile(14, 26, 3, 4);
        $addTile(15, 32, 3, 5);
        $addTile(16, 17, 4, 4);
        $addTile(17, 18, 4, 5);
        $addTile(18, 35, 5, 1);
        $addTile(19, 12, 5, 3);
        $addTile(20, 36, 5, 4);
        $addTile(21, 25, 5, 5);

        return $boardTiles;
    }
    
    private function prepareBoardTokens(int $nb = 0, bool $day = true) : Collection
    {
        $boardTokens = new Collection();
            
        $addToken = function ($id,$type, $row, $col,) use (&$boardTokens) {
            $boardTokens[$id] = new AuctionMarker([
                'type' => $type,
                'token_id'=> $id, 'token_row'=> $row, 'token_col'=> $col,'row'=> $row, 'col'=> $col],
                Tokens::getTokensTypes()[$type]);
        };
        if($day){
            if($nb>=1) $addToken(1, TOKEN_TYPE_AUCTION_MARKER_1, 5,5);//to test lonely tile
            if($nb>=2) $addToken(2, TOKEN_TYPE_AUCTION_MARKER_2, 3,3);
        }
        else {
            if($nb>=1) $addToken(1, TOKEN_TYPE_AUCTION_MARKER_1, 5,1);//to test lonely tile
            if($nb>=2) $addToken(2, TOKEN_TYPE_AUCTION_MARKER_2, 4,4);
        }
        return $boardTokens;
    }

    public function test_TilePriority_Card1(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[1]);
        $expectedSpot = ['row'=> 3, 'col'=>6];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[2]);
        $expectedSpot = ['row'=> 6, 'col'=>3];//teal tile on market bottom
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[3]);
        $expectedSpot = ['row'=> 6, 'col'=>5];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card4(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[4]);
        $expectedSpot = ['row'=> 5, 'col'=>0];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card5(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[5]);
        $expectedSpot = ['row'=> 2, 'col'=>0];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    
    public function test_TilePriority_Card6(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[6]);
        $expectedSpot = ['row'=> 0, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 2 without the scoring tile
        unset($boardTiles[1]);
        $expectedSpot = ['row'=> 3, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

    }
    public function test_TilePriority_Card7(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[7]);
        $expectedSpot = ['row'=> 6, 'col'=>2];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card8(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens();
        $cardType = 8;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 0, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //Test 2 without the scoring tile
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[1]);
        $expectedSpot = ['row'=> 3, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 3 without the teal tile 5
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[5]);
        $expectedSpot = ['row'=> 4, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 4 without the teal tile 10, now we can reach Blue tile
        //$expectedSpot = ['row'=> 4, 'col'=>1];
        //Change rule : only edges, we cannot reach Blue tile
        $expectedSpot = ['row'=> 5, 'col'=>0];
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[10]);
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 5 without the blue tile 11
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[11]);
        $expectedSpot = ['row'=> 5, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 6 without the teal tile 15
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[15]);
        $expectedSpot = ['row'=> 6, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 7 without the teal tile 17
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[17]);
        //$expectedSpot = ['row'=> 2, 'col'=>5];
        //Change rule : only edges, we cannot reach last Teal tile
        $expectedSpot = ['row'=> 2, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 8 without the last teal tile, we can use depot
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[4]);
        $expectedSpot = ['row'=> 2, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card9_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 9;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card9_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 9;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 2, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //TEST 2 if there is 1 possible spot around previous marker : 
        $boardTokens[1]->setRow(2); $boardTokens[1]->setCol(4);
        $boardTokens[2]->setRow(1); $boardTokens[2]->setCol(4);
        $expectedSpot = ['row'=> 2, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 3 if there is 0 possible spot around previous marker : look elsewhere ! Radius 2
        unset($boardTiles[3]);
        $expectedSpot = ['row'=> 3, 'col'=>5];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 4 
        unset($boardTiles[9]);
        $expectedSpot = ['row'=> 3, 'col'=>4];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //TEST 5 
        unset($boardTiles[8]);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 6
        unset($boardTiles[7]);
        $expectedSpot = ['row'=> 3, 'col'=>2];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 7 : radius 3
        unset($boardTiles[6]);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card10_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 10;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card10_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 10;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        
        $expectedSpot = ['row'=> 2, 'col'=>4];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 2
        unset($boardTiles[4]);
        $expectedSpot = ['row'=> 3, 'col'=>4];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 
        unset($boardTiles[8]);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //TEST 
        unset($boardTiles[12]);
        $expectedSpot = ['row'=> 3, 'col'=>2];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //TEST 
        unset($boardTiles[6]);
        $expectedSpot = ['row'=> 2, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 
        unset($boardTiles[3]);
        $expectedSpot = ['row'=> 4, 'col'=>2];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //TEST 
        unset($boardTiles[11]);
        $expectedSpot = ['row'=> 5, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card11_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 11;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card11_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 11;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        
        $expectedSpot = ['row'=> 4, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 
        unset($boardTiles[12]);
        $expectedSpot = ['row'=> 3, 'col'=>2];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card12_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 12;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card12_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 12;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 2, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card13_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 13;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>5];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card13_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 13;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 2, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card14_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 14;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);

        $expectedSpot = ['row'=> 5, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST 
        unset($boardTiles[17]);
        $expectedSpot = ['row'=> 3, 'col'=>5];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST with scoring tile
        $this->changeTestTileType($boardTiles, 12, MARKET_TYPE_DUTY);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST with Blue tile
        $this->changeTestTileType($boardTiles, 12, 8);
        $expectedSpot = ['row'=> 3, 'col'=>5];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card14_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 14;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 2, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card15_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 15;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        
        $expectedSpot = ['row'=> 3, 'col'=>5];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST with Blue tile instead -> continue to depot
        $this->changeTestTileType($boardTiles, 9, 8);
        $expectedSpot = ['row'=> 3, 'col'=>4];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST with Blue tile instead -> continue to depot
        $this->changeTestTileType($boardTiles, 8, 8);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST with Blue tile instead -> continue to depot
        $this->changeTestTileType($boardTiles,7, 8);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST with Score tile instead ->return
        $this->changeTestTileType($boardTiles,12, 40);
        $expectedSpot = ['row'=> 3, 'col'=>5];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST with Score tile instead 
        $this->changeTestTileType($boardTiles,9, 40);
        $this->changeTestTileType($boardTiles,8, 40);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //TEST
        $this->changeTestTileType($boardTiles,7, 40);
        $expectedSpot = ['row'=> 3, 'col'=>5];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card15_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 15;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card16_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(1);
        $cardType = 16;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);

        $expectedSpot = ['row'=> 5, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
        
        //TEST
        $this->changeTestTileType($boardTiles,8, 40);
        $expectedSpot = ['row'=> 3, 'col'=>4];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );

        //TEST
        $this->changeTestTileType($boardTiles,12, 40);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);
        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card16_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $boardTokens = $this->prepareBoardTokens(2);
        $cardType = 16;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 4, 'col'=>2];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }

    // ---------------- NIGHT -----------------------------
    public function test_TilePriority_Card21(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 21;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 6, 'col'=>1];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card22(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 22;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 2, 'col'=>0];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card23(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 23;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        //Change rule : only edges, we cannot reach scoring tile
        $expectedSpot = ['row'=> 2, 'col'=>0];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card24(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 24;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 4, 'col'=>3];
        //Change rule : only edges, we cannot reach scoring tile
        $expectedSpot = ['row'=> 4, 'col'=>6];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card25(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 25;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 6, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card26(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 26;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>0];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card27(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 27;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 0, 'col'=>1];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card28(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(0,false);
        $cardType = 28;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 1, 'col'=>6];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card29_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 29;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card29_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 29;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card30_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 30;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card30_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 30;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card31_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 31;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card31_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 31;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card32_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 32;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card32_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 32;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card33_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 33;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>1];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card33_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 33;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card34_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 34;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card34_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 34;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>5];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card35_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 35;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>1];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card35_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 35;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 3, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card36_Auction2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_2;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(1,false);
        $cardType = 36;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 5, 'col'=>3];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card36_Auction3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_3;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTilesNight();
        $boardTokens = $this->prepareBoardTokens(2,false);
        $cardType = 36;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 4, 'col'=>5];
        
        $result = $card->findAuctionSpotByPriority($token,$boardTiles, $boardTokens);

        self::assertSame( $expectedSpot, $result, );
    }
}