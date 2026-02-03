
/**
 * Client STATE
 */
export class ClientFoundOutpost {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
        
        let outpostsIds = args.outpostsToFound; 
    
        this.game.outpostsTilesStocks.forEach((stock) => { 
            stock.setSelectionMode('single');
            stock.setSelectableCards(stock.getCards().filter((card, index) => outpostsIds.includes(card.id)));
        });
        
        Object.values(outpostsIds).forEach( outpostId => {
            let outpostDiv = document.getElementById(`goa_outpost-${outpostId}-front`);
            let colony = outpostDiv.dataset.colony;
            let outpostMinContractsNeeded = this.game.getNbContractsForColony(colony);
            let colonyName = this.game.getColonyName(colony);
            let stock = this.game.outpostsTilesStocks[colony];

            this.game.onClick(outpostDiv, () => {
                //unselect before select
                this.game.outpostsTilesStocks.forEach((s) => { s.unselectAll();});
                stock.selectCard({id: outpostId});
                //refresh button text
                let button = document.getElementById('goa_button_found');
                if(button) this.game.destroy(button);
                this.bga.statusBar.addActionButton( 
                    this.bga.gameui.format_string( 
                        _('Found ${name} : requires ${n} ${icon_contracts}'), 
                        {'name': colonyName, 'n':outpostMinContractsNeeded, 'icon_contracts':this.game.formatIcon('contracts',_('Contracts')), }),
                    () => { 
                        this.bga.actions.performAction('actFoundOutpost', { 'tile_id': outpostId,});
                    }
                , { id:'goa_button_found',destination: document.getElementById('customPrimary') }); 
            });
        });
    
        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.bga.states.restoreServerGameState();
        }, { 'color':'secondary',destination: document.getElementById('customSecondary') }); 

    }

    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }
 
}