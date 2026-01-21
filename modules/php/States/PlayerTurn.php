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
                $args['bid'] = $currentBid;
                $args['increaseAmounts'] = [1,2,3,4,5]; //TODO JSA COMPUTE
                $args['possibleActions'][] = 'actBid';
                $args['possibleActions'][] = 'actPassAuction';
                break;
            case ROUND_STEP_PLAYER_ACTIONS:
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
        $increaseAmounts = $args['increaseAmounts'];
        if (!in_array($n, $increaseAmounts)) {
            throw new UnexpectedException(102,"Invalid bid $n");
        }
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic 
        $currentBid = $args['bid'];
        $nextBid = $currentBid + $n;
        Globals::setCurrentBid($nextBid);
        Notifications::newBid($player, $currentBid, $nextBid);

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }

    /**
     * Player action, example content.
     *
     * In this scenario, each time a player pass, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     */
    #[PossibleAction]
    public function actPass(int $activePlayerId)
    {
        Log::addStep();
        // Notify all players about the choice to pass.
        $this->notify->all("pass", clienttranslate('${player_name} passes'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->game->getPlayerNameById($activePlayerId), // remove this line if you uncomment notification decorator
        ]);

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