
/**
 * Client STATE
 */
export class ClientSelectSpicesForProgress {
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
        let allSpicesIds = [];
        let costSpices = args.costSpices;
        let spicesDivs = [];
    
        Object.entries(costSpices).forEach( ([spice, cost]) => {
            Object.values(cost.from).forEach((tId) => {
                allSpicesIds.push(tId);
            });
        });
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
            let sortedTokens = selectedSpices.sort((a,b) =>  a - b );
            this.game.customPerformAction('actProgress', { 'track': args.track, 't_ids': sortedTokens.join(','), 'card_id': args.withCard});
        }, spicesSets, true, _('Confirm'), selectionHint );
    
        this.game.addButtonCloseClientState(args);
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }
 
}