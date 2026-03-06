
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
        let tokens_ids = args.spendSpices.t_ids;
        let spicesDivs = [];
        let withCard = args.withCard;
        let withTile = args.withTile;

        /*
        let plantations = document.getElementById(`goa_player_plantations-${activePlayer}`);
        let outposts = document.getElementById(`goa_player_outposts-${activePlayer}`);
        [...plantations.getElementsByClassName('goa_token'), ...outposts.getElementsByClassName('goa_token')].forEach((node) => {
            spicesDivs[node.dataset.id] = node;
        });
        */
        Object.values(tokens_ids).forEach((tId) => {
            //spicesDivs[tId] = $(`goa_token-${tId}`);
            //click on goa_tile_spot
            spicesDivs[tId] = $(`goa_token-${tId}`).parentNode;
        });
     
        this.bga.statusBar.setTitle(
            minSpices == nbSpices ? 
                _("${you} must select ${n} Goods to discard").replace('${n}',nbSpices)
                :
                _("${you} must select ${a} to ${b} Goods to discard").replace('${a}',minSpices).replace('${b}',nbSpices)
            );
        let actionName = 'actSpendSpices';

        if(withTile){
            let tileDiv = document.getElementById(`goa_tile-${withTile}`);
            tileDiv.classList.add('selected');
            tileDiv.classList.add('selected_for_client_state');
        }

        this.game.addButtonCloseClientState(args);
            
        let selectionHint = _('Select this Good token');
        this.game.onSelectN(spicesDivs, nbSpices, (selectedSpices) => {
            let sortedTokens = selectedSpices.sort((a,b) =>  a - b );
            this.game.customPerformAction(actionName, { 't_ids': sortedTokens.join(','), 'card_id': withCard, 'tile_id': withTile, });
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