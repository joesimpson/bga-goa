<?php

declare(strict_types=1);

namespace Bga\GameFramework;

use Bga\Games\Goa\Game;

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
    }
}

class GamestateMachineMock extends \Bga\GameFramework\GamestateMachine
{
    
}

abstract class Table
{
    public function __construct()
    {
    }

    public function error(string $message): void
    {
        //echo "ERROR : $message\n";
        fwrite(STDOUT, print_r("ERROR : $message\n", TRUE));
    }
    
    public function dump(string $message): void
    {
        //echo "DUMP : $message\n";
        fwrite(STDOUT, print_r("DUMP : $message\n", TRUE));
    }
    final public function debug(string $message): void
    {
        //echo "DEBUG : $message\n";
        fwrite(STDOUT, print_r("DEBUG : $message\n", TRUE));
    }
    final public function trace(string $message): void
    {
        //echo "TRACE : $message\n";
        fwrite(STDOUT, print_r("TRACE : $message\n", TRUE));
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
        return 0;
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
        fwrite(STDOUT, print_r("getUniqueValueFromDb: $sql\n", TRUE));
        return null;
    }

    public function getCollectionFromDb(string $sql, bool $singleColumn = false): array
    {
        fwrite(STDOUT, print_r("getCollectionFromDb: $sql\n", TRUE));
        return [];
    }
    public static function getObjectListFromDB(string $sql, bool $bUniqueValue = false): array
    {
        //echo "getObjectListFromDB($sql)\n";
        fwrite(STDOUT, print_r("getObjectListFromDB: $sql\n", TRUE));
        switch($sql){
            case 'SELECT name AS `result_associative_index` , `value` , `name` FROM `bga_globals`':
                fwrite(STDOUT, print_r("MOCK select globals\n", TRUE));
                return [
                    ['name' => 'session', 'value' => 1, 'result_associative_index' => 'session'],
                    ['name' => 'round', 'value' => 1, 'result_associative_index' => 'round'],
                    ['name' => 'roundStep', 'value' => 1, 'result_associative_index' => 'roundStep'],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = ".TOKEN_TYPE_FIRSTAUCTION_MARKER." AND `subType` = ".TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER:
                fwrite(STDOUT, print_r("MOCK select first  token\n", TRUE));
                return [
                    ['token_id' => 1, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER, 'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,'token_row' => 3, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 1,  ],

                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 1 AND `player_id` = 1":
                fwrite(STDOUT, print_r("MOCK select player 1 progress tokens \n", TRUE));
                return [
                    ['token_id' => 11, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_SHIPS, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 11,  ],
                    ['token_id' => 15, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 5,  'player_id' => 1,  'result_associative_index' => 15,  ],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 1 AND `player_id` = 2":
                fwrite(STDOUT, print_r("MOCK select player 2 progress tokens \n", TRUE));
                return [
                     ['token_id' => 16, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_SHIPS, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 16,  ],
                    ['token_id' => 20, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 5,  'player_id' => 2,  'result_associative_index' => 20,  ],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 4 AND `player_id` = 2":
                fwrite(STDOUT, print_r("MOCK select spice tokens \n", TRUE));
                return [
                    ['token_id' => 21, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'1' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 21,  ],
                    ['token_id' => 22, 'token_state' => 2, 'token_location'=> TOKEN_LOCATION_TILE.'2' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 2,  'player_id' => 2,  'result_associative_index' => 22,  ],
                ];
            case 'SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = 1 LIMIT 1': 
                fwrite(STDOUT, print_r("MOCK select  player one \n", TRUE));
                return [
                    ['player_id' => 1, 'ships' => 12 ,'player_name' => 'Player_1', 'result_associative_index' => 1],
                    ];
            case 'SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = 2 LIMIT 1': 
                fwrite(STDOUT, print_r("MOCK select  player two \n", TRUE));
                return [
                    ['player_id' => 2, 'ships' => 15 ,'player_name' => 'Player_2', 'result_associative_index' => 2],
                    ];
        }
        if( str_starts_with( $sql, 'SELECT *, player_id AS `result_associative_index` FROM `player`' )){
            fwrite(STDOUT, print_r("MOCK select players\n", TRUE));
            return [
                ['player_id' => 1, 'ships' => 12 ,'player_name' => 'Player_1', 'result_associative_index' => 1],
                ['player_id' => 2, 'ships' => 15 ,'player_name' => 'Player_2', 'result_associative_index' => 2],
                ];
        }
        if( str_starts_with( $sql,'SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subType` = '.TOKEN_SUBTTYPE_AUCTION_MARKER)){
            fwrite(STDOUT, print_r("MOCK select token to auction \n", TRUE));
            return [
                    ['token_id' => 2, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_1, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 2,  ],
                    ['token_id' => 3, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_2, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 3,  'player_id' => 1,  'result_associative_index' => 3,  ],
                ];
        }

        fwrite(STDOUT, print_r("don't know what to return for this request\n", TRUE));
        return [];
    }

    public function getCurrentPlayerId(bool $bReturnNullIfNotLogged = false): string/*|int*/
    {
        return '0';
    }
    public function getDoubleKeyCollectionFromDB(string $sql, bool $nullIfEmpty = false): array
    {
        fwrite(STDOUT, print_r("getDoubleKeyCollectionFromDB: $sql\n", TRUE));
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
    public function getActivePlayerId(): string/*|int*/
    {
        return '0'; 
    }
}
class Notify {
    public function player(int $playerId, string $notifName, string $message = '', array $args = []): void {
        fwrite(STDOUT, print_r("Notify_player $playerId $notifName : $message, with args ".json_encode($args)."\n", TRUE));
    }
    public function all(string $notifName, string $message = '', array $args = []): void {
        fwrite(STDOUT, print_r("Notify_ALL $notifName : $message, with args ".json_encode($args)."\n", TRUE));
    }
    public function __construct(
        $game,
    ) {
    }
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
