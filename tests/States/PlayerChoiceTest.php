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
use PHPUnit\Framework\TestCase;
use Tests\Utils\TestDatas;

final class PlayerChoiceTest extends TestCase
{
    /**
     * @before
     */
    public function setupTest(): void
    {
        TestDatas::$test_activePlayerId = 1;
    }
    
    private function createGameMock()
    {
        $mock = new GameMock();
        $roundStep = ROUND_STEP_PLAYER_ACTIONS;
        Globals::setRoundStep($roundStep);
        return $mock;
    }
    public function testPlayerChoice_ActionDiscard_Exception_With_Wrong_Version(): void
    {
         logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerChoice($game);
        $activePlayerId = 1;
        $gameVersion = 999;

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('!!!checkVersion');
        $state->actDiscard([1], $activePlayerId, [], $gameVersion, );
    }

    public function testPlayerChoice_ActionDiscard_NotPossible(): void
    {
         logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerChoice($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $cardId = 2;
        $args = [
            'possibleActions' => ['actPlayCard'],
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleDiscards' => [$cardId],
                ],
            ],
        ];

        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage("Invalid action actDiscard");
        $state->actDiscard([$cardId], $activePlayerId, $args, $gameVersion, );
    }
    
    public function testPlayerChoice_ActionDiscard_WrongArgs(): void
    {
         logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerChoice($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $cardId = 999;
        $args = [
            'possibleActions' => ['actDiscard'],
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleDiscards' => [1,2,3],
                ],
            ],
        ];

        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage("Invalid card $cardId");
        $state->actDiscard([$cardId], $activePlayerId, $args, $gameVersion, );
    }
    
    public function testPlayerChoice_ActionDiscard_Pass(): void
    {
         logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerChoice($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $cardIds = [2];

        $args = [
            'possibleActions' => ['actDiscard'],
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleDiscards' => $cardIds,
                ],
            ],
        ];
        $newState = $state->actDiscard($cardIds, $activePlayerId, $args, $gameVersion, );
        self::assertSame(PlayerChoice::class, $newState );

        $args = [
            'possibleActions' => ['actAnytimeDiscard'],
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleDiscards' => $cardIds,
                ],
            ],
        ];
        $newState = $state->actDiscard($cardIds, $activePlayerId, $args, $gameVersion, );
        self::assertSame(PlayerChoice::class, $newState );
        
        $cardIds = [2,3];
        $args = [
            'possibleActions' => ['actAnytimeDiscard'],
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleDiscards' => $cardIds,
                ],
            ],
        ];
        $newState = $state->actDiscard($cardIds, $activePlayerId, $args, $gameVersion, );
        self::assertSame(PlayerChoice::class, $newState );
    }
}