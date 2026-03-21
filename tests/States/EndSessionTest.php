<?php

declare(strict_types=1);

namespace Tests\States;

use Bga\GameFramework\Bga;
use Bga\GameFramework\GameMock;
use Bga\GameFramework\GamestateMachineMock;
use Bga\GameFramework\Notify;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Helpers\DB_Manager;
use Bga\Games\Goa\States\EndSession;
use PHPUnit\Framework\TestCase;

final class EndSessionTest extends TestCase
{
    
    private function createGameMock()
    {
        $mock = new GameMock();
        DB_Manager::startLog();
        Globals::fetch();
        return $mock;
    }
    
    public function testScoring(): void
    {
        $session = 1;
        $player1 = 1;
        $player2 = 2;
        $expected = [ 
            $session => [
                $player1 => [
                    SCORING_PROGRESS     => 8,
                    SCORING_OUTPOSTS     => 0,
                    SCORING_EXPLORATION  => [
                        /*
                        CARD_SYMBOL_ELEPHANT => 0,
                        CARD_SYMBOL_TIGER    => 0,
                        CARD_SYMBOL_URN      => 0,
                        CARD_SYMBOL_PALMTREE => 0,
                        CARD_SYMBOL_SHELL    => 0,
                        CARD_SYMBOL_STATUE   => 0,
                        */
                    ],
                    SCORING_COINS        => 0,
                    SCORING_TILES        => 0,
                ],
                $player2 => [
                    SCORING_PROGRESS     => 1,
                    SCORING_OUTPOSTS     => 0,
                    SCORING_EXPLORATION  => [
                        /*
                        CARD_SYMBOL_ELEPHANT => 0,
                        CARD_SYMBOL_TIGER    => 0,
                        CARD_SYMBOL_URN      => 0,
                        CARD_SYMBOL_PALMTREE => 0,
                        CARD_SYMBOL_SHELL    => 0,
                        CARD_SYMBOL_STATUE   => 0,
                        */
                    ],
                    SCORING_COINS        => 3,
                    SCORING_TILES        => 0,
                ],
            ]
        ];
        
        $this-> createGameMock();
        $scoring = EndSession::computeScoring($session);

        self::assertSame( $expected, $scoring, );
    }


}