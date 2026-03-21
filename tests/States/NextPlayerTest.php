<?php

declare(strict_types=1);

namespace Tests\States;

use Bga\GameFramework\Bga;
use Bga\GameFramework\GameMock;
use Bga\GameFramework\GamestateMachineMock;
use Bga\GameFramework\Notify;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\States\EndPlayerTurn;
use Bga\Games\Goa\States\NextPlayer;
use PHPUnit\Framework\TestCase;

final class NextPlayerTest extends TestCase
{
    public function testEnteringState_step1(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $roundStep = ROUND_STEP_PLACE_AUCTIONS;
        $game = $this->createGameMock();
        Globals::setRoundStep($roundStep);

        $activePlayerId = 1;
        $state = new NextPlayer($game);

        self::assertSame('Bga\\Games\\Goa\\States\\PlayerTurn', $state->onEnteringState($activePlayerId));
    }
    
    public function testEnteringState_step2(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $roundStep = ROUND_STEP_TILE_AUCTIONS;
        $game = $this->createGameMock();
        Globals::setRoundStep($roundStep);

        $activePlayerId = 1;
        $state = new NextPlayer($game);

        self::assertSame('Bga\\Games\\Goa\\States\\PlayerTurn', $state->onEnteringState($activePlayerId));
    }

    public function testEnteringState_step3(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $roundStep = ROUND_STEP_PLAYER_ACTIONS;
        $game = $this->createGameMock();
        Globals::setRoundStep($roundStep);

        $activePlayerId = 1;
        $state = new NextPlayer($game);

        self::assertSame('Bga\\Games\\Goa\\States\\PlayerTurn', $state->onEnteringState($activePlayerId));
    }
    
    public function testEnteringState_step2_Automa(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $roundStep = ROUND_STEP_TILE_AUCTIONS;
        $game = $this->createGameMock();
        Globals::setRoundStep($roundStep);
        Globals::setAutomaColor("123");

        $activePlayerId = 2;
        $state = new NextPlayer($game);

        self::assertSame(EndPlayerTurn::class, $state->onEnteringState($activePlayerId));
    }

    private function createGameMock()
    {
        $mock = new GameMock();
        return $mock;
    }
}