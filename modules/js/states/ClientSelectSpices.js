
/**
 * Client STATE
 */
export class ClientSelectSpices {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let spicesSets = args.spicesSets; 
        let nbSpices = args.nbSpices;
        let allSpicesIds = args.spices_ids; 
        let spicesDivs = [];
        Object.values(allSpicesIds).forEach((tId) => {
            spicesDivs[tId] = $(`goa_token-${tId}`);
        });

        let selectionHint = _('Select this spice token');
        this.game.onSelectN(spicesDivs, nbSpices, (selectedSpices) => {
            this.bga.actions.performAction('actProgress', { 'track': args.track, 't_ids': selectedSpices.join(',')});
        }, spicesSets, true, _('Confirm'), selectionHint );
    
        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.bga.states.restoreServerGameState();
        }, { 'color':'secondary','title':_('Cancel'), destination: document.getElementById('customSecondary') }); 

    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }
 
}