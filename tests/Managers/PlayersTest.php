<?php

declare(strict_types=1);

namespace Tests\Managers;

use Bga\GameFramework\Bga;
use Bga\GameFramework\GameMock;
use Bga\GameFramework\GamestateMachineMock;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Helpers\DB_Manager;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Models\AutomaPlayer;
use Bga\Games\Goa\Models\Player;
use PHPUnit\Framework\TestCase;

final class PlayersTest extends TestCase
{
    
    private function createGameMock()
    {
        $mock = new GameMock();
        $mock->gamestate = new GamestateMachineMock();
        $mock->bga = new Bga($mock);
        DB_Manager::startLog();
        Globals::fetch();
        return $mock;
    }

    public function test_getNextPlayerWithActions_automa(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $this->createGameMock();
        Globals::setAutomaColor("aaa");
        $playerId = 2;
        $nextPlayer = Players::getNextPlayerWithActions($playerId);
        $expected = AUTOMA_PLAYER_ID;
        self::assertSame( $expected, $nextPlayer->getId(), );
        self::assertTrue( $nextPlayer instanceof AutomaPlayer );
    }
    
    public function test_getNextPlayerWithActions_afterAutoma(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $this->createGameMock();
        Globals::setAutomaColor("aaa");
        $playerId = AUTOMA_PLAYER_ID;
        $nextPlayer = Players::getNextPlayerWithActions($playerId);
        $expected = 1;
        self::assertSame( $expected, $nextPlayer->getId(), );
        self::assertTrue( $nextPlayer instanceof Player );
    }
    
    public function test_getNextPlayerWithActions_after1(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $this->createGameMock();
        Globals::setAutomaColor("aaa");
        $playerId = 1;
        $nextPlayer = Players::getNextPlayerWithActions($playerId);
        $expected = 2;
        self::assertSame( $expected, $nextPlayer->getId(), );
        self::assertTrue( $nextPlayer instanceof Player );
    }
}