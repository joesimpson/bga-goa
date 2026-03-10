
/**
 * Client STATE
 */
export class ClientCardAction {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
        
        if(args.mode =='multiple'){
            
            this.bga.statusBar.setTitle(
                _("Select an action for these cards")
            );
            this.game.updateHandSelectableList(args);

            //keep first card selected
            this.game.explorationHand.selectCard(args.card);
            this.refreshConfirmMultipleMode();

            this.bga.statusBar.addActionButton( _('Return'), () => {
                //this.game.explorationHand.setSelectionMode('none');
                this.game.explorationHand.unselectAll();
                this.game.restorePreviousState(args,'ClientCardAction');
            }, { 'id':`goa_btn_playcard_return`, 'color':'secondary','title':_('Return'), destination: document.getElementById('customSecondary') }); 
            return;
        }

        this.bga.statusBar.setTitle(
                    _("Select an action for this card")
                );

        let card_id = args.card.id;
        let possibleDiscards = args.possibleDiscards; 
        let possiblePlayCards = args.possiblePlayCards; 
        let div = document.getElementById(`goa_exploration_card-${card_id}`);
        div.classList.add('selected');
        //div.classList.add('bga-cards_selected-card');
        //let selection in case we want to click on another card instead
        this.game.explorationHand.setSelectionMode('single');
        let selectableCards = this.game.explorationHand.getCards().filter((card, index) => possibleDiscards.includes(card.id) || Object.keys(possiblePlayCards).includes(""+card.id) );
        //this.game.explorationHand.setSelectableCards(selectableCards);
        this.game.updateHandSelectableList(args);

        this.game.explorationHand.setLastPlayedCards([args.card]);

        //if(args.isPlayableCard){
            this.bga.statusBar.addActionButton(_('Play'), () => {
                let play_datas = args.possiblePlayCards[card_id].play_datas;
                if(play_datas ) { 
                    if(play_datas.progressTracks){
                        this.bga.states.setClientState("ClientSelectProgress", {
                            args: {
                                'progressTracks': play_datas.progressTracks, 
                                'withCard':card_id,
                                'previousClientState' : this.game.getCurrentClientStateDatas(),
                            },
                        });
                    }
                    else if(play_datas.outpostsToFound){
                        this.bga.states.setClientState("ClientFoundOutpost", {
                            descriptionmyturn: '',
                            args: {
                                'outpostsToFound': play_datas.outpostsToFound,
                                'playerBaseContracts':play_datas.playerBaseContracts,
                                'withCard':card_id,
                                'previousClientState' : this.game.getCurrentClientStateDatas(),
                            },
                        });
                    }
                    else if(play_datas.spendSpices){
                        this.bga.states.setClientState("ClientSpendSpices", {
                            descriptionmyturn: '',
                            args: {
                                'spendSpices': play_datas.spendSpices,
                                'withCard':card_id,
                                'previousClientState' : this.game.getCurrentClientStateDatas(),
                            },
                        });
                    }
                    else if(play_datas.harvest){
                        this.bga.states.setClientState("ClientHarvestResources", {
                            descriptionmyturn: '',
                            args: {
                                'countAction': play_datas.harvest.countAction, 
                                'harvestSpots': play_datas.harvest.spots , 
                                'min': play_datas.harvest.min,
                                'max': play_datas.harvest.max,
                                'others': play_datas.harvest.others,
                                'withCard':card_id,
                                'previousClientState' : this.game.getCurrentClientStateDatas(),
                            },
                        });
                    }
                    else {
                        //DIRECT CARD PLAY
                        this.game.customPerformAction("actPlayCard", {'card_id':card_id});
                    }
                }
                else {
                    //DIRECT CARD PLAY
                    this.game.customPerformAction("actPlayCard", {'card_id':card_id});
                }
            }, {'id':`goa_btn_playcard_${card_id}`, 'disabled': !args.isPlayableCard,  'title':_('Play this card'),destination: document.getElementById('customPrimary') }); 
        //}
        
        //if(args.isDiscardableCard){
            this.bga.statusBar.addActionButton(_('Discard'), () => this.game.customPerformAction("actDiscard", {'card_ids':card_id}), { 'id':`goa_btn_discardcard_${card_id}`, 'disabled': !args.isDiscardableCard,'title':_('Discard this card'),destination: document.getElementById('customPrimary') }); 
        //}

        this.bga.statusBar.addActionButton( _('Return'), () => {
            //this.game.explorationHand.setSelectionMode('none');
            this.game.explorationHand.unselectAll();
            this.game.restorePreviousState(args,'ClientCardAction');
        }, { 'id':`goa_btn_playcard_return`, 'color':'secondary','title':_('Return'), destination: document.getElementById('customSecondary') }); 

    }

    onLeavingState(args, isCurrentPlayerActive) {
        
        let card_id = args.card.id;
        let div = document.getElementById(`goa_exploration_card-${card_id}`);
        //div.classList.remove('bga-cards_selected-card');
        this.game.explorationHand.removeLastPlayedCardsClass();
        //this.game.explorationHand.setSelectionMode('none');
        this.game.clearSelection(true);
        this.game.removeAllActionButtons();
    }

    restorePreviousState(){
        this.game.restorePreviousState(this.args,'ClientCardAction');
    }
    
    refreshConfirmMultipleMode(){
        if ($('btnConfirmMultiple')) $('btnConfirmMultiple').remove();
        let sortedCards = this.game.explorationHand.getSelection().map((a) => a.id).sort((a,b) =>  a.id - b.id );
        let buttonText = this.bga.gameui.format_string( 
                    _('Discard ${n} cards'),
                    { 'n': sortedCards.length }
                );
        this.bga.statusBar.addActionButton(buttonText , () => {
            this.game.customPerformAction("actDiscard", {'card_ids':sortedCards.join(',')})
        }, { 'id':'btnConfirmMultiple', 
            'title':buttonText,
            'destination': document.getElementById('customPrimary'),
        });
    }
 
}