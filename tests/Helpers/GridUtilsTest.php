<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Models\AutomaAuctionDirectionType;
use Bga\Games\Goa\Models\AutomaAuctionStartType;
use PHPUnit\Framework\TestCase;

final class GridUtilsTest extends TestCase
{
    
    public function test_orderedSpots_TopLeft_ClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 0, 'col'=>1],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>5],

            ['row'=> 1, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 5, 'col'=>6],
            
            ['row'=> 6, 'col'=>5],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>1],
            
            ['row'=> 5, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 1, 'col'=>0],
        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_TopLeft_ClockWise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpots_TopLeft_CounterClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 1, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 5, 'col'=>0],

            ['row'=> 6, 'col'=>1],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>5],

            ['row'=> 5, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 1, 'col'=>6],
            
            ['row'=> 0, 'col'=>5],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>1],
            
        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_TopLeft_CounterClockWise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpots_TopRight_ClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [

            ['row'=> 1, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 5, 'col'=>6],
            
            ['row'=> 6, 'col'=>5],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>1],
            
            ['row'=> 5, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 1, 'col'=>0],
            
            ['row'=> 0, 'col'=>1],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>5],
        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_TopRight_Clockwise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpots_BottomRight_ClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            
            ['row'=> 6, 'col'=>5],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>1],
            
            ['row'=> 5, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 1, 'col'=>0],
            
            ['row'=> 0, 'col'=>1],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>5],
            
            ['row'=> 1, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 5, 'col'=>6],
        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_BottomRight_Clockwise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpots_BottomLeft_ClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            
            ['row'=> 5, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 1, 'col'=>0],
            
            ['row'=> 0, 'col'=>1],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>5],
            
            ['row'=> 1, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 5, 'col'=>6],
            
            ['row'=> 6, 'col'=>5],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>1],
        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_BottomLeft_Clockwise();
        self::assertSame( $expectedArray, $result, );
    }

    public function test_orderedSpots_TopRight_CounterClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 0, 'col'=>5],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>1],

            ['row'=> 1, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 5, 'col'=>0],

            ['row'=> 6, 'col'=>1],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>5],

            ['row'=> 5, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 1, 'col'=>6],
            
        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_TopRight_CounterClockWise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpots_BottomRight_CounterClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 5, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 1, 'col'=>6],

            ['row'=> 0, 'col'=>5],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>1],
            
            ['row'=> 1, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 5, 'col'=>0],

            ['row'=> 6, 'col'=>1],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>5],
        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_BottomRight_CounterClockWise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpots_BottomLeft_CounterClockWise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 6, 'col'=>1],
            ['row'=> 6, 'col'=>2],
            ['row'=> 6, 'col'=>3],
            ['row'=> 6, 'col'=>4],
            ['row'=> 6, 'col'=>5],

            ['row'=> 5, 'col'=>6],
            ['row'=> 4, 'col'=>6],
            ['row'=> 3, 'col'=>6],
            ['row'=> 2, 'col'=>6],
            ['row'=> 1, 'col'=>6],

            ['row'=> 0, 'col'=>5],
            ['row'=> 0, 'col'=>4],
            ['row'=> 0, 'col'=>3],
            ['row'=> 0, 'col'=>2],
            ['row'=> 0, 'col'=>1],
            
            ['row'=> 1, 'col'=>0],
            ['row'=> 2, 'col'=>0],
            ['row'=> 3, 'col'=>0],
            ['row'=> 4, 'col'=>0],
            ['row'=> 5, 'col'=>0],

        ];
        
        $result = GridUtils::listOrderedSpotsOnEdges_BottomLeft_CounterClockWise();
        self::assertSame( $expectedArray, $result, );
    }

    //--------------------------------------------------
    
    public function test_orderedSpotsMidddle_TopLeft_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
        ];
        $result = GridUtils::listOrderedSpotsMiddle_TopLeft_Clockwise();
        self::assertSame( $expectedArray, $result, );
    }

    public function test_orderedSpotsMidddle_TopRight_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
        ];
        $result = GridUtils::listOrderedSpotsMiddle_TopRight_Clockwise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpotsMidddle_BottomRight_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
        ];
        $result = GridUtils::listOrderedSpotsMiddle_BottomRight_Clockwise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpotsMidddle_BottomLeft_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
        ];
        $result = GridUtils::listOrderedSpotsMiddle_BottomLeft_Clockwise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpotsMidddle_TopLeft_CounterClockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ];
            $result = GridUtils::listOrderedSpotsMiddle_TopLeft_CounterClockwise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpotsMidddle_TopRight_CounterClockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ];
            $result = GridUtils::listOrderedSpotsMiddle_TopRight_CounterClockwise();
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpotsMidddle_BottomRight_CounterClockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ];
            $result = GridUtils::listOrderedSpotsMiddle_BottomRight_CounterClockwise();
        self::assertSame( $expectedArray, $result, );
    }
    public function test_orderedSpotsMidddle_BottomLeft_CounterClockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $expectedArray = [
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ];
            $result = GridUtils::listOrderedSpotsMiddle_BottomLeft_CounterClockwise();
        self::assertSame( $expectedArray, $result, );
    }

    
    public function test_orderedSpotsAround_TopLeft_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::TOP_LEFT;
        $dir = AutomaAuctionDirectionType::CLOCKWISE;
        $expectedArray = [
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
    public function test_orderedSpotsAround_TopLeft_CounterClockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::TOP_LEFT;
        $dir = AutomaAuctionDirectionType::COUNTER_CLOCKWISE;
        $expectedArray = [
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
    
    public function test_orderedSpotsAround_TopRight_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::TOP_RIGHT;
        $dir = AutomaAuctionDirectionType::CLOCKWISE;
        $expectedArray = [
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
    public function test_orderedSpotsAround_TopRight_CounterClockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::TOP_RIGHT;
        $dir = AutomaAuctionDirectionType::COUNTER_CLOCKWISE;
        $expectedArray = [
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
    public function test_orderedSpotsAround_BottomRight_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::BOTTOM_RIGHT;
        $dir = AutomaAuctionDirectionType::CLOCKWISE;
        $expectedArray = [
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
    public function test_orderedSpotsAround_BottomRight_Counterclockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::BOTTOM_RIGHT;
        $dir = AutomaAuctionDirectionType::COUNTER_CLOCKWISE;
        $expectedArray = [
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
    public function test_orderedSpotsAround_BottomLeft_Clockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::BOTTOM_LEFT;
        $dir = AutomaAuctionDirectionType::CLOCKWISE;
        $expectedArray = [
            ['row'=> 4, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ['row'=> 2, 'col'=>2],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 4, 'col'=>4],
            ['row'=> 4, 'col'=>3],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
    public function test_orderedSpotsAround_BottomLeft_Counterclockwise(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $ref = ['row'=> 3, 'col'=>3];
        $start = AutomaAuctionStartType::BOTTOM_LEFT;
        $dir = AutomaAuctionDirectionType::COUNTER_CLOCKWISE;
        $expectedArray = [
            ['row'=> 4, 'col'=>2],
            ['row'=> 4, 'col'=>3],
            ['row'=> 4, 'col'=>4],
            ['row'=> 3, 'col'=>4],
            ['row'=> 2, 'col'=>4],
            ['row'=> 2, 'col'=>3],
            ['row'=> 2, 'col'=>2],
            ['row'=> 3, 'col'=>2],
            ];
        $result = GridUtils::listOrderedSpotsAround($ref['row'], $ref['col'], $start,$dir);
        self::assertSame( $expectedArray, $result, );
    }
}