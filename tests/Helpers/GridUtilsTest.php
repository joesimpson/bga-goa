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


}