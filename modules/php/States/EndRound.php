<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Managers\Tiles;

class EndRound extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_END_ROUND,
            type: StateType::GAME,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("EndRound.onEnteringState($activePlayerId)");

        $sessionEnd = false;
        $round = Globals::getRound();
        if($round >= (NB_ROUNDS_DAY + NB_ROUNDS_NIGHT) ){
            $sessionEnd = true;
        }
        else if($round == NB_ROUNDS_DAY ){
            //START NIGHT PHASE
            Tiles::setupMarket(2);
        }

        if ($sessionEnd) {
            Log::addCheckpoint(ST_END_SESSION);
            return EndSession::class;
        } else {
            Log::addCheckpoint(ST_NEW_ROUND);
            return NewRound::class;
        }
    }
}