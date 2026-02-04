export class NextPlayer {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    onEnteringState(args, isCurrentPlayerActive) {
      
        if(this.game.explorationHand){
            this.game.explorationHand.setSelectionMode('none');
        }

    }

    onLeavingState(args, isCurrentPlayerActive) {
    }
 
    
}