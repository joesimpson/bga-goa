
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
                
            let spicesIcons = '';
            Object.entries(costSpices).forEach( ([spice, cost]) => {
                new Array(cost).fill(0).forEach( x => { 
                    spicesIcons += this.game.formatIcon('spices_'+spice);
                });
            });
            let progressTrack = document.getElementById(`goa_progress_track_${track}_${activePlayer}`);
            this.game.onClick(progressTrack,() => {
                //refresh selection
                [...document.getElementsByClassName('goa_progress_track')].forEach( a => a.classList.remove('selected'));
                progressTrack.classList.add('selected');
                //refresh button text
                let button = document.getElementById('goa_button_progress');
                if(button) this.game.destroy(button);
                this.bga.statusBar.addActionButton( 
                    this.bga.gameui.format_string( 
                        _('Spend ${n} ${icon_ship} and ${spices}'), 
                        {'n':costShips, 'icon_ship':this.game.formatIcon('ships'), 'spices': spicesIcons  }),
                    () => { 
                        //TODO JSA ANOTHER CLIENT STATE to select where we pick spices to spend
                        this.bga.actions.performAction("actProgress", {'track': track})
                    }
                , { id:'goa_button_progress',destination: document.getElementById('customActions') }); 
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