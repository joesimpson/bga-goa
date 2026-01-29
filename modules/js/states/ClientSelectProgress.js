
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
        let tracksDatas = args.progressTracks;
        Object.values(tracksDatas).forEach( trackData => {
            let track = trackData.track;
            let costShips = trackData.costShips;
            let costSpices = trackData.costSpices;
            let spicesSets = [[1022,1027],[1022,1028],[1023,1027],[1023,1028]];// TODO JSA SERVER SIDE
                
            let spicesIcons = '';
            let allSpicesIds = [];
            Object.entries(costSpices).forEach( ([spice, cost]) => {
                Object.values(cost.from).forEach((tId) => {
                    allSpicesIds.push(tId);
                });
                new Array(cost.n).fill(0).forEach( x => { 
                    spicesIcons += this.game.formatIcon('spices_'+spice);
                });
            });
            let spicesDivs = [];
            Object.values(allSpicesIds).forEach((tId) => {
                spicesDivs[tId] = $(`goa_token-${tId}`);
            });

            let progressTrack = document.getElementById(`goa_progress_track_${track}_${activePlayer}`);
            this.game.onClick(progressTrack,() => {
                //refresh selection
                [...document.getElementsByClassName('goa_progress_track')].forEach( a => a.classList.remove('selected'));
                progressTrack.classList.add('selected');
                //refresh button text
                /*
                let button = document.getElementById('goa_button_progress');
                if(button) this.game.destroy(button);
                this.bga.statusBar.addActionButton( 
                    this.bga.gameui.format_string( 
                        _('Spend ${n} ${icon_ship} and ${spices}'), 
                        {'n':costShips, 'icon_ship':this.game.formatIcon('ships'), 'spices': spicesIcons  }),
                    () => { 
                        //this.bga.actions.performAction("actProgress", {'track': track});
                       
                    }
                , { id:'goa_button_progress',destination: document.getElementById('customActions') }); 
                */
        
                this.game.onSelectN(spicesDivs, costShips, (selectedSpices) => {
                    this.bga.actions.performAction('actProgress', { 'track': track, t_ids: selectedSpices.join(',')});
                }, spicesSets, true, this.bga.gameui.format_string( 
                        _('Spend ${n} ${icon_ship} and ${spices}'), 
                        {'n':costShips, 'icon_ship':this.game.formatIcon('ships'), 'spices': spicesIcons  }));
            });
        });
        
        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.bga.states.restoreServerGameState();
        }, { 'color':'secondary',destination: document.getElementById('restartActions') }); 

    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
}