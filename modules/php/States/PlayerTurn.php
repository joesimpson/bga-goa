<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Exceptions\PrematureStateChangeException;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Effects;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\Models\Player;

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
                    Utils::addPossibleActionInArgs('actPlaceAuction', $args);
                }
                break;
            case ROUND_STEP_TILE_AUCTIONS: 
                $currentBid = Globals::getCurrentBid();
                $auctionToken = Tokens::getCurrentAuctionToken();
                $args['token'] = $auctionToken->getUiData();
                $currentAuctioneer = $auctionToken->getPId();
                $args['bid'] = $currentBid;
                $args['currentMoney'] = $player->getMoney();
                $args['minBid'] = min(1, $args['currentMoney'] - $currentBid);
                $args['maxBid'] = max(0, $args['currentMoney'] - $currentBid);
                $args['bidRebate'] = 0;
                $canPass = true;

                $automaActive = Globals::isAutomaActive();
                $currentAuctioneerActive = isset($currentAuctioneer) 
                    &&( ($currentAuctioneer == $player->getId() && !$automaActive ) 
                        // !\ $activePlayerId is set to a real player even during automa turn /!\->  should not happen here...
                        );
                if($currentAuctioneerActive){
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
                    Utils::addPossibleActionInArgs('actBid', $args);
                }
                if($canPass){
                    Utils::addPossibleActionInArgs('actPassBid', $args);
                }
                break;
            case ROUND_STEP_PLAYER_ACTIONS:
                $actionPlayed = $player->isActionPlayed();
                $args['actionPlayed'] = $actionPlayed;
                $args['nbActions'] = $player->getNbActions();
                $args['minActions'] = NB_MIN_ACTIONS_PER_ROUND + max(0,$player->getCompasses()-1);
                if(! $actionPlayed) {
                    if($player->getNbActions() <NB_MIN_ACTIONS_PER_ROUND || $player->getCompasses()>0){
                        Utils::addPossibleActionInArgs('actProgress', $args);
                        $args['progressTracks'] = Tokens::getUpgradableProgressMarkers($player);
                        Utils::addPossibleActionInArgs('actBuildShips', $args);
                        $args['harvestSpots'] = Tiles::getPlayerAvailableSpotsForSpices($player);
                        if(count($args['harvestSpots'])>0) {
                            Utils::addPossibleActionInArgs('actHarvest', $args);
                            $args['playerBaseHarvest'] = Players::getHarvestBaseGain($player);
                        }
                        Utils::addPossibleActionInArgs('actTaxes', $args);
                        Utils::addPossibleActionInArgs('actFoundOutpost', $args);
                        $args['playerBaseContracts'] = Players::getFoundBaseContracts($player);
                        $args['outpostsToFound'] = Tiles::getFoundableOutposts($player)->getIds();
                        $args['nbCardsExploration'] = Cards::maxExplorationToDraw($player);
                        Utils::addPossibleActionInArgs('actExploration', $args);
                    }
                    //if($player->getNbActions() >= $args['minActions'] ) {
                    if($player->getNbActions() >= NB_MIN_ACTIONS_PER_ROUND && $player->getCompasses()<=1){
                        //Game rule : player must keep max 1 compass card
                        Utils::addPossibleActionInArgs('actPassAction', $args);
                    }
                } else {
                    Utils::addPossibleActionInArgs('actStopAction', $args);
                }
                break;
            default : break;
        }
        self::addArgsForCardPlay($player,$args);
        self::addArgsForTilePlay($player,$args);
        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }    
    
    public function onEnteringState(int $activePlayerId, array $args) {
        $automaActive = Globals::isAutomaActive();
        $this->game->trace("PlayerTurn.onEnteringState($activePlayerId, $automaActive)");

        $player = Players::get($activePlayerId);
        $actionPlayed = $player->isActionPlayed();
        $possibleActions = $args['possibleActions'];
        if($player->isPassed() 
            || 
            count($possibleActions)==1 && in_array('actPassAction',$possibleActions) && $player->getCompasses() == 0
        ) {
            //AUTO PASS when no more to play
            //return $this->actPassAction($activePlayerId,$args);
            //COMMENTED because we should not come here, we only activate players with something to play
        }
        if($actionPlayed){
            if(in_array('actStopAction',$possibleActions)){
                //AUTO STOP if no tile/card action (absence of actPlayCard/actPlayTile), but we may fin the action actDiscard anyway
                if(!in_array('actPlayCard',$possibleActions) && !in_array('actPlayTile',$possibleActions) ) {
                    $this->game->trace(__CLASS__.'.'.__FUNCTION__."() : AUTOMATIC Stop action");
                    return $this->actStopAction(Utils::gameVersion(), $activePlayerId, $args, true);
                }
            }
        }
        if(in_array('actBid',$possibleActions) && !in_array('actPassBid',$possibleActions)){
            if($args['minBid'] == $args['maxBid'] && $args['maxBid'] == 0){
                //AUTO BID MIN BID if we cannot pass (because it is linked to "auto win tile")
                $this->game->trace(__CLASS__.'.'.__FUNCTION__."() : AUTOMATIC bid 0");
                return $this->actBid($args['minBid'], $activePlayerId, $args, Utils::gameVersion(),true);
            }
        }
    }

    /**
     * Player action in round step 1
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actPlaceAuction(
        int $row, 
        int $col, 
        int $activePlayerId, array $args,
        int $v,
        )
    {
        Utils::checkVersion($v);
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
        GridUtils::placeAuction($player,  $row, $col, $auctionType);

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }

    
    /**
     * Player action in round step 2
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actBid(
        int $n, 
        int $activePlayerId, array $args,
        int $v,
        bool $auto = false,
        )
    {
        Utils::checkVersion($v);
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
        $possibleActions = $args['possibleActions'];
        if(!in_array('actBid',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actBid");
        }
        
        if(!$auto) Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic 
        $currentBid = $args['bid'];
        $nextBid = $currentBid + $n;
        Globals::setCurrentBid($nextBid);
        Globals::setHighBidPlayer($activePlayerId);
        Notifications::newBid($player, $currentBid, $nextBid);

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }

    /**
     * Player action : pass the bid
     */
    #[PossibleAction]
    public function actPassBid(int $v,int $activePlayerId)
    {
        Utils::checkVersion($v);
        $this->game->trace("actPassBid($activePlayerId)");
        Log::addStep();
        $player = Players::get($activePlayerId);
        $player->setPassed(true);
        $auctionToken = Tokens::getCurrentAuctionToken();
        Notifications::passBid($player, $auctionToken);

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }
    
    /**
     * Player action : pass the action turn
     */
    #[PossibleAction]
    public function actPassAction(int $v,int $activePlayerId, array $args)
    {
        Utils::checkVersion($v);
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
     * Player action : stop the action turn
     */
    #[PossibleAction]
    public function actStopAction(int $v,int $activePlayerId, array $args, bool $auto = false,)
    {
        Utils::checkVersion($v);
        $this->game->trace("actStopAction($activePlayerId)");
        
        $possibleActions = $args['possibleActions'];
        if(!in_array('actStopAction',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actStopAction, see ".json_encode($possibleActions));
        }

        //don't save step if automatic action, else the player won't be able to reverse the 2 steps at a time
        if(!$auto) Log::addStep();

        return ConfirmTurn::class;
    }

    /**
     * Player action : turn ACTION #1 PROGRESS
     */
    #[PossibleAction]
    public function actProgress(
        int $track, 
        #[IntArrayParam] array|null $t_ids, 
        int|null $card_id, 
        int|null $tile_id, 
        int $activePlayerId, array $args,
        int $v,
    )
    { 
        Utils::checkVersion($v);
        $this->game->trace("PlayerTurn.actProgress($track,$card_id,$tile_id,$activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actProgress',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actProgress, see ".json_encode($possibleActions));
        }

        Log::addStep();
        $doCheckpoint = false;
        PlayerTurn::actProgress_Content(
            $track, 
            $t_ids, 
            $card_id, 
            $tile_id, 
            $activePlayerId, 
            $args,
            $doCheckpoint,
        );

        if($doCheckpoint) Log::addStateCheckpoint(self::class);
        return self::class;
    }
    
    public static function actProgress_Content(
        int $track, 
        array|null $t_ids, 
        int|null $card_id, 
        int|null $tile_id, 
        int $activePlayerId, array $args,
        bool &$doCheckpoint, 
    )
    { 
        if(isset($args['progressTracks'])) $progressTracks = $args['progressTracks'];
        $selectedTrack = null;
        $countAsAPlayerAction = true;
        if(isset($card_id)){
            $possibleCards = $args['possiblePlayCards'];
            if(!in_array($card_id,$possibleCards->getIds())){
                throw new UnexpectedException(111,"Invalid card $card_id to progress on ($track) ");
            }
            $playCardDatas = $possibleCards[$card_id]['play_datas'];
            $progressTracks = $playCardDatas['progressTracks'];
        }
        else if(isset($tile_id)){
            $possibleTiles = $args['possiblePlayTiles'];
            if(!in_array($tile_id,$possibleTiles->getIds())){
                throw new UnexpectedException(111,"Invalid tile $tile_id to play");
            }
            $playDatas = $possibleTiles[$tile_id]['play_datas'];
            if(!isset($playDatas['progressTracks'])){
                throw new UnexpectedException(112,"Invalid tile $tile_id to progress");
            } 
            $progressTracks = $playDatas['progressTracks'];
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

        $withCoins = $selectedTrack['withCoins'];
        $withShips = $selectedTrack['withShips'];
        $withSpices = $selectedTrack['withSpices'];
        if(isset($card_id)){
            Cards::playExploration($player, $card_id, $doCheckpoint );
        } else if(isset($tile_id)){
            Tiles::playTile($player, $tile_id, null, $doCheckpoint );
        }
        
        if($withCoins){
            $countAsAPlayerAction = false;//linked to player card/tile
        }
        if($countAsAPlayerAction){
            Players::registerNewAction($player, 'actProgress');
        }

        if($withSpices){
            $costSpices = $selectedTrack['costSpices'];
            $totalSpicesExpected = 0;
            foreach($costSpices as $datas){
                $totalSpicesExpected+= $datas['n'];
            }
            if (!isset($t_ids) || $totalSpicesExpected != count($t_ids)) {
                throw new UnexpectedException(120,"You must select $totalSpicesExpected tokens");
            }
            /* spicesSets is not computed anymore -> use next loop on costSpices instead
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
            */
            foreach($costSpices as $spice => $datas){
                $expectedOfThisType = $datas['n'];
                $foundSpicesOfThisType = array_intersect($t_ids, $datas['from']);
                $foundSpicesOfThisTypeV2 = array_intersect( $datas['from'], $t_ids,);
                if (count($foundSpicesOfThisType) != $expectedOfThisType || count($foundSpicesOfThisTypeV2) != $expectedOfThisType){
                    throw new UnexpectedException(121,("These tokens are not a valid set: you must select $expectedOfThisType of $spice"));
                }
            }
            Game::get()->trace("PlayerTurn.actProgress_content($track,$card_id,$tile_id,$activePlayerId) valid set : ".json_encode($t_ids). " according to costSpices ".json_encode($costSpices));

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
        }
        
        $upgradedToken = Tokens::progressOnTrack($track, $activePlayerId);
        
        $allMarkers = Tokens::getProgressMarkers($activePlayerId);
        //BONUSES IF ALL tokens are >= this token row : draw 1 compass card 
        $nbTokensToUpgradeBeforeExtraCompass = $allMarkers->filter(function ($token) use ($upgradedToken) {
                return $token->getRow() < $upgradedToken->getRow();
            })->count();
        if($nbTokensToUpgradeBeforeExtraCompass == 0) {
            Notifications::progressBonusAllColumns($player, $upgradedToken->getRow());
            Players::giveExtraActionCards($player,1);
        }

        //BONUSES the first time of the FIRST PLAYER ON LINE 4 or 5 : draw 1 exploration card 
        if($upgradedToken->getRow() >=4 ){
            $nbTokensBelowRow = Tokens::countProgressMarkersBelowRow($upgradedToken->getRow());
            if($nbTokensBelowRow == 1) //consider =1 because the token has already moved down
            {
                Notifications::progressBonusFirst($player, $upgradedToken->getRow());
                Cards::drawExplorationCardsToPlayer($player,1, $doCheckpoint);
            }
        }

    }
    /**
     * Player action : turn ACTION #2 take ships
     */
    #[PossibleAction]
    public function actBuildShips(
        int $activePlayerId, array $args,
        int $v,
        )
    { 
        Utils::checkVersion($v);
        $this->game->trace("actBuildShips($activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actBuildShips',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actBuildShips, see ".json_encode($possibleActions));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        Players::registerNewAction($player,'actBuildShips');

        PlayerTurn::actBuildShips_content($player,$activePlayerId);

        return PlayerTurn::class;
    }

    public static function actBuildShips_content(Player $player, int $progressPlayerId): void{
        $progressMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_SHIPS,$progressPlayerId);
        $amount = PROGRESS_GAINS_SHIPS[$progressMarker->getRow() - 1];
        Players::giveShips($player, $amount);
    }
    
    /**
     * Player action : turn ACTION #3 Harvest 
     */
    #[PossibleAction]
    public function actHarvest(
        #[IntArrayParam] array $spots, 
        #[IntArrayParam(name:'res')] array $resources, 
        int|null $card_id, 
        int|null $tile_id, 
        int $ships, 
        int $contracts, 
        int $activePlayerId, array $args,
        int $v,
    )
    { 
        Utils::checkVersion($v);
        Log::addStep();
        $this->game->trace("actHarvest(".json_encode($spots).",".json_encode($resources).",$card_id, $tile_id, $ships, $contracts, $activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actHarvest',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actHarvest, see ".json_encode($possibleActions));
        }
        $player = Players::get($activePlayerId);
        $doCheckpoint = false;
        $nextStateClass = self::class;

        if(isset($card_id)){
            Cards::playExploration($player, $card_id, $doCheckpoint);
        } else if(isset($tile_id)){
            $tile = Tiles::playTile($player, $tile_id, null, $doCheckpoint, $prematureStateChange );
        }

        PlayerTurn::actHarvest_content(
            $spots, 
            $resources, 
            $card_id , 
            $tile_id , 
            $ships, 
            $contracts, 
            $activePlayerId,   
            $activePlayerId, 
            $args,
        );
 
        if(isset($prematureStateChange) ){
            //SHOULD NOT HAPPEN IN this use case
            $nextStateClass = $prematureStateChange->nextStateClass;
        }
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        return $nextStateClass;

    }
    
    public static function actHarvest_content(
        #[IntArrayParam] array $spots, 
        #[IntArrayParam(name:'res')] array $resources, 
        int|null $card_id, 
        int|null $tile_id, 
        int $ships, 
        int $contracts, 
        int $progressRefPlayerId,
        int $activePlayerId, array $args,
        bool $countAsAPlayerAction = true,
    )
    { 
        if(count($spots) !== count($resources)){
            throw new UnexpectedException(110,"Invalid number of resources/spots");
        }
        $uniqueSpots = array_unique($spots);
        if(count($spots) !== count($uniqueSpots)){
            throw new UnexpectedException(110,"Duplicate spots");
        }
        $selectionSize = count($uniqueSpots);
        $harvestSpots = [];
        $min  = 1;
        $max = $min;
        $others = [];
        if(isset($args['harvestSpots'])) $harvestSpots = $args['harvestSpots'];
        if(isset($args['playerBaseHarvest'])) $max = $args['playerBaseHarvest'];
        if(isset($args['playersBaseHarvest'])) $max = $args['playersBaseHarvest'][$progressRefPlayerId];
        if(isset($card_id)){
            $possibleCards = $args['possiblePlayCards'];
            if(!in_array($card_id,$possibleCards->getIds())){
                throw new UnexpectedException(111,"Invalid card $card_id to play");
            }
            $playCardDatas = $possibleCards[$card_id]['play_datas'];
            if(!isset($playCardDatas['harvest'])){
                throw new UnexpectedException(111,"Invalid card $card_id for harvest");
            }
            $harvestDatas = $playCardDatas['harvest'];
            $harvestSpots = $harvestDatas['spots'];
            $countAsAPlayerAction = $harvestDatas['countAction'];
            $min = $harvestDatas['min'];
            $max = $harvestDatas['max'];
            if(isset($harvestDatas['others']) ) $others = $harvestDatas['others'] ;
            
        } else if(isset($tile_id)){
            $possibleTiles = $args['possiblePlayTiles'];
            if(!in_array($tile_id,$possibleTiles->getIds())){
                throw new UnexpectedException(111,"Invalid tile $tile_id to play");
            }
            $playDatas = $possibleTiles[$tile_id]['play_datas'];
            if(!isset($playDatas['harvest'])){
                throw new UnexpectedException(112,"Invalid tile $tile_id to harvest");
            } 
            $harvestDatas = $playDatas['harvest'];
            $harvestSpots = $harvestDatas['spots'];
            $countAsAPlayerAction = $harvestDatas['countAction'];
            if(isset($harvestDatas['min'])) $max = $harvestDatas['min'];
            if(isset($harvestDatas['max'])) $max = $harvestDatas['max'];
            if(isset($harvestDatas['maxTiles'])) $maxTiles = $harvestDatas['maxTiles'];
            if(isset($harvestDatas['others']) ) $others = $harvestDatas['others'] ;
        } 
        if(in_array(ACTION_SHIP,$others)) $selectionSize += $ships;
        if(in_array(ACTION_CONTRACT,$others)) $selectionSize += $contracts;

        $tilesToFill = [];
        foreach($uniqueSpots as $k => $spotId){
            if(!in_array($spotId, array_keys($harvestSpots) )){
                throw new UnexpectedException(111,"Invalid spot $spotId for harvest ");
            }
            $resource = $resources[$k];
            if(!in_array($resource, $harvestSpots[$spotId]['spices'] )){
                throw new UnexpectedException(112,"Invalid resource $resource for spot $spotId for harvest ");
            }
            $spot_tile_id = $harvestSpots[$spotId]['tile_id'];
            if(!in_array($spot_tile_id,$tilesToFill)) $tilesToFill[] = $spot_tile_id;
        }
        //check min < count($uniqueSpots) < max
        if($selectionSize < $min ){
            throw new UnexpectedException(113,"Minimum is $min resources");
        }
        if($selectionSize > $max && !isset($maxTiles)){
            throw new UnexpectedException(113,"Maximum is $max resources");
        }
        if(isset($maxTiles) && (count($tilesToFill) > $maxTiles)) {
            throw new UnexpectedException(113,"Maximum is $maxTiles tiles to fill");
        }

        $player = Players::get($activePlayerId);

        if($countAsAPlayerAction){
            Players::registerNewAction($player,'actHarvest');
        }

        $tokenSpices = [];
        //LOOP TO FILL SPOTS with specified resources
        foreach($uniqueSpots as $k => $spotId){
            $slotPosition = $harvestSpots[$spotId]['index'];
            $spot_tile_id = $harvestSpots[$spotId]['tile_id'];
            $tile = Tiles::get($spot_tile_id);
            $resource = $resources[$k];
            $spiceTokenType = Tokens::getTokenSpiceTypeFromSpice($resource);
            $tokenSpices[] = Tokens::addSpiceOnTile($tile, $player, $spiceTokenType, $slotPosition,false);
        }
        Notifications::newSpices($player, new Collection($tokenSpices));
        if(in_array(ACTION_SHIP,$others)){
            Players::giveShips($player, $ships);
        }
        if(in_array(ACTION_CONTRACT,$others)){
            Players::giveContracts($player, $contracts);
        }
    }
    /**
     * Player action : turn ACTION #4 take taxes
     */
    #[PossibleAction]
    public function actTaxes(int $v,int $activePlayerId, array $args)
    { 
        Utils::checkVersion($v);
        $this->game->trace("actTaxes($activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actTaxes',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actTaxes, see ".json_encode($possibleActions));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        Players::registerNewAction($player,'actTaxes');

        PlayerTurn::actTaxes_content($player,$activePlayerId);

        return PlayerTurn::class;
    }
    
    public static function actTaxes_content(Player $player, int $progressPlayerId): void{
        $taxesMarker = Tokens::getProgressMarkerByType(TOKEN_TYPE_PROGRESS_MARKER_TAXES,$progressPlayerId);
        $amount = PROGRESS_GAINS_TAXES[$taxesMarker->getRow() - 1];
        Players::giveMoney($player, $amount);
    }
    /**
     * Player action : turn ACTION #5 explore : draw some cards
     */
    #[PossibleAction]
    public function actExploration(int $v,int $activePlayerId, array $args)
    { 
        Utils::checkVersion($v);
        $this->game->trace("actExploration($activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actExploration',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actExploration, see ".json_encode($possibleActions));
        }
        Log::addStep();
        $doCheckpoint = false;
        $player = Players::get($activePlayerId);

        Players::registerNewAction($player,'actExploration');

        $amount = $args['nbCardsExploration'];
        Cards::drawExplorationCardsToPlayer($player,$amount, $doCheckpoint);

        //if more cards than max, let's force player to discard or play cards
        $nbMaxToKeep = Cards::maxExplorationToKeep($player);
        $nbCardsInHand = Cards::countInExplorationHand($activePlayerId);
        if($nbCardsInHand > $nbMaxToKeep){
            Notifications::cleanHandStart($player, $nbMaxToKeep);
            Effects::addEffectOnAction($player,ACTION_TYPE_EXP, EFFECT_TYPE_CLEAN_HAND, null, []);

            Log::addStateCheckpoint(PlayerChoice::class);
            return PlayerChoice::class;
        }

        Log::addStateCheckpoint(PlayerTurn::class);
        return PlayerTurn::class;
    }
    /**
     * Player action : turn ACTION #6 try to found an outpost
     */
    #[PossibleAction]
    public function actFoundOutpost(
        int $tile_id, 
        int|null $card_id,
        int $activePlayerId, array $args,
        int $v,
        )
    { 
        Utils::checkVersion($v);
        $this->game->trace("actFoundOutpost($activePlayerId, $tile_id, $card_id)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actFoundOutpost',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actFoundOutpost, see ".json_encode($possibleActions));
        }
        Log::addStep();
        $player = Players::get($activePlayerId);

        Players::registerNewAction($player,'actFoundOutpost');
        
        PlayerTurn::actFoundOutpost_content($tile_id,$card_id, $player, $activePlayerId, $args);

        //CHECKPOINT because draw cards
        Log::addStateCheckpoint(PlayerChoice::class);
        return PlayerChoice::class;
    }
    
    public static function actFoundOutpost_content(
        int $tile_id, 
        int|null $card_id,
        Player $player, 
        int $progressRefPlayerId,
        array $args,
        int|null $fromEffectId = null,
        )
    { 
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
        
        $doCheckpoint = false;
        if(isset($card_id)){
            Cards::playExploration($player, $card_id, $doCheckpoint);
        }

        $tile = Tiles::get($tile_id);
        $nbNeededContracts = Utils::getNbContractsForColony($tile->getColony());
        Notifications::tryFoundOutpost($player, $tile, $nbNeededContracts);
        $cards = Cards::drawAndDiscardExploration($player, $nbCardsToDraw);
        $doCheckpoint = true;
        if(isset($args['playersBaseContracts'])) $base = $args['playersBaseContracts'][$progressRefPlayerId];
        else $base = $args['playerBaseContracts'];
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
        
        Effects::addEffectOnTile($player,$tile_id, EFFECT_TYPE_FOUND_TILE, $missingContracts, $playerChoices, ACTION_TYPE_FOUND, $fromEffectId);
    }
    
    /**
     * Player action : discard some card in hand
     */
    #[PossibleAction]
    public function actDiscard(#[IntArrayParam] array $card_ids, int $v, int $activePlayerId, array $args)
    { 
        Utils::checkVersion($v);
        Log::addStep();
        foreach( $card_ids as $card_id){
            PlayerTurn::actDiscard_content($card_id, $activePlayerId, $args);
        }
        return PlayerTurn::class;
    }

    /**Reusable action content */
    static function actDiscard_content(int $card_id, int $activePlayerId, array $args)
    { 
        Game::get()->trace("actDiscard_content($card_id,$activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actDiscard',$possibleActions) && !in_array('actAnytimeDiscard',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actDiscard, see ".json_encode($possibleActions));
        }
        $possibleDiscards = $args['possibleDiscards'];
        if(!in_array($card_id,$possibleDiscards)){
            throw new UnexpectedException(130,"Invalid card $card_id, see ".json_encode($possibleDiscards));
        }
        $player = Players::get($activePlayerId);

        Cards::discardExploration($player, $card_id, );
    }
    
    /**
     * Player action : Play some card in hand
     */
    #[PossibleAction]
    public function actPlayCard(int $card_id, int $v, int $activePlayerId, array $args)
    { 
        Utils::checkVersion($v);
        $doCheckpoint = false;
        PlayerTurn::actPlayCard_content($card_id, $activePlayerId, $args, $doCheckpoint);
        if($doCheckpoint) Log::addStateCheckpoint(self::class);
        return self::class;
    }
    
    /**Reusable action content */
    public static function actPlayCard_content(
        int $card_id, 
        int $activePlayerId, 
        array $args,
        bool &$doCheckpoint, 
    )
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

        Cards::playExploration($player, $card_id, $doCheckpoint);
    }
    
    /**
     * Player action : Play some tile in hand
     */
    #[PossibleAction]
    public function actPlayTile(
        int $tile_id,
        int|null $row,
        int|null $col,
        int $v, int $activePlayerId, array $args,
        )
    { 
        Utils::checkVersion($v);
        $doCheckpoint = false;
        $nextStateClass = PlayerTurn::class;
        PlayerTurn::actPlayTile_content($tile_id, $row, $col, $activePlayerId, $args, $doCheckpoint,$prematureStateChange);
        if(isset($prematureStateChange) ){
            $nextStateClass = $prematureStateChange->nextStateClass;
        }
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        return $nextStateClass;
    }
    
    /**Reusable action content */
    static function actPlayTile_content(
        int $tile_id, 
        int|null $row, //row to play the tile
        int|null $col, //column to play the tile
        int $activePlayerId, 
        array $args, 
        bool &$doCheckpoint,
        PrematureStateChangeException| null &$stateChange = null, //modify arg  directly instead of throw in order to go until the return value
    )
    { 
        Game::get()->trace("actPlayTile_content($tile_id,$row,$col,$activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actPlayTile',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actPlayTile, see ".json_encode($possibleActions));
        }
        $possibleTiles = $args['possiblePlayTiles'];
        $possibleTilesIds = $possibleTiles->getIds();
        if(!in_array($tile_id,$possibleTilesIds)){
            throw new UnexpectedException(130,"Invalid tile $tile_id, see ".json_encode($possibleTilesIds));
        }
        $playDatas = $possibleTiles[$tile_id]['play_datas'];
        if(!isset($playDatas['market_spots']) && (isset($row) || isset($col))){
            throw new UnexpectedException(131,"Tile is not expected to be placed on the market");
        }
        if(isset($playDatas['market_spots'])){
            $possibleSpots = $playDatas['market_spots'];
            if(!in_array(['row'=>$row,'col'=>$col], $possibleSpots)) {
                throw new UnexpectedException(132,"Invalid market spot [$row, $col] for tile");
            }
        }
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        Tiles::playTile($player, $tile_id, ['row'=>$row, 'col'=>$col], $doCheckpoint, $stateChange, );
    }
    
    /**
     * Optional Action with some cards/tiles
     */
    #[PossibleAction]
    public function actSpendSpices(
        #[IntArrayParam] array $t_ids, 
        int|null $card_id, 
        int|null $tile_id,
        int $activePlayerId, array $args,
        int $v,)
    { 
        Utils::checkVersion($v);

        $doCheckpoint = false;
        $nextStateClass = self::class;
        PlayerTurn::actSpendSpices_content(
            $t_ids, 
            $card_id,
            $tile_id,
            $activePlayerId,
            $args,
            $doCheckpoint,
            $prematureStateChange,
        );
        
        if(isset($prematureStateChange) ){//should not happen in this use case
            $nextStateClass = $prematureStateChange->nextStateClass;
        }
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        return $nextStateClass;
    }
    
    public static function actSpendSpices_content(
        array $t_ids, 
        int|null $card_id, 
        int|null $tile_id,
        int $activePlayerId, array $args,
        bool &$doCheckpoint, 
        PrematureStateChangeException| null &$stateChange = null,
    )
    { 
        Game::get()->trace("actSpendSpices(".json_encode($t_ids)."$card_id,$tile_id,$activePlayerId)");
        $possibleActions = $args['possibleActions'];
        if(!in_array('actSpendSpices',$possibleActions)){
            throw new UnexpectedException(110,"Invalid action actSpendSpices, see ".json_encode($possibleActions));
        }
        $nbMinTokens = 1;
        $totalSpicesExpected = 1;
        $selectableTokens = [];
        $forCoins = false;
        $forScore = 0;
        if(isset($card_id)){
            $possibleCards = $args['possiblePlayCards'];
            if(!in_array($card_id,$possibleCards->getIds())){
                throw new UnexpectedException(111,"Invalid card $card_id to play");
            }
            $playCardDatas = $possibleCards[$card_id]['play_datas'];
            if(!isset($playCardDatas['spendSpices'])){
                throw new UnexpectedException(112,"Invalid card $card_id to spend spices");
            }
            $spendDatas = $playCardDatas['spendSpices'];
            $totalSpicesExpected = $spendDatas['max'];
            $nbMinTokens = $spendDatas['min'];
            $selectableTokens = $spendDatas['t_ids'];
            $forCoins = $spendDatas['forCoins'];
        } else if(isset($tile_id)){
            $possibleTiles = $args['possiblePlayTiles'];
            if(!in_array($tile_id,$possibleTiles->getIds())){
                throw new UnexpectedException(111,"Invalid tile $tile_id to play");
            }
            $playDatas = $possibleTiles[$tile_id]['play_datas'];
            if(!isset($playDatas['spendSpices'])){
                throw new UnexpectedException(112,"Invalid tile $tile_id to spend spices");
            }
            $spendDatas = $playDatas['spendSpices'];
            $totalSpicesExpected = $spendDatas['max'];
            $nbMinTokens = $spendDatas['min'];
            $selectableTokens = $spendDatas['t_ids'];
            $forScore = $spendDatas['forScore'];
        } 
        if (!isset($t_ids) || $totalSpicesExpected < count($t_ids) || $nbMinTokens > count($t_ids)) {
            throw new UnexpectedException(120,"You must select $totalSpicesExpected tokens");
        }
        $diff = array_diff($t_ids, $selectableTokens); 
        if (count($diff) !== 0) {
            throw new UnexpectedException(121,("These tokens are not a valid set"));
        }

        $player = Players::get($activePlayerId);
        Log::addStep();
        $doCheckpoint = false;
        if(isset($card_id)){
            Cards::playExploration($player, $card_id, $doCheckpoint );
        }
        if(isset($tile_id)){
            $tile = Tiles::playTile($player, $tile_id, null, $doCheckpoint, $prematureStateChange);
        }
        $playerSpices = Tokens::getPlayerSpices($player->getId());
        $tokens = $playerSpices->filter(function($t) use ($t_ids){
            return in_array($t->getId(), $t_ids);
        });
        Tokens::removeSpices($tokens, $player);
        if($forCoins){
            $amount = CARD_GAINS_RESSOURCES_TO_COINS[count($t_ids)];
            Players::giveMoney($player, $amount);
        }
        if($forScore>0){
            //We want to keep score unknown until end
            //$player->addPointsWithTile($forScore, $tile);
        }
        
    }

    /**
     * Player action : undo ALL
     *
     * @throws UnexpectedException
     */
    #[PossibleAction]
    public function actRestart(int $v,)
    {
        Utils::checkVersion($v);
        ConfirmTurn::processRestartTurn();
    }
    
    /**
     * Player action : undo last step
     *
     * @throws UnexpectedException
     */
    #[PossibleAction]
    public function actUndoLastStep(int $v,)
    {
        Utils::checkVersion($v);
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
    function zombie(int $playerId, array $args) {
        $gameVersion = Utils::gameVersion();
        $roundStep = Globals::getRoundStep();
        $actions = $args['possibleActions'];
        
        //ZOMBIE LEVEL 1
        switch($roundStep){
            case ROUND_STEP_PLACE_AUCTIONS:
                if(in_array('actPlaceAuction',$actions)){
                    // random choice over possible moves
                    $zombieChoice = $args['pAuctionableSpots'][array_rand($args['pAuctionableSpots'])];
                    return $this->actPlaceAuction($zombieChoice['row'],$zombieChoice['col'], $playerId, $args, $gameVersion);
                }
                break;
            case ROUND_STEP_TILE_AUCTIONS:
                if(in_array('actBid',$actions)){
                    $possibleBids = range($args['minBid'],$args['maxBid']);
                    $zombieChoice = $possibleBids[array_rand($possibleBids)];
                    return $this->actBid($zombieChoice, $playerId, $args, $gameVersion,true);
                }
                else {
                    return $this->actPassBid($gameVersion, $playerId);
                }
            case ROUND_STEP_PLAYER_ACTIONS:
                if(in_array('actStopAction',$actions)){
                    return $this->actStopAction($gameVersion, $playerId, $args, true);
                }
                $badChoice = true;
                $security = 0;
                while($badChoice && $security < 10){
                    $security++;
                    $zombieChoice = $actions[array_rand($actions)];
                    //we could improve zombie with card/tile choices
                    try{
                            
                        switch($zombieChoice){
                            case 'actProgress': 
                                $progressTracks = $args['progressTracks'];
                                if(count($progressTracks) > 0){
                                    $trackData = $progressTracks[array_rand($progressTracks)];
                                    $track = $trackData['track'];
                                    $costSpices = $trackData['costSpices'];
                                    //$spices = $trackData['spicesSets'][array_rand($trackData['spicesSets'])];
                                    $spices = [];
                                    foreach($costSpices as $spice => $costDatas ){
                                        $nbOfThisType = 0;
                                        foreach($costDatas['from'] as $tokenId ){
                                            if($nbOfThisType >= $costDatas['n']) break;
                                            $spices[] = $tokenId;
                                            $nbOfThisType++;
                                        }

                                    }

                                    return $this->actProgress($track,$spices,null,null, $playerId, $args,$gameVersion, );
                                }
                                $badChoice = true;
                                break;
                            case 'actBuildShips': 
                                return $this->actBuildShips( $playerId, $args, $gameVersion,);
                            case 'actHarvest': 
                                $spots = $args['harvestSpots'];
                                if(count($spots) > 0 ){
                                    //Harvest 1 spot
                                    $spot = $spots[array_rand($spots)];
                                    $spots_ids = [$spot['id']];
                                    $spots_resources = [$spot['spices'][0]];
                                    return $this->actHarvest($spots_ids,$spots_resources,null,null, 0,0,$playerId, $args,$gameVersion, );
                                }
                                $badChoice = true;
                                break;
                            case 'actTaxes': 
                                return $this->actTaxes( $gameVersion, $playerId, $args,);
                            case 'actExploration': 
                                return $this->actExploration( $gameVersion, $playerId, $args,);
                            case 'actFoundOutpost': 
                                $outpostsToFound = $args['outpostsToFound'];
                                if(count($outpostsToFound) > 0 ){
                                    $outpostId = $outpostsToFound[array_rand($outpostsToFound)];
                                    return $this->actFoundOutpost($outpostId,null,  $playerId, $args, $gameVersion,);
                                }
                                $badChoice = true;
                                break;
                            default: 
                                $badChoice = true;
                                break;
                        }
                    } catch(UnexpectedException $exc){
                        //If we fall in an edge case where that random choice is not really possible
                        $badChoice = true;
                        Game::get()->trace(__FUNCTION__."($playerId) : UnexpectedException, continue zombie choices. ", json_encode($exc));
                    }
                }
                break;
            default: break;
        }

    }

    // -----------------------------

    public static function addArgsForCardPlay(
        Player $player, 
        array &$args, 
        array|null $actionsWhiteList = null, 
        bool $withDiscard = false,
    ){
        Game::get()->trace("PlayerTurn.addArgsForCardPlay(".json_encode($actionsWhiteList).")");
        $roundStep = Globals::getRoundStep();
        
        switch($roundStep){
            case ROUND_STEP_PLAYER_ACTIONS:
                $discardableCards = Cards::getPossibleToDiscard($player);
                $args['possibleDiscards'] = $discardableCards->getIds();
                $playableCards = Cards::getPossibleToPlay($player, $args,$actionsWhiteList);
                $args['possiblePlayCards'] = $playableCards;
                if( $discardableCards->count()>0){
                    if($withDiscard){
                        Utils::addPossibleActionInArgs('actDiscard', $args);
                    }
                    else {
                        //Allow to discard at anytime based on player preference (on client side)
                        Utils::addPossibleActionInArgs('actAnytimeDiscard', $args);
                    }
                }
                if( $playableCards->count()>0){
                    Utils::addPossibleActionInArgs('actPlayCard', $args);
                }
                break;
            default: return;
        }

    }
    
    public static function addArgsForTilePlay(
        Player $player, 
        array &$args, 
        array|null $actionsWhiteList = null
    ){
        Game::get()->trace("PlayerTurn.addArgsForTilePlay(".json_encode($actionsWhiteList).")");
        $roundStep = Globals::getRoundStep();
        
        switch($roundStep){
            case ROUND_STEP_PLAYER_ACTIONS:
                $playableTiles = Tiles::getPossibleToPlay($player, $args, $actionsWhiteList);
                $args['possiblePlayTiles'] = $playableTiles;
                if( $playableTiles->count()>0){
                    Utils::addPossibleActionInArgs('actPlayTile', $args);
                }
                break;
            default: return;
        }

    }
}