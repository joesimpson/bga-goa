<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Core\Globals;

/**
 * Fake player for the automata, not saved in Player Table,
 * 
 * override custom methods 
 */
class AutomaPlayer extends Player
{  

    /**
     * @return int minimum number of actions to be played by this player during actions phase
     */
    public function getMinRoundActions(): int {
        return NB_AUTOMA_ACTIONS_PER_ROUND;
    }

    function getCardsPlayed(){
        return [];
    }
    function setCardsPlayed($value){
    }
    
    function setNbActions(int $value){
        Globals::setAutomaNbActions($value);
    }
    function incNbActions(){
        Globals::incAutomaNbActions();
    }
    function getNbActions() : int{
        return Globals::getAutomaNbActions();
    }

    function setActionPlayed(bool $value){
        Globals::setAutomaActionPlayed($value);
    }
    function isActionPlayed() : bool {
        return Globals::isAutomaActionPlayed();
    }
    
    function setPassed(bool $value){
        Globals::setAutomaPassed($value);
    }
    function isPassed() : bool {
        return Globals::isAutomaPassed();
    }
}
