export class ConfirmTurn {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
            _('${you} must confirm or restart') :
            _('${actplayer} must confirm or restart')
        );
      
        if(!isCurrentPlayerActive) return;
        
        this.bga.statusBar.addActionButton(_('Confirm'), () => this.bga.actions.performAction("actConfirmTurn"), {}); 
        
        if (args && args.previousChoices && args.previousChoices >= 1) {
            if (args && args.previousSteps) {
                let lastStep = Math.max(...args.previousSteps);
                if (lastStep > 0){
                    this.bga.statusBar.addActionButton(_('Undo last step'), () => this.bga.actions.performAction("actUndoToStep",{ 'stepId': lastStep }), { color:'alert', destination: document.getElementById('restartActions')}); 
                }
            }
        }
        //TODO JSA PREF ICON BUTTON
        this.bga.statusBar.addActionButton(_('Restart turn'), () => this.bga.actions.performAction("actRestart"), { color:'alert', destination: document.getElementById('restartActions')}); 
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
    }

    /**
     * This method is called each time the current player becomes active or inactive in a MULTIPLE_ACTIVE_PLAYER state. You can use this method to perform some user interface changes at this moment.
     * on MULTIPLE_ACTIVE_PLAYER states, you may want to call this function in onEnteringState using `this.onPlayerActivationChange(args, isCurrentPlayerActive)` at the end of onEnteringState.
     * If your state is not a MULTIPLE_ACTIVE_PLAYER one, you can delete this function.
     */
    onPlayerActivationChange(args, isCurrentPlayerActive) {
    }

    
}