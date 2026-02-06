
/**
 * Client STATE
 */
export class ClientSpendSpices {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let activePlayer = this.bga.players.getActivePlayerId();

        let nbSpices = args.spendSpices.max;
        let minSpices = args.spendSpices.min;
        //TODO JSA t_ids
        let spicesDivs = [];
        let withCard = args.withCard;
        let withTile = args.withTile;

        let plantations = document.getElementById(`goa_player_plantations-${activePlayer}`);
        let outposts = document.getElementById(`goa_player_outposts-${activePlayer}`);
        [...plantations.getElementsByClassName('goa_token'), ...outposts.getElementsByClassName('goa_token')].forEach((node) => {
            spicesDivs[node.dataset.id] = node;
        });
     
        this.bga.statusBar.setTitle(
                _("${you} must select ${a} to ${b} spices to discard").replace('${a}',minSpices).replace('${b}',nbSpices)
            );
        let actionName = 'actSpendSpices';
            
        let selectionHint = _('Select this spice token');
        this.game.onSelectN(spicesDivs, nbSpices, (selectedSpices) => {
            this.game.customPerformAction(actionName, { 't_ids': selectedSpices.join(','), 'card_id': withCard, 'tile_id': withTile, });
        }, null, false, _('Confirm'), selectionHint, minSpices );
    
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