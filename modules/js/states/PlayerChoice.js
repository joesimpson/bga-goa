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
        let effects = args.effects;
        let tiles = args.tiles;
        let directState = false;

        this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
            _('${you} must choose an effect to resolve'):
            _('${actplayer} must choose an effect to resolve')
        );

        //We should not select cards to play now in order to not loose our current server choices
        if(isCurrentPlayerActive) this.game.updateHandSelectable(args);
      
        if (isCurrentPlayerActive || true) { // display buttons choices for other players to let them know

            if(possibleActions.includes('actTileChoice')){
                Object.entries(effects).forEach( ([effectId,effect])  => {
                    if(effect.sourceTile){
                        let tile_id = effect.sourceTile;
                        let tile = tiles[tile_id];
                        directState = (Object.entries(effects).length == 1) ;
                        if(tile){
                            let tile_icon = this.game.tplTile(tile, '_log');
                            let nextState = function (bga) {
                                bga.states.setClientState("ClientSelectTileAction", {
                                    descriptionmyturn: '',
                                    args: { 'effect':effect, 'tile':tile, 'choice_index': effectId
                                        , 'cancelable' : !directState, 'undo': args.undo,
                                        'previousArgs' : args,
                                    },
                                });
                            };
                            this.bga.statusBar.addActionButton( tile_icon, () => {
                                nextState(this.bga);
                            }, {  destination: document.getElementById('customPrimary'),'title':_('Select this tile effect'),  }); 
                            if(directState){
                                //UNIQUE CHOICE => go for it
                                nextState(this.bga);
                            }
                        }
                    }
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
        
        let tile_choice = args.effect;
        let tile_id = args.tile.id;
        let icon_contracts = this.game.formatIcon('contracts',_('Contracts'));
        let isOutpost = args.tile.subtype == this.game.const.TILE_SUBTTYPE_OUTPOST;
        let activePlayerId = this.bga.players.getActivePlayerId();

        this.bga.statusBar.setTitle(
                isCurrentPlayerActive ? 
                _("${you} must select one of these gains")
                :
                _("${actplayer} must select one of these gains")
            );
            //TODO JSA tile_name in title

        if(isCurrentPlayerActive) this.game.updateTilesHandSelectable(args.previousArgs);
      
        let choices = args.effect.result;

        Object.entries(choices).forEach( ([choice_id,choice])  => {
            switch(tile_choice.type){
                case this.game.const.EFFECT_TYPE_HARVEST:
                case this.game.const.EFFECT_TYPE_PICK_ACTION:
                default:
                    switch(choice){
                        case this.game.const.ACTION_HARVEST:
                            let buttonText2 = this.bga.gameui.format_string(
                                        _("Produce ${n} goods")
                                        ,{ 'n':tile_choice.quantity, }
                                    );
                            this.bga.statusBar.addActionButton( buttonText2, () => {
                                this.bga.states.setClientState("ClientHarvestResources", {
                                    descriptionmyturn: '',
                                    args: {
                                        'harvestSpots': args.previousArgs.harvestSpots , 
                                        'min': 1,
                                        'max': tile_choice.quantity,
                                        'withTile':tile_id,
                                        'serverAction': 'actTileChoice',
                                        'choice_index':args.choice_index,
                                        'previousClientState' : this.game.getCurrentClientStateDatas(),
                                    },
                                });
                            }, {'destination': document.getElementById('customPrimary'),'title':_('Produce'),  }); 
                            break;
                        case this.game.const.ACTION_SHIP:
                            let buttonText = this.bga.gameui.format_string(
                                        _("Take ${n} ${resource_name} : ${icon_resource}")
                                        ,{n:tile_choice.quantity, 'resource_name': _('Ships'), icon_resource : this.game.formatIcon('ships',_('Ships'))}
                                    );
                            this.bga.statusBar.addActionButton( buttonText, () => {
                                this.game.customPerformAction("actTileChoice", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, {destination: document.getElementById('customPrimary'),'title':_('Take ships'),  }); 
                            break;
                        case this.game.const.ACTION_CONTRACT:
                            let buttonText1 = this.bga.gameui.format_string(
                                        _("Take ${n} ${resource_name} : ${icon_resource}")
                                        ,{n:tile_choice.quantity, 'resource_name': _('Contracts'), icon_resource : this.game.formatIcon('contracts',_('Contracts'))}
                                    );
                            this.bga.statusBar.addActionButton( buttonText1, () => {
                                this.game.customPerformAction("actTileChoice", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, { destination: document.getElementById('customPrimary'),'title':_('Take contracts'),  }); 

                            break;
                        default: break;
                    }
                break;
                case this.game.const.EFFECT_TYPE_PICK_TILE:
                    let tile_icon = this.game.tplTileFromType(choice, '_log');
                    let action = () => { 
                            this.game.customPerformAction("actTileChoice", { 
                                'tile_id' : tile_id,
                                'choice': choice_id,
                                'choice_index':args.choice_index,
                            });
                        };
                    this.bga.statusBar.addActionButton( tile_icon, () => {
                            action();
                    }, {  destination: document.getElementById('customPrimary'),'title':_('Select this tile'),  }); 
                    //+Click on tile itself if displayed (With Favor tile we displayed tiles on page)
                    let tileToPick = document.getElementById(`goa_tile-${choice_id}`);
                    if(tileToPick){
                        this.game.onClick(tileToPick,() => {
                            action();
                        });
                    }
                    break;
                case this.game.const.EFFECT_TYPE_PICK_SPICE:
                    this.bga.statusBar.setTitle(
                            isCurrentPlayerActive ? 
                            _("${you} must select the next good to fill that depot/outpost") :
                            _("${actplayer} must select the next good to fill the depot/outpost")
                        );
                    let tileToFill = document.getElementById(`goa_tile-${tile_id}`);
                    //if(!tileToFill && isOutpost) tileToFill = document.getElementById(`goa_outpost-${tile_id}`);
                    if(tileToFill){
                        tileToFill.classList.add('selected');
                        tileToFill.scrollIntoView({'behavior': 'smooth', 'block':'center'});
                    }
                    let spice_icon = this.game.formatSpiceIcon(choice);
                    this.bga.statusBar.addActionButton( spice_icon, () => {
                        this.game.customPerformAction("actTileChoice", { 
                                'tile_id' : tile_id,
                                'choice': choice,
                                'choice_index':args.choice_index,
                            })
                    }, {  destination: document.getElementById('customPrimary'), }); 
                    break;
                case this.game.const.EFFECT_TYPE_REPLACE_TILE:
                    let newtile_icon = this.game.tplTileFromType(args.tile.type, '_log');
                    this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                        _('${you} must choose a Depot to replace on your board by ${tile_icon}').replace('${tile_icon}',newtile_icon) : 
                        _('${actplayer} must choose a Depot to replace on his board by ${tile_icon}').replace('${tile_icon}',newtile_icon) 
                    );
                    //click on each selectable tile
                    let tileDiv = document.getElementById(`goa_tile-${choice}`);
                    tileDiv.setAttribute("title",_('Select this tile'));
                    this.game.onClick(tileDiv,() => {
                            this.game.customPerformAction("actTileChoice", { 
                                'tile_id' : tile_id,
                                'choice': choice,
                                'choice_index':args.choice_index,
                            });
                        });
                        
                    break;
                case this.game.const.EFFECT_TYPE_FOUND_TILE:
                    let missingContracts = tile_choice.quantity;
                    this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                        _('${you} must establish the outpost by spending ${n} ${icon_contracts} OR abandon to receive ${x} ${icon_contracts}').replaceAll('${icon_contracts}', icon_contracts).replace('${n}', missingContracts).replace('${x}', 1): 
                        _('${actplayer} must establish the outpost by spending ${n} ${icon_contracts} OR abandon to receive ${x} ${icon_contracts}').replaceAll('${icon_contracts}', icon_contracts).replace('${n}', missingContracts).replace('${x}', 1)
                    );
                    let tileToFound= document.getElementById(`goa_outpost-${tile_id}-front`);
                    let colony = tileToFound.dataset.colony;
                    let stock = this.game.outpostsTilesStocks[colony];
                    stock.setLastPlayedCards([args.tile]);
                    
                    switch(choice){
                        case this.game.const.ACTION_FOUND_ABANDON: 
                            this.bga.statusBar.addActionButton( _("Abandon Outpost"), () => {
                            this.game.customPerformAction("actTileChoice", { 
                                    'tile_id' : tile_id,
                                    'choice': choice,
                                    'choice_index':args.choice_index,
                                })
                        }, {destination: document.getElementById('customPrimary'),'title':_('Abandon Outpost'),  }); 
                        break;
                        case this.game.const.ACTION_FOUND_FORWARD: 
                            this.bga.statusBar.addActionButton( _("Establish Outpost"), () => {
                                this.game.customPerformAction("actTileChoice", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, {destination: document.getElementById('customPrimary') ,'title':_('Establish Outpost'),}); 
                        break;
                    }
                    
                    break;
                case this.game.const.EFFECT_TYPE_ESPIONAGE:
                    this.bga.statusBar.setTitle(
                        isCurrentPlayerActive ? 
                        _('Espionage : ${you} must choose an action from an opponent board') 
                        : _('Espionage : ${actplayer} must choose an action from an opponent board') 
                    );
                    Object.entries(this.game.gamedatas.players).forEach(([pId, player]) => {
                        if(pId == activePlayerId) return;
                        let opponentId = pId;

                        switch(choice){
                            case this.game.const.ACTION_SHIP: 
                                this.game.addClickHandlerOnProgressMarker(
                                    1,
                                    this.bga.gameui.format_string(_('Action : ${name}'),{'name':  _('Build Ships')}),
                                    opponentId,
                                    () => this.game.customPerformAction("actTileChoice", { 
                                                'tile_id' : tile_id,
                                                'choice': choice,
                                                'choice_index':args.choice_index,
                                                'progressRefPlayerId': opponentId,
                                            }),
                                );
                                break;
                            case this.game.const.ACTION_HARVEST:
                                let playersBaseHarvest = args.previousArgs.playersBaseHarvest[opponentId];
                                this.game.addClickHandlerOnProgressMarker(
                                    2,
                                    this.bga.gameui.format_string(_('Action : ${name}'),{'name':  _('Produce')}),
                                    opponentId,
                                    () => this.bga.states.setClientState("ClientHarvestResources", {
                                        descriptionmyturn: '',
                                        args: {
                                            'harvestSpots': args.previousArgs.harvestSpots , 
                                            'min': 1,
                                            'max': playersBaseHarvest,
                                            'withTile':tile_id,
                                            'serverAction': 'actTileChoice',
                                            'choice_index':args.choice_index,
                                            'progressRefPlayerId': opponentId,
                                            'previousClientState' : this.game.getCurrentClientStateDatas(),
                                        },
                                    }),
                                );
                                break;
                            case this.game.const.ACTION_MONEY: 
                                this.game.addClickHandlerOnProgressMarker(
                                    3,
                                    this.bga.gameui.format_string(_('Action : ${name}'),{'name':  _('Collect Taxes')}),
                                    opponentId,
                                    () => this.game.customPerformAction("actTileChoice", { 
                                                'tile_id' : tile_id,
                                                'choice': choice,
                                                'choice_index':args.choice_index,
                                                'progressRefPlayerId': opponentId,
                                            }),
                                );
                                break;
                            case this.game.const.ACTION_FOUND_ABANDON: 
                                let playerBaseContracts = args.previousArgs.playersBaseContracts[opponentId];
                                this.game.addClickHandlerOnProgressMarker(
                                    5,
                                    this.bga.gameui.format_string(_('Action : ${name}'),{'name':  _('Establish Outpost')}),
                                    opponentId,
                                    () => this.bga.states.setClientState("ClientFoundOutpost", {
                                            descriptionmyturn: '',
                                            args: {
                                                'outpostsToFound': args.previousArgs.outpostsToFound,
                                                'playerBaseContracts':playerBaseContracts,
                                                'tile_choice': {
                                                    'tile_id' : tile_id,
                                                    'choice': choice,
                                                    'choice_index':args.choice_index,
                                                    'progressRefPlayerId': opponentId,
                                                },
                                                'previousClientState' : this.game.getCurrentClientStateDatas(),
                                            },
                                        }),
                                );
                                break;
                        }
                    });
                    break;
            }
        });

        if(args.cancelable){
            this.game.addButtonCloseClientState(args);
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