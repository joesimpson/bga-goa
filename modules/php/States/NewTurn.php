<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;

class NewTurn extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_NEW_TURN,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("NewTurn.onEnteringState($activePlayerId)");

        $turn = Globals::getTurn();
        $turn++;
        Globals::setTurn($turn);
        Notifications::newTurn($turn);

        // Go to another gamestate
        Log::addCheckpoint(ST_NEXT_PLAYER);
        return NextPlayer::class;
    }
}