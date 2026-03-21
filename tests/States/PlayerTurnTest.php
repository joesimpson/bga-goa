<?php

declare(strict_types=1);

namespace Tests\States;

use Bga\GameFramework\Bga;
use Bga\GameFramework\GameMock;
use Bga\GameFramework\GamestateMachineMock;
use Bga\GameFramework\Notify;
use Bga\GameFramework\Table;
use Bga\GameFramework\UserException;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\States\PlayerChoice;
use Bga\Games\Goa\States\PlayerTurn;
use PHPUnit\Framework\TestCase;

final class PlayerTurnTest extends TestCase
{
    /**
     * @before
     */
    public function setupTest(): void
    {
        Table::$test_activePlayerId = 1;
    }
    
    private function createGameMock()
    {
        $mock = new GameMock();
        $roundStep = ROUND_STEP_PLAYER_ACTIONS;
        Globals::setRoundStep($roundStep);
        return $mock;
    }
    public function testPlayerTurn_ActionProgress_Exception_With_Wrong_Version(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999;

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('!!!checkVersion');
        $state->actProgress(1,  [], null, null, $activePlayerId, [],
            $gameVersion
        );
    }
    
    public function testPlayerTurn_ActionProgress_Exception_Wrong_Track(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $tokens_ids = [23];
        $track = 12;

        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage('Invalid track to progress on (12)');
        $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
    }
    
    public function testPlayerTurn_ActionProgress_Track1_Pass(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;

        $tokens_ids = [27,28,29,30];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [27,29,30,32];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [28,29,30,32];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );

        $tokens_ids = [27,28,29,31];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [27,29,31,32];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [28,29,31,32];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
    }

    public function testPlayerTurn_ActionProgress_Track2_Pass(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 2;

        $tokens_ids = [29,30];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [29,31];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
    }

    public function testPlayerTurn_ActionProgress_Track3_Pass(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 3;

        $tokens_ids = [30];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [31];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
    }
    
    public function testPlayerTurn_ActionProgress_Track4_Exception_With_Wrong_Spices(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $tokens_ids = [21,22];
        $track = 4;

        //$this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage('These tokens are not a valid set');
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
    }

    public function testPlayerTurn_ActionProgress_Track4_Pass(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 4;

        $tokens_ids = [21, 23];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [22, 23];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
    }

    public function testPlayerTurn_ActionProgress_Track5_Exception_With_Wrong_Spices(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $tokens_ids = [23];
        $track = 5;

        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage('These tokens are not a valid set');
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
    }
    public function testPlayerTurn_ActionProgress_Track5_Exception_TooManySpices(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 5;
        $tokens_ids = [21,22,23];
        
        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage("You must select 1 tokens");
        $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
    }
    
    public function testPlayerTurn_ActionProgress_Track5_Exception_NotEnoughSpices(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 5;
        $tokens_ids = [];
        
        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage("You must select 1 tokens");
        $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
    }

    public function testPlayerTurn_ActionProgress_Track5_Pass(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 5;

        $tokens_ids = [21];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
        
        $tokens_ids = [22];
        $newState = $state->actProgress($track, 
            $tokens_ids, 
            null, 
            null, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerTurn::class, $newState );
    }

    public function testPlayerTurn_ActionDiscard_Exception_With_Wrong_Version(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999;

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('!!!checkVersion');
        $state->actDiscard([1], $gameVersion, $activePlayerId, [], );
    }

    public function testPlayerTurn_ActionDiscard_NotPossible(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $cardId = 2;
        $args = [
            'possibleActions' => ['actPlayCard'],
            'possibleDiscards' => [$cardId],
        ];

        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage("Invalid action actDiscard");
        $state->actDiscard([$cardId], $gameVersion, $activePlayerId, $args, );
    }
    
    public function testPlayerTurn_ActionDiscard_WrongArgs(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $cardId = 999;
        $args = [
            'possibleActions' => ['actDiscard'],
            'possibleDiscards' => [1,2,3],
        ];

        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage("Invalid card $cardId");
        $state->actDiscard([$cardId], $gameVersion, $activePlayerId, $args, );
    }
    
    public function testPlayerTurn_ActionDiscard_Pass(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $cardIds = [2];
        
        $args = [
            'possibleActions' => ['actDiscard'],
            'possibleDiscards' => $cardIds,
        ];
        $newState = $state->actDiscard($cardIds, $gameVersion, $activePlayerId, $args, );
        self::assertSame(PlayerTurn::class, $newState );
        
        $args = [
            'possibleActions' => ['actAnytimeDiscard'],
            'possibleDiscards' => $cardIds,
        ];
        $newState = $state->actDiscard($cardIds, $gameVersion, $activePlayerId, $args, );
        self::assertSame(PlayerTurn::class, $newState );
    }

    public function testPlayerTurn_ActionFound_Pass(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        Globals::setOptionPlanned(OPTION_PLANNED_OFF);
        $activePlayerId = 1;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $outpostId = 1;
        $cardid = null;

        $args['playerBaseContracts'] = 6;
        $args['outpostsToFound'] = [$outpostId];
        $newState = $state->actFoundOutpost(
            $outpostId, 
            $cardid, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerChoice::class, $newState );
    }
    public function testPlayerTurn_ActionFound_PlannedExpansion(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        Globals::setOptionPlanned(OPTION_PLANNED_ON);
        $activePlayerId = 1;
        Table::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $outpostId = 1;
        $cardid = null;

        $args['playerBaseContracts'] = 10;
        $args['outpostsToFound'] = [$outpostId];
        $newState = $state->actFoundOutpost(
            $outpostId, 
            $cardid, 
            $activePlayerId, 
            $args,
            $gameVersion
        );
        self::assertSame(PlayerChoice::class, $newState );
    }
    public function testPlayerTurn_Zombie_Random(): void
    {
        logForTests(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        Table::$test_activePlayerId = $activePlayerId;
        $args = $state->getArgs();

        //Zombie will call a random choice, so let's test IT multiple times
        $nbTests = 0;
        while($nbTests < 10){
            $nbTests++;

            $newState = $state->zombie($activePlayerId,  $args);
            self::assertSame(PlayerTurn::class, $newState );
        }
        
    }
}