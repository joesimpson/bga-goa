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
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;

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
            'tiles_choices' => [],
            'tiles' => [],
        ];
        $player = Players::getActive();

        //Use a whitelist of possible card actions, because we don't want complex actions to interfere with multiple steps
        $actionsWhiteList = [];
        $choiceTiles = Globals::getChoiceTiles(); 
        if(isset($choiceTiles)){
            foreach($choiceTiles as $tileId => $tileChoices){
                $tile = Tiles::get((int)$tileId);
                $args['tiles'][$tileId] = $tile->getUiData();
                foreach($tileChoices as $tileChoiceDatas){
                    if($tileChoiceDatas['selectType'] === CHOICE_FOUND_TILE ){
                        //IF we are near abandon, let player play a card to receive contracts
                        $actionsWhiteList[] = CARD_ACTION_2_CONTRACTS;
                        //IF we are near abandon, let player play a tile to receive contracts
                        $actionsWhiteList[] = ACTION_CONTRACT;
                    }
                    else if($tileChoiceDatas['type'] === ACTION_HARVEST ){
                        $args['harvestSpots'] = Tiles::getPlayerAvailableSpotsForSpices($player);
                        $args['playerBaseHarvest'] = $tileChoiceDatas['n'];
                    }
                    else if($tileChoiceDatas['selectType'] === CHOICE_ESPIONAGE){
                        if(in_array(ACTION_HARVEST, $tileChoiceDatas['choices'])){
                            $args['harvestSpots'] = Tiles::getPlayerAvailableSpotsForSpices($player);
                            $args['playersBaseHarvest'] = Players::getAllHarvestBaseGain();
                        }
                        if(in_array(ACTION_HARVEST, $tileChoiceDatas['choices'])){
                            $args['outpostsToFound'] = Tiles::getFoundableOutposts($player)->getIds();
                            $args['playersBaseContracts'] = Players::getAllFoundBaseContracts();
                        }
                    }
                }
            }
            $args['tiles_choices'] = $choiceTiles;
            $args['possibleActions'][] = 'actTileChoice';
        }
        PlayerTurn::addArgsForCardPlay($player,$args, $actionsWhiteList );
        PlayerTurn::addArgsForTilePlay($player,$args, $actionsWhiteList);

        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }     
    /**
     * Player action in round step 2
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actTileChoice(
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
        $this->game->trace("actTileChoice( $tile_id, $choice, $choice_index, $activePlayerId)");
        // check input values
        if ( !in_array('actTileChoice',$args['possibleActions'])) {
            throw new UnexpectedException(501,"Invalid action actTileChoice");
        } 
        $tiles = $args['tiles'];
        if ( !in_array($tile_id,array_keys($tiles))) {
            throw new UnexpectedException(502,"Invalid tile $tile_id");
        } 
        $choiceTiles = $args['tiles_choices'];
        if ( !in_array($tile_id,array_keys($choiceTiles))) {
            throw new UnexpectedException(503,"Invalid tile $tile_id");
        } 
        $tile = Tiles::get($tile_id);
        $tile_choices = $args['tiles_choices'][$tile_id];
        $this->game->trace("actTileChoice()... tiles_choices= ".json_encode($tile_choices));
        if ( !in_array($choice_index,array_keys($tile_choices))) {
            throw new UnexpectedException(504,"Invalid tile choice $choice_index");
        } 
        $tile_choice = $tile_choices[$choice_index];
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic  
        $prematureStateChange = null;
        $doCheckpoint = false;
        $roundStep = Globals::getRoundStep();
        try{
            switch($tile_choice['selectType']){
                //use 2 different switches, because in case of DB elements ids (like tiles ids), we cannot tell in advance if the id is the same as an predefined action id
                case CHOICE_PICK_TILE:
                    //SELECT A TILE : choice is a tile id
                    $foundChoice = $this->checkChoiceAsKey($tile_choice,$tile_id,$choice);
                    $selectedTile = Tiles::get($choice);
                    Tiles::giveMarketTileToPlayer($player, $selectedTile, $doCheckpoint);
                    break;
                case CHOICE_REPLACE_TILE:
                    //SELECT A TILE : choice is a tile id
                    $foundChoice = $this->checkChoiceAsValue($tile_choice,$tile_id,$choice);
                    $selectedTile = Tiles::get($choice);
                    Tiles::discardTileFromPlayer($player, $selectedTile);
                    Tiles::giveMarketTileToPlayer($player, $tile, $doCheckpoint);
                    break;
                case CHOICE_PICK_SPICE:
                    //SELECT A SPICE : choice is a spice type
                    $foundChoice = $this->checkChoiceAsValue($tile_choice,$tile_id,$choice);
                    $slotPosition = $foundChoice['n'];
                    $spiceTokenType = Tokens::getTokenSpiceTypeFromSpice($choice);
                    Tokens::addSpiceOnTile($tile, $player, $spiceTokenType, $slotPosition);
                    break;
                case CHOICE_ESPIONAGE:
                    $foundChoice = $this->checkChoiceAsValue($tile_choice,$tile_id,$choice);
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
                            PlayerTurn::actFoundOutpost_content($selectedTileId,null, $player, $progressRefPlayerId, $args,);
                            $doCheckpoint = true;
                            break;
                        default:
                            throw new UnexpectedException(505,"Wrong espionage choice $choice for tile $tile_id");
                    }
                    break;
                case CHOICE_PICK_ACTION:
                default:
                    //SELECT AN ACTION : choice is an action id
                    $foundChoice = $this->checkChoiceAsValue($tile_choice,$tile_id,$choice);
                    switch($choice){
                        case ACTION_SHIP:
                        case ACTION_CONTRACT:
                            Players::directGain($player,$choice, $foundChoice['n'], $tile, $doCheckpoint );
                            break;
                        case ACTION_FOUND_ABANDON:
                            Notifications::abandonOutpost($player,$tile);
                            Players::giveContracts($player, 1);
                            break;
                        case ACTION_FOUND_FORWARD:
                            $amount = $foundChoice['n'];
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
                            );
                            break;
                        default: break;
                    }
                break;
            }
        }
        catch(PrematureStateChangeException $exc){
            $prematureStateChange = $exc;
        }

        //remove the right tile choice in Globals
        $remainingChoices = Globals::removeChoiceTile($tile_id, $choice_index);

        // at the end of the action, move to the next state
        if(isset($prematureStateChange) ){
            //Players::changeActive($prematureStateChange->changePlayer->getId());
            //Should not be necessary, but we cannot change active player in active state
            $nextStateClass = $prematureStateChange->nextStateClass;
        }
        if(count($remainingChoices) > 0){
            //continue to have choices
            $nextStateClass = PlayerChoice::class;
        }
        else if(ROUND_STEP_PLAYER_ACTIONS === $roundStep ){
            //Else return to player turn to see if something else to play
            $nextStateClass = PlayerTurn::class;
        }
        else {
            //Else end
            $nextStateClass = ConfirmTurn::class;
        }
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        return $nextStateClass;
    }
    
    /**
     * Player action : discard some card in hand
     */
    #[PossibleAction]
    public function actDiscard(
        int $card_id, 
        int $activePlayerId, array $args,
        int $v,
        )
    { 
        Utils::checkVersion($v);
        PlayerTurn::actDiscard_content($card_id, $activePlayerId, $args);
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
        PlayerTurn::actPlayCard_content($card_id, $activePlayerId, $args);
        
        UTils::refreshFoundTileChoice($activePlayerId);

        return self::class;
    }

    #[PossibleAction]
    public function actPlayTile(
        int $tile_id, 
        int|null $row = null,
        int|null $col = null,
        int $activePlayerId, array $args,
        int $v,
        )
    { 
        Utils::checkVersion($v);
        
        $doCheckpoint = false;
        $nextStateClass = self::class;
        PlayerTurn::actPlayTile_content($tile_id, $row, $col, $activePlayerId, $args, $doCheckpoint,$prematureStateChange);
        if(isset($prematureStateChange) ){
            $nextStateClass = $prematureStateChange->nextStateClass;
        }
        
        Utils::refreshFoundTileChoice($activePlayerId);
        if($doCheckpoint) Log::addStateCheckpoint($nextStateClass);
        
        return $nextStateClass;
    }

    function zombie(int $playerId) {
        //TODO JSA Zombie
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
    function checkChoiceAsKey(array $tile_choice, int $tile_id, int $choice): array{
        $foundChoice = null;
        $this->game->trace("checkChoiceAsKey()... choice $choice for tile $tile_id in ".json_encode($tile_choice));
        
        $choices = $tile_choice['choices'];
        foreach($choices as $key => $value){
            if ( intval( $key) == $choice) {
                $foundChoice = $tile_choice;
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
    function checkChoiceAsValue(array $tile_choice, int $tile_id, int $choice) : array{
        $foundChoice = null;
        $choices = $tile_choice['choices'];
        foreach($choices as $key => $value){
            if ( intval( $value) == $choice) {
                $foundChoice = $tile_choice;
                break;
            }
        }
        if ( !isset($foundChoice)) {
            throw new UnexpectedException(504,"Invalid choice $choice for tile $tile_id");
        } 
        return $foundChoice;
    }

}