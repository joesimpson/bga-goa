<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tokens;

class PlayerTurn extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_PLAYER_TURN,
            type: StateType::ACTIVE_PLAYER,
        );
    }

    /**
     * Game state arguments
     *
     * This method returns some additional information that is very specific to the `PlayerTurn` game state.
     */
    public function getArgs(): array
    {
        // Get some values from the current game situation from the database.

        $roundStep = Globals::getRoundStep();
        $player = Players::getActive();

        $args = [
            'roundStep' => $roundStep,
            'possibleActions' => [],
        ];

        switch($roundStep){
            case ROUND_STEP_PLACE_AUCTIONS:
                $nextAuctionToken = Tokens::getNextAuctionToken($player);
                if(isset($nextAuctionToken)) {
                    $args['token'] = $nextAuctionToken->getUiData();
                    $args['pAuctionableSpots'] = GridUtils::listPossibleSpotsForNextAuctionMarker($nextAuctionToken);
                    $args['possibleActions'][] = 'actPlaceAuction';
                }
                break;
            case ROUND_STEP_TILE_AUCTIONS: 
                $currentBid = Globals::getCurrentBid();
                $auctionToken = Tokens::getCurrentAuctionToken();
                $args['token'] = $auctionToken->getUiData();
                $currentAuctioneer = $auctionToken->getPId();
                $args['bid'] = $currentBid;
                $args['currentMoney'] = $player->getMoney();
                $args['minBid'] = min(1, $args['currentMoney'] - 1);
                $args['maxBid'] = max(0, $args['currentMoney'] - $currentBid);
                $args['bidRebate'] = 0;
                $canPass = true;

                if($currentAuctioneer == $player->getId()){
                    if($currentBid == 0){
                        //auctioneer will bid 0 and win the auction
                        $args['minBid'] = 0;
                        $args['maxBid'] = 0;
                        $canPass = false;
                    }
                    else {
                        $args['bidRebate'] = 1;
                        $minMoneyForAuctioneer = $currentBid - $args['bidRebate'];
                        if($args['currentMoney'] >= $minMoneyForAuctioneer) {
                            $args['minBid'] = $minMoneyForAuctioneer - $currentBid;
                            $args['maxBid'] = $args['minBid'];
                        }
                    }
                }


                if($args['minBid'] + $args['bidRebate'] >= 0 
                && $args['maxBid'] >= $args['minBid'] 
                ){
                    $args['possibleActions'][] = 'actBid';
                }
                if($canPass){
                    $args['possibleActions'][] = 'actPassBid';
                }
                break;
            case ROUND_STEP_PLAYER_ACTIONS:
                //TODO JSA REMOVE DEBUG when real actions ready
                $args['possibleActions'][] = 'actPassBid';
                break;
            default : break;
        }

        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }    

    /**
     * Player action in round step 1
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actPlaceAuction(int $row, int $col, int $activePlayerId, array $args)
    {
        // check input values
        $pAuctionableSpots = $args['pAuctionableSpots'];
        if (!in_array(['row'=>$row,'col'=>$col], $pAuctionableSpots)) {
            throw new UnexpectedException(101,"Invalid auction $row,$col");
        }
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic 
        $auctionType =  $args['token']['type'];
        if($auctionType == TOKEN_TYPE_AUCTION_MARKER_1){
            //add first auction token when needed
            $token1 = Tokens::placeFirstAuctionMarker($player,$row,$col);
            Notifications::auctionPlaced( $player, $row, $col, $token1 );
        }
        $token = Tokens::placeAuction($player,$row,$col, $auctionType);
        Notifications::auctionPlaced( $player, $row, $col, $token );

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }

    
    /**
     * Player action in round step 2
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actBid(int $n, int $activePlayerId, array $args)
    {
        // check input values
        $minBid = $args['minBid'];
        $maxBid = $args['maxBid'];
        if ( $n < $minBid) {
            throw new UnexpectedException(102,"Invalid bid $n, min is $minBid");
        }
        if ( $n > $maxBid) {
            throw new UnexpectedException(102,"Invalid bid $n, max is $maxBid");
        }
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic 
        $currentBid = $args['bid'];
        $nextBid = $currentBid + $n;
        Globals::setCurrentBid($nextBid);
        Globals::setHighBidPlayer($activePlayerId);
        if($nextBid > $currentBid){
            Notifications::newBid($player, $currentBid, $nextBid);
        }

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }

    /**
     * Player action : pass the bid
     */
    #[PossibleAction]
    public function actPassBid(int $activePlayerId)
    {
        Log::addStep();
        $player = Players::get($activePlayerId);
        Notifications::passBid($player);

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     * 
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, 
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     */
    function zombie(int $playerId) {
        // Example of zombie level 0: return NextPlayer::class; or $this->actPass($playerId);

        // Example of zombie level 1:
        $args = $this->getArgs();
        $zombieChoice = $this->getRandomZombieChoice($args['pAuctionableSpots']); // random choice over possible moves
        return $this->actPlaceAuction($zombieChoice['row'],$zombieChoice['col'], $playerId, $args); // this function will return the transition to the next state
    }
}