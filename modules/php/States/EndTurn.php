<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;

class EndTurn extends \Bga\GameFramework\States\GameState
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
        $this->game->trace("EndTurn.onEnteringState($activePlayerId)");

        $roundEnd = false;
            //TODO JSA REMOVE DEBUG when ready
            //$roundEnd = true;
        if ($roundEnd) {
            Log::addStateCheckpoint(EndRound::class);
            return EndRound::class;
        } else {
            Log::addStateCheckpoint(NewTurn::class);
            return NewTurn::class;
        }
    }
}