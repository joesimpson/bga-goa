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

abstract class Table
{
    public function __construct()
    {
    }

    final public function trace(string $message): void
    {
    }
    protected function initGameStateLabels(array $stateLabels): void
    {
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

    public function getUniqueValueFromDb(string $sql): mixed
    {
        return null;
    }

    public function getCollectionFromDb(string $sql, bool $singleColumn = false): array
    {
        return [];
    }
    public static function getObjectListFromDB(string $sql, bool $bUniqueValue = false): array
    {
        return [];
    }

    public function getDoubleKeyCollectionFromDB(string $sql, bool $nullIfEmpty = false): array
    {
        return [];
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