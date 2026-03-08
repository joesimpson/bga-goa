<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Bga\Games\Goa\Helpers\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    
    //---------------------------------------------------------------------
    public function testNbContractsForKollam(): void
    {
        /*
        $utils = $this->getMockBuilder(Utils::class)
            ->onlyMethods([])
            ->getMock();

        $colony = COLONY_KOLLAM;
        $nb = $utils->getNbContractsForColony($colony);
        self::assertSame( 6, $nb, "Nb of contracts should be right");
        */

        self::assertSame( 6, Utils::getNbContractsForColony(COLONY_KOLLAM), );
    }

    public function testNbContractsForKochi(): void
    {
        /*
        $utils = $this->getMockBuilder(Utils::class)
            ->onlyMethods([])
            ->getMock();

        $colony = COLONY_KOCHI;
        $nb = $utils->getNbContractsForColony($colony);

        self::assertSame( 8, $nb, "Nb of contracts should be right");
        */
        self::assertSame( 8, Utils::getNbContractsForColony(COLONY_KOCHI), );
    }

    public function testNbContractsForMadras(): void
    {
        /*
        $utils = $this->getMockBuilder(Utils::class)
            ->onlyMethods([])
            ->getMock();

        $colony = COLONY_MADRAS;
        $nb = $utils->getNbContractsForColony($colony);

        self::assertSame( 10, $nb, "Nb of contracts should be right");
        */
        self::assertSame( 10, Utils::getNbContractsForColony(COLONY_MADRAS), );
    }
    
    public function testNbContractsForKoz(): void
    {
        /*
        $utils = $this->getMockBuilder(Utils::class)
            ->onlyMethods([])
            ->getMock();

        $colony = COLONY_KOZHIKODE;
        $nb = $utils->getNbContractsForColony($colony);

        self::assertSame( 12, $nb, "Nb of contracts should be right");
        */
        self::assertSame( 12, Utils::getNbContractsForColony(COLONY_KOZHIKODE), );
    }

    //---------------------------------------------------------------------
    
    public function testColonyName_Kochi(): void
    {
        self::assertSame( 'Kochi', Utils::getColonyName(COLONY_KOCHI), );
    }
    public function testColonyName_Kollam(): void
    {
        self::assertSame( 'Kollam', Utils::getColonyName(COLONY_KOLLAM), );
    }
    public function testColonyName_Madras(): void
    {
        self::assertSame( 'Madras', Utils::getColonyName(COLONY_MADRAS), );
    }
    public function testColonyName_Kozhikode(): void
    {
        self::assertSame( 'Kozhikode', Utils::getColonyName(COLONY_KOZHIKODE), );
    }
    
    //---------------------------------------------------------------------
    public function testActionType1(): void
    {
        self::assertSame( ACTION_TYPE_BUILD_SHIPS, Utils::getActionType(1), );
    }
    public function testActionType2(): void
    {
        self::assertSame( ACTION_TYPE_PRODUCE, Utils::getActionType(2), );
    }
    public function testActionType3(): void
    {
        self::assertSame( ACTION_TYPE_TAXES, Utils::getActionType(3), );
    }
    public function testActionType4(): void
    {
        self::assertSame( ACTION_TYPE_EXP, Utils::getActionType(4), );
    }
    public function testActionType5(): void
    {
        self::assertSame( ACTION_TYPE_FOUND, Utils::getActionType(5), );
    }

    //---------------------------------------------------------------------
    public function testComputePermutations(): void
    {
        $from = [1,];
        $expected = [ [1,],  ];
        self::assertSame( $expected,Utils::computePermutations($from), );
        
        $from = [1,2];
        $expected = [  [1,2], [2,1],  ];
        self::assertSame( $expected,Utils::computePermutations($from), );
        
        $from = [1,2,3,];
        $expected = [ [1,2,3], [1,3,2], [2,1,3], [2,3,1], [3,1,2], [3,2,1],  ];
        self::assertSame( $expected,Utils::computePermutations($from), );
        
        $from = [1,2,3,4];
        $expected = [ 
            [1,2,3,4], [1,2,4,3], [1,3,2,4], [1,3,4,2], [1,4,2,3], [1,4,3,2], 
            [2,1,3,4], [2,1,4,3], [2,3,1,4], [2,3,4,1], [2,4,1,3], [2,4,3,1], 
            [3,1,2,4], [3,1,4,2], [3,2,1,4], [3,2,4,1], [3,4,1,2], [3,4,2,1],
            [4,1,2,3], [4,1,3,2], [4,2,1,3], [4,2,3,1], [4,3,1,2], [4,3,2,1],
            ];
        self::assertSame( $expected,Utils::computePermutations($from), );

        //TEST max 13 tile spots of the same spice (7 from depots + 6 from outposts)
        /* 
        $from = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,];
        $expected = null;
        self::assertSame( $expected,Utils::computePermutations($from), );
        // -> PHP Fatal error: Allowed memory size of 134217728 bytes exhausted (tried to allocate 4096 bytes)
        */
    }

    public function testArrayProduct(): void
    {
        $possibleSets = [ [  [1,2], [2,1],  ], [  [12,20], [3,4],  ] ];
        $expected = [ 0 => [1,2,12,20,], 2 => [1,2,3,4], ];
        self::assertSame( $expected,Utils::array_cartesian($possibleSets), );

        $possibleSets = [ [[15],], [[50],[52]], [[1034,1032,],[1035,1032],[1035,1034]] ];
        $expected = [[15,50,1032, 1034 ], 
                    [15,52,1032, 1034 ],
                    [15,50,1032, 1035 ], 
                    [15,50,1034, 1035 ], 
                    [15,52,1032, 1035 ],
                    [15,52,1034, 1035 ],
                     ];
        self::assertSame( $expected,Utils::array_cartesian($possibleSets), );
    }

}