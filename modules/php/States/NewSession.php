<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;

class NewSession extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_NEW_SESSION,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("NewSession.onEnteringState($activePlayerId)");

        $session = Globals::getSession();
        $session ++;
        Globals::setSession($session);
        //TODO JSA notif only when multiples 
        Notifications::newSession($session);

        // Go to another gamestate
        $gameEnd = false; // TODO JSA game end condition
        if ($gameEnd) {
            Log::addCheckpoint(ST_END_SCORING);
            return EndScore::class;
        } else {
            Log::addCheckpoint(ST_NEW_ROUND);
            return NewRound::class;
        }
    }
}