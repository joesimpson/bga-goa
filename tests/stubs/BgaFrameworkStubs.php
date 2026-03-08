<?php

declare(strict_types=1);

namespace Bga\GameFramework;

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
        fwrite(STDOUT, print_r("getUniqueValueFromDb($sql)\n", TRUE));
        return null;
    }

    public function getCollectionFromDb(string $sql, bool $singleColumn = false): array
    {
        fwrite(STDOUT, print_r("getCollectionFromDb($sql)\n", TRUE));
        return [];
    }
    public static function getObjectListFromDB(string $sql, bool $bUniqueValue = false): array
    {
        //echo "getObjectListFromDB($sql)\n";
        fwrite(STDOUT, print_r("getObjectListFromDB($sql)\n", TRUE));
        switch($sql){
            case 'SELECT name AS `result_associative_index` , `value` , `name` FROM `bga_globals`':
                return [
                    ['name' => 'session', 'value' => 1, 'result_associative_index' => 'session'],
                    ['name' => 'round', 'value' => 1, 'result_associative_index' => 'round'],
                    ['name' => 'roundStep', 'value' => 1, 'result_associative_index' => 'roundStep'],
                ];
        }
        if( str_starts_with( $sql, 'SELECT *, player_id AS `result_associative_index` FROM `player`' ))
            return [
                ['player_id' => 1, 'player_name' => 'Player_1', 'result_associative_index' => 'player_id'],
                ['player_id' => 2, 'player_name' => 'Player_2', 'result_associative_index' => 'player_id'],
            ];
        return [];
    }

    public function getCurrentPlayerId(bool $bReturnNullIfNotLogged = false): string/*|int*/
    {
        return '0';
    }
    public function getDoubleKeyCollectionFromDB(string $sql, bool $nullIfEmpty = false): array
    {
        fwrite(STDOUT, print_r("getDoubleKeyCollectionFromDB($sql)\n", TRUE));
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