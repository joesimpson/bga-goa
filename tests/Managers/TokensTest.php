<?php

declare(strict_types=1);

namespace Tests\Managers;

use Bga\GameFramework\GameMock;
use Bga\GameFramework\GamestateMachineMock;
use Bga\Games\Goa\Helpers\DB_Manager;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\Models\Player;
use PHPUnit\Framework\TestCase;

final class TokensTest extends TestCase
{
    
    private function createGameMock()
    {
        $mock = new GameMock();
        $mock->gamestate = new GamestateMachineMock();
        DB_Manager::startLog();
        return $mock;
    }
    
    //---------------------------------------------------------------------
    public function testUpgradableProgressMarkers(): void
    {

        $this->createGameMock();
        //$player = Players::getAll()->first();
        $player = Players::get(2);
        $progressTracks = Tokens::getUpgradableProgressMarkers($player);
        $expected = [
            [
                'track' => 4,
                'row' => 2,
                'withSpices' => true,
                'withShips' => true,
                'withCoins' => false,
                'costShips' => 2,
                'costSpices' => [
                    SPICE_PINK => ['n' => 1, 'from' => [23,]],
                    SPICE_GREEN => ['n' => 1, 'from' => [21, 22,]]
                ],
                'spicesSets' => [ [21,23], [22,23], ],
            ],
            [
                'track' => 5,
                'row' => 1,
                'withSpices' => true,
                'withShips' => true,
                'withCoins' => false,
                'costShips' => 1,
                'costSpices' => [
                    SPICE_GREEN => ['n' => 1, 'from' => [21, 22,]]
                ],
                'spicesSets' => [ [21], [22], ],
            ]
        ];

        self::assertSame( $expected, $progressTracks, );
    }

}