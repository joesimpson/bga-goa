
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
        
        let maxSpices = args.max;
        let minSpices = args.min;
        let maxTiles = args.maxTiles;
        let harvestSpots = args.harvestSpots;
        let withCard = args.withCard;
        let withTile = args.withTile;
        let otherResources = (args.others) ? args.others : [];
        let activePlayer = this.bga.players.getActivePlayerId();

        this.bga.statusBar.setTitle(
            minSpices == maxSpices ? 
                _("${you} must select ${n} Goods to place on your tiles").replace('${n}',maxSpices)
                :
                _("${you} must select ${a} to ${b} Goods to place on your tiles").replace('${a}',minSpices).replace('${b}',maxSpices)
            );
        if(maxTiles){
            this.bga.statusBar.setTitle(
                _("${you} must select Goods to place on ${n} tiles").replace('${n}',maxTiles)
            );
        }
        let displayConfirmAsDisabled = false;    
        let icon_ships = this.game.formatIcon('ships', _('Ships'));
        let icon_contracts = this.game.formatIcon('contracts', _('Contracts'));
        if(otherResources.includes(this.game.const.ACTION_SHIP)  
            && otherResources.includes(this.game.const.ACTION_CONTRACT)   
        ){
            this.bga.statusBar.setTitle( _("${you} must select a combination of ${n} ${icon_ships}, ${icon_contracts} and Goods to place on your tiles").replace('${n}',maxSpices) .replace('${icon_ships}',icon_ships).replace('${icon_contracts}',icon_contracts ) ) ;
            displayConfirmAsDisabled = true;
        }

        let actionName = 'actHarvest';
        if(args.serverAction) actionName = args.serverAction;

        if(withTile){
            let tileDiv = document.getElementById(`goa_tile-${withTile}`);
            if(tileDiv){
                tileDiv.classList.add('selected');
                tileDiv.classList.add('selected_for_client_state');
            }
        }

        this.game.addButtonCloseClientState(args);
        
        this.game.autoScrollTo(`goa_playerboard_top_zone-${activePlayer}`);
            
        let selectionHint = _('Select this tile field to fill');
        this.onSelectNSpiceSpots(harvestSpots, minSpices, maxSpices, (selectedResources, selectedShips, selectedContracts) => {
            //SORT server params in order to keep same order in replays/tutorials
            let sortedIds = Array.from(selectedResources.keys()).sort((a,b) =>  a - b );
            let sortedResources = [];
            sortedIds.forEach( a => {
                sortedResources.push(selectedResources.get(a));
            });

            let actionArgs = {};
            switch(actionName){
                case 'actResolveEffect' : 
                    //when we come from ClientSelectTileEffect
                    let progressRefPlayerId = args.progressRefPlayerId;
                    actionArgs = { 
                        'tile_id' : withTile,
                        'choice': this.game.const.ACTION_HARVEST,
                        'choice_index':args.choice_index,
                        'arr1': sortedIds.join(','),
                        'arr2': sortedResources.join(','), 
                        'progressRefPlayerId': progressRefPlayerId,
                    };
                    break;
                default:
                    actionArgs = { 
                        'spots': sortedIds.join(','),
                        'res': sortedResources.join(','), 
                        'card_id': withCard, 
                        'tile_id': withTile, 
                        'ships':selectedShips, 
                        'contracts':selectedContracts,
                    };
                    break;
            }
            this.game.customPerformAction(actionName, actionArgs);
        }, otherResources, displayConfirmAsDisabled, selectionHint, maxTiles );
    
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }


    //----------------------------------------
    
    onSelectNSpiceSpots(harvestSpots, minElements, maxElements, callbackOnSelectionEnd, otherResources, displayConfirmAsDisabled = false, selectionHint = null, maxTiles = null) {
        
        let activePlayer = this.bga.players.getActivePlayerId();
        let selectedResources = new Map();
        let selectedTiles = [];
        let selectedShips = 0;
        let selectedContracts = 0;
        let confirmText = '';
        
        let elements = [];
        //LOOP ON tiles spots
        Object.values(harvestSpots).forEach((datas) => {
                let tileId = datas.tile_id;
                let index = datas.index;
                elements[datas.id] = $(`goa_tile_spot-${tileId}-${index}`);
            selectedTiles[tileId] = 0;
        });
     
        let harvestTopZone = document.getElementById(`goa_playerboard_top_zone-${activePlayer}`);

        let icon_ships = this.game.formatIcon('ships', _('Ships'));
        let icon_contracts = this.game.formatIcon('contracts', _('Contracts'));
        if(otherResources.includes(this.game.const.ACTION_SHIP)  
            && otherResources.includes(this.game.const.ACTION_CONTRACT)   
        ){
            document.getElementById('goa_top_zone').classList.add('goa_display');
            let topZoneContent = document.getElementById('goa_top_zone_content');
            this.game.empty(topZoneContent);
            topZoneContent.insertAdjacentHTML('beforeend', `
                    <div id="goa_harvest_others">
                        <div id='goa_harvest_inc' class='goa_inc'></div>
                    </div>
                `);
            let harvestInc = document.getElementById('goa_harvest_inc');

            let onIncreaseShips = (delta,min,max) => {
                this.game.customDebug( 'onIncreaseShips', delta,selectedShips,min,max);
                let nextValue = selectedShips + delta;
                let nextTotal = nextValue + selectedResources.size + selectedContracts;
                if ( nextValue < 0) {
                    return;
                }
                if ( nextTotal > max) {
                    return;
                }
                selectedShips = nextValue;
                updateStatus();
            }
            let onIncreaseContracts = (delta,min,max) => {
                this.game.customDebug( 'onIncreaseContracts', delta,selectedContracts,min,max);
                let nextValue = selectedContracts + delta;
                let nextTotal = nextValue + selectedResources.size + selectedShips;
                if ( nextValue < 0) {
                    return;
                }
                if ( nextTotal > max) {
                    return;
                }
                selectedContracts = nextValue;
                updateStatus();
            }
        
            this.bga.statusBar.addActionButton('<span>-1</span>' + icon_ships, 
                    () => onIncreaseShips(-1, minElements, maxElements) 
                ,{ 'id':'goa_button_ships_dec','title':_('Decrease Ships'),'destination': harvestInc, 'color':'secondary'  }
            );
            
            this.bga.statusBar.addActionButton('<span>+1</span>'+ icon_ships, 
                    () => onIncreaseShips(1, minElements, maxElements) 
                ,{ 'id':'goa_button_ships_inc','title':_('Increase Ships'),'destination': harvestInc, 'color':'secondary'  }
            );
            
            this.bga.statusBar.addActionButton('<span>-1</span>' + icon_contracts, 
                    () => onIncreaseContracts(-1, minElements, maxElements) 
                ,{ 'id':'goa_button_contracts_dec','title':_('Decrease Contracts'),'destination': harvestInc, 'color':'secondary'  }
            );
            
            this.bga.statusBar.addActionButton('<span>+1</span>'+ icon_contracts, 
                    () => onIncreaseContracts(1, minElements, maxElements) 
                ,{ 'id':'goa_button_contracts_inc','title':_('Increase Contracts'),'destination': harvestInc, 'color':'secondary'  }
            );

            displayConfirmAsDisabled = true;
            confirmText = this.bga.gameui.format_string(  _('Confirm : ${x} ${icon_ships} / ${y} ${icon_contracts} / ${z} Goods') ,
                { 
                    'x': selectedShips, 
                    'y': selectedContracts,
                    'z': selectedResources.size,
                    'icon_ships': icon_ships,
                    'icon_contracts': icon_contracts,
                });
        }

        if(displayConfirmAsDisabled){
            this.bga.statusBar.addActionButton( confirmText, () => {
                endSelection();
            }, { id:'btnConfirmChoice', 'title':_('Confirm'),destination: document.getElementById('customPrimary'), 'disabled': true});
        }
        
        let endSelection = () => {
            if (callbackOnSelectionEnd(selectedResources, selectedShips, selectedContracts)) {
                selectedResources.clear();
                selectedShips = 0;
                selectedContracts = 0;
                Object.values(selectedTiles).forEach((t) => {t = 0  });
                updateStatus();
            }
        }
        let updateStatus = () => {
            if ($('btnConfirmChoice')) $('btnConfirmChoice').remove();
            let selectionGood = selectedResources.size == maxElements || selectedResources.size >= minElements && selectedResources.size <=maxElements;

            let nbTiles = 0;
            Object.values(selectedTiles).forEach((t) => {
                if(t>0) nbTiles++;
            });
            if(maxTiles){
                selectionGood = (nbTiles <= maxTiles);
            }

            if (selectionGood || displayConfirmAsDisabled) {
                if(otherResources.includes(this.game.const.ACTION_SHIP)  
                    && otherResources.includes(this.game.const.ACTION_CONTRACT)   
                ){
                    confirmText = this.bga.gameui.format_string(  _('Confirm : ${x} ${icon_ships} / ${y} ${icon_contracts} / ${z} Goods') ,
                    { 
                        'x': selectedShips, 
                        'y': selectedContracts,
                        'z': selectedResources.size,
                        'icon_ships': icon_ships,
                        'icon_contracts': icon_contracts,
                    });
                    selectionGood = ((selectedResources.size + selectedShips + selectedContracts) == maxElements);
                } else {
                    confirmText = this.bga.gameui.format_string(  _('Confirm : ${z} Goods') ,
                    { 
                        'z': selectedResources.size,
                    });
                }
                this.bga.statusBar.addActionButton( confirmText, () => {
                    endSelection();
                }, { id:'btnConfirmChoice', 'title':_('Confirm'),destination: document.getElementById('customPrimary'), 'disabled': !(selectionGood)});
            }

            if ($('btnCancelChoice')) $('btnCancelChoice').remove();
            if (selectedResources.size  > 0 || selectedShips > 0 || selectedContracts > 0 ) {
                this.bga.statusBar.addActionButton( _('Clear selection'), () => {
                    selectedResources.clear();
                    Object.values(selectedTiles).forEach((t) => {t = 0  });
                    selectedShips = 0;
                    selectedContracts = 0;
                    [...document.getElementsByClassName('goa_token_tmp_for_harvest')].forEach((node) => {
                        this.game.destroy(node);
                    });
                    updateStatus();
                }, { id:'btnCancelChoice', 'title':_('Clear selection'), 'color':'secondary',destination: document.getElementById('customSecondary')},);
            }

            if(maxTiles){
                let nbTiles = 0;
                Object.values(selectedTiles).forEach((t) => {
                    if(t>0) nbTiles++;
                });
                selectionGood = (nbTiles <= maxTiles);
            }

            Object.keys(elements).forEach((id) => {
                let elt = elements[id];
                let selected = selectedResources.has(parseInt(id));
                let tile_id = harvestSpots[id]['tile_id'];
                let selectedTile = (selectedTiles[tile_id] > 0);
                elt.classList.toggle('selected', selected);
                elt.classList.toggle('selectable', selected || selectedResources.size < maxElements  || maxTiles && (nbTiles < maxTiles || selectedTile) );
            });

            let harvestResourcesZone = harvestTopZone.getElementsByClassName('goa_harvest_resources')[0]; 
            if(harvestResourcesZone) this.game.empty(harvestResourcesZone);
            harvestTopZone.classList.remove('goa_display');
            [...harvestTopZone.getElementsByClassName('goa_harvest_zone')].forEach((h) => {
                this.game.empty(h);
            });
            
        }; 

        Object.keys(elements).forEach((id) => {
            let elt = elements[id];
            if(selectionHint) elt.setAttribute("title",selectionHint);
            let unSelectAction = (id, tile_id) => {
                let tmpTokenDiv = document.getElementById(`goa_token_tmp_for_harvest-TMP_${id}`)
                if(tmpTokenDiv) this.game.destroy(tmpTokenDiv);
                this.game.empty(elt);
                elt.classList.remove('selected_for_client_state');
                selectedResources.delete(parseInt(id));
                selectedTiles[tile_id] --;
                updateStatus();
            };
            let clickAction = () => {
                this.game.customDebug("onSelectNSpiceSpots...CLICK",id);
                let harvestResourcesZone = harvestTopZone.getElementsByClassName('goa_harvest_resources')[0]; 
                if(harvestResourcesZone) this.game.empty(harvestResourcesZone);

                if(!elt.classList.contains("selectable")) return;

                let tile_spot = harvestSpots[id]['index'];
                let tile_id = harvestSpots[id]['tile_id'];

                if(elt.classList.contains('selected_for_client_state')){
                    unSelectAction(id, tile_id);
                }
                else if (!selectedResources.has(parseInt(id))) {
                    [...document.querySelectorAll('.goa_tile_spot.selected_for_client_state')].forEach((node) => {
                        $(node).classList.remove('selected_for_client_state');
                    });
                    if (selectedResources.size >= maxElements) return;

                    elt.classList.add('selected_for_client_state');
                    
                    harvestTopZone.classList.add('goa_display');

                    if(!harvestResourcesZone){
                        let harvestZone = harvestTopZone.getElementsByClassName('goa_harvest_zone')[0]; 
                        harvestZone.insertAdjacentHTML('beforeend', `
                            <span id='goa_harvest_label' class="goa_harvest_label">${_("Select a good to produce for that field") }</span>
                            `);
                        harvestZone.insertAdjacentHTML('beforeend', `
                            <div class="goa_harvest_resources" id="goa_harvest_resources-${activePlayer}"></div>
                            `);
                        harvestResourcesZone = harvestTopZone.getElementsByClassName('goa_harvest_resources')[0]; 
                    }
                    this.game.autoScrollTo(harvestResourcesZone.id);

                    let possibleSpices = this.game.const.SPICES_ALL;
                    possibleSpices = harvestSpots[id]['spices'];
                    possibleSpices.forEach((spice) => {
                        let spice_icon = this.game.formatSpiceIcon(spice);
                        this.bga.statusBar.addActionButton( spice_icon, () => {
                            elt.classList.remove('selected_for_client_state');
                            selectedResources.set(parseInt(id),spice);
                            let tmpTokenDiv = this.game.addToken(
                                {
                                    'id': 'TMP_'+id, 
                                    'spice':spice, 
                                    'subtype': this.game.const.TOKEN_SUBTTYPE_SPICE,
                                    'location': this.game.const.TOKEN_LOCATION_TILE + tile_id,
                                    'state' : tile_spot,
                                }
                                , `goa_tile_spot-${tile_id}-${tile_spot}`
                                , '_tmp_for_harvest'
                            );
                            this.game.animationManager.slideIn(tmpTokenDiv, document.getElementById('btnHarvestSelect'+spice), {duration: 800});
                             
                            selectedTiles[tile_id] ++;
                            updateStatus();
                        }, { 'id': 'btnHarvestSelect'+spice, 'destination': harvestResourcesZone,  }); 
                    });


                } else {
                    unSelectAction(id, tile_id);
                }
            };
            this.game.onClick(elt, clickAction);
        });
      
    }
}