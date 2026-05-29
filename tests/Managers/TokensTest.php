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
        return $mock;
    }
    
    //---------------------------------------------------------------------
    public function testUpgradableProgressMarkers_Player1(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $this->createGameMock();
        $player = Players::get(1);
        $progressTracks = Tokens::getUpgradableProgressMarkers($player);
        $expected = [
            [
                'track' => 1,
                'row' => 4,
                'withSpices' => true,
                'withShips' => true,
                'withCoins' => false,
                'costShips' => 4,
                'costSpices' => [
                    SPICE_WHITE => ['n' => 2, 'from' => [ 27,28, 32]],
                    SPICE_YELLOW => ['n' => 1, 'from' => [ 29,]],
                    SPICE_BLACK => ['n' => 1, 'from' => [30,31, ]],
                ],
                'spicesSets' => [ 
                    /*
                    // keys modified by Utils::array_cartesian()
                    0 => [27,28,29,30], 
                    1 => [27,29,30,32], 
                    3 => [28,29,30,32], 
                    6 => [27,28,29,31], 
                    7 => [27,29,31,32],  
                    9 => [28,29,31,32],
                    */
                ],
            ],
            [
                'track' => 2,
                'row' => 2,
                'withSpices' => true,
                'withShips' => true,
                'withCoins' => false,
                'costShips' => 2,
                'costSpices' => [
                    SPICE_YELLOW => ['n' => 1, 'from' => [ 29,]],
                    SPICE_BLACK => ['n' => 1, 'from' => [30,31, ]],
                ],
                //'spicesSets' => [ [29,30], [29,31], ],
                'spicesSets' => [],
            ],
            [
                'track' => 3,
                'row' => 1,
                'withSpices' => true,
                'withShips' => true,
                'withCoins' => false,
                'costShips' => 1,
                'costSpices' => [
                    SPICE_BLACK => ['n' => 1, 'from' => [30,31, ]],
                ],
                //'spicesSets' => [ [30,], [31], ],
                'spicesSets' => [],
            ],
            [
                'track' => 4,
                'row' => 2,
                'withSpices' => true,
                'withShips' => true,
                'withCoins' => false,
                'costShips' => 2,
                'costSpices' => [
                    SPICE_PINK => ['n' => 1, 'from' => [26,]],
                    SPICE_GREEN => ['n' => 1, 'from' => [24, 25,]]
                ],
                //'spicesSets' => [ [24,26], [25,26], ],
                'spicesSets' => [],
            ],
            [
                'track' => 5,
                'row' => 1,
                'withSpices' => true,
                'withShips' => true,
                'withCoins' => false,
                'costShips' => 1,
                'costSpices' => [
                    SPICE_GREEN => ['n' => 1, 'from' => [24, 25,]]
                ],
                //'spicesSets' => [ [24], [25], ],
                'spicesSets' => [],
            ]
        ];

        self::assertSame( $expected, $progressTracks, );
    }
    public function testUpgradableProgressMarkers_Player2(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
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
                //'spicesSets' => [ [21,23], [22,23], ],
                'spicesSets' => [],
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
                //'spicesSets' => [ [21], [22], ],
                'spicesSets' => [],
            ]
        ];

        self::assertSame( $expected, $progressTracks, );
    }
}