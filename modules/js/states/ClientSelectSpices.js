
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
        
        let track = args.track;
        let activePlayer = this.bga.players.getActivePlayerId();

        let spicesSets = args.spicesSets; 
        let nbSpices = args.nbSpices;
        let allSpicesIds = args.spices_ids; 
        let costSpices = args.costSpices;
        let spicesDivs = [];
        Object.values(allSpicesIds).forEach((tId) => {
            spicesDivs[tId] = $(`goa_token-${tId}`);
        });
        let spicesIcons = '';
        Object.entries(costSpices).forEach( ([spice, cost]) => {
            new Array(cost.n).fill(0).forEach( x => { 
                spicesIcons += this.game.formatIcon('spices_'+spice,_('Spices'));
            });
        });
        
        this.bga.statusBar.setTitle(
                _("${you} must select spices ${icons} to discard and progress on track ${n}").replace('${icons}',spicesIcons).replace('${n}',track)
            );
            
        let progressTrack = document.getElementById(`goa_progress_track_${track}_${activePlayer}`);
        progressTrack.classList.add('selected');
        progressTrack.classList.add('selected_for_client_state');

        let selectionHint = _('Select this spice token');
        this.game.onSelectN(spicesDivs, nbSpices, (selectedSpices) => {
            this.bga.actions.performAction('actProgress', { 'track': args.track, 't_ids': selectedSpices.join(','), 'card_id': args.withCard});
        }, spicesSets, true, _('Confirm'), selectionHint );
    
        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.game.restorePreviousState(args);
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