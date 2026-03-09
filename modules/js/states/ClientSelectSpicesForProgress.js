
/**
 * Client STATE
 */
export class ClientSelectSpicesForProgress {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let track = args.track;
        let activePlayer = this.bga.players.getActivePlayerId();

        let spicesSets = args.spicesSets; 
        let nbSpices = args.nbSpices;
        let allSpicesIds = [];
        let costSpices = args.costSpices;
        let spicesDivs = [];
    
        Object.entries(costSpices).forEach( ([spice, cost]) => {
            Object.values(cost.from).forEach((tId) => {
                allSpicesIds.push(tId);
            });
        });
        Object.values(allSpicesIds).forEach((tId) => {
            //spicesDivs[tId] = $(`goa_token-${tId}`);
            //click on goa_tile_spot
            spicesDivs[tId] = $(`goa_token-${tId}`).parentNode;
        });
        let spicesIcons = '';
        Object.entries(costSpices).forEach( ([spice, cost]) => {
            new Array(cost.n).fill(0).forEach( x => { 
                spicesIcons += this.game.formatSpiceIcon(spice);
            });
        });
        
        this.bga.statusBar.setTitle(
                _("${you} must select ${icons} Goods to discard and progress on track ${n}").replace('${icons}',spicesIcons).replace('${n}',track)
            );
            
        let progressTrack = document.getElementById(`goa_progress_track_${track}_${activePlayer}`);
        progressTrack.classList.add('selected');
        progressTrack.classList.add('selected_for_client_state');

        let selectionHint = _('Select this Good token');
        //Fix : spicesSets are not computed anymore...-> custom check 
        //this.game.onSelectN(spicesDivs, nbSpices, (selectedSpices) => {
        this.onSelectN(costSpices, nbSpices, (selectedSpices) => {
            let sortedTokens = selectedSpices.sort((a,b) =>  a - b );
            this.game.customPerformAction('actProgress', { 'track': args.track, 't_ids': sortedTokens.join(','), 'card_id': args.withCard});
        }, spicesSets, true, _('Confirm'), selectionHint );
    
        let callbackOnClose = this.game.addButtonCloseClientState(args);
        this.game.onClick(progressTrack, callbackOnClose);
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }

    /////////////////////////////------------------------------------
    
    onSelectN(costSpices, maxElements, callbackOnSelectionEnd, spicesSets, autoConfirm, confirmText, selectionHint = null,) {
        
        let countResources = new Map();
        let elements = [];
        let elementsResources = [];

        Object.entries(costSpices).forEach( ([spice, cost]) => {
            countResources.set(parseInt(spice),0);
            Object.values(cost.from).forEach((tId) => {
                elements[tId] = $(`goa_token-${tId}`).parentNode;
                elementsResources[tId] = spice;
            });
        });
    
        let selectedElements = [];
        let minElements = maxElements;
        let n = maxElements;

        let endSelection = () => {
            if (callbackOnSelectionEnd(selectedElements)) {
                selectedElements = [];
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
                    updateStatus();
                }, { id:'btnCancelChoice', 'title':_('Clear selection'), 'color':'secondary',destination: document.getElementById('customSecondary')},);
            }

            Object.keys(elements).forEach((id) => {
                let elt = elements[id];
                let selected = selectedElements.includes(parseInt(id));
                elt.classList.toggle('selected', selected);
                elt.classList.toggle('selectable', selected || selectedElements.length < n);
            });
            
        };
        let checkPossibleSets = () => {

            if(costSpices!=null){
                //Check each element not currently selected to know if it is now selectable
                Object.keys(elements).forEach((id2) => {
                    let elt2 = elements[id2];
                    let selected2 = selectedElements.includes(parseInt(id2));
                    if(selected2) return;
                    let possibleSet = false;
                    let spice = elementsResources[parseInt(id2)];
                        let datasCost = costSpices[spice];
                        let maxOfThisType = datasCost.n;
                        let possibleIds = datasCost.from;
                        if(countResources.get(parseInt(spice) ) < maxOfThisType 
                            && possibleIds.includes(parseInt(id2))){
                            possibleSet = true;
                        }
                    
                    elt2.classList.toggle('selectable', selected2 || possibleSet && selectedElements.length < n);
                    
                });

                let nbSelectables = 0;
                Object.keys(elements).forEach((id) => {
                    let elt = elements[id];
                    if(elt.classList.contains("selectable")) nbSelectables++;
                });
                if(nbSelectables == n){
                    //If only 1 match in fixed possible sets, let's auto select it
                    this.game.customDebug("onSelectN... auto select ",nbSelectables);
                    Object.keys(elements).forEach((id) => {
                        let elt = elements[id];
                        if(elt.classList.contains("selectable")){
                            elt.classList.add("selected");
                            if(!selectedElements.includes(parseInt(id))) selectedElements.push(parseInt(id));
                        }
                    });
                    updateStatus();
                    if(autoConfirm) endSelection();

                }
            }
        };

        Object.keys(elements).forEach((id) => {
            let elt = elements[id];
            if(selectionHint) elt.setAttribute("title",selectionHint);
            let clickAction = () => {
                this.game.customDebug("onSelectN...CLICK",id);
                if(!elt.classList.contains("selectable")) return;

                let index = selectedElements.findIndex((t) => t == id);

                let spice = elementsResources[parseInt(id)];
                if (index === -1) {
                    if (selectedElements.length >= n) return;
                    selectedElements.push(parseInt(id));
                    let currentSpiceCount = countResources.get(parseInt(spice));
                    currentSpiceCount++;
                    countResources.set(parseInt(spice),countResources);
                } else {
                    selectedElements.splice(index, 1);
                    let currentSpiceCount = countResources.get(parseInt(spice));
                    currentSpiceCount--;
                    countResources.set(parseInt(spice),countResources);
                }
                updateStatus();
                checkPossibleSets();
            };
            this.game.onClick(elt, clickAction);
        });
        
      
    }
 
}