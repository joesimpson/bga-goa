<?php

declare(strict_types=1);

namespace Tests\Models;

use Bga\GameFramework\GameMock;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Managers\AutomaCards;
use Bga\Games\Goa\Managers\Tiles;
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

    private function prepareBoardTiles() : Collection
    {
        $boardTiles = new Collection();
            
        $addTile = function ($id,$type, $row, $col,) use (&$boardTiles) {
            $boardTiles[$id] = new MarketTile(['tile_id'=>$id, 'tile_row'=> $row, 'tile_col'=> $col,'row'=> $row, 'col'=> $col],Tiles::getMarketTilesTypes()[$type]);
        };
        // Real use case taken from DB
        // SELECT tile_id,type, tile_row, tile_col FROM `tiles` where tile_location='market' ORDER BY `tiles`.`tile_row` ASC, `tile_col` ASC;
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
        $addTile(13, 15, 4,4);
        $addTile(14, 2, 4,5);
        $addTile(15, 24, 5,1);
        $addTile(16, 4, 5,2);
        $addTile(17, 25, 5,3);
        $addTile(18, 5, 5,4);
        $addTile(19, 13, 5,5);

        return $boardTiles;
    }

    public function test_TilePriority_Card1(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[1]);
        $expectedSpot = ['row'=> 3, 'col'=>6];
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[2]);
        $expectedSpot = ['row'=> 6, 'col'=>3];//teal tile on market bottom
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[3]);
        $expectedSpot = ['row'=> 6, 'col'=>5];
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card4(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[4]);
        $expectedSpot = ['row'=> 5, 'col'=>0];
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);

        self::assertSame( $expectedSpot, $result, );
    }
    
    public function test_TilePriority_Card5(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[5]);
        $expectedSpot = ['row'=> 2, 'col'=>0];
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);

        self::assertSame( $expectedSpot, $result, );
    }
    
    
    public function test_TilePriority_Card6(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[6]);
        $expectedSpot = ['row'=> 0, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 2 without the scoring tile
        unset($boardTiles[1]);
        $expectedSpot = ['row'=> 3, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );

    }
    public function test_TilePriority_Card7(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $card = new AutomaAuctionCard([],AutomaCards::getAutomaAuctionCardsTypes()[7]);
        $expectedSpot = ['row'=> 6, 'col'=>2];
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
    }
    public function test_TilePriority_Card8(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $auctionType = TOKEN_TYPE_AUCTION_MARKER_1;
        $token = new AuctionMarker(['type'=>$auctionType],[]);
        $boardTiles = $this->prepareBoardTiles();
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $cardType = 8;
        $card = new AutomaAuctionCard(['type'=>$cardType],AutomaCards::getAutomaAuctionCardsTypes()[$cardType]);
        $expectedSpot = ['row'=> 0, 'col'=>4];
        
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );

        //Test 2 without the scoring tile
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[1]);
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $expectedSpot = ['row'=> 3, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 3 without the teal tile 5
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[5]);
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $expectedSpot = ['row'=> 4, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 4 without the teal tile 10, now we can reach Blue tile
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[10]);
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $expectedSpot = ['row'=> 4, 'col'=>1];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 5 without the blue tile 11
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[11]);
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $expectedSpot = ['row'=> 5, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 6 without the teal tile 15
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[15]);
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $expectedSpot = ['row'=> 6, 'col'=>3];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 7 without the teal tile 17
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[17]);
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $expectedSpot = ['row'=> 2, 'col'=>5];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
        
        //Test 8 without the last teal tile, we can use depot
        logForTests(__CLASS__.".".__FUNCTION__);
        unset($boardTiles[4]);
        $allAuctionableSpots = GridUtils::listPossibleSpotsForNextAuctionMarker($token, $boardTiles);
        $expectedSpot = ['row'=> 2, 'col'=>0];
        $result = $card->findAuctionSpotByPriority($auctionType,$boardTiles, $allAuctionableSpots);
        self::assertSame( $expectedSpot, $result, );
    }
}