<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Managers\Players;

class NextPlayer extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_NEXT_PLAYER,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action, example content.
     *
     * The onEnteringState method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("NextPlayer.onEnteringState($activePlayerId)");

        Globals::setupNewTurn();
        $player = Players::getNextPlayerNotEliminated($activePlayerId);
        Players::changeActive($player->getId());
        $player->giveExtraTime();
        Players::setupNewTurn($player);

        // Go to another gamestate
        Log::addCheckpoint(ST_PLAYER_TURN);
        return PlayerTurn::class;
    }
}