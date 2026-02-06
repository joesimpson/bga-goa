<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

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
use Bga\Games\Goa\Managers\Players;

class ConfirmTurn extends GameState
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
        $data = [];
        $this->addArgsForUndo($data);
        $player = Players::getActive();
        //PlayerTurn::addArgsForCardPlay($player,$data);
        return $data;
    }    
    
    /**
     * Update state args with some parameters used for canceling actions. To be called in each state args where we will want to cancel.
     * 
     */
    public static function addArgsForUndo(&$args)
    {
        $args['undo'] = [
            'previousSteps' => Log::getUndoableSteps(),
            'previousChoices' => Globals::getChoices(),
        ];
        
    }

    public function onEnteringState(int $activePlayerId) {
        $this->game->trace("ConfirmTurn.onEnteringState()");
        $player = Players::getActive();
        $prefConfirm = $player->getPref(PREF_CONFIRM);
        $this->game->trace("ConfirmTurn.onEnteringState() prefConfirm=",json_encode($prefConfirm));
        if (Globals::getChoices() == 0 
            || $prefConfirm == PREF_CONFIRM_DISABLED 
        ) {//AUTO CONFIRM
            return $this->actConfirmTurn(Utils::gameVersion(),true);
        }
    }

    /**
     * Player action : Confirm ALL
     *
     */
    #[PossibleAction]
    public function actConfirmTurn( int $v, bool $auto = false)
    {
        Utils::checkVersion($v);
        $this->game->trace("actConfirmTurn($auto)");
        return EndPlayerTurn::class;
    }
    
    /**
     * Player action : undo ALL
     *
     * @throws UnexpectedException
     */
    #[PossibleAction]
    public function actRestart(int $v, )
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
    public function actUndoLastStep(int $v, )
    {
        Utils::checkVersion($v);
        ConfirmTurn::processUndoLastStep();
    }
    
    ///**
    // * Player action : discard some card in hand
    // */
    //#[PossibleAction]
    //public function actDiscard(int $card_id, int $activePlayerId, array $args)
    //{ 
    //    PlayerTurn::actDiscard_content($card_id, $activePlayerId, $args);
    //    return ConfirmTurn::class;
    //}
    //
    ///**
    // * Player action : Play some card in hand
    // */
    //#[PossibleAction]
    //public function actPlayCard(int $card_id, int $activePlayerId, array $args)
    //{ 
    //    PlayerTurn::actPlayCard_content($card_id, $activePlayerId, $args);
    //    return ConfirmTurn::class;
    //}

    function zombie(int $playerId) {
        //TODO JSA ZOMBIE 
    }
    
    ///------------------------------------------------------------
    // CUSTOM FUNCTIONS :
    ///------------------------------------------------------------
    /**
     * undo ALL player steps
     *
     * @throws UnexpectedException
     */
    public static function processRestartTurn(){
        $player = Players::getCurrent();
        $pId = $player->getId();
        if (Globals::getChoices($pId) < 1) {
            throw new UnexpectedException(404,'No choice to undo. You may need to reload the page.');
        }
        Log::undoTurn();
        Notifications::restartTurn($player);
    }
    /**
     * undo last step
     *
     * @throws UnexpectedException
     */
    public static function processUndoLastStep(){
        $player = Players::getCurrent();
        $pId = $player->getId();
        $steps = Log::getUndoableSteps($pId);
        if(count($steps) <1){
            throw new UnexpectedException(404,'This step is not undoable anymore. You may need to reload the page.');
        }
        $stepId = array_shift($steps);
        Log::undoToStep($stepId);
        Notifications::undoStep($player, $stepId);
    }
}