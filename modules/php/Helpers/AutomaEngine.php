<?php
namespace Bga\Games\Goa\Helpers;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Managers\AutomaCards;
use Bga\Games\Goa\Models\CardLocation;

/**
 * Define rules for the automa turns
 */
class AutomaEngine {

    function __construct(
        protected Game $game,
    ) {

    }

    public function playTurn(int $roundStep) {
        //TODO JSA AUTOMA ENGINE RULES
        
        switch($roundStep){
            case ROUND_STEP_PLACE_AUCTIONS:
                //TODO JSA Token 2,3
                AutomaCards::giveAuctionCardToAutoma();

                Notifications::message("TODO AUTOMA ENGINE STEP 1");
                break;
            case ROUND_STEP_TILE_AUCTIONS:
                Notifications::message("TODO AUTOMA ENGINE STEP 2");
                break;
            case ROUND_STEP_PLAYER_ACTIONS: 
                Notifications::message("TODO AUTOMA ENGINE STEP 3");
                break;
            default : 
                Notifications::message("TODO AUTOMA ENGINE RULES");
                break;
        }

    }
}