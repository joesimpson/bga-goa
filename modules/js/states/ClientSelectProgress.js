
/**
 * Client STATE
 */
export class ClientSelectProgress {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let activePlayer = this.bga.players.getActivePlayerId();

        this.bga.statusBar.setTitle(
                    _("${you} must select a progress track to improve on your board")
                );

        let tracksDatas = args.progressTracks;
        Object.values(tracksDatas).forEach( trackData => {
            let track = trackData.track;
            let costShips = trackData.costShips;
            let withSpices = trackData.withSpices;
            let withShips = trackData.withShips;
            let costSpices = trackData.costSpices;
            let spicesSets = trackData.spicesSets;// [[1022,1027],[1022,1028],[1023,1027],[1023,1028]];
                
            let allSpicesIds = [];
            Object.entries(costSpices).forEach( ([spice, cost]) => {
                Object.values(cost.from).forEach((tId) => {
                    allSpicesIds.push(tId);
                });
            });
            let spicesDivs = [];
            Object.values(allSpicesIds).forEach((tId) => {
                spicesDivs[tId] = $(`goa_token-${tId}`);
            });

            let progressTrack = document.getElementById(`goa_progress_track_${track}_${activePlayer}`);
            progressTrack.setAttribute("title",_('Select this progress track'));
            this.game.onClick(progressTrack,() => {
                //refresh selection
                [...document.getElementsByClassName('goa_progress_track')].forEach( a => a.classList.remove('selected'));
                progressTrack.classList.add('selected');
                if(withSpices){
                    progressTrack.classList.add('selected_for_client_state');
                    this.bga.states.setClientState("ClientSelectSpices", {
                        descriptionmyturn: '',
                        args: {'track':track, 'spices_ids': allSpicesIds, 'nbSpices':costShips, 'spicesSets': spicesSets,
                            'costSpices': costSpices,
                            'withCard': args.withCard,
                            //keep previous state datas in order to go back when cancel 
                            'previousClientState' : this.game.getCurrentClientStateDatas(),
                         },
                    });
                }
                else {
                    this.bga.actions.performAction('actProgress', { 'track': track, 't_ids': null, 'card_id':args.withCard});
                }
            });
        });
        
        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.game.restorePreviousState(args);
        }, { 'color':'secondary','title':_('Cancel'), destination: document.getElementById('customSecondary') }); 

    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
}