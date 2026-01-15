<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;

class EndSession extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_END_SESSION,
            type: StateType::GAME,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("EndSession.onEnteringState($activePlayerId)");

        //TODO JSA END session
        $gameEnd = false;
        if ($gameEnd) {
            Log::addCheckpoint(ST_END_SCORING);
            return EndScore::class;
        } else {
            Log::addCheckpoint(ST_NEW_SESSION);
            return NewSession::class;
        }
    }
}