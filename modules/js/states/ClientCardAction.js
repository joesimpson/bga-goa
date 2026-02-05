
/**
 * Client STATE
 */
export class ClientCardAction {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
        
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

        this.game.explorationHand.setLastPlayedCards([args.card]);

        //if(args.isPlayableCard){
            this.bga.statusBar.addActionButton(_('Play'), () => {
                let play_datas = args.possiblePlayCards[card_id].play_datas;
                if(play_datas && play_datas.progressTracks){
                    this.bga.states.setClientState("ClientSelectProgress", {
                        args: {
                            'progressTracks': play_datas.progressTracks, 
                            'withCard':card_id,
                            'previousClientState' : this.game.getCurrentClientStateDatas(),
                        },
                    });
                }
                else {
                    //DIRECT CARD PLAY
                    this.bga.actions.performAction("actPlayCard", {'card_id':card_id}).then( () =>{
                        }
                    );
                    
                }
            }, { 'disabled': !args.isPlayableCard,  'title':_('Play this card'),destination: document.getElementById('customPrimary') }); 
        //}
        
        //if(args.isDiscardableCard){
            this.bga.statusBar.addActionButton(_('Discard'), () => this.bga.actions.performAction("actDiscard", {'card_id':card_id}), { 'disabled': !args.isDiscardableCard,'title':_('Discard this card'),destination: document.getElementById('customPrimary') }); 
        //}

        this.bga.statusBar.addActionButton( _('Cancel'), () => {
            //this.game.explorationHand.setSelectionMode('none');
            this.game.explorationHand.unselectAll();
            this.game.restorePreviousState(args,'ClientCardAction');
        }, { 'color':'secondary','title':_('Cancel'), destination: document.getElementById('customSecondary') }); 

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
 
}