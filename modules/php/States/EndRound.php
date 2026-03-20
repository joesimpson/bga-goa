<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\AutomaCards;
use Bga\Games\Goa\Managers\MoneyCards;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Models\GamePhase;

class EndRound extends \Bga\GameFramework\States\GameState
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
        $this->game->trace("EndRound.onEnteringState($activePlayerId)");

        $sessionEnd = false;
        $round = Globals::getRound();
        Notifications::endRound($round);
        if($round >= (NB_ROUNDS_DAY + NB_ROUNDS_NIGHT) ){
            $sessionEnd = true;
        }
        else if($round == NB_ROUNDS_DAY ){
            //START NIGHT PHASE
            Notifications::newPhase(GamePhase::NIGHT);
            Tiles::setupMarket(2);
                    
            if(Utils::isGameWithAutoma()){
                AutomaCards::setupPhase(GamePhase::NIGHT);
                MoneyCards::reshuffleAutomaDeck();
            }
        }

        if ($sessionEnd) {
            Log::addStateCheckpoint(EndSession::class);
            return EndSession::class;
        } else {
            Log::addStateCheckpoint(NewRound::class);
            return NewRound::class;
        }
    }
}