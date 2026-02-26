/**
 * We create one State class per declared state on the PHP side, to handle all state specific code here.
 * onEnteringState, onLeavingState and onPlayerActivationChange are predefined names that will be called by the framework.
 * When executing code in this state, you can access the args using this.args
 */

export class CleanHand {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let possibleActions = args.possibleActions;
        let maxCards = args.maxCards;
        let icon_explo = this.game.formatIcon('exploration_card',_('Exploration card'));

        this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
            _('${you} must play or discard cards until your hand size is below ${n} ${icon_cards}').replace('${n}', maxCards).replace('${icon_cards}',icon_explo) :
            _('${actplayer} must play or discard cards until his hand size is below ${n} ${icon_cards}').replace('${n}', maxCards).replace('${icon_cards}',icon_explo)
        );

        if(isCurrentPlayerActive) this.game.updateHandSelectable(args);

        if(isCurrentPlayerActive ) this.game.confirmTurn.displayUndoButtons(args);
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
} 