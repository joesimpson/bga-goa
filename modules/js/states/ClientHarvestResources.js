
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

        let tilesSpotsDivs = [];
        //LOOP ON tiles spots
        Object.values(harvestSpots).forEach((harvestSpot) => {
            let tileId = harvestSpot.tile_id;
            Object.values(harvestSpot.available).forEach((index) => {
                tilesSpotsDivs[tileId * 10 + index] = $(`goa_tile_spot-${tileId}-${index}`);
            });
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
            
        let selectionHint = _('Select this tile spot to fill');
        this.onSelectNSpiceSpots(tilesSpotsDivs, minSpices, maxSpices, (selectedElements,selectedResources) => {
            this.game.customPerformAction(actionName, { 'spots': selectedElements.join(','),'res': selectedResources.join(','), 'card_id': withCard, 'tile_id': withTile, });
        }, selectionHint );
    
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }


    //----------------------------------------
    
    onSelectNSpiceSpots(elements, minElements, maxElements, callbackOnSelectionEnd, selectionHint = null, confirmText = _('Confirm')) {
        
        let activePlayer = this.bga.players.getActivePlayerId();
        let selectedElements = [];
        let selectedResources = [];
        let n = maxElements;
        
        let countElements = 0;
        let allElementsIds = [];
        Object.keys(elements).forEach((id) => {
            countElements++;
            allElementsIds.push(id);
        });
        
        let harvestTopZone = document.getElementById(`goa_playerboard_top_zone-${activePlayer}`);
        
        let endSelection = () => {
            if (callbackOnSelectionEnd(selectedElements,selectedResources)) {
            selectedElements = [];
            selectedResources = [];
            updateStatus();
            }
        }
        let updateStatus = () => {
            if ($('btnConfirmChoice')) $('btnConfirmChoice').remove();
            if (selectedElements.length == n || selectedElements.length >= minElements && selectedElements.length <=n ) {
                this.bga.statusBar.addActionButton( confirmText, () => {
                    endSelection();
                }, { id:'btnConfirmChoice', 'title':confirmText,destination: document.getElementById('customPrimary')});
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
                            <span class="goa_harvest_label">${_("Select a resource to harvest for that spot") }</span>
                            `);
                        harvestZone.insertAdjacentHTML('beforeend', `
                            <div class="goa_harvest_resources" id="goa_harvest_resources-${activePlayer}"></div>
                            `);
                        harvestResourcesZone = harvestTopZone.getElementsByClassName('goa_harvest_resources')[0]; 
                    }

                    //id is made as (tileId * 10 + index)
                    let tile_spot = id % 10;
                    let tile_id = (id - tile_spot) / 10;
                    this.game.const.SPICES_ALL.forEach((spice) => {
                        //TODO JSA FILTER SPICE FOR SELECTED SPOT
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
                    elt.classList.remove('selected_for_client_state');
                    selectedElements.splice(index, 1);
                    selectedResources.splice(index, 1);
                    updateStatus();
                }
            };
            this.game.onClick(elt, clickAction);
        });
      
    }
 
}