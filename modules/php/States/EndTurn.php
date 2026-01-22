<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;

class EndTurn extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_END_TURN,
            type: StateType::GAME,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("EndTurn.onEnteringState($activePlayerId)");

        $roundEnd = false;
            //TODO JSA REMOVE DEBUG when ready
            $roundEnd = true;
        if ($roundEnd) {
            Log::addCheckpoint(ST_END_ROUND);
            return EndRound::class;
        } else {
            Log::addCheckpoint(ST_NEW_TURN);
            return NewTurn::class;
        }
    }
}