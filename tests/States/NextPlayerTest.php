<?php

declare(strict_types=1);

namespace Tests\States;

use Bga\GameFramework\GamestateMachineMock;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\DB_Manager;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Models\Player;
use Bga\Games\Goa\States\NextPlayer;
use PHPUnit\Framework\TestCase;

class GameStateInMem extends GameState {}

#[\AllowDynamicProperties]
    class GameMock extends Game {
        //readonly public \Bga\GameFramework\GamestateMachine $gamestate;

        public function __construct()
        {
            parent::__construct();
            //$this->gamestate = new GameState($this, ST_NEXT_PLAYER, StateType::GAME );
        }
    }
    

final class NextPlayerTest extends TestCase
{
    public function testEnteringState(): void
    {
        $roundStep = ROUND_STEP_PLACE_AUCTIONS;
        $game = $this->createGameMock();
        Globals::setRoundStep($roundStep);
        //$game->method('activeNextPlayer')->willReturn(1);
        //$this->attachPlayersManager($game, 0, []);

        $activePlayerId = 1;
        $state = new NextPlayer($game);

        self::assertSame('Bga\\Games\\Goa\\States\\PlayerTurn', $state->onEnteringState($activePlayerId));
    }

    private function createGameMock()
    {
        //$mockTable = $this->getMockBuilder(Table::class)
        //    ->disableOriginalConstructor()
        //    ->onlyMethods([
        //        'trace'
        //    ])
        //    ->getMock();
        //$mockTable->method('trace')->willReturn( '');

        /*
        $mock = $this->getMockBuilder(Game::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'activeNextPlayer',
                'getPlayerAfter',
                'giveExtraTime',
                'get'
            ])
            ->getMock();
        $mock->method('get')->willReturn($mock);
        return $mock;
        */
        //$mock = new Game();
        $mock = new GameMock();
        //$mock->gamestate = new GameStateInMem($machinestates);
        $mock->gamestate = new GamestateMachineMock();

        /*
        $mock = $this->getMockBuilder(GameMock::class)
            ->onlyMethods([
                'getObjectListFromDB',
            ])
            ->getMock();
        $mock->method('getObjectListFromDB')->willReturn(
            [1 => ['player_id'=> 1], 2 => ['player_id'=> 2], ]
            );
        return $mock;
        */
        return $mock;
    }
    
    private function attachPlayersManager(Game $game, int $freeSquares, array $discsByPlayer, array $possibleMovesByPlayer = []): void
    {
        $nextPlayerId = 1;
        $playersManager = $this->getMockBuilder(Players::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'getNextPlayer', 'changeActivePlayer'])
            ->getMock();

        $playersManager->method('changeActivePlayer')->willReturn('');
        $playersManager->method('getNextPlayer')
        //->willReturn( 
        //    new Player($nextPlayerId)
        //);
        ->willReturnCallback(
            static fn (int $playerId): Player => $playerId == 0 ? new Player(1) : new Player(0)
        );
        //$game->playersManager = $playersManager;
    }

    /*
    public function testDB_Manager(): void
    {
        $game = $this->createGameMock();
        
        $mock = $this->getMockBuilder(DB_Manager::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([$game])
            ->onlyMethods([
            ])
            ->getMock();

        $state = new DB_Manager($game);

        self::assertSame(true, $state->test());
    }
        */
}