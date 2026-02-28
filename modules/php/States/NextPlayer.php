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
use Bga\Games\Goa\Managers\Tokens;

class NextPlayer extends \Bga\GameFramework\States\GameState
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
     * Game state action, example content.
     *
     * The onEnteringState method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("NextPlayer.onEnteringState($activePlayerId)");

        $roundStep = Globals::getRoundStep();
        switch($roundStep){
            case ROUND_STEP_TILE_AUCTIONS:
                $nextAuctionTokenToBid = Tokens::getCurrentAuctionToken();
                if(!isset($nextAuctionTokenToBid)) {
                    //Time to end this step
                    $roundStep = ROUND_STEP_PLAYER_ACTIONS;
                    Globals::setRoundStep($roundStep);
                    Notifications::newRoundStep($roundStep, clienttranslate('Player Actions'));
                    //Recall this switch with new step value
                    return $this->onEnteringState($activePlayerId);
                }
                //TODO JSA INCREASE OnLY when same player bid again (when multiple bid turns)
                Globals::incBidTurns();
                $bidTurns = Globals::getBidTurns();
                if($bidTurns == 1){
                    //First turn of bidding on each auction => player after the auctioneer
                    $auctionToken = $nextAuctionTokenToBid;
                    $currentAuctioneerId = $auctionToken->getPId();
                    $nextPlayer = Players::getNextPlayer($currentAuctioneerId);
                }
                break;
            case ROUND_STEP_PLAYER_ACTIONS: 
                $activePlayer = Players::get($activePlayerId);
                if($activePlayer->getNbActions() == 0) {
                    //FIRST TURN for player with marker
                    $firstPlayerToken = Tokens::getFirstPlayerToken();
                    $nextPlayer = Players::get($firstPlayerToken->getPId());
                }
                else {
                    //FIND NEXT PLAYER with remaining actions
                    $nextPlayer = Players::getNextPlayerWithActions($activePlayerId);
                }
                if(isset($nextPlayer)){
                    $nextPlayer->setCardsPlayed([]);
                    $nextPlayer->setActionPlayed(false);
                }
            default : 
                break;
        }

        if(!isset($nextPlayer)) $nextPlayer = Players::getNextPlayer($activePlayerId);

        Players::changeActive($nextPlayer->getId());
        $nextPlayer->giveExtraTime();
        Players::setupNewTurn($nextPlayer);

        // Go to another gamestate
        Log::addStateCheckpoint(PlayerTurn::class);
        return PlayerTurn::class;
    }
}