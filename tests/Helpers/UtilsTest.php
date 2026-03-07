<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Bga\Games\Goa\Helpers\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    
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
}