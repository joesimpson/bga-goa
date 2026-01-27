<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;

class EndSession extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: Utils::getStateIdFromClass(self::class),
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
            Log::addStateCheckpoint(EndScore::class);
            return EndScore::class;
        } else {
            Log::addStateCheckpoint(NewSession::class);
            return NewSession::class;
        }
    }
}