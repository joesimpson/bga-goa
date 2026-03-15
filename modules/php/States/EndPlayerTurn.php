<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Exceptions\PrematureStateChangeException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\MoneyCards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\Models\MoneyCard;

class EndPlayerTurn extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: Utils::getStateIdFromClass(self::class),
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
        $automaActive = Globals::isAutomaActive();
        $this->game->trace("EndPlayerTurn.onEnteringState($activePlayerId, $automaActive)");

        $roundEnd = false;
        $prematureStateChange = null;
        $doCheckpoint = false;

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
                $bidTurns = Globals::getBidTurns();
                if(isset($auctionToken)){
                    $currentAuctioneerId = $auctionToken->getPId();
                    $currentAuctioneer = Players::get($currentAuctioneerId);
                }
                $currentAuctioneerActive = isset($currentAuctioneerId) 
                    &&( ($activePlayerId == $currentAuctioneerId && !$automaActive ) // !\ $activePlayerId is set to a real player even during automa turn /!\
                        || AUTOMA_PLAYER_ID == $currentAuctioneerId && $automaActive
                        );
                $this->game->trace("EndPlayerTurn.onEnteringState($activePlayerId)... currentAuctioneerActive ? $currentAuctioneerActive, bidTurns = $bidTurns");

                if($bidTurns > 1 && isset($highBidPlayerId) && $currentAuctioneerActive) { // END the auction when auctioneer played
                    if($highBidPlayerId == $currentAuctioneerId) {
                        $highBidPlayer = $currentAuctioneer;
                        //AUCTIONEER PAYS THE BANK
                        if($highBidPlayerId == AUTOMA_PLAYER_ID){
                            $moneyCard = MoneyCards::bankAutomaAuction($auctionToken->getType());
                            
                            Notifications::winsAuctionWithCard($currentAuctioneer, $highBid, $moneyCard);
                        } else {
                            if($highBid>0){
                                Players::giveMoney($currentAuctioneer, - $highBid, false);
                            }
                            Notifications::winsAuction($currentAuctioneer, $highBid);
                            Stats::inc( "auctionsWon", $currentAuctioneerId);
                            MoneyCards::discardAutomaAuction($auctionToken->getType());
                        }
                    }
                    else {
                        //auction winner PAYS the auctioneer
                        $highBidPlayer = Players::get($highBidPlayerId);
                        if($highBidPlayerId == AUTOMA_PLAYER_ID){
                            //AUTOMATA PAYS the player with a card
                            $highBidPlayer = Players::automaPlayer();
                            MoneyCards::automaGiveToPlayer($auctionToken->getType(), $highBidPlayer, $currentAuctioneer);
                        } else if($currentAuctioneerId == AUTOMA_PLAYER_ID){
                            //AUTOMA REceives Player Money card
                            Players::giveMoney($highBidPlayer, - $highBid , false);
                            Notifications::winsAuction($highBidPlayer, $highBid,$currentAuctioneer);
                            MoneyCards::discardAutomaAuction($auctionToken->getType());
                            MoneyCards::automaReceivesFromPlayer($highBid, $auctionToken->getType(), $currentAuctioneer, $highBidPlayer,);
                            Stats::inc( "auctionsWon", $highBidPlayerId);
                        }
                        else {
                            //Player 1 pays player 2
                            Players::giveMoney($highBidPlayer, - $highBid , false);
                            Players::giveMoney($currentAuctioneer, + $highBid , false );
                            Notifications::winsAuction($highBidPlayer, $highBid,$currentAuctioneer);
                            Stats::inc( "auctionsWon", $highBidPlayerId);
                        }
                    }

                    if( TOKEN_TYPE_AUCTION_MARKER_1 == $auctionToken->getType()){
                        //$highBidPlayer receives the FIRST token if this is the first auction
                        Tokens::giveFirstPlayerToken($highBidPlayer);
                        Players::giveExtraActionCards($highBidPlayer);
                    }
                    else {
                        //ELSE highBidPlayer receives a tile
                        try{
                            Tiles::giveMarketTileToPlayerAt($highBidPlayer, $auctionToken->getRow(), $auctionToken->getCol(), $doCheckpoint );
                        }
                        catch(PrematureStateChangeException $exc){
                            $prematureStateChange = $exc;
                        }
                    }

                    Tokens::returnAuctionMarkerToPlayer($currentAuctioneer,$auctionToken);
                    Globals::setBidTurns(0);
                    Globals::setCurrentBid(0);
                    Globals::setHighBidPlayer(null);
                    Players::resetBidPhase();
                }
                break;
            case ROUND_STEP_PLAYER_ACTIONS:
                $nextPlayer = Players::getNextPlayerWithActions($activePlayerId);
                $roundEnd = false;
                if(!isset($nextPlayer)){
                    $roundEnd = true;
                }
                break;
            default : break;
        }

        if(isset($prematureStateChange) ){
            Players::changeActive($prematureStateChange->changePlayer->getId());
            if($activePlayerId != $prematureStateChange->changePlayer->getId()){
                Log::addStateCheckpoint($prematureStateChange->nextStateClass);
            }
            return $prematureStateChange->nextStateClass;
        }
        else if ($roundEnd) {
            //Log::addStateCheckpoint(EndTurn::class);
            //return EndTurn::class;
            Log::addStateCheckpoint(EndRound::class);
            return EndRound::class;
        } else {
            Log::addStateCheckpoint(NextPlayer::class);
            return NextPlayer::class;
        }
    }
}