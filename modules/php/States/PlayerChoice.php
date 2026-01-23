<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;

class PlayerChoice extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: ST_PLAYER_CHOICE,
            type: StateType::ACTIVE_PLAYER,
        );
    }

    /**
     * Game state arguments
     *
     */
    public function getArgs(): array
    {
        $roundStep = Globals::getRoundStep();
        $player = Players::getActive();

        $args = [
            'possibleActions' => [],
            'tiles_choices' => [],
            'tiles' => [],
        ];

        $choiceTiles = Globals::getChoiceTiles(); 
        if(isset($choiceTiles)){
            foreach($choiceTiles as $choice){
                $tileId = $choice['tile_id'];
                $tile = Tiles::get((int)$tileId);
                $args['tiles'][$tileId] = $tile->getUiData();
                $args['tiles_choices'][$tileId][] = $choice;
            }
            $args['possibleActions'][] = 'actTileChoice';
        }

        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }     
    /**
     * Player action in round step 2
     *
     * @throws UserException
     */
    #[PossibleAction]
    public function actTileChoice(int $tile_id, int $choice, int $activePlayerId, array $args)
    {
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
        $tile_choices = $args['tiles_choices'][$tile_id];
        $foundChoice = null;
        foreach($tile_choices as $tile_choice){
            $choices = $tile_choice['choices'];
            if ( in_array($choice,($choices))) {
                $foundChoice = $tile_choice;
                break;
            }
        }
        if ( !isset($foundChoice)) {
            throw new UnexpectedException(504,"Invalid choice $choice ");
        } 
        $tile = Tiles::get($tile_id);
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic  
        switch($choice){
            case ACTION_SHIP:
            case ACTION_CONTRACT:
                Players::directGain($player,$choice, $foundChoice['n'], $tile );
                break;
            default: break;
        }

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }
 
    function zombie(int $playerId) {
        //TODO JSA Zombie
    }
}