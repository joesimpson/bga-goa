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
            _('${you} must choose the next effect to resolve'):
            _('${actplayer} must choose the next effect to resolve')
        );

        if(isCurrentPlayerActive) this.game.updateHandSelectable(args);
      
        if (isCurrentPlayerActive || true) { // display buttons choices for other players to let them know

            if(possibleActions.includes('actResolveEffect')){
                Object.entries(effects).forEach( ([effectId,effect])  => {
                    directState = (Object.entries(effects).length == 1) ;
                    let nextState = function () { };
                    let tile_id, tile = null;
                    if(effect.sourceTile){
                        tile_id = effect.sourceTile;
                        tile = tiles[tile_id];
                    }
                    if(effect.sourceAction){
                        nextState = function (bga) {
                            bga.states.setClientState("ClientSelectActionEffect", {
                                descriptionmyturn: '',
                                args: { 'effect':effect,
                                    'tile':tile,
                                    'choice_index': effectId,
                                    'cancelable' : !directState, 
                                    'undo': args.undo,
                                    'previousArgs' : args,
                                    //Allow to play Cards/tiles
                                    'possibleDiscards': args.possibleDiscards,
                                    'possiblePlayCards': args.possiblePlayCards,
                                    'possibleActions': args.possibleActions,
                                    'possiblePlayTiles': args.possiblePlayTiles,
                                },
                            });
                        };
                        let actionName = this.game.formatActionName(effect.sourceAction);
                        let effectName =  _('Player action');
                        if(effect.parent){
                            switch(effect.parent.type){
                                case this.game.const.EFFECT_TYPE_ESPIONAGE: effectName = _('Espionage'); break;
                            }
                        }
                        let actionHint = this.bga.gameui.format_string(_('${effect} : ${name}'),{'effect': effectName , 'name': actionName});
                        this.bga.statusBar.addActionButton(actionHint, () => {
                            nextState(this.bga);
                        }, {  
                            'id': `btnEffect_${effectId}`,
                            'destination': document.getElementById('customPrimary'),
                            'title':_('Select this action effect'),  
                        }); 
                    }
                    else if(tile){
                            let tile_icon = this.game.tplTile(tile, '_log');
                            nextState = function (bga) {
                                bga.states.setClientState("ClientSelectTileEffect", {
                                    descriptionmyturn: '',
                                    args: { 'effect':effect, 'tile':tile, 'choice_index': effectId
                                        , 'cancelable' : !directState, 'undo': args.undo,
                                        'tiles' : tiles,
                                        'previousArgs' : args,
                                        //Allow to play Cards/tiles
                                        'possibleDiscards': args.possibleDiscards,
                                        'possiblePlayCards': args.possiblePlayCards,
                                        'possibleActions': args.possibleActions,
                                        'possiblePlayTiles': args.possiblePlayTiles,
                                    },
                                });
                            };
                            this.bga.statusBar.addActionButton( tile_icon, () => {
                                nextState(this.bga);
                            }, {   
                                'id': `btnEffect_${effectId}`,
                                'destination': document.getElementById('customPrimary'),
                                'title':_('Select this tile effect'),  
                            }); 
                    }
                    if(directState){
                        //UNIQUE CHOICE => go for it
                        nextState(this.bga);
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
export class ClientSelectTileEffect {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let tile_choice = args.effect;
        let tile = args.tile;
        let tile_type = tile.type;
        let tile_id = tile.id;
        let tileMaterial = this.game.gamedatas.material.tiles[tile.subtype][tile.type];
        let tile_name = _(tileMaterial.name);
        let icon_contracts = this.game.formatIcon('contracts',_('Contracts'));
        let activePlayerId = this.bga.players.getActivePlayerId();

        this.bga.statusBar.setTitle(
                isCurrentPlayerActive ? 
                _("${tile_name} : ${you} must select one of these gains").replace('${tile_name}',tile_name)
                :
                _("${tile_name} : ${actplayer} must select one of these gains").replace('${tile_name}',tile_name)
            );

        if(isCurrentPlayerActive){
            this.game.updateHandSelectable(args.previousArgs, true);
        }
      
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
                                        'serverAction': 'actResolveEffect',
                                        'choice_index':args.choice_index,
                                        'previousClientState' : this.game.getCurrentClientStateDatas(),
                                    },
                                });
                            }, {
                                'id': `btnEffectChoice_${args.choice_index}_${choice}`,
                                'destination': document.getElementById('customPrimary'),
                                'title': this.game.formatActionName(this.game.const.ACTION_TYPE_PRODUCE),  
                            }); 
                            break;
                        case this.game.const.ACTION_SHIP:
                            let buttonText = this.bga.gameui.format_string(
                                        _("Take ${n} ${resource_name} : ${icon_resource}")
                                        ,{n:tile_choice.quantity, 'resource_name': _('Ships'), icon_resource : this.game.formatIcon('ships',_('Ships'))}
                                    );
                            this.bga.statusBar.addActionButton( buttonText, () => {
                                this.game.customPerformAction("actResolveEffect", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, {
                                'id': `btnEffectChoice_${args.choice_index}_${choice}`,
                                'destination': document.getElementById('customPrimary'),
                                'title':_('Take ships'),  
                            }); 
                            break;
                        case this.game.const.ACTION_CONTRACT:
                            let buttonText1 = this.bga.gameui.format_string(
                                        _("Take ${n} ${resource_name} : ${icon_resource}")
                                        ,{n:tile_choice.quantity, 'resource_name': _('Contracts'), icon_resource : this.game.formatIcon('contracts',_('Contracts'))}
                                    );
                            this.bga.statusBar.addActionButton( buttonText1, () => {
                                this.game.customPerformAction("actResolveEffect", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, {
                                'id': `btnEffectChoice_${args.choice_index}_${choice}`,
                                'destination': document.getElementById('customPrimary'),
                                'title':_('Take contracts'),  }); 

                            break;
                        default: break;
                    }
                break;
                case this.game.const.EFFECT_TYPE_PICK_TILE:
                    let tile_icon = this.game.tplTileFromType(args.tiles[choice].type, '_log');
                    let action = () => { 
                            this.game.customPerformAction("actResolveEffect", { 
                                'tile_id' : tile_id,
                                'choice': choice,
                                'choice_index':args.choice_index,
                            });
                        };
                    this.bga.statusBar.addActionButton( tile_icon, () => {
                            action();
                    }, { 
                            'id': `btnEffectChoice_${args.choice_index}_${choice}`,
                            'destination': document.getElementById('customPrimary'),
                            'title':_('Select this tile'),  
                        }); 
                    //+Click on tile itself if displayed (With Favor tile we displayed tiles on page)
                    let tileToPick = document.getElementById(`goa_tile-${choice}`);
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
                        this.game.autoScrollTo(tileToFill.id);
                    }
                    let spice_icon = this.game.formatSpiceIcon(choice);
                    this.bga.statusBar.addActionButton( spice_icon, () => {
                        this.game.customPerformAction("actResolveEffect", { 
                                'tile_id' : tile_id,
                                'choice': choice,
                                'choice_index':args.choice_index,
                            })
                    }, {  
                            'id': `btnEffectChoice_${args.choice_index}_${choice}`,
                            'destination': document.getElementById('customPrimary'),
                        }); 
                    break;
                case this.game.const.EFFECT_TYPE_REPLACE_TILE:
                    let newtile_icon = this.game.tplTileFromType(args.tile.type, '_log');
                    this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                        _('${you} must choose a Depot to replace on your board by ${tile_icon}').replace('${tile_icon}',newtile_icon) : 
                        _('${actplayer} must choose a Depot to replace on his board by ${tile_icon}').replace('${tile_icon}',newtile_icon) 
                    );
                    this.game.autoScrollTo(`goa_player_plantations-${activePlayerId}`);
                    //click on each selectable tile
                    let tileDiv = document.getElementById(`goa_tile-${choice}`);
                    tileDiv.setAttribute("title",_('Select this tile'));
                    this.game.onClick(tileDiv,() => {
                            this.game.customPerformAction("actResolveEffect", { 
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
                    this.game.autoScrollTo(`goa_board_outposts_1`);
                    let tileToFound= document.getElementById(`goa_outpost-${tile_id}-front`);
                    if(tileToFound){
                        let colony = tileToFound.dataset.colony;
                        let stock = this.game.outpostsTilesStocks[colony];
                        stock.setLastPlayedCards([args.tile]);
                        stock.setOpened(true);
                    }
                    
                    switch(choice){
                        case this.game.const.ACTION_FOUND_ABANDON: 
                            this.bga.statusBar.addActionButton( this.game.formatActionButton(choice,_("Abandon Outpost")), () => {
                            this.game.customPerformAction("actResolveEffect", { 
                                    'tile_id' : tile_id,
                                    'choice': choice,
                                    'choice_index':args.choice_index,
                                })
                        }, {
                            'id': `btnEffectChoice_${args.choice_index}_${choice}`,
                            'destination': document.getElementById('customPrimary'),
                            'title':_('Abandon Outpost'),
                        }); 
                        break;
                        case this.game.const.ACTION_FOUND_FORWARD: 
                            this.bga.statusBar.addActionButton( this.game.formatActionButton(choice,_("Establish Outpost")), () => {
                                this.game.customPerformAction("actResolveEffect", { 
                                        'tile_id' : tile_id,
                                        'choice': choice,
                                        'choice_index':args.choice_index,
                                    })
                            }, {
                                'id': `btnEffectChoice_${args.choice_index}_${choice}`,
                                'destination': document.getElementById('customPrimary'),
                                'title':_('Establish Outpost'),
                            }); 
                        break;
                    }
                    
                    break;
                case this.game.const.EFFECT_TYPE_ESPIONAGE:
                    this.bga.statusBar.setTitle(
                        isCurrentPlayerActive ? 
                        _('${tile_name} : ${you} must choose an action from an opponent board').replace('${tile_name}',tile_name)
                        : _('${tile_name} : ${actplayer} must choose an action from an opponent board').replace('${tile_name}',tile_name)
                    );
                    Object.entries(this.game.gamedatas.players).forEach(([pId, player]) => {
                        if(pId == activePlayerId) return;
                        let opponentId = pId;

                        switch(choice){
                            case this.game.const.ACTION_SHIP: 
                                this.game.addClickHandlerOnProgressMarker(
                                    1,
                                    this.bga.gameui.format_string(_('Player action : ${name}'),{'name': this.game.formatActionName(this.game.const.ACTION_TYPE_BUILD_SHIPS)}),
                                    opponentId,
                                    () => this.game.customPerformAction("actResolveEffect", { 
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
                                    this.bga.gameui.format_string(_('Player action : ${name}'),{'name': this.game.formatActionName(this.game.const.ACTION_TYPE_PRODUCE)}),
                                    opponentId,
                                    () => this.bga.states.setClientState("ClientHarvestResources", {
                                        descriptionmyturn: '',
                                        args: {
                                            'harvestSpots': args.previousArgs.harvestSpots , 
                                            'min': 1,
                                            'max': playersBaseHarvest,
                                            'withTile':tile_id,
                                            'serverAction': 'actResolveEffect',
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
                                    this.bga.gameui.format_string(_('Player action : ${name}'),{'name': this.game.formatActionName(this.game.const.ACTION_TYPE_TAXES)}),
                                    opponentId,
                                    () => this.game.customPerformAction("actResolveEffect", { 
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
                                    this.bga.gameui.format_string(_('Player action : ${name}'),{'name': this.game.formatActionName(this.game.const.ACTION_TYPE_FOUND)}),
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

/// -----------------------------------------------------------
/// -----------------------------------------------------------
/// -----------------------------------------------------------
/// -----------------------------------------------------------

/**
 * Client STATE
 */
export class ClientSelectActionEffect {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let effect = args.effect;
        let activePlayerId = this.bga.players.getActivePlayerId();

        if(isCurrentPlayerActive){
            this.game.updateHandSelectable(args.previousArgs, true);
        }
      
        switch(effect.type){
            case this.game.const.EFFECT_TYPE_FOUND_TILE:
                this.bga.states.setClientState("ClientSelectTileEffect", {
                                    descriptionmyturn: '',
                                    args: { 
                                        'effect':args.effect, 
                                        'tile':args.tile, 
                                        'choice_index': args.choice_index,
                                        'cancelable' : false, //false because 2 successive client states
                                        'undo': false, //false because 2 successive client states
                                        'previousArgs' : args,
                                        //Allow to play Cards/tiles
                                        'possibleDiscards': args.possibleDiscards,
                                        'possiblePlayCards': args.possiblePlayCards,
                                        'possibleActions': args.possibleActions,
                                        'possiblePlayTiles': args.possiblePlayTiles,
                                    },
                                });
                break;
            case this.game.const.EFFECT_TYPE_CLEAN_HAND:
                //CleanHand was before designed as a server state
                this.bga.states.setClientState("CleanHand", {
                    descriptionmyturn: '',
                    args: { 
                        'previousArgs' : args,
                        'maxCards' : args.previousArgs.maxCards,
                        //Allow to play Cards/tiles
                        'possibleDiscards': args.possibleDiscards,
                        'possiblePlayCards': args.possiblePlayCards,
                        'possibleActions': args.possibleActions,
                        'possiblePlayTiles': args.possiblePlayTiles,
                    },
                });
                break;
            default:
                break;
        }

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