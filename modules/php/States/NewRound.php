<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;

class NewRound extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_NEW_ROUND,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("NewRound.onEnteringState($activePlayerId)");

        $round = Globals::getRound();
        $round ++;
        Globals::setRound($round);
        Notifications::newRound($round);

        // Go to another gamestate
        Log::addCheckpoint(ST_NEW_TURN);
        return NewTurn::class;
    }
}