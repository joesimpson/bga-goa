
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
        let harvestSpots = args.harvestSpots;
        let withCard = args.withCard;
        let withTile = args.withTile;
        let otherResources = (args.others) ? args.others : [];

        let tilesSpotsDivs = [];
        let tilesSpotsSpices = [];
        //LOOP ON tiles spots
        Object.values(harvestSpots).forEach((harvestSpot) => {
            Object.values(harvestSpot.available).forEach((datas) => {
                let tileId = datas.tile_id;
                let index = datas.index;
                tilesSpotsDivs[datas.id] = $(`goa_tile_spot-${tileId}-${index}`);
                tilesSpotsSpices[datas.id] = datas.spices;
            });
        });
     
        this.bga.statusBar.setTitle(
            minSpices == maxSpices ? 
                _("${you} must select ${n} spices to place on your tiles").replace('${n}',maxSpices)
                :
                _("${you} must select ${a} to ${b} spices to place on your tiles").replace('${a}',minSpices).replace('${b}',maxSpices)
            );
        let displayConfirmAsDisabled = false;    
        let icon_ships = this.game.formatIcon('ships', _('Ships'));
        let icon_contracts = this.game.formatIcon('contracts', _('Contracts'));
        if(otherResources.includes(this.game.const.ACTION_SHIP)  
            && otherResources.includes(this.game.const.ACTION_CONTRACT)   
        ){
            this.bga.statusBar.setTitle( _("${you} must select a combination of ${n} ${icon_ships}, ${icon_contracts} and resources to place on your tiles").replace('${n}',maxSpices) .replace('${icon_ships}',icon_ships).replace('${icon_contracts}',icon_contracts ) ) ;
            displayConfirmAsDisabled = true;
        }

        let actionName = 'actHarvest';

        if(withTile){
            let tileDiv = document.getElementById(`goa_tile-${withTile}`);
            tileDiv.classList.add('selected');
            tileDiv.classList.add('selected_for_client_state');
        }

        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            this.game.restorePreviousState(args);
        }, { 'color':'secondary','title':_('Cancel'), destination: document.getElementById('customSecondary') }); 
            
        let selectionHint = _('Select this tile spot to fill');
        this.onSelectNSpiceSpots(tilesSpotsDivs, tilesSpotsSpices, minSpices, maxSpices, (selectedElements,selectedResources, selectedShips, selectedContracts) => {
            this.game.customPerformAction(actionName, { 
                'spots': selectedElements.join(','),
                'res': selectedResources.join(','), 
                'card_id': withCard, 
                'tile_id': withTile, 
                'ships':selectedShips, 
                'contracts':selectedContracts
            });
        }, otherResources, displayConfirmAsDisabled, selectionHint );
    
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }


    //----------------------------------------
    
    onSelectNSpiceSpots(elements, tilesSpotsSpices, minElements, maxElements, callbackOnSelectionEnd, otherResources, displayConfirmAsDisabled = false, selectionHint = null,) {
        
        let activePlayer = this.bga.players.getActivePlayerId();
        let selectedElements = [];
        let selectedResources = [];
        let selectedShips = 0;
        let selectedContracts = 0;
        let n = maxElements;
        let confirmText = '';
        
        let countElements = 0;
        let allElementsIds = [];
        Object.keys(elements).forEach((id) => {
            countElements++;
            allElementsIds.push(id);
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
            //this.refreshHarvestButton();

            let onIncreaseShips = (delta,min,max) => {
                console.log( 'onIncreaseShips', delta,selectedShips,min,max);
                let nextValue = selectedShips + delta;
                let nextTotal = nextValue + selectedElements.length + selectedContracts;
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
                console.log( 'onIncreaseContracts', delta,selectedContracts,min,max);
                let nextValue = selectedContracts + delta;
                let nextTotal = nextValue + selectedElements.length + selectedShips;
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
            confirmText = this.bga.gameui.format_string(  _('Confirm : ${x} ${icon_ships} / ${y} ${icon_contracts} / ${z} resources') ,
                { 
                    'x': selectedShips, 
                    'y': selectedContracts,
                    'z': selectedElements.length,
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
            if (callbackOnSelectionEnd(selectedElements,selectedResources, selectedShips, selectedContracts)) {
                selectedElements = [];
                selectedResources = [];
                selectedShips = 0;
                selectedContracts = 0;
                updateStatus();
            }
        }
        let updateStatus = () => {
            if ($('btnConfirmChoice')) $('btnConfirmChoice').remove();
            let selectionGood = selectedElements.length == n || selectedElements.length >= minElements && selectedElements.length <=n;
            if (selectionGood || displayConfirmAsDisabled) {
                if(otherResources.includes(this.game.const.ACTION_SHIP)  
                    && otherResources.includes(this.game.const.ACTION_CONTRACT)   
                ){
                    confirmText = this.bga.gameui.format_string(  _('Confirm : ${x} ${icon_ships} / ${y} ${icon_contracts} / ${z} resources') ,
                    { 
                        'x': selectedShips, 
                        'y': selectedContracts,
                        'z': selectedElements.length,
                        'icon_ships': icon_ships,
                        'icon_contracts': icon_contracts,
                    });
                    selectionGood = ((selectedElements.length + selectedShips + selectedContracts) == n);
                } else {
                    confirmText = this.bga.gameui.format_string(  _('Confirm : ${z} resources') ,
                    { 
                        'z': selectedElements.length,
                    });
                }
                this.bga.statusBar.addActionButton( confirmText, () => {
                    endSelection();
                }, { id:'btnConfirmChoice', 'title':_('Confirm'),destination: document.getElementById('customPrimary'), 'disabled': !(selectionGood)});
            }

            if ($('btnCancelChoice')) $('btnCancelChoice').remove();
            if (selectedElements.length  > 0) {
                this.bga.statusBar.addActionButton( _('Clear selection'), () => {
                    selectedElements = [];
                    selectedResources = [];
                    [...document.getElementsByClassName('goa_token_tmp_for_harvest')].forEach((node) => {
                        this.game.destroy(node);
                    });
                    updateStatus();
                }, { id:'btnCancelChoice', 'title':_('Clear selection'), 'color':'secondary',destination: document.getElementById('customSecondary')},);
            }

            Object.keys(elements).forEach((id) => {
                let elt = elements[id];
                let selected = selectedElements.includes(parseInt(id));
                elt.classList.toggle('selected', selected);
                elt.classList.toggle('selectable', selected || selectedElements.length < n);
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
            let clickAction = () => {
                console.log("onSelectNSpiceSpots...CLICK",id);
                let harvestResourcesZone = harvestTopZone.getElementsByClassName('goa_harvest_resources')[0]; 
                if(harvestResourcesZone) this.game.empty(harvestResourcesZone);

                if(!elt.classList.contains("selectable")) return;

                let index = selectedElements.findIndex((t) => t == id);

                [...document.querySelectorAll('.goa_tile_spot.selected_for_client_state')].forEach((node) => {
                    $(node).classList.remove('selected_for_client_state');
                });
                if (index === -1) {
                    if (selectedElements.length >= n) return;

                    //selectedElements.push(parseInt(id));

                    elt.classList.add('selected_for_client_state');
                    
                    harvestTopZone.classList.add('goa_display');

                    if(!harvestResourcesZone){
                        let harvestZone = harvestTopZone.getElementsByClassName('goa_harvest_zone')[0]; 
                        harvestZone.insertAdjacentHTML('beforeend', `
                            <span id='goa_harvest_label' class="goa_harvest_label">${_("Select a resource to harvest for that spot") }</span>
                            `);
                        harvestZone.insertAdjacentHTML('beforeend', `
                            <div class="goa_harvest_resources" id="goa_harvest_resources-${activePlayer}"></div>
                            `);
                        harvestResourcesZone = harvestTopZone.getElementsByClassName('goa_harvest_resources')[0]; 
                    }
                    harvestResourcesZone.scrollIntoView({'behavior': 'smooth', 'block':'center'});

                    //id is made as (tileId * 10 + index)
                    let tile_spot = id % 10;
                    let tile_id = (id - tile_spot) / 10;

                    let possibleSpices = this.game.const.SPICES_ALL;
                    if(tilesSpotsSpices[id]) possibleSpices = tilesSpotsSpices[id];
                    possibleSpices.forEach((spice) => {
                        let spice_icon = this.game.formatIcon('spices_'+spice,_('Spices'));
                        this.bga.statusBar.addActionButton( spice_icon, () => {
                            elt.classList.remove('selected_for_client_state');
                            selectedElements.push(parseInt(id));
                            selectedResources.push(spice);
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
                             
                            updateStatus();
                        }, { 'id': 'btnHarvestSelect'+spice, 'destination': harvestResourcesZone,'title':_('Select this spice'),  }); 
                    });


                } else {
                    let tmpTokenDiv = document.getElementById(`goa_token_tmp_for_harvest-TMP_${id}`)
                    if(tmpTokenDiv) this.game.destroy(tmpTokenDiv);
                    this.game.empty(elt);
                    elt.classList.remove('selected_for_client_state');
                    selectedElements.splice(index, 1);
                    selectedResources.splice(index, 1);
                    updateStatus();
                }
            };
            this.game.onClick(elt, clickAction);
        });
      
    }
 /*
    onIncreaseShips(delta,min,max) {
        console.log( 'onIncreaseShips', delta,this.selectedShips,min,max);
        let nextValue = this.selectedShips + delta;
        if ( nextValue < min) {
            return;
        }
        if ( nextValue > max) {
            return;
        }
        this.selectedShips = nextValue;
        
        updateStatus();

        //this.refreshHarvestButton();
    }
    refreshHarvestButton(){
        let button = document.getElementById('btnConfirmChoice');
        if(button) this.game.destroy(button);

        let confirmText = this.bga.gameui.format_string(  _('Confirm : ${x} ${icon_ships} / ${y} ${icon_contracts} / ${z} resources') ,
                { 
                    'x': selectedShips, 
                    'y': selectedContracts,
                    'z': selectedElements.length,
                    'icon_ships': icon_ships,
                    'icon_contracts': icon_contracts,
                });
    }
    */
}