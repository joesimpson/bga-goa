<?php

declare(strict_types=1);

namespace Bga\GameFramework;

use Bga\Games\Goa\Game;
use Bga\Games\Goa\Models\CardLocation;
use Exception;

class UserException extends \Exception
{
    /**
     * @param string|NotificationMessage $message Error message to be surrounded by `clienttranslate`, with optional arguments.
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

enum StateType: string
{
    case ACTIVE_PLAYER = 'activeplayer';
    case MULTIPLE_ACTIVE_PLAYER = 'multipleactiveplayer';
    case PRIVATE = 'private';
    case GAME = 'game';
    case MANAGER = 'manager';
}

class GamestateMachine
{
    public function changeActivePlayer(int $playerId): void
    {
    }
}

#[\AllowDynamicProperties]
class GameMock extends Game {

    public function __construct()
    {
        parent::__construct();
        $this->gamestate = new GamestateMachineMock();
        $this->notify = new Notify($this);
        $this->bga = new Bga($this);
    }

    public function getStatTypes(){
        $stats = json_decode('{
                "table": {

                },
                "player": {
                "score": {
                    "id": 11,
                    "name": "Score",
                    "type": "int"
                },
                "turnOrder": {
                    "id": 13,
                    "name": "Turn order",
                    "type": "int"
                }
                
                ,"totalMoney": {
                    "id": 15,
                    "name": "Total Pardaos coins",
                    "type": "int"
                }
                ,"totalShips": {
                    "id": 16,
                    "name": "Total Ships",
                    "type": "int"
                }
                ,"totalContracts": {
                    "id": 17,
                    "name": "Total Contracts",
                    "type": "int"
                }
                ,"totalCompasses": {
                    "id": 18,
                    "name": "Total Compasses",
                    "type": "int"
                }
                
                ,"progressShips": {
                    "id": 21,
                    "name": "Progress row : Build Ships",
                    "type": "int"
                }
                ,"progressHarvest": {
                    "id": 22,
                    "name": "Progress row : Produce",
                    "type": "int"
                }
                ,"progressTaxes": {
                    "id": 23,
                    "name": "Progress row : Collect Taxes",
                    "type": "int"
                }
                ,"progressExploration": {
                    "id": 24,
                    "name": "Progress row : Explore",
                    "type": "int"
                }
                ,"progressFound": {
                    "id": 25,
                    "name": "Progress row : Establish an Outpost",
                    "type": "int"
                }
                
                ,"auctionsWon": {
                    "id": 30,
                    "name": "Auctions won",
                    "type": "int"
                }
                
                ,"playedPlantations": {
                    "id": 31,
                    "name": "Played depots",
                    "type": "int"
                }
                ,"playedOutposts": {
                    "id": 32,
                    "name": "Played outposts",
                    "type": "int"
                }
                
                ,"receivedSpices": {
                    "id": 33,
                    "name": "Received goods on depots/outposts fields",
                    "type": "int"
                }
                
                ,"playedTiles": {
                    "id": 34,
                    "name": "Played other tiles",
                    "type": "int"
                }
                
                ,"playedExplorations": {
                    "id": 35,
                    "name": "Played Exploration cards",
                    "type": "int"
                }

                ,"actionA": {
                    "id": 40,
                    "name": "Progress",
                    "type": "int"
                }
                ,"actionB": {
                    "id": 41,
                    "name": "Build Ships",
                    "type": "int"
                }
                ,"actionC": {
                    "id": 42,
                    "name": "Produce",
                    "type": "int"
                }
                ,"actionD": {
                    "id": 43,
                    "name": "Collect Taxes",
                    "type": "int"
                }
                ,"actionE": {
                    "id": 44,
                    "name": "Explore",
                    "type": "int"
                }
                ,"actionF": {
                    "id": 45,
                    "name": "Establish an Outpost",
                    "type": "int"
                }
                
                },
                "value_labels": { 
                }
            }', true);
        //$this->dump("StatsTypes : ", $stats);
        return $stats;
    }
}

class GamestateMachineMock extends \Bga\GameFramework\GamestateMachine
{
    public function getCurrentMainStateId(): ?int {
        return ST_PLAYER_TURN;
    }
    
}

abstract class Table
{
    //added for tests
    static $test_activePlayerId = 1;

    public function __construct()
    {
    }

    public function error(string $message): void
    {
        logForTests($message, "ERROR");
    }
    
    public function dump(string $message, mixed $object): void
    {
        logForTests($message, "DUMP");
        logForTests($object, "DUMP");
    }
    final public function debug(string $message): void
    {
        logForTests($message, "DEBUG");
    }
    final public function trace(string $message): void
    {
        logForTests($message, "TRACE");
    }
    protected function initGameStateLabels(array $stateLabels): void
    {
    }
    public function getGameStateValue(string $label, ?int $default = null): int|string
    {
        return '0';
    }

    public function activeNextPlayer(): int|string
    {
        static::$test_activePlayerId = 2;
        return static::$test_activePlayerId;
    }

    public function getPlayerAfter(int $playerId): int
    {
        return 0;
    }

    public function giveExtraTime(int $playerId, ?int $specificTime = null): void
    {
    }

    public static function getUniqueValueFromDb(string $sql): mixed
    {
        logForTests("getUniqueValueFromDb: $sql");
        return null;
    }

    public function getCollectionFromDb(string $sql, bool $singleColumn = false): array
    {
        logForTests("getCollectionFromDb: $sql");
        return [];
    }
    public static function getObjectListFromDB(string $sql, bool $bUniqueValue = false): array
    {
        logForTests("getObjectListFromDB: $sql");
        $players = [
           1 => ['player_id' => 1, 'money' => 87 ,'ships' => 12 ,'player_name' => 'Player_1', 'player_score' => 19, 'contracts' => 1, 'compasses' => 0, 'nbActions' => 0, 'passed' => 0, 'actionPlayed' => 0, 'cardsPlayed' => '[]','result_associative_index' => 1, ],
           2 => ['player_id' => 2, 'money' => 88 ,'ships' => 15 ,'player_name' => 'Player_2', 'player_score' => 27, 'contracts' => 1, 'compasses' => 0, 'nbActions' => 0, 'passed' => 0, 'actionPlayed' => 0, 'cardsPlayed' => '[]','result_associative_index' => 2, ],
        ];
        $tokens = [
            1 => ['token_id' => 1, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER, 'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,'token_row' => 3, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 1,  ],
            2 => ['token_id' => 2, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_1, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 2,  ],
            3 => ['token_id' => 3, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_2, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 3,  'player_id' => 1,  'result_associative_index' => 3,  ],
            4 => ['token_id' => 4, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_3, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => 1,  'result_associative_index' => 4,  ],

            5 => ['token_id' => 5, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_AUTOMA_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_1, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => AUTOMA_PLAYER_ID,  'result_associative_index' => 5,  ],
            6 => ['token_id' => 6, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_2, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 1, 'token_col' => 1,  'player_id' => AUTOMA_PLAYER_ID,  'result_associative_index' => 6,  ],
            7 => ['token_id' => 7, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_AUTOMA_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_3, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => AUTOMA_PLAYER_ID,  'result_associative_index' => 7,  ],

            11 => ['token_id' => 11, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_SHIPS, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 4, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 11,  ],
            12 => ['token_id' => 12, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_HARVEST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 2, 'token_col' => 2,  'player_id' => 1,  'result_associative_index' => 12,  ],
            13 => ['token_id' => 13, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_TAXES, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 3,  'player_id' => 1,  'result_associative_index' => 13,  ],
            14 => ['token_id' => 14, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_EXP, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => 1,  'result_associative_index' => 14,  ],
            15 => ['token_id' => 15, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 5,  'player_id' => 1,  'result_associative_index' => 15,  ],
                
            16 =>   ['token_id' => 16, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_SHIPS, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 16,  ],
            17 =>   ['token_id' => 17, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_HARVEST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 2,  'player_id' => 2,  'result_associative_index' => 17,  ],
            18 =>   ['token_id' => 18, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_TAXES, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 3,  'player_id' => 2,  'result_associative_index' => 18,  ],
            19 =>   ['token_id' => 19, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_EXP, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => 2,  'result_associative_index' => 19,  ],
            20 =>   ['token_id' => 20, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 5,  'player_id' => 2,  'result_associative_index' => 20,  ],

            21 =>   ['token_id' => 21, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'1' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 21,  ],
            22 =>   ['token_id' => 22, 'token_state' => 2, 'token_location'=> TOKEN_LOCATION_TILE.'2' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 2,  'player_id' => 2,  'result_associative_index' => 22,  ],
            23 =>   ['token_id' => 23, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'3' ,'type' => TOKEN_TYPE_SPICE_PINK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 23,  ],
            
            24 =>   ['token_id' => 24, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'4' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 24,  ],
            25 =>   ['token_id' => 25, 'token_state' => 2, 'token_location'=> TOKEN_LOCATION_TILE.'5' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 25,  ],
            26 =>   ['token_id' => 26, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_PINK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  26,  ],
            27 =>   ['token_id' => 27, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_WHITE, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  27,  ],
            28 =>   ['token_id' => 28, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_WHITE, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  28,  ],
            29 =>   ['token_id' => 29, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_YELLOW, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  29,  ],
            30 =>   ['token_id' => 30, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_BLACK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  30,  ],
            31 =>   ['token_id' => 31, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_BLACK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  31,  ],
            32 =>   ['token_id' => 32, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_WHITE, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  32,  ],
                
        ];
        $cards = [
           1 => ['result_associative_index' => 1,'card_id' => 1, 'card_location' => CARD_LOCATION_HAND, 'card_state' => 0, 'player_id' => 1, 'type' => 10, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],
           2 => ['result_associative_index' => 2,'card_id' => 2, 'card_location' => CARD_LOCATION_HAND, 'card_state' => 0, 'player_id' => 2, 'type' => 11, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],
           3 => ['result_associative_index' => 3,'card_id' => 3, 'card_location' => CARD_LOCATION_HAND, 'card_state' => 0, 'player_id' => 2, 'type' => 11, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],

           100 => ['result_associative_index' => 100,'card_id' => 100, 'card_location' => CARD_LOCATION_AUTOMA_AUCTION_DAY_1_DECK, 'card_state' => 0, 'player_id' => AUTOMA_PLAYER_ID, 'type' => 1, 'subtype' => CARD_SUBTTYPE_AUTOMA_AUCTION,],

           200 => ['result_associative_index' => 200,'card_id' => 200, 'card_location' => CARD_LOCATION_AUTOMA_MONEY_DECK, 'card_state' => 0, 'player_id' => AUTOMA_PLAYER_ID, 'type' => 1, 'subtype' => CARD_SUBTTYPE_MONEY,],
        ];
        $tiles = [
           1 => ['result_associative_index' => 1,'tile_id' => 1, 'tile_location' => TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOLLAM], 'tile_state' => 0, 'player_id' => null, 'type' => 1, 'subtype' => TILE_SUBTTYPE_OUTPOST, 'tile_start_location' => null , 'tile_row' => 1 , 'tile_col' => 1],
        ];
        
        $effects = [//SELECT effect_id AS `result_associative_index` , `effect_id` , `effect_location` , `effect_state` , `player_id` , `effect_parent_id` , `effect_type` , `effect_qty` , `effect_source_tile` , `effect_source_action` , `effect_result` FROM `effects` WHERE (`effect_id` IN ('1'))
           1 => ['result_associative_index' => 1,'effect_id' => 1, 'effect_location' => EFFECT_LOCATION_TO_RESOLVE, 'effect_state' => 0, 'player_id' => null, 'effect_parent_id' => null, 'effect_type' => 1, 'effect_qty' => 1, 'effect_source_tile' => null , 'effect_source_action' => null , 'effect_result' => '[]'],
        ];
        

        switch($sql){
            case 'SELECT name AS `result_associative_index` , `value` , `name` FROM `my_global_variables`':
                logForTests("MOCK select globals");
                return [
                    ['name' => 'session', 'value' => 1, 'result_associative_index' => 'session'],
                    ['name' => 'round', 'value' => 1, 'result_associative_index' => 'round'],
                    ['name' => 'roundStep', 'value' => 1, 'result_associative_index' => 'roundStep'],
                    ['name' => 'endScoring', 'value' => '[]', 'result_associative_index' => 'endScoring'],
                    ['name' => 'automaColor', 'value' => '000000', 'result_associative_index' => 'automaColor'],
                    ['name' => 'automaActive', 'value' => false, 'result_associative_index' => 'automaActive'],
                    ['name' => 'automaNbActions', 'value' => 0, 'result_associative_index' => 'automaNbActions'],
                    ['name' => 'automaPassed', 'value' => false, 'result_associative_index' => 'automaPassed'],
                    ['name' => 'automaActionPlayed', 'value' => false, 'result_associative_index' => 'automaActionPlayed'],
                ];
            case "SELECT value,name FROM `my_global_variables` WHERE  `name` = 'automaColor'":
                return [
                    ['name' => 'automaColor', 'value' => 'aaa', 'result_associative_index' => 'automaColor'],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = ".TOKEN_TYPE_FIRSTAUCTION_MARKER." AND `subType` = ".TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER:
                logForTests("MOCK select first  token");
                return [
                    $tokens[1],
                ];
            //case "SELECT token_id AS `result_associative_index` , `token_id` , `token_location` , `token_state` , `player_id` , `type` , `subtype` , `token_row` , `token_col` FROM `tokens` WHERE (`token_id` IN ('1'))":
            //    return [
            //        $tokens[1],
            //    ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 1 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[11] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 2 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[12] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 3 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[13] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 4 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[14] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 5 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[15] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 1 AND `player_id` = 1":
                logForTests("MOCK select player 1 progress tokens ");
                return [
                    $tokens[11],
                    $tokens[12],
                    $tokens[13],
                    $tokens[14],
                    $tokens[15],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 1 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[16] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 2 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[17] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 3 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[18] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 4 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[19] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 5 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[20] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 1 AND `player_id` = 2":
                logForTests("MOCK select player 2 progress tokens ");
                return [
                    $tokens[16],
                    $tokens[17],
                    $tokens[18],
                    $tokens[19],
                    $tokens[20],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 4 AND `player_id` = 1":
                logForTests("MOCK select spice tokens player 1 ");
                return [
                    $tokens[24],
                    $tokens[25],
                    $tokens[26],
                    $tokens[27],
                    $tokens[28],
                    $tokens[29],
                    $tokens[30],
                    $tokens[31],
                    $tokens[32],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 4 AND `player_id` = 2":
                logForTests("MOCK select spice tokens player 2 ");
                return [
                    $tokens[21],
                    $tokens[22],
                    $tokens[23],
                ];
            case 'SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = 1 LIMIT 1': 
                logForTests("MOCK select  player one ");
                return [
                    $players[1],
                    ];
            case "SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = '0' LIMIT 1":
            case 'SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = 2 LIMIT 1': 
                logForTests("MOCK select  player two ");
                return [
                    $players[2],
                    ];
            case 'SELECT player_score,player_id FROM `player` WHERE `player_id` = 2':
                return [
                    $players[2],
                ];
            case 'SELECT stats_value,stats_id FROM `stats` WHERE `stats_type` = 11 AND `stats_player_id` = 2':
                return [
                    ['stats_id' => 1, 'stats_value' => 27 ],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_id` IN ('1'))":
                return [
                    $cards[1],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_id` IN ('2'))":
                return [
                    $cards[2],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_id` IN ('3'))":
                return [
                    $cards[3],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_location` = '".CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value."') ORDER BY card_state DESC LIMIT 1":
                return [
                    $cards[100],
                ];
            case "SELECT *, card_id AS `result_associative_index` FROM `cards` WHERE `subType` = 7 AND `card_location` = '".CardLocation::AUTOMA_MONEY_DECK->value."'":
                return [
                    $cards[200],
                ];
            case "SELECT player_score,player_id FROM `player` WHERE `player_id` = 1":
            case "SELECT player_id AS `result_associative_index` , `player_score` FROM `player` WHERE `player_id` = 1":
                return[
                    $players[1],
                ];
            case "SELECT player_score,player_id FROM `player` WHERE `player_id` = 2":
            case "SELECT player_id AS `result_associative_index` , `player_score` FROM `player` WHERE `player_id` = 2":
                return[
                    $players[2],
                ];
            case "SELECT tile_id AS `result_associative_index` , `tile_id` , `tile_location` , `tile_state` , `player_id` , `type` , `subtype` , `tile_start_location` , `tile_row` , `tile_col` FROM `tiles` WHERE (`tile_id` IN ('1'))":
                return [
                        $tiles[1],
                    ];
            case "SELECT effect_id AS `result_associative_index` , `effect_id` , `effect_location` , `effect_state` , `player_id` , `effect_parent_id` , `effect_type` , `effect_qty` , `effect_source_tile` , `effect_source_action` , `effect_result` FROM `effects` WHERE (`effect_id` IN ('1'))":
                return [
                        $effects[1],
                    ];
        }
        if( str_starts_with( $sql, 'SELECT *, player_id AS `result_associative_index` FROM `player`' )){
            logForTests("MOCK select players");
            return [
                $players[1],
                $players[2],
            ];
        }
        if( str_starts_with( $sql,'SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subType` = '.TOKEN_SUBTTYPE_AUCTION_MARKER)){
            logForTests("MOCK select token to auction ");
            return [
                    $tokens[2],
                    $tokens[3],
                ];
        }
        //if( str_starts_with( $sql,'SELECT *, tile_id AS `result_associative_index` FROM `tiles` WHERE `subtype` = '.TILE_SUBTTYPE_OUTPOST)){
        //    logForTests("MOCK select tiles ");
        //    return [
        //            $tiles[1],
        //        ];
        //}

        logForTests(" /!\ __ don't know what to return for this request __ /!\ ");
        return [];
    }

    public function getCurrentPlayerId(bool $bReturnNullIfNotLogged = false): string|int
    {
        return static::$test_activePlayerId; 
    }
    public function getDoubleKeyCollectionFromDB(string $sql, bool $nullIfEmpty = false): array
    {
        logForTests("getDoubleKeyCollectionFromDB: $sql");
        return [];
    }
    public static function DbAffectedRow(): int
    {
        return 1;
    }
    public static function DbGetLastId(): int
    {
        return 1;
    }
    public static function DbQuery(string $sql): null|\mysqli_result|bool
    {
        return null;
    }
    public function getNextPlayerTable(): array
    {
        return [ 1 => 2, 2 => 1, 0 => 1,];
    }
    public function getActivePlayerId(): string|int
    {
        return static::$test_activePlayerId; 
    }
}
 class TableOptions {
    public function get(int $optionId): ?int {
        switch($optionId){
            case BGA_GAMESTATE_GAMEVERSION: return 999999;
        }
        return 0;
    }

    function isTurnBased(): bool {
        return false;
    }
    function isRealTime(): bool {
        return false;
    }
}
class Bga {
        public Db\Globals $globals;
        public Notify $notify;
        public Legacy $legacy;
        public Tournament $tournament;
        public TableOptions $tableOptions;
        public UserPreferences $userPreferences;
        public TableStats $tableStats;
        public PlayerStats $playerStats;
        public Components\DeckFactory $deckFactory;
        public Components\Counters\CounterFactory $counterFactory;
        public Debug $debug;
        
        public Components\Counters\PlayerCounter $playerScore;
        public Components\Counters\PlayerCounter $playerScoreAux;
            
        public function __construct(
            $game,
        ) {
            $this->tableOptions = new TableOptions();
        }
    }
class Notify {
    public function player(int $playerId, string $notifName, string $message = '', array $args = []): void {
        logForTests("Notify_player $playerId $notifName : $message, with args ".json_encode($args)."");
    }
    public function all(string $notifName, string $message = '', array $args = []): void {
        logForTests("Notify_ALL $notifName : $message, with args ".json_encode($args)."");
    }
    public function __construct(
        $game,
    ) {
    }
}
class NotificationMessage {
    public function __construct(
        public string $message = '',
        public array $args = [],
    ) {}
}

namespace Bga\GameFramework\States;

use Bga\GameFramework\StateType;

#[\Attribute]
class PossibleAction
{
}

abstract class GameState
{
    public function __construct(
        $game,
        public int $id,
        public StateType $type,
        public ?string $name = null,
        public string $description = '',
        public string $descriptionMyTurn = '',
        public array $transitions = [],
        public bool $updateGameProgression = false,
        public ?int $initialPrivate = null,
    ) {
    }
}
class GameStateInMem extends GameState {}
