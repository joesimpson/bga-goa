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

        switch($roundStep){
            case ROUND_STEP_TILE_AUCTIONS: 
                $choiceTiles = Globals::getChoiceTiles(); 
                if(isset($choiceTiles)){
                    foreach($choiceTiles as $choice){
                        //foreach($choiceTile as $choice){
                            $tileId = $choice['tile_id'];
                            $tile = Tiles::get((int)$tileId);
                            $args['tiles'][$tileId] = $tile->getUiData();
                            $args['tiles_choices'][] = $choice;
                        //}
                    }
                    $args['possibleActions'][] = 'actTileChoice';
                }
                break;
            default : break;
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
    public function actTileChoice(int $tile_id, int $activePlayerId, array $args)
    {
        // check input values
        if ( in_array('actTileChoice',$args['possibleActions'])) {
            throw new UnexpectedException(501,"Invalid action actTileChoice");
        } 
        
        Log::addStep();
        $player = Players::get($activePlayerId);

        // game logic  
        $choiceTiles = $args['choiceTiles'];

        // at the end of the action, move to the next state
        return ConfirmTurn::class;
    }
 
    function zombie(int $playerId) {
        //TODO JSA Zombie
    }
}