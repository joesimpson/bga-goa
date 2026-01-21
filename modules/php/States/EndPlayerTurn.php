<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tokens;

class EndPlayerTurn extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_END_PLAYERTURN,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("EndPlayerTurn.onEnteringState($activePlayerId)");

        $player = Players::get($activePlayerId);
        $roundStep = Globals::getRoundStep();
        switch($roundStep){
            case ROUND_STEP_PLACE_AUCTIONS:
                $nextAuctionToken = Tokens::getNextAuctionToken($player);
                if(!isset($nextAuctionToken)) {
                    //Time to end this step
                    $roundStep = ROUND_STEP_TILE_AUCTIONS;
                    Globals::setRoundStep($roundStep);
                    Notifications::newRoundStep($roundStep, clienttranslate('Tile Auctions'));
                    Globals::setCurrentBid(0);
                    Globals::setHighBidPlayer(null);
                }
                break;
            case ROUND_STEP_TILE_AUCTIONS: 
                $highBid = Globals::getCurrentBid();
                $highBidPlayerId = Globals::getHighBidPlayer();
                $auctionToken = Tokens::getCurrentAuctionToken();
                $currentAuctioneerId = $auctionToken->getPId();
                $currentAuctioneer = Players::get($currentAuctioneerId);
                
                if($activePlayerId == $currentAuctioneerId) { // END the auction when auctioneer played
                    if($highBidPlayerId == $currentAuctioneerId) {
                        //AUCTIONEER PAYS THE BANK
                        if($highBid>0){
                            $currentAuctioneer->setMoney( $currentAuctioneer->getMoney() - $highBid );
                        }
                        Notifications::winsAuction($currentAuctioneer, $highBid);
                    }
                    else {
                        //auction winner PAYS the auctioneer
                        $highBidPlayer = Players::get($highBidPlayerId);
                        $highBidPlayer->setMoney( $highBidPlayer->getMoney() - $highBid );
                        $currentAuctioneer->setMoney( $currentAuctioneer->getMoney() + $highBid );
                        $highBidPlayer->setMoney( $highBidPlayer->getMoney() - $highBid );
                        Notifications::winsAuction($highBidPlayer, $highBid,$currentAuctioneer);
                    }
                }
                break;
            case ROUND_STEP_PLAYER_ACTIONS:
                break;
            default : break;
        }

        $turnEnd = false;
        if ($turnEnd) {
            Log::addCheckpoint(ST_END_TURN);
            return EndTurn::class;
        } else {
            Log::addCheckpoint(ST_NEXT_PLAYER);
            return NextPlayer::class;
        }
    }
}