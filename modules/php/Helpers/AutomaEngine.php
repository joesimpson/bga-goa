<?php
namespace Bga\Games\Goa\Helpers;

use Bga\GameFramework\VisibleSystemException;
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

        switch($roundStep){
            case ROUND_STEP_PLACE_AUCTIONS:
                $nextAuctionToken = Tokens::getNextAuctionToken(Players::automaPlayer());
                $auctionCard = AutomaCards::giveAuctionCardToAutoma($nextAuctionToken);
                Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : play auction card ".json_encode($auctionCard)." with token ".json_encode($nextAuctionToken));
                $auctionCard->play($nextAuctionToken);
                break;
            case ROUND_STEP_TILE_AUCTIONS:
                $auctionToken = Tokens::getCurrentAuctionToken();
                $currentAuctioneer = $auctionToken->getPId();
                if($currentAuctioneer == AUTOMA_PLAYER_ID){
                    // don't draw if automa is auctioneer
                    $automaAuctionCardLocation = AutomaCards::getAuctionBoardForTokenType($auctionToken->getType());
                    $auctionCard = AutomaCards::getTopOf($automaAuctionCardLocation->value);
                }
                else {
                    $auctionCard = AutomaCards::giveAuctionCardToAutoma($auctionToken);
                }
                $auctionCard->bid($auctionToken);
                break;
            case ROUND_STEP_PLAYER_ACTIONS: 
                $player = Players::automaPlayer();
                if($player->getNbActions() >= $player->getMinRoundActions()) {
                    //AVOID Unexpected INFINITE LOOP of automa actions
                    Game::get()->trace(__CLASS__.'.'.__FUNCTION__."() : Automata is taking too many actions on her turn : {$player->getNbActions()}");
                    throw new VisibleSystemException("Automata is taking too many actions on her turn : {$player->getNbActions()}");
                }
                $player->incNbActions();
                $player->setActionPlayed(true);
                Notifications::automaPlayAction($player);
                $actionCard = AutomaCards::giveActionCardToAutoma();
                $actionCard->play($player);
                break;
            default : 
                break;
        }

    }
}