/**
 * We create one State class per declared state on the PHP side, to handle all state specific code here.
 * onEnteringState, onLeavingState and onPlayerActivationChange are predefined names that will be called by the framework.
 * When executing code in this state, you can access the args using this.args
 */

export class PlayerChoice {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let possibleActions = args.possibleActions;
        let tiles_choices = args.tiles_choices;
        let tiles = args.tiles;

        this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
            _('${you} must choose a tile action'):
            _('${actplayer} must choose a tile action')
        );
      
        if (isCurrentPlayerActive) {

            if(possibleActions.includes('actTileChoice')){
                Object.values(tiles_choices).forEach( tile_choice => {
                    let tile_id = tile_choice.tile_id;
                    let tile = tiles[tile_id];
                    if(tile){
                        let tile_icon = this.game.tplTile(tile, '_log');
                        this.bga.statusBar.addActionButton( tile_icon, () => {
                            //TODO JSA CLIENT STATE
                        }, { destination: document.getElementById('customActions') }); 
                    }
                });

            }

        }
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
}
