<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Managers\Tiles;

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
        //TODO JSA notif only when game with multiples sessions
        Notifications::newSession($session);
        
        //TODO JSA RESETUP Players MONEY etc...
        Globals::setRound(0);
        Tiles::setupMarket();

        // Go to another gamestate
        Log::addCheckpoint(ST_NEW_ROUND);
        return NewRound::class;
    }
}