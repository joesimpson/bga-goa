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
        let directState = false;

        this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
            _('${you} must choose a tile action'):
            _('${actplayer} must choose a tile action')
        );
      
        if (isCurrentPlayerActive || true) { // display buttons choices for other players to let them know

            if(possibleActions.includes('actTileChoice')){
                Object.values(tiles_choices).forEach( tile_choice_entry => {
                    //Object.values(tile_choice_entry).forEach( tile_choice => {
                    Object.entries(tile_choice_entry).forEach( ([tile_choice_entry_index,tile_choice])  => {
                        let tile_id = tile_choice.tile_id;
                        let tile = tiles[tile_id];
                        directState = (Object.entries(tiles_choices).length == 1) && (Object.entries(tile_choice_entry).length == 1);
                        if(tile){
                            let tile_icon = this.game.tplTile(tile, '_log');
                            let nextState = function (bga) {
                                bga.states.setClientState("ClientSelectTileAction", {
                                    descriptionmyturn: _("${you} must select one of these"),
                                    description : _("${actplayer} must select one of these"),
                                    args: { 'tile_choice':tile_choice, 'tile':tile, 'choice_index': tile_choice_entry_index
                                        , 'cancelable' : !directState, 'undo': args.undo,
                                    },
                                });
                            };
                            this.bga.statusBar.addActionButton( tile_icon, () => {
                                nextState(this.bga);
                            }, {  destination: document.getElementById('customPrimary'),'title':_('Select this tile'),  }); 
                            if(directState){
                                //UNIQUE CHOICE => go for it
                                nextState(this.bga);
                            }
                        }
                    });
                });

            }

        }
        if(isCurrentPlayerActive && !directState) this.game.confirmTurn.displayUndoButtons(args);
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
}
/// -----------------------------------------------------------
/// -----------------------------------------------------------
/// -----------------------------------------------------------
/// -----------------------------------------------------------

/**
 * Client STATE
 */
export class ClientSelectTileAction {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let tile_choice = args.tile_choice;
        let tile_id = args.tile.id;
        let icon_contracts = this.game.formatIcon('contracts',_('Contracts'));
        let isOutpost = args.tile.subtype == this.game.const.TILE_SUBTTYPE_OUTPOST;
      
        Object.entries(tile_choice.choices).forEach( ([choice_id,choice])  => {
            switch(tile_choice.selectType){
                case this.game.const.CHOICE_PICK_ACTION:
                default:
                    switch(choice){
                        case this.game.const.ACTION_SHIP:
                            let buttonText = this.bga.gameui.format_string(
                                        _("Take ${n} ${resource_name} : ${icon_resource}")
                                        ,{n:tile_choice.n, 'resource_name': _('Ships'), icon_resource : this.game.formatIcon('ships',_('Ships'))}
                                    );
                            this.bga.statusBar.addActionButton( buttonText, () => {
                                this.bga.actions.performAction("actTileChoice", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, {destination: document.getElementById('customPrimary'),'title':_('Take ships'),  }); 
                            break;
                        case this.game.const.ACTION_CONTRACT:
                            let buttonText1 = this.bga.gameui.format_string(
                                        _("Take ${n} ${resource_name} : ${icon_resource}")
                                        ,{n:tile_choice.n, 'resource_name': _('Contracts'), icon_resource : this.game.formatIcon('contracts',_('Contracts'))}
                                    );
                            this.bga.statusBar.addActionButton( buttonText1, () => {
                                this.bga.actions.performAction("actTileChoice", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, { destination: document.getElementById('customPrimary'),'title':_('Take contracts'),  }); 

                            break;
                        default: break;
                    }
                break;
                case this.game.const.CHOICE_PICK_TILE:
                    let tile_icon = this.game.tplTileFromType(choice, '_log');
                    this.bga.statusBar.addActionButton( tile_icon, () => {
                        this.bga.actions.performAction("actTileChoice", { 
                                'tile_id' : tile_id,
                                'choice': choice_id,
                                'choice_index':args.choice_index,
                            })
                    }, {  destination: document.getElementById('customPrimary'),'title':_('Select this tile'),  }); 
                    break;
                case this.game.const.CHOICE_PICK_SPICE:
                    let tileToFill = document.getElementById(`goa_tile-${tile_id}`);
                    //if(!tileToFill && isOutpost) tileToFill = document.getElementById(`goa_outpost-${tile_id}`);
                    if(tileToFill) tileToFill.classList.add('selected');
                    let spice_icon = this.game.formatIcon('spices_'+choice,_('Spices'));
                    this.bga.statusBar.addActionButton( spice_icon, () => {
                        this.bga.actions.performAction("actTileChoice", { 
                                'tile_id' : tile_id,
                                'choice': choice,
                                'choice_index':args.choice_index,
                            })
                    }, {  destination: document.getElementById('customPrimary'),'title':_('Select this spice'),  }); 
                    break;
                case this.game.const.CHOICE_REPLACE_TILE:
                    this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                        _('${you} must choose a plantation to replace on your board'): 
                        _('${actplayer} must choose a plantation to replace on his board')
                    );
                    //click on each selectable tile
                    let tileDiv = document.getElementById(`goa_tile-${choice}`);
                    tileDiv.setAttribute("title",_('Select this tile'));
                    this.game.onClick(tileDiv,() => {
                            this.bga.actions.performAction("actTileChoice", { 
                                'tile_id' : tile_id,
                                'choice': choice,
                                'choice_index':args.choice_index,
                            });
                        });
                        
                    break;
                case this.game.const.CHOICE_FOUND_TILE:
                    let missingContracts = tile_choice.n;
                    this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                        _('${you} must found the outpost by spending ${n} ${icon_contracts} or abandon and receive ${x} ${icon_contracts}').replaceAll('${icon_contracts}', icon_contracts).replace('${n}', missingContracts).replace('${x}', 1): 
                        _('${actplayer} must found the outpost by spending ${n} ${icon_contracts} or abandon and receive ${x} ${icon_contracts}').replaceAll('${icon_contracts}', icon_contracts).replace('${n}', missingContracts).replace('${x}', 1)
                    );
                    let tileToFound= document.getElementById(`goa_outpost-${tile_id}-front`);
                    let colony = tileToFound.dataset.colony;
                    let stock = this.game.outpostsTilesStocks[colony];
                    stock.setLastPlayedCards([args.tile]);
                    
                    switch(choice){
                        case this.game.const.ACTION_FOUND_ABANDON: 
                            this.bga.statusBar.addActionButton( _("Abandon Outpost"), () => {
                            this.bga.actions.performAction("actTileChoice", { 
                                    'tile_id' : tile_id,
                                    'choice': choice,
                                    'choice_index':args.choice_index,
                                })
                        }, {destination: document.getElementById('customPrimary'),'title':_('Abandon Outpost'),  }); 
                        break;
                        case this.game.const.ACTION_FOUND_FORWARD: 
                            this.bga.statusBar.addActionButton( _("Found Outpost"), () => {
                                this.bga.actions.performAction("actTileChoice", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, {destination: document.getElementById('customPrimary') ,'title':_('Found Outpost'),}); 
                        break;
                    }
                    
                    break;
            }
        });

        if(args.cancelable){
            this.bga.statusBar.addActionButton( _('Cancel'), () => {
                this.bga.states.restoreServerGameState();
            }, { 'color':'secondary','title':_('Cancel'),destination: document.getElementById('customSecondary') }); 
        }
        if(isCurrentPlayerActive) this.game.confirmTurn.displayUndoButtons(args);

    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }
 
}