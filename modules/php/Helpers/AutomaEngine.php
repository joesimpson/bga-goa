<?php
namespace Bga\Games\Goa\Helpers;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Managers\AutomaCards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tokens;
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
        Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : roundStep $roundStep");

        //TODO JSA AUTOMA ENGINE RULES
        
        switch($roundStep){
            case ROUND_STEP_PLACE_AUCTIONS:
                $nextAuctionToken = Tokens::getNextAuctionToken(Players::automaPlayer());

                $auctionCard = AutomaCards::giveAuctionCardToAutoma($nextAuctionToken);
                //TODO JSA CHECKPOINT

                Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : play auction card ".json_encode($auctionCard)." with token ".json_encode($nextAuctionToken));
                $auctionCard->play($nextAuctionToken);

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