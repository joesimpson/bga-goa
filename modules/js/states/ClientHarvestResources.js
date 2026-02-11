
/**
 * Client STATE
 */
export class ClientHarvestResources {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let activePlayer = this.bga.players.getActivePlayerId();

        let maxSpices = args.max;
        let minSpices = args.min;
        let harvestSpots = args.harvestSpots;
        let tiles_ids = Object.keys(harvestSpots);
        let withAction = args.withAction;
        let withCard = args.withCard;
        let withTile = args.withTile;

        let tilesDivs = [];
        Object.values(tiles_ids).forEach((tId) => {
            tilesDivs[tId] = $(`goa_tile-${tId}`);
            //TODO JSA LOOP ON tiles spots
        });
     
        this.bga.statusBar.setTitle(
            minSpices == maxSpices ? 
                _("${you} must select ${n} spices to place on your tiles").replace('${n}',maxSpices)
                :
                _("${you} must select ${a} to ${b} spices to place on your tiles").replace('${a}',minSpices).replace('${b}',maxSpices)
            );
        let actionName = 'actHarvest';

        if(withTile){
            let tileDiv = document.getElementById(`goa_tile-${withTile}`);
            tileDiv.classList.add('selected');
            tileDiv.classList.add('selected_for_client_state');
        }

        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.game.restorePreviousState(args);
        }, { 'color':'secondary','title':_('Cancel'), destination: document.getElementById('customSecondary') }); 
            
        let selectionHint = _('Select this tile');
        this.game.onSelectN(tilesDivs, maxSpices, (selectedElements) => {
            this.game.customPerformAction(actionName, { 't_ids': selectedElements.join(','), 'card_id': withCard, 'tile_id': withTile, });
        }, null, true, _('Confirm'), selectionHint, minSpices );
    
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }
 
}