<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;

class EndPlayerTurn extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_END_PLAYERTURN,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("EndPlayerTurn.onEnteringState($activePlayerId)");

        $turnEnd = false;
        if ($turnEnd) {
            Log::addCheckpoint(ST_END_TURN);
            return EndTurn::class;
        } else {
            Log::addCheckpoint(ST_NEXT_PLAYER);
            return NextPlayer::class;
        }
    }
}