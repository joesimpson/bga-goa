<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
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
            description: clienttranslate('Ending player turn'),
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("EndPlayerTurn.onEnteringState($activePlayerId)");

        $turnEnd = false;

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
                    Globals::setBidTurns(0);
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
                        $highBidPlayer = $currentAuctioneer;
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

                    if( TOKEN_TYPE_AUCTION_MARKER_1 == $auctionToken->getType()){
                        //$highBidPlayer receives the FIRST token if this is the first auction
                        Tokens::giveFirstPlayerToken($highBidPlayer);
                        Players::giveExtraActionCard($highBidPlayer);
                    }
                    else {
                        //ELSE highBidPlayer receives a tile
                        Tiles::giveMarketTileToPlayer($highBidPlayer, $auctionToken->getRow(), $auctionToken->getCol() );
                    }

                    Tokens::returnAuctionMarkerToPlayer($currentAuctioneer,$auctionToken);
                    Globals::setBidTurns(0);

                    $nextAuctionTokenToBid = Tokens::getCurrentAuctionToken();
                    if(!isset($nextAuctionTokenToBid)) {
                        //Time to end this step
                        $roundStep = ROUND_STEP_PLAYER_ACTIONS;
                        Globals::setRoundStep($roundStep);
                        Notifications::newRoundStep($roundStep, clienttranslate('Player Auctions'));
                    }
                }
                break;
            case ROUND_STEP_PLAYER_ACTIONS:
                //TODO JSA REMOVE DEBUG when real actions ready
                $turnEnd = true;
                break;
            default : break;
        }

        if ($turnEnd) {
            Log::addCheckpoint(ST_END_TURN);
            return EndTurn::class;
        } else {
            Log::addCheckpoint(ST_NEXT_PLAYER);
            return NextPlayer::class;
        }
    }
}