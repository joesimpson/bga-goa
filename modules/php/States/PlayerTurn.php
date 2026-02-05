<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Exceptions\PrematureStateChangeException;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;

class PlayerTurn extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: Utils::getStateIdFromClass(self::class),
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
                $args['nbActions'] = $player->getNbActions();
                $args['minActions'] = NB_MIN_ACTIONS_PER_ROUND + max(0,$player->getCompasses()-1);
                if($player->getNbActions() <NB_MIN_ACTIONS_PER_ROUND || $player->getCompasses()>0){
                    $args['possibleActions'][] = 'actProgress';
                    $args['progressTracks'] = Tokens::getUpgradableProgressMarkers($player);
                    $args['possibleActions'][] = 'actBuildShips';
                    $args['possibleActions'][] = 'actTaxes';
                    $args['possibleActions'][] = 'actFoundOutpost';
                    $outpostMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, $player->getId());
                    $args['playerBaseContracts'] = PROGRESS_GAINS_OUTPOST[$outpostMarker->getRow() - 1];
                    $args['outpostsToFound'] = Tiles::getFoundableOutposts($player)->getIds();
                    $args['nbCardsExploration'] = Cards::maxExplorationToDraw($player);
                    $args['possibleActions'][] = 'actExploration';
                }
                //if($player->getNbActions() >= $args['minActions'] ) {
                if($player->getNbActions() >= NB_MIN_ACTIONS_PER_ROUND && $player->getCompasses()<=1){
                    //Game rule : player must keep max 1 compass card
                    $args['possibleActions'][] = 'actPassAction';
                }
                break;
            default : break;
        }
        self::addArgsForCardPlay($player,$args);
        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }    
    
    public function onEnteringState(int $activePlayerId, array $args) {
        $this->game->trace("PlayerTurn.onEnteringState()");

        $player = Players::get($activePlayerId);
        $possibleActions = $args['possibleActions'];
        if($player->isPassed() 
            || 
            count($possibleActions)==1 && in_array('actPassAction',$possibleActions) && $player->getCompasses() == 0
        ) {
            //AUTO PASS when no more to play
            //return $this->actPassAction($activePlayerId,$args);
            //COMMENTED because we should not come here
        }
    }

    /**
     * Player action in round step 1
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actPlaceAuction(int $row, int $col, int $activePlayerId, array $args)
    {
        $this->game->trace("actPlaceAuction($row,$col,$activePlayerId)");
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
        $this->game->trace("actBid($n,$activePlayerId)");
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
        $this->game->trace("actPassBid($activePlayerId)");
        Log::addStep();
        $player = Players::get($activePlayerId);
        $player->setPassed(true);
        Notifications::passBid($player);

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }
    
    /**
     * Player action : pass the bid
     */
    #[PossibleAction]
    public function actPassAction(int $activePlayerId, array $args)
    {
        $this->game->trace("actPassAction($activePlayerId)");
        
        $possibleActions = $args['possibleActions'];
        if(!in_array('actPassAction',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actPassAction, see ".json_encode($possibleActions));
        }

        Log::addStep();
        $player = Players::get($activePlayerId);
        $player->setPassed(true);
        Notifications::passAction($player);

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }

    /**
     * Player action : turn ACTION #1 PROGRESS
     */
    #[PossibleAction]
    public function actProgress(int $track, #[IntArrayParam] array|null $t_ids = null, int|null $card_id = null, int $activePlayerId, array $args)
    { 
        $this->game->trace("actProgress($track,$card_id,$activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actProgress',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actProgress, see ".json_encode($possibleActions));
        }
        $progressTracks = $args['progressTracks'];
        $selectedTrack = null;
        if(isset($card_id)){
            $possibleCards = $args['possiblePlayCards'];
            if(!in_array($card_id,$possibleCards->getIds())){
                throw new UnexpectedException(111,"Invalid card $card_id to progress on ($track) ");
            }
            $playCardDatas = $possibleCards[$card_id]['play_datas'];
            $progressTracks = $playCardDatas['progressTracks'];
        }
        foreach($progressTracks as $progressTrack){
            if($progressTrack['track'] == $track){
                $selectedTrack = $progressTrack;
            }
        }
        if(!isset($selectedTrack)){
            throw new UnexpectedException(111,"Invalid track to progress on ($track), see ".json_encode($progressTracks));
        }
        $player = Players::get($activePlayerId);
        Log::addStep();

        $countAsAPlayerAction = true;
        $withCoins = $selectedTrack['withCoins'];
        $withShips = $selectedTrack['withShips'];
        $withSpices = $selectedTrack['withSpices'];
        if(isset($card_id)){
            Cards::playExploration($player, $card_id, );
            Players::registerCardPlayed($player,$card_id);
        }
        if($withSpices){
            $costSpices = $selectedTrack['costSpices'];
            $totalSpicesExpected = 0;
            foreach($costSpices as $datas){
                $costSpices = $datas['n'];
                $totalSpicesExpected+= $costSpices;
            }
            if (!isset($t_ids) || $totalSpicesExpected != count($t_ids)) {
                throw new UnexpectedException(120,"You must select $totalSpicesExpected tokens");
            }
            $foundMatch = false;
            $possibleTokensIdsArrays = $selectedTrack['spicesSets'];
            foreach($possibleTokensIdsArrays as $possibleTokensIdsArray){
                $diff = array_diff($t_ids, $possibleTokensIdsArray);
                if (count($diff) == 0){
                    $foundMatch = true;
                    break;
                }
            }
            if (!$foundMatch) {
                throw new UnexpectedException(121,("These tokens are not a valid set"));
            }

            $playerSpices = Tokens::getPlayerSpices($player->getId());
            $tokens = $playerSpices->filter(function($t) use ($t_ids){
                return in_array($t->getId(), $t_ids);
            });

            Tokens::removeSpices($tokens, $player);
        }
        if($withShips){
            $costShips = $selectedTrack['costShips'];
            Players::giveShips($player,-$costShips);
        }
        if($withCoins){
            $cost = $selectedTrack['costMoney'];
            Players::giveMoney($player,-$cost);
            $countAsAPlayerAction = false;//linked to player card
        }

        $upgradedToken = Tokens::progressOnTrack($track, $activePlayerId);
        
        $allMarkers = Tokens::getProgressMarkers($activePlayerId);
        //BONUSES IF ALL tokens are >= this token row : draw 1 compass card 
        $nbTokensToUpgradeBeforeExtraCompass = $allMarkers->filter(function ($token) use ($upgradedToken) {
                return $token->getRow() < $upgradedToken->getRow();
            })->count();
        if($nbTokensToUpgradeBeforeExtraCompass == 0) {
            Players::giveExtraActionCards($player,1);
        }

        //BONUSES the first time of the FIRST PLAYER ON LINE 4 or 5 : draw 1 exploration card 
        if($upgradedToken->getRow() >=4 ){
            $nbTokensBelowRow = Tokens::countProgressMarkersBelowRow($upgradedToken->getRow());
            if($nbTokensBelowRow == 1) //consider =1 because the token has already moved down
            {
                Cards::drawExplorationCardsToPlayer($player,1);
                Log::addStateCheckpoint(ConfirmTurn::class);
            }
        }
        
        if($countAsAPlayerAction){
            Players::registerNewAction($player);
            return ConfirmTurn::class;
        }
        return PlayerTurn::class;

    }
    /**
     * Player action : turn ACTION #2 take ships
     */
    #[PossibleAction]
    public function actBuildShips(int $activePlayerId, array $args)
    { 
        $this->game->trace("actBuildShips($activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actBuildShips',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actBuildShips, see ".json_encode($possibleActions));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        $progressMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_SHIPS,$activePlayerId);
        $amount = PROGRESS_GAINS_SHIPS[$progressMarker->getRow() - 1];
        Players::giveShips($player, $amount);
        
        Players::registerNewAction($player);

        return ConfirmTurn::class;
    }
    /**
     * Player action : turn ACTION #4 take taxes
     */
    #[PossibleAction]
    public function actTaxes(int $activePlayerId, array $args)
    { 
        $this->game->trace("actTaxes($activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actTaxes',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actTaxes, see ".json_encode($possibleActions));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        $taxesMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_TAXES,$activePlayerId);
        $amount = PROGRESS_GAINS_TAXES[$taxesMarker->getRow() - 1];
        Players::giveMoney($player, $amount);
        
        Players::registerNewAction($player);

        return ConfirmTurn::class;
    }
    
    /**
     * Player action : turn ACTION #5 explore : draw some cards
     */
    #[PossibleAction]
    public function actExploration(int $activePlayerId, array $args)
    { 
        $this->game->trace("actExploration($activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actExploration',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actExploration, see ".json_encode($possibleActions));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        $amount = $args['nbCardsExploration'];
        Cards::drawExplorationCardsToPlayer($player,$amount);
        
        Players::registerNewAction($player);

        Log::addStateCheckpoint(ConfirmTurn::class);
        return ConfirmTurn::class;
    }
    /**
     * Player action : turn ACTION #6 try to found an outpost
     */
    #[PossibleAction]
    public function actFoundOutpost(int $tile_id, int|null $card_id = null,int $activePlayerId, array $args)
    { 
        $this->game->trace("actFoundOutpost($activePlayerId, $tile_id, $card_id)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actFoundOutpost',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actFoundOutpost, see ".json_encode($possibleActions));
        }
        
        $nbCardsToDraw = 2;
        $outpostsToFound =  $args['outpostsToFound'];
        if(isset($card_id)){
            $possibleCards = $args['possiblePlayCards'];
            if(!in_array($card_id,$possibleCards->getIds())){
                throw new UnexpectedException(111,"Invalid card $card_id to play now ");
            }
            $playCardDatas = $possibleCards[$card_id]['play_datas'];
            $outpostsToFound = $playCardDatas['outpostsToFound'];
            $nbCardsToDraw = $nbCardsToDraw + $playCardDatas['extraDraw'];
        }
        if(!in_array($tile_id,$outpostsToFound)){
            throw new UnexpectedException(111,"Invalid outpost $tile_id, see ".json_encode($outpostsToFound));
        }
        
        Log::addStep();
        $player = Players::get($activePlayerId);
        
        if(isset($card_id)){
            Cards::playExploration($player, $card_id, );
            Players::registerCardPlayed($player,$card_id);
        }

        $tile = Tiles::get($tile_id);
        $nbNeededContracts = Utils::getNbContractsForColony($tile->getColony());
        Notifications::tryFoundOutpost($player, $tile, $nbNeededContracts);
        $cards = Cards::drawAndDiscardExploration($player, $nbCardsToDraw);
        $base = $args['playerBaseContracts'];
        $sum = 0;
        foreach($cards as $card){
            $sum += $card->getContracts();
        }
        Notifications::resultFoundOutpost($player, $sum);
        $sum+= $base;
        $missingContracts = max(0,$nbNeededContracts - $sum);
        $canSpendContracts = $player->getContracts() >= $missingContracts;

        $playerChoices = [];
        if($missingContracts == 0 || $canSpendContracts){
            $playerChoices[] = ACTION_FOUND_FORWARD;
        }
        $playerChoices[] = ACTION_FOUND_ABANDON;//always possible

        Globals::addChoiceTile($tile,CHOICE_FOUND_TILE, $missingContracts, $playerChoices, CHOICE_FOUND_TILE);
        
        Players::registerNewAction($player);
        
        //CHECKPOINT because draw cards
        Log::addStateCheckpoint(PlayerChoice::class);
        return PlayerChoice::class;
    }
    
    
    /**
     * Player action : discard some card in hand
     */
    #[PossibleAction]
    public function actDiscard(int $card_id, int $activePlayerId, array $args)
    { 
        PlayerTurn::actDiscard_content($card_id, $activePlayerId, $args);
        return PlayerTurn::class;
    }

    /**Reusable action content */
    static function actDiscard_content(int $card_id, int $activePlayerId, array $args)
    { 
        Game::get()->trace("actDiscard_content($card_id,$activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actDiscard',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actDiscard, see ".json_encode($possibleActions));
        }
        $possibleDiscards = $args['possibleDiscards'];
        if(!in_array($card_id,$possibleDiscards)){
            throw new UnexpectedException(130,"Invalid card $card_id, see ".json_encode($possibleDiscards));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        Cards::discardExploration($player, $card_id, );
    }
    
    /**
     * Player action : Play some card in hand
     */
    #[PossibleAction]
    public function actPlayCard(int $card_id, int $activePlayerId, array $args)
    { 
        PlayerTurn::actPlayCard_content($card_id, $activePlayerId, $args);
        return PlayerTurn::class;
    }
    
    /**Reusable action content */
    static function actPlayCard_content(int $card_id, int $activePlayerId, array $args)
    { 
        Game::get()->trace("actPlayCard_content($card_id,$activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actPlayCard',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actPlayCard, see ".json_encode($possibleActions));
        }
        $possibleCards = $args['possiblePlayCards'];
        $possibleCardsIds = $possibleCards->getIds();
        if(!in_array($card_id,$possibleCardsIds)){
            throw new UnexpectedException(130,"Invalid card $card_id, see ".json_encode($possibleCardsIds));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        Cards::playExploration($player, $card_id, );
        //keep trace of played card per players
        Players::registerCardPlayed($player,$card_id);
    }

    /**
     * Player action : undo ALL
     *
     * @throws UnexpectedException
     */
    #[PossibleAction]
    public function actRestart()
    {
        ConfirmTurn::processRestartTurn();
    }
    
    /**
     * Player action : undo last step
     *
     * @throws UnexpectedException
     */
    #[PossibleAction]
    public function actUndoLastStep()
    {
        ConfirmTurn::processUndoLastStep();
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

    // -----------------------------

    public static function addArgsForCardPlay($player, array &$args){
        $roundStep = Globals::getRoundStep();
        
        switch($roundStep){
            case ROUND_STEP_PLAYER_ACTIONS:
                $discardableCards = Cards::getPossibleToDiscard($player);
                $args['possibleDiscards'] = $discardableCards->getIds();
                $playableCards = Cards::getPossibleToPlay($player, $args);
                $args['possiblePlayCards'] = $playableCards;
                if( $discardableCards->count()>0){
                    $args['possibleActions'][] = 'actDiscard';
                }
                if( $playableCards->count()>0){
                    $args['possibleActions'][] = 'actPlayCard';
                }
                break;
            default: return;
        }

    }
}