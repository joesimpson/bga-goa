<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;

class NewRound extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: Utils::getStateIdFromClass(self::class),
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
        Globals::setRoundStep(ROUND_STEP_PLACE_AUCTIONS);
        Notifications::newRound($round);
        Notifications::newRoundStep(ROUND_STEP_PLACE_AUCTIONS, clienttranslate('Place Auction Markers'));

        Globals::setBidTurns(0);
        Globals::setCurrentBid(0);
        Players::resetActionsPhase();
        Tiles::resetActionsPhase();

        // Go to another gamestate
        Log::addStateCheckpoint(NewTurn::class);
        return NewTurn::class;
    }
}