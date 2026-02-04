
/**
 * Client STATE
 */
export class ClientCardAction {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
        
        let card_id = args.card.id;
        let possibleDiscards = args.possibleDiscards; 
        let possiblePlayCards = args.possiblePlayCards; 
        let div = document.getElementById(`goa_exploration_card-${card_id}`);
        div.classList.add('selected');
        //div.classList.add('bga-cards_selected-card');
        //let selection in case we want to click on another card instead
        this.game.explorationHand.setSelectionMode('single');
        let selectableCards = this.game.explorationHand.getCards().filter((card, index) => possibleDiscards.includes(card.id) || playableCards.includes(card.id) );
        this.game.explorationHand.setSelectableCards(selectableCards);
        this.game.explorationHand.setLastPlayedCards([args.card]);

        if(args.isPlayableCard){
            this.bga.statusBar.addActionButton(_('Play'), () => this.bga.actions.performAction("actPlayCard", {'card_id':card_id}), { 'title':_('Play this card'),destination: document.getElementById('customPrimary') }); 
        }
        
        if(args.isDiscardableCard){
            this.bga.statusBar.addActionButton(_('Discard'), () => this.bga.actions.performAction("actDiscard", {'card_id':card_id}), { 'title':_('Discard this card'),destination: document.getElementById('customPrimary') }); 
        }

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
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }

    restorePreviousState(){
        this.game.restorePreviousState(this.args,'ClientCardAction');
    }
 
}