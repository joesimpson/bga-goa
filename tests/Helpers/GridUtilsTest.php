<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Bga\Games\Goa\Helpers\GridUtils;
use PHPUnit\Framework\TestCase;

final class GridUtilsTest extends TestCase
{
    
    public function test_orderedSpots_TopLeft_ClockWise(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
        logForTests(__CLASS__.".".__FUNCTION__);
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
}