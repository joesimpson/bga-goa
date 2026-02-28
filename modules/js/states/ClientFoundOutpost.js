
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
        let baseContractsWithAction = args.playerBaseContracts;

        let actionName = 'actFoundOutpost';
        let actionArgs = {};
        let fromTileChoice = args.tile_choice;
        if(fromTileChoice){
            actionName = 'actTileChoice';
            actionArgs = { 
                        'tile_id' : fromTileChoice.tile_id,
                        'choice': fromTileChoice.choice,
                        'choice_index':fromTileChoice.choice_index,
                        'progressRefPlayerId': fromTileChoice.progressRefPlayerId,
                    };
        }
        
        this.bga.statusBar.setTitle(
                   _("${you} must select a new outpost to establish with a base of ${n} ${icon_contracts}").replace('${icon_contracts}', this.game.formatIcon('contracts',_('Contracts'))).replace('${n}', baseContractsWithAction)
                );
    
        document.getElementById(`goa_board_outposts_1`).scrollIntoView({'behavior': 'smooth', 'block':'center'});
            
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

            outpostDiv.setAttribute("title",_('Select this tile'));
            this.game.onClick(outpostDiv, () => {
                //unselect before select
                this.game.outpostsTilesStocks.forEach((s) => { s.unselectAll();});
                stock.selectCard({id: outpostId});
                //refresh button text
                let button = document.getElementById('goa_button_found');
                if(button) this.game.destroy(button);
                this.bga.statusBar.addActionButton( 
                    this.bga.gameui.format_string( 
                        _('Establish ${name} : requires ${n} ${icon_contracts}'), 
                        {'name': colonyName, 'n':outpostMinContractsNeeded, 'icon_contracts':this.game.formatIcon('contracts',_('Contracts')), }),
                    () => { 
                        if(fromTileChoice){
                            actionArgs.arr1 = outpostId;
                        }
                        else {
                            actionArgs.tile_id = outpostId;
                            actionArgs.card_id = args.withCard;
                        }
                        this.game.customPerformAction(actionName, actionArgs);
                    }
                , { id:'goa_button_found', 'title':this.bga.gameui.format_string(_('Action : ${name}'),{'name':  _('Establish Outpost')}), destination: document.getElementById('customPrimary') }); 
            });
        });
    
        this.game.addButtonCloseClientState(args);
    }

    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }
 
}