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
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;

class CleanHand extends GameState
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

        $player = Players::getActive();
        $nbCardsInHand = Cards::countInExplorationHand($player->getId());
        $maxCards = Cards::maxExplorationToKeep($player);
        $args = [
            'maxCards' => $maxCards,
            'handSize' => $nbCardsInHand,
            'possibleActions' => [
                'actPlayCard',
                'actDiscard',
                //actions needed for some cards :
                'actProgress',
            ],
        ];

        PlayerTurn::addArgsForCardPlay($player,$args, null, true );

        ConfirmTurn::addArgsForUndo($args);
        return $args;
    }     
    
    public function onEnteringState(int $activePlayerId, array $args) {
        $this->game->trace("CleanHand.onEnteringState($activePlayerId)");

        $player = Players::get($activePlayerId);
        if( $args['handSize'] <=  $args['maxCards'])
        {
            //Go back to player turn actions when hand is clean
            Notifications::cleanHandEnd($player,$args['handSize']);
            return PlayerTurn::class;
        }
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
        
        Utils::refreshFoundTileChoice($activePlayerId);

        return self::class;
    }
    
    /**
     * Copy of PlayerTurn action for some cards (e)
     */
    #[PossibleAction]
    public function actSpendSpices(
        #[IntArrayParam] array $t_ids, 
        int|null $card_id = null, 
        int|null $tile_id = null,
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
    
    /**
     * Copy of PlayerTurn action for some cards (f)
     */
    #[PossibleAction]
    public function actHarvest(
        #[IntArrayParam] array $spots, 
        #[IntArrayParam(name:'res')] array $resources, 
        int|null $card_id = null, 
        int|null $tile_id = null, 
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
            Cards::playExploration($player, $card_id, );
        } 
        /* Should not be needed here ?
        else if(isset($tile_id)){
            $tile = Tiles::playTile($player, $tile_id, null, $doCheckpoint, $prematureStateChange );
        }
        */

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

    /** Copy of PlayerTurn action for some cards with progress (j) */
    #[PossibleAction]
    public function actProgress(
        int $track, 
        #[IntArrayParam] array|null $t_ids = null, 
        int|null $card_id = null, 
        int|null $tile_id = null, 
        int $activePlayerId, array $args,
        int $v,
    )
    { 
        Utils::checkVersion($v);
        $this->game->trace("CleanHand.actProgress($track,$card_id,$tile_id,$activePlayerId)");

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

    
    function zombie(int $playerId, array $args) {
        $actions = $args['possibleActions'];
        $gameVersion = Utils::gameVersion();

        if(in_array('actDiscard',$actions)){
            $possibleDiscards = $args['possibleDiscards'];
            $card_id = $this->getRandomZombieChoice($possibleDiscards);
            return $this->actDiscard($card_id,$playerId, $args, $gameVersion);
        }

    }

}