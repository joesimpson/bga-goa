
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
        let tracks = args.progressTracks;
        Object.values(tracks).forEach( track => {
            let progressTrack = document.getElementById(`goa_progress_track_${track}_${activePlayer}`);
            this.game.onClick(progressTrack,() => this.bga.actions.performAction("actProgress", {'track': track}));
        });
        
        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.bga.states.restoreServerGameState();
        }, { 'color':'secondary',destination: document.getElementById('customActions') }); 

    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
}