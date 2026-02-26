
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
                    _("${you} must select an action track to improve on your board")
                );

        
        if(args.withTile){
            let tileSelected = document.getElementById(`goa_tile-${args.withTile}`);
            if(tileSelected) tileSelected.classList.add('selected');
        }

        let tracksDatas = args.progressTracks;
        Object.values(tracksDatas).forEach( trackData => {
            let track = trackData.track;
            let costShips = trackData.costShips;
            let withSpices = trackData.withSpices;
            let withShips = trackData.withShips;
            let costSpices = trackData.costSpices;
            let spicesSets = trackData.spicesSets;// [[1022,1027],[1022,1028],[1023,1027],[1023,1028]];

            let progressTrack = document.getElementById(`goa_progress_track_${track}_${activePlayer}`);
            progressTrack.setAttribute("title",_('Select this action track'));
            this.game.onClick(progressTrack,() => {
                //refresh selection
                [...document.getElementsByClassName('goa_progress_track')].forEach( a => a.classList.remove('selected'));
                progressTrack.classList.add('selected');
                if(withSpices){
                    progressTrack.classList.add('selected_for_client_state');
                    this.bga.states.setClientState("ClientSelectSpicesForProgress", {
                        descriptionmyturn: '',
                        args: {'track':track, 'nbSpices':costShips, 'spicesSets': spicesSets,
                            'costSpices': costSpices,
                            'withCard': args.withCard,
                            //keep previous state datas in order to go back when cancel 
                            'previousClientState' : this.game.getCurrentClientStateDatas(),
                         },
                    });
                }
                else {
                    this.game.customPerformAction('actProgress', { 'track': track, 't_ids': null, 'card_id':args.withCard, 'tile_id':args.withTile});
                }
            });
        });
        
        this.game.addButtonCloseClientState(args);
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
}