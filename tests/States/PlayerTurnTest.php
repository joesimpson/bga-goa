<?php

declare(strict_types=1);

namespace Tests\States;

use Bga\GameFramework\GameMock;
use Bga\GameFramework\Table;
use Bga\GameFramework\UserException;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\States\ConfirmTurn;
use Bga\Games\Goa\States\PlayerChoice;
use Bga\Games\Goa\States\PlayerTurn;
use PHPUnit\Framework\TestCase;
use Tests\Utils\TestDatas;

final class PlayerTurnTest extends TestCase
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
        Globals::setChoices(0);//for undo module to be tested separately
        Globals::setAutomaActive(false);
        return $mock;
    }
    // ---------------------------------------------------------------------------------------------
    
    public function testPlayerTurn_getArgs_tileAuctions_OwnToken(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $roundStep = ROUND_STEP_TILE_AUCTIONS;
        Globals::setRoundStep($roundStep);
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 3;
        $state = new PlayerTurn($game);
        $auctionTokenData = [
            'id' => 2,
            'state' => 0,
            'location' => 'board',
            'pId' => TestDatas::$test_activePlayerId,
            'type' => 11,
            'row' => 2,
            'col' => 1,
            'subtype' => 2,
            'auctionOrder' => 1,
        ];

        //TEST 1 : auctioneer can overbid or Pass
        Globals::setCurrentBid(1);
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 1,
            'minBid' => -1,
            'bidRebate' => 1,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid', 'actPassBid'],
                    'currentMoney' => 3,
                    'maxBid' => -1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );

        //TEST 2 : Previous bid == player money -> they can have the rebate or Pass
        Globals::setCurrentBid(3);
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 3,
            'minBid' => -1,
            'bidRebate' => 1,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid', 'actPassBid'],
                    'currentMoney' => 3,
                    'maxBid' => -1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
        //TEST 3 : Previous bid 0 -> player cannot pass
        Globals::setCurrentBid(0);
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ ],
            'token' => $auctionTokenData,
            'bid' => 0,
            'minBid' => 0,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid',],
                    'currentMoney' => 3,
                    'maxBid' => 0,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
        //TEST 4 : Previous bid == player money +1 -> they can have the rebate or Pass
        Globals::setCurrentBid(4);
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [  'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 4,
            'minBid' => -1,
            'bidRebate' => 1,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid', 'actPassBid'],
                    'currentMoney' => 3,
                    'maxBid' => -1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
        //TEST 5 : Previous bid == player money +2 -> they cannot have the rebate but can Pass
        Globals::setCurrentBid(5);
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 5,
            'minBid' => -1,
            'bidRebate' => 1,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actPassBid'],
                    'currentMoney' => 3,
                    'maxBid' => -1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
    }
    
    public function testPlayerTurn_getArgs_tileAuctions_CanBid(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $roundStep = ROUND_STEP_TILE_AUCTIONS;
        Globals::setRoundStep($roundStep);
        TestDatas::$test_activePlayerId = 2;
        $state = new PlayerTurn($game);
        $auctionTokenData = [
            'id' => 2,
            'state' => 0,
            'location' => 'board',
            'pId' => 1,
            'type' => 11,
            'row' => 2,
            'col' => 1,
            'subtype' => 2,
            'auctionOrder' => 1,
        ];
        
        //TEST 0 : Money>0 &&  current Bid 0
        Globals::setCurrentBid(0);
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 3;
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 0,
            'minBid' => 1,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid', 'actPassBid'],
                    'currentMoney' => 3,
                    'maxBid' => 3,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
        //TEST 1 : Money = current Bid + 1
        Globals::setCurrentBid(2);
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 3;
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 2,
            'minBid' => 1,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid', 'actPassBid'],
                    'currentMoney' => 3,
                    'maxBid' => 1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
        //TEST 2 : Money = current Bid + 2
        Globals::setCurrentBid(2);
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 4;
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [  'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 2,
            'minBid' => 1,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid', 'actPassBid'],
                    'currentMoney' => 4,
                    'maxBid' => 2,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
        //TEST 3 : Money = current Bid + 3
        Globals::setCurrentBid(2);
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 5;
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [  'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 2,
            'minBid' => 1,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid', 'actPassBid'],
                    'currentMoney' => 5,
                    'maxBid' => 3,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );

    }
    
    public function testPlayerTurn_getArgs_tileAuctions_NotEnoughMoney(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $roundStep = ROUND_STEP_TILE_AUCTIONS;
        Globals::setRoundStep($roundStep);
        TestDatas::$test_activePlayerId = 2;
        $state = new PlayerTurn($game);
        $auctionTokenData = [
            'id' => 2,
            'state' => 0,
            'location' => 'board',
            'pId' => 1,
            'type' => 11,
            'row' => 2,
            'col' => 1,
            'subtype' => 2,
            'auctionOrder' => 1,
        ];
        
        //TEST 1 : Money = current Bid - 1
        Globals::setCurrentBid(2);
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 1;
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 2,
            'minBid' => 1,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [  'actPassBid'],
                    'currentMoney' => 1,
                    'maxBid' => 1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );
        
        //TEST 2 : Money = current Bid - 2
        Globals::setCurrentBid(2);
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 0;
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 2,
            'minBid' => 1,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [  'actPassBid'],
                    'currentMoney' => 0,
                    'maxBid' => 1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];
        self::assertSame($expectedArgs, $state->getArgs() );

    }
    /**
     * Test what is possible when we have N money and the current Bid is N => we CANNOT OVERBID ! we MUST pass
     */
    public function testPlayerTurn_getArgs_tileAuctions_WithMoneyEqualsBid(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $roundStep = ROUND_STEP_TILE_AUCTIONS;
        Globals::setRoundStep($roundStep);
        Globals::setCurrentBid(3);
        TestDatas::$test_activePlayerId = 2;
        TestDatas::$test_players[TestDatas::$test_activePlayerId]['money'] = 3;
        $state = new PlayerTurn($game);
        $auctionTokenData = [
            'id' => 2,
            'state' => 0,
            'location' => 'board',
            'pId' => 1,
            'type' => 11,
            'row' => 2,
            'col' => 1,
            'subtype' => 2,
            'auctionOrder' => 1,
        ];
        $expectedArgs = [
            'roundStep' => $roundStep,
            'possibleActions' => [ 'actPassBid'],
            'token' => $auctionTokenData,
            'bid' => 3,
            'minBid' => 1,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [  'actPassBid'],
                    'currentMoney' => 3,
                    'maxBid' => 1,
                ],
            ],
            '_merge_private' => true,
            'undo' => [ 'previousSteps' => [], 'previousChoices' => 0, ],
        ];

        self::assertSame($expectedArgs, $state->getArgs() );
    }
    // ---------------------------------------------------------------------------------------------
    
    public function testPlayerTurn_actBid_0_KO_whenNotPossible(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;
        $args = [
            'roundStep' => ROUND_STEP_TILE_AUCTIONS,
            'possibleActions' => [],
            'bid' => 3,
            'minBid' => 0,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'currentMoney' => 3,
                    'maxBid' => 0,
                ],
            ],
            '_merge_private' => true,
        ];

        //Invalid action actBid
        $amount = 0;
        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage('Invalid action actBid');
        $newState = $state->actBid( $amount, $activePlayerId, $args, $gameVersion );

    }
    public function testPlayerTurn_actBid_0_PASS_whenPossible(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;
        $args = [
            'roundStep' => ROUND_STEP_TILE_AUCTIONS,
            'possibleActions' => [],
            'bid' => 3,
            'minBid' => 0,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid',],
                    'currentMoney' => 3,
                    'maxBid' => 0,
                ],
            ],
            '_merge_private' => true,
        ];
        $amount = 0;

        $newState = $state->actBid( $amount, $activePlayerId, $args, $gameVersion );
        self::assertSame(ConfirmTurn::class, $newState );
    }
    
    public function testPlayerTurn_actBid_Min_KO(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;
        $args = [
            'bid' => 3,
            'minBid' => 0,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid',],
                    'currentMoney' => 3,
                    'maxBid' => 10,
                ],
            ],
            '_merge_private' => true,
        ];

        $amount = -1;
        
        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage('Invalid bid -1, min is 0');
        $newState = $state->actBid( $amount, $activePlayerId, $args, $gameVersion );
    }
    
    public function testPlayerTurn_actBid_Max_KO(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;
        $args = [
            'bid' => 3,
            'minBid' => 0,
            'bidRebate' => 0,
            '_private' => [//will appear as merged 
                TestDatas::$test_activePlayerId => [
                    'possibleActions' => [ 'actBid',],
                    'currentMoney' => 3,
                    'maxBid' => 12,
                ],
            ],
            '_merge_private' => true,
        ];

        $amount = 13;
        
        $this->expectException(UnexpectedException::class);
        $this->expectExceptionMessage('Invalid bid 13, max is 12');
        $newState = $state->actBid( $amount, $activePlayerId, $args, $gameVersion );
    }

    public function testPlayerTurn_actPassBid(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        $gameVersion = 999999;

        $newState = $state->actPassBid( $gameVersion, $activePlayerId,);
        self::assertSame(ConfirmTurn::class, $newState );
    }

    // ---------------------------------------------------------------------------------------------

    public function testPlayerTurn_ActionProgress_Exception_With_Wrong_Version(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
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
        logTestRun(__CLASS__.".".__FUNCTION__);
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
    
    public function testPlayerTurn_ActionProgress_Track1_Pass_SpiceSet1(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
    }
    
    public function testPlayerTurn_ActionProgress_Track1_Pass_SpiceSet2(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;
        
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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
    }
    
    public function testPlayerTurn_ActionProgress_Track1_Pass_SpiceSet3(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;

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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
    }
    
    public function testPlayerTurn_ActionProgress_Track1_Pass_SpiceSet4(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;
        
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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
    }
    
    public function testPlayerTurn_ActionProgress_Track1_Pass_SpiceSet5(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;
        
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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
    }
    
    public function testPlayerTurn_ActionProgress_Track1_Pass_SpiceSet6(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;

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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
    }
    
    public function testPlayerTurn_ActionProgress_Track1_BonusForFirstOnRow4(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;
        TestDatas::$tokens[11]['token_row'] = 3;

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
        self::assertSame(4,TestDatas::$tokens[11]['token_row']);
        //manual check in logs and coverage we have notif "progressBonusFirst", then check cards
        self::assertSame(2, Cards::getExplorationHand(1)->count());
    }
    
    public function testPlayerTurn_ActionProgress_Track1_NoBonusForSecondOnRow4_SameColumn(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;
        TestDatas::$tokens[11]['token_row'] = 3;
        TestDatas::$tokens[16]['token_row'] = 5;

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
        self::assertSame(4,TestDatas::$tokens[11]['token_row']);
        //manual check in logs and coverage we DON T have notif "progressBonusFirst", then check cards
        self::assertSame(1, Cards::getExplorationHand(1)->count());
    }
    
    public function testPlayerTurn_ActionProgress_Track1_NoBonusForSecondOnRow4_OtherColumn(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        $gameVersion = 999999;
        $args = $state->getArgs();
        $track = 1;
        TestDatas::$tokens[11]['token_row'] = 3;
        TestDatas::$tokens[17]['token_row'] = 5;

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
        self::assertSame(4,TestDatas::$tokens[11]['token_row']);
        //manual check in logs and coverage we have notif "progressBonusFirst", then check cards
        self::assertSame(2, Cards::getExplorationHand(1)->count());
    }

    public function testPlayerTurn_ActionProgress_Track1_BonusForFirstOnRow5(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
        //manual check in logs and coverage we have notif "progressBonusFirst", then check cards
        self::assertSame(2, Cards::getExplorationHand(1)->count());
    }
    
    public function testPlayerTurn_ActionProgress_Track1_NoBonusForSecondOnRow5_SameColumn(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        TestDatas::$tokens[16]['token_row'] = 5;
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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
        //manual check in logs and coverage we DON T have notif "progressBonusFirst", then check cards
        self::assertSame(1, Cards::getExplorationHand(1)->count());
    }
    
    public function testPlayerTurn_ActionProgress_Track1_NoBonusForSecondOnRow5_OtherColumn(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
        TestDatas::$tokens[17]['token_row'] = 5;
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
        self::assertSame(5,TestDatas::$tokens[11]['token_row']);
        //manual check in logs and coverage we have notif "progressBonusFirst", then check cards
        self::assertSame(2, Cards::getExplorationHand(1)->count());
    }

    public function testPlayerTurn_ActionProgress_Track2_Pass(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        self::assertSame(3,TestDatas::$tokens[12]['token_row']);
        
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
        self::assertSame(4,TestDatas::$tokens[12]['token_row']);
    }

    public function testPlayerTurn_ActionProgress_Track3_Pass(): void
    {
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        self::assertSame(2,TestDatas::$tokens[13]['token_row']);
        
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        self::assertSame(3,TestDatas::$tokens[19]['token_row']);
        
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        logTestRun(__CLASS__.".".__FUNCTION__);
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
        logTestRun(__CLASS__.".".__FUNCTION__);
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        self::assertSame(2,TestDatas::$tokens[20]['token_row']);
        
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
        logTestRun(__CLASS__.".".__FUNCTION__);
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        Globals::setOptionPlanned(OPTION_PLANNED_OFF);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        Globals::setOptionPlanned(OPTION_PLANNED_ON);
        $activePlayerId = 1;
        TestDatas::$test_activePlayerId = $activePlayerId;
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
        logTestRun(__CLASS__.".".__FUNCTION__);
        $game = $this->createGameMock();
        $state = new PlayerTurn($game);
        $activePlayerId = 2;
        TestDatas::$test_activePlayerId = $activePlayerId;
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