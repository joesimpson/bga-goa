<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

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
                    }
                }
            }
            $args['tiles_choices'] = $choiceTiles;
            $args['possibleActions'][] = 'actTileChoice';
        }
        PlayerTurn::addArgsForCardPlay($player,$args, $actionsWhiteList );

        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }     
    /**
     * Player action in round step 2
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actTileChoice(int $tile_id, int $choice, int $choice_index, int $activePlayerId, array $args)
    {
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
        try{
            switch($tile_choice['selectType']){
                //use 2 different switches, because in case of DB elements ids (like tiles ids), we cannot tell in advance if the id is the same as an predefined action id
                case CHOICE_PICK_TILE:
                    //SELECT A TILE : choice is a tile id
                    $foundChoice = $this->checkChoiceAsKey($tile_choice,$tile_id,$choice);
                    $selectedTile = Tiles::get($choice);
                    Tiles::giveMarketTileToPlayer($player, $selectedTile);
                    break;
                case CHOICE_REPLACE_TILE:
                    //SELECT A TILE : choice is a tile id
                    $foundChoice = $this->checkChoiceAsValue($tile_choice,$tile_id,$choice);
                    $selectedTile = Tiles::get($choice);
                    Tiles::discardTileFromPlayer($player, $selectedTile);
                    Tiles::giveMarketTileToPlayer($player, $tile);
                    break;
                case CHOICE_PICK_SPICE:
                    //SELECT A SPICE : choice is a spice type
                    $foundChoice = $this->checkChoiceAsValue($tile_choice,$tile_id,$choice);
                    $slotPosition = $foundChoice['n'];
                    $spiceTokenType = Tokens::getTokenSpiceTypeFromSpice($choice);
                    Tokens::addSpiceOnTile($tile, $player, $spiceTokenType, $slotPosition);
                    break;
                case CHOICE_PICK_ACTION:
                default:
                    //SELECT AN ACTION : choice is an action id
                    $foundChoice = $this->checkChoiceAsValue($tile_choice,$tile_id,$choice);
                    switch($choice){
                        case ACTION_SHIP:
                        case ACTION_CONTRACT:
                            Players::directGain($player,$choice, $foundChoice['n'], $tile );
                            break;
                        case ACTION_FOUND_ABANDON:
                            Notifications::abandonOutpost($player,$tile);
                            Players::giveContracts($player, 1);
                            //We come from PlayerTurn, stay there
                            throw new PrematureStateChangeException (10,PlayerTurn::class,$player);
                            break;
                        case ACTION_FOUND_FORWARD:
                            $amount = $foundChoice['n'];
                            Players::giveContracts($player, -$amount);
                            Tiles::giveOutpostToPlayer($player,$tile);
                            //We come from PlayerTurn, stay there
                            throw new PrematureStateChangeException (11,PlayerTurn::class,$player);
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
            return $prematureStateChange->nextStateClass;
        }
        if(count($remainingChoices) > 0){
            //continue to have choices
            return PlayerChoice::class;
        }
        //Else end
        return ConfirmTurn::class;
    }
    
    /**
     * Player action : discard some card in hand
     */
    #[PossibleAction]
    public function actDiscard(int $card_id, int $activePlayerId, array $args)
    { 
        PlayerTurn::actDiscard_content($card_id, $activePlayerId, $args);
        return self::class;
    }
    
    /**
     * Player action : Play some card in hand
     */
    #[PossibleAction]
    public function actPlayCard(int $card_id, int $activePlayerId, array $args)
    { 
        PlayerTurn::actPlayCard_content($card_id, $activePlayerId, $args);
        
        $player = Players::get($activePlayerId);
        $choiceTiles = $args['tiles_choices'];
        //REFRESH TILES CHOICES DATAS according to played card
        foreach($choiceTiles as $tileId => &$tileChoices){
            foreach($tileChoices as &$tileChoiceDatas){
                if($tileChoiceDatas['selectType'] === CHOICE_FOUND_TILE 
                && !in_array(ACTION_FOUND_FORWARD,$tileChoiceDatas['choices']) ){
                    //
                    $missingContracts = $tileChoiceDatas['n'];
                    if($player->getContracts() >= $missingContracts){
                        $playerChoices[] = ACTION_FOUND_FORWARD;
                    }
                    $tileChoiceDatas['choices'][] = ACTION_FOUND_FORWARD;
                }
            }
        }
        Globals::setChoiceTiles($choiceTiles);

        return self::class;
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
            throw new UnexpectedException(504,"Invalid choice $choice for tile $tile_id");
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