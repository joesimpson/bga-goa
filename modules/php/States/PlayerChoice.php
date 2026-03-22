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
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Effects;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\Models\Effect;

/**
 * State to resolve different player effects during player turn, before returning to player turn (or confirmturn)
 */
class PlayerChoice extends GameState
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
     */
    public function getArgs(): array
    {

        $args = [
            'possibleActions' => [],
            'effects' => [],
            'tiles' => [],
        ];
        $player = Players::getActive();

        //Use a whitelist of possible card actions, because we don't want complex actions to interfere with multiple steps
        $actionsWhiteList = [];
        $lookForDiscardCards = false;
        $effects = Effects::listToResolve($player->getId()); 
        if(isset($effects)){
            foreach($effects as $effectId => $effect){
                $tileId = $effect->getSourceTile();
                if(isset($tileId)) {
                    $tile = Tiles::get((int)$tileId);
                    $args['tiles'][$tileId] = $tile->getUiData();
                }
                switch($effect->getType())
                {
                    case EFFECT_TYPE_PICK_TILE:
                        $tilesToChoose = $effect->getResult();
                        foreach($tilesToChoose as $t_id){
                            $tile = Tiles::get((int)$t_id);
                            $args['tiles'][$t_id] = $tile->getUiData();
                        }
                        break;
                    case EFFECT_TYPE_FOUND_TILE:
                        //IF we are near abandon, let player play a card to receive contracts
                        $actionsWhiteList[] = CARD_ACTION_2_CONTRACTS;
                        //IF we are near abandon, let player play a tile to receive contracts
                        $actionsWhiteList[] = ACTION_CONTRACT;
                        $args['doublecheckfound'] = true;
                        break;
                    case EFFECT_TYPE_HARVEST:
                        $args['harvestSpots'] = Tiles::getPlayerAvailableSpotsForSpices($player);
                        $args['playerBaseHarvest'] = $effect->getQuantity();
                        break;
                    case EFFECT_TYPE_ESPIONAGE:
                        if(in_array(ACTION_HARVEST, $effect->getResult())){
                            $args['harvestSpots'] = Tiles::getPlayerAvailableSpotsForSpices($player);
                            $args['playersBaseHarvest'] = Players::getAllHarvestBaseGain();
                        }
                        if(in_array(ACTION_FOUND_ABANDON, $effect->getResult())){
                            $args['outpostsToFound'] = Tiles::getFoundableOutposts($player)->getIds();
                            $args['playersBaseContracts'] = Players::getAllFoundBaseContracts();
                        }
                        break;
                    case EFFECT_TYPE_CLEAN_HAND:
                        $args['maxCards'] = Cards::maxExplorationToKeep($player);
                        $nbCardsInHand = Cards::countInExplorationHand($player->getId());
                        if( $nbCardsInHand <=  $args['maxCards'])
                        {
                            // hand is clean
                            $args['auto_resolve_hand'] = $effect->getId();
                            $args['handSize'] = $nbCardsInHand;
                        }
                        else {
                            $lookForDiscardCards = true;
                        }
                        break;
                }
            }
            $args['effects'] = $effects->uiAssoc();
            Utils::addPossibleActionInArgs('actResolveEffect', $args);
        }
        //Allow to play any card/tile during any effect, it may add new effects to the list
        PlayerTurn::addArgsForCardPlay($player,$args, null,$lookForDiscardCards);
        PlayerTurn::addArgsForTilePlay($player,$args);

        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }     
    
    public function onEnteringState(int $activePlayerId, array $args) {
        $this->game->trace(__CLASS__.".".__FUNCTION__."($activePlayerId)");

        $player = Players::get($activePlayerId);
        $roundStep = Globals::getRoundStep();
        if(isset( $args['auto_resolve_hand']))
        {
            $effectId = $args['auto_resolve_hand'];
            Notifications::cleanHandEnd($player,$args['handSize']);
            Effects::resolve($effectId);
            return self::class;
        }
        if(isset($args['doublecheckfound'])){
            $updated = Utils::refreshFoundTileChoice($activePlayerId);
            if($updated) return self::class;
        }
        
        $remainingChoices = Effects::countPlayerEffectsToResolve($activePlayerId);
        if($remainingChoices == 0){
            //END Choices selection when no more choices
            if(ROUND_STEP_PLAYER_ACTIONS === $roundStep ){
                // return to player turn to see if something else to play
                $nextStateClass = PlayerTurn::class;
            }
            else {
                //Else end
                $nextStateClass = ConfirmTurn::class;
            }
            return $nextStateClass;
        }
    }
    /**
     * Player action in round step 2 + resolve effects in round step 3
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actResolveEffect(
        int $tile_id, 
        int $choice, 
        int $choice_index, 
        int $activePlayerId, array $args,
        int $v,
        /** 2 arrays if we need to transfer more datas according to some complex choices */
        #[IntArrayParam(name:'arr1')] array|null $choice_array1, 
        #[IntArrayParam(name:'arr2')] array|null $choice_array2, 
        int|null $progressRefPlayerId,
        )
    {
        Utils::checkVersion($v);
        $this->game->trace("actResolveEffect( $tile_id, $choice, $choice_index, $activePlayerId)");
        // check input values
        if ( !in_array('actResolveEffect',$args['possibleActions'])) {
            throw new UnexpectedException(501,"Invalid action actResolveEffect");
        } 
        $tiles = $args['tiles'];
        if ( !in_array($tile_id,array_keys($tiles))) {
            throw new UnexpectedException(502,"Invalid tile $tile_id");
        } 
        $effects = $args['effects'];
        if ( !in_array($choice_index,array_keys($effects))) {
            throw new UnexpectedException(503,"Invalid effect $choice_index");
        } 
        $tile = Tiles::get($tile_id);
        $effect = $effects[$choice_index];
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic  
        $doCheckpoint = false;
        $roundStep = Globals::getRoundStep();
            switch($effect['type']){
                //use 2 different switches, because in case of DB elements ids (like tiles ids), we cannot tell in advance if the id is the same as an predefined action id
                case EFFECT_TYPE_PICK_TILE:
                    //SELECT A TILE : choice is a tile id
                    $foundChoice = $this->checkChoiceAsValue($effect,$tile_id,$choice);
                    $selectedTile = Tiles::get($choice);
                    Tiles::giveMarketTileToPlayer($player, $selectedTile, $doCheckpoint);
                    break;
                case EFFECT_TYPE_REPLACE_TILE:
                    //SELECT A TILE : choice is a tile id
                    $foundChoice = $this->checkChoiceAsValue($effect,$tile_id,$choice);
                    $selectedTile = Tiles::get($choice);
                    Tiles::discardTileFromPlayer($player, $selectedTile);
                    if($selectedTile->getId() != $tile->getId()){
                        //if we effectively replaces a tile, we give this one after
                        Tiles::giveMarketTileToPlayer($player, $tile, $doCheckpoint);
                    }
                    break;
                case EFFECT_TYPE_PICK_SPICE:
                    //SELECT A SPICE : choice is a spice type
                    $foundChoice = $this->checkChoiceAsValue($effect,$tile_id,$choice);
                    $slotPosition = $foundChoice['quantity'];
                    $spiceTokenType = Tokens::getTokenSpiceTypeFromSpice($choice);
                    Tokens::addSpiceOnTile($tile, $player, $spiceTokenType, $slotPosition);
                    break;
                case EFFECT_TYPE_ESPIONAGE:
                    $foundChoice = $this->checkChoiceAsValue($effect,$tile_id,$choice);
                    if(isset($progressRefPlayerId)){
                        $opponentId = $progressRefPlayerId;
                        $opponent = Players::get($opponentId);
                    }
                    if(!isset($opponent) || $opponentId == $activePlayerId){
                        throw new UnexpectedException(505,"Wrong opponent choice ");
                    }
                    switch($choice){
                        case ACTION_SHIP:
                            Notifications::espionage($player, $opponent, 1);
                            PlayerTurn::actBuildShips_content($player, $progressRefPlayerId);
                            break;
                        case ACTION_HARVEST:
                            Notifications::espionage($player, $opponent, 2);
                            PlayerTurn::actHarvest_content(
                                $choice_array1, 
                                $choice_array2, 
                                null , 
                                null , //don't replay the tile
                                0, 
                                0, 
                                $progressRefPlayerId,
                                $activePlayerId,   
                                $args,
                                false,
                            );
                            break;
                        case ACTION_MONEY:
                            Notifications::espionage($player, $opponent, 3);
                            PlayerTurn::actTaxes_content($player, $progressRefPlayerId);
                            break;
                        case ACTION_FOUND_ABANDON:
                            if(isset($choice_array1) && isset($choice_array1[0])){
                                $selectedTileId = $choice_array1[0];
                            }
                            Notifications::espionage($player, $opponent, 5);
                            PlayerTurn::actFoundOutpost_content($selectedTileId,null, $doCheckpoint, $player, $progressRefPlayerId, $args, $choice_index);
                            break;
                        default:
                            throw new UnexpectedException(505,"Wrong espionage choice $choice for tile $tile_id");
                    }
                    break;
                case EFFECT_TYPE_HARVEST:
                case EFFECT_TYPE_PICK_ACTION:
                default:
                    //SELECT AN ACTION : choice is an action id
                    $foundChoice = $this->checkChoiceAsValue($effect,$tile_id,$choice);
                    switch($choice){
                        case ACTION_SHIP:
                        case ACTION_CONTRACT:
                            Players::directGain($player,$choice, $foundChoice['quantity'], $tile, $doCheckpoint );
                            break;
                        case ACTION_FOUND_ABANDON:
                            Utils::abandonEffectFoundTileChoice($player, $tile, $choice_index);
                            break;
                        case ACTION_FOUND_FORWARD:
                            //if other FOUND effect on same tile (example with espionage), auto choice ABANDON because we cannot found it 2 times
                            Utils::abandonOtherEffectsFoundTileChoice($choice_index,$player,$tile);

                            $amount = $foundChoice['quantity'];
                            Notifications::foundOutpost($player,$tile);
                            Players::giveContracts($player, -$amount);
                            Tiles::giveOutpostToPlayer($player,$tile);
                            break;
                        case ACTION_HARVEST:
                            PlayerTurn::actHarvest_content(
                                $choice_array1, 
                                $choice_array2, 
                                null , 
                                null , //don't replay the tile
                                0, 
                                0, 
                                $activePlayerId,  
                                $activePlayerId,   
                                $args,
                                false,
                            );
                            break;
                        default: break;
                    }
                break;
            }

        Effects::resolve($choice_index);

        // at the end of the action, move to the next state
            $nextStateClass = PlayerChoice::class;
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        return $nextStateClass;
    }

    
    /**
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actIgnoreEffect(
        int $id, 
        int $activePlayerId, array $args,
        int $v,
    )
    {
        Utils::checkVersion($v);
        $this->game->trace("actIgnoreEffect( $id, $activePlayerId)");
        // check input values
        if ( !in_array('actResolveEffect',$args['possibleActions'])) {
            throw new UnexpectedException(502,"Invalid action actResolveEffect");
        } 
        $effects = $args['effects'];
        if ( !in_array($id,array_keys($effects))) {
            throw new UnexpectedException(503,"Invalid effect $id");
        } 
        $effect = $effects[$id];
        $type = $effect['type'];
        $ignoreableTypes = [EFFECT_TYPE_HARVEST]; //we may ignore a direct harvest while we have no empty spot, but we must resolve others
        if ( !in_array($type,$ignoreableTypes)) {
            throw new UnexpectedException(504,"Invalid effect type to ignore $type");
        } 
        Log::addStep();

        Effects::ignore($id);

        return PlayerChoice::class;

    }
    
    /**
     * Player action : discard some card in hand
     */
    #[PossibleAction]
    public function actDiscard(
        #[IntArrayParam] array $card_ids, 
        int $activePlayerId, array $args,
        int $v,
        )
    { 
        Utils::checkVersion($v);
        Log::addStep();
        foreach( $card_ids as $card_id){
            PlayerTurn::actDiscard_content($card_id, $activePlayerId, $args);
        }
        return self::class;
    }
    
    /**
     * Player action : Play some card in hand
     */
    #[PossibleAction]
    public function actPlayCard(
        int $card_id, 
        int $activePlayerId, array $args,
        int $v,
        )
    { 
        Utils::checkVersion($v);
        $doCheckpoint = false;
        PlayerTurn::actPlayCard_content($card_id, $activePlayerId, $args, $doCheckpoint);
        
        if($doCheckpoint) Log::addStateCheckpoint(self::class);
        return self::class;
    }

    #[PossibleAction]
    public function actPlayTile(
        int $tile_id, 
        int|null $row,
        int|null $col,
        int $activePlayerId, array $args,
        int $v,
        )
    { 
        Utils::checkVersion($v);
        
        $doCheckpoint = false;
        $nextStateClass = self::class;
        PlayerTurn::actPlayTile_content($tile_id, $row, $col, $activePlayerId, $args, $doCheckpoint,);
        $nextPlayerToResolve = Effects::nextPlayerToResolve();
        if(isset($nextPlayerToResolve) ){
            $nextStateClass = PlayerChoice::class;
        }
        
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        
        return $nextStateClass;
    }
    
    /**
     * Copy of PlayerTurn action for some cards (e) or tiles
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
        );
        
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        return $nextStateClass;
    }
    
    /**
     * Copy of PlayerTurn action for some cards (f) or tiles
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
            Cards::playExploration($player, $card_id, $doCheckpoint );
        } 
        else if(isset($tile_id)){
            $tile = Tiles::playTile($player, $tile_id, null, $doCheckpoint, );
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
 
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        return $nextStateClass;
    }

    /** Copy of PlayerTurn action for some cards or tiles with progress (j) */
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
        $this->game->trace(__CLASS__.".".__FUNCTION__."($track,$card_id,$tile_id,$activePlayerId)");

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

    function zombie(int $playerId, array $args) {
        $actions = $args['possibleActions'];
        $gameVersion = Utils::gameVersion();

        $effects = $args['effects'];
        $possible = true;
        if(count($effects) > 0){
            //We must treat all choices one by one
            $choice_index = array_keys($effects)[0];
            $tile_choice = $effects[$choice_index];
            $effectType = $tile_choice['type'];
            $tile_id = $tile_choice['sourceTile'];
            $choices = $tile_choice['result'];
            if(isset($tile_id) && isset($choices)){
                $choice = $choices[array_rand($choices)];
                $choice_array1 = null;
                $choice_array2 = null;
                $progressRefPlayerId = $playerId;
                switch($effectType){
                    case EFFECT_TYPE_ESPIONAGE:
                        $progressRefPlayerId = Players::getNextId($playerId);
                        //easier choice and best challenge ?
                        $choice = ACTION_MONEY;
                        break;
                }
                switch($choice){
                    case ACTION_HARVEST:
                        $harvestSpots = $args['harvestSpots'];
                        $spot1 = array_key_first($harvestSpots);
                        if(isset($harvestSpots[$spot1]) && isset($harvestSpots[$spot1]['spices']) ){
                            $res1 = $harvestSpots[$spot1]['spices'][0];
                            $choice_array1 = [$spot1];
                            $choice_array2 = [$res1];
                        }
                        else {
                            $possible = false;
                        }
                        break;
                }
                if($possible) 
                    return $this->actResolveEffect($tile_id, $choice, $choice_index, $playerId, $args, $gameVersion, $choice_array1, $choice_array2, $progressRefPlayerId );
                else {
                    Effects::resolve($choice_index);
                    return self::class;
                }
            }
            
            if(in_array('actDiscard',$actions)){
                $possibleDiscards = $args['possibleDiscards'];
                $card_id = $this->getRandomZombieChoice($possibleDiscards);
                return $this->actDiscard($card_id,$playerId, $args, $gameVersion);
            }
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
    ///------------------------------------------------------------
    // CUSTOM FUNCTIONS :
    ///------------------------------------------------------------
    
    /**
     * @throws UnexpectedException when incorrect player inputs
     * */
    function checkChoiceAsKey(array $effect, int $tile_id, int $choice): array{
        $foundChoice = null;
        $this->game->trace("checkChoiceAsKey()... choice $choice for tile $tile_id in ".json_encode($effect));
        
        $choices = $effect['result'];
        foreach($choices as $key => $value){
            if ( intval( $key) == $choice) {
                $foundChoice = $effect;
            }
        }
        if ( !isset($foundChoice)) {
            throw new UnexpectedException(504,"Invalid key choice $choice for tile $tile_id");
        }    
        return $foundChoice;   
    }
    /**
     * @throws UnexpectedException when incorrect player inputs
     * */
    function checkChoiceAsValue(array $effect, int $tile_id, int $choice) : array{
        $foundChoice = null;
        $choices = $effect['result'];
        foreach($choices as $key => $value){
            if ( intval( $value) == $choice) {
                $foundChoice = $effect;
                break;
            }
        }
        if ( !isset($foundChoice)) {
            throw new UnexpectedException(504,"Invalid choice $choice for tile $tile_id");
        } 
        return $foundChoice;
    }

}