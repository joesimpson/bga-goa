
/**
 * Client STATE
 */
export class ClientSelectMarketPlace {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let market_spots = args.spots;
        let withTile = args.withTile;

        this.bga.statusBar.setTitle(
                _("${you} must select a spot on the market to take tile(s) from")
            );

        let actionName = 'actPlayTile';

        if(withTile){
            let tileDiv = document.getElementById(`goa_tile-${withTile}`);
            if(tileDiv){
                tileDiv.classList.add('selected');
                tileDiv.classList.add('selected_for_client_state');
            }
        }

        this.game.addButtonCloseClientState(args);
    
        market_spots.forEach( spot => {
            let marketSpace = $(`goa_market_grid_space-${spot.row}-${spot.col}`);
            this.game.onClick(marketSpace,() => {
                this.game.customPerformAction(actionName, { 
                                    'tile_id':withTile, 
                                    'row': spot.row,
                                    'col': spot.col, 
                                });
            });
            marketSpace.setAttribute("title",_('Select this market place'));
        }); 
    
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
}