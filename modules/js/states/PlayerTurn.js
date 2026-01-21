/**
 * We create one State class per declared state on the PHP side, to handle all state specific code here.
 * onEnteringState, onLeavingState and onPlayerActivationChange are predefined names that will be called by the framework.
 * When executing code in this state, you can access the args using this.args
 */

export class PlayerTurn {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        let roundStep = args.roundStep;
        let possibleActions = args.possibleActions;
        let icon_money = this.game.formatIcon('money');

        switch(roundStep){
            case this.game.const.ROUND_STEP_PLACE_AUCTIONS: 
                let token_icon = (args.token) ? this.game.tplToken(args.token, '_log') : '';
                this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                    _('${you} must place your auction marker ${token_icon}').replace('${token_icon}', token_icon) :
                    _('${actplayer} must place the auction marker ${token_icon}').replace('${token_icon}', token_icon)
                );
                break;
            case this.game.const.ROUND_STEP_TILE_AUCTIONS: 
                let currentBid = args.bid;
                this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                    _('${you} must pass or increase the current bid of ${bid} ${icon_money}').replace('${bid}', currentBid).replace('${icon_money}', icon_money) :
                    _('${actplayer} must pass or increase the current bid of ${bid} ${icon_money}').replace('${bid}', currentBid).replace('${icon_money}', icon_money)
                );
                break;
            case this.game.const.ROUND_STEP_PLAYER_ACTIONS: 
                this.bga.statusBar.setTitle('TODO ROUND_STEP_PLAYER_ACTIONS');
                break;
            default: 
                //UNEXPECTED
                this.bga.statusBar.setTitle('');
                break;
        }
        
      
        if (isCurrentPlayerActive) {

            if(possibleActions.includes('actPlaceAuction')){
                let pAuctionableSpots = args.pAuctionableSpots;
                pAuctionableSpots.forEach( spot => {
                    // remove button after tests, because with edges it will give a lot (20)
                    //this.bga.statusBar.addActionButton(_('Market row ${row}, column ${col}').replace('${row}', spot.row).replace('${col}', spot.col), () => this.onMarketSpotClick(spot.row,spot.col) );

                    let marketSpace = $(`goa_market_grid_space-${spot.row}-${spot.col}`);
                    this.game.onClick(marketSpace,() => this.onMarketSpotClick(spot.row,spot.col));
                }); 
            }
            
            if(possibleActions.includes('actBid')){
                let increaseAmounts = args.increaseAmounts;
                this.currentBid = args.bid;
                this.selectedBid = args.minBid;
                //Don't display all as status bar buttons because with many coins, it becomes a long list
                //increaseAmounts.forEach( increaseAmount => {
                //    this.bga.statusBar.addActionButton(_('Increase +${n}').replace('${n}', increaseAmount), 
                //        () => this.onBid(increaseAmount) 
                //    );
                //}); 
                let hintLine1 = this.bga.gameui.format_string(  _('My current money : ${n} ${icon_money}') ,{ n:args.currentMoney , icon_money: icon_money});
                let hintLineMin = this.bga.gameui.format_string(  _('Minimum bid : ${n} ${icon_money}') ,{ n:args.minBid , icon_money: icon_money});
                let hintLineMax = this.bga.gameui.format_string(  _('Maximum bid : ${n} ${icon_money}') ,{ n:args.maxBid , icon_money:  icon_money});
                let hintLine4 = this.bga.gameui.format_string(  _('Selected bid : ${n} ${icon_money}') ,{ n:args.minBid , icon_money:  icon_money});

                document.getElementById('goa_top_zone').classList.remove('goa_no_display');
                document.getElementById('goa_top_zone').insertAdjacentHTML('beforeend', `
                        <div id="goa_bid_hints">
                            <span>${hintLine1 }</span>
                            <span>${''/*hintLineMin */}</span>
                            <div id='goa_bid_reset_min'></div>
                            <div class='goa_bid' id='goa_bid_inc'>
                                <!-- 
                                <span>${''/*hintLine4*/}</span>
                               
                                <button id='goa_button_dec_bid' class='action-button bgabutton bgabutton_blue'>-</button>
                                <button id='goa_button_inc_bid' class='action-button bgabutton bgabutton_blue'>+</button>
                                -->
                            </div>
                            <div id='goa_bid_reset_max'></div>
                            <span>${''/*hintLineMax */}</span>
                        </div>
                    `);
                    
                //this.bga.statusBar.addActionButton(this.bga.gameui.format_string(  _('+ ${n} ${icon_money} = ${m} ${icon_money}') ,{ n:args.minBid, m: (this.currentBid + args.minBid) , icon_money: icon_money}), 
                //        () => this.onBid(args.minBid) 
                //    , {'id':'goa_button_bid',});
                this.refreshBidButton(); //this.selectedBid, (this.currentBid + this.selectedBid));

                this.bga.statusBar.addActionButton(this.bga.gameui.format_string(  hintLineMin ,{ n:args.minBid, m: (this.currentBid + args.minBid) , icon_money: icon_money}), 
                        () => {
                            //this.onBid(args.minBid);
                            this.selectedBid = args.minBid;
                            this.refreshBidButton();
                        }
                    ,{ destination:document.getElementById('goa_bid_reset_min'), 'color':'secondary' }
                );
                this.bga.statusBar.addActionButton(this.bga.gameui.format_string(  hintLineMax ,{ n:args.minBid, m: (this.currentBid + args.minBid) , icon_money: icon_money}), 
                        () => {
                            //this.onBid(args.maxBid);
                            this.selectedBid = args.maxBid;
                            this.refreshBidButton();
                        } 
                    ,{ destination: document.getElementById('goa_bid_reset_max') , 'color':'secondary'}
                );
                
                this.bga.statusBar.addActionButton('+1', 
                        () => this.onIncreaseBid(1) 
                    ,{ 'id':'goa_button_bid_inc', destination: document.getElementById('goa_bid_inc'), 'color':'secondary'  }
                );
                this.bga.statusBar.addActionButton('-1', 
                        () => this.onIncreaseBid(-1) 
                    ,{ 'id':'goa_button_bid_dec',destination: document.getElementById('goa_bid_inc'), 'color':'secondary'  }
                );

            }

            //TODO JSA MANAGE when we can pass
            this.bga.statusBar.addActionButton(_('Pass'), () => this.bga.actions.performAction("actPass"), { color: 'secondary', destination: document.getElementById('restartActions') }); 

        }
    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.clearSelection();
        this.game.removeAllActionButtons();
    }

    /**
     * This method is called each time the current player becomes active or inactive in a MULTIPLE_ACTIVE_PLAYER state. You can use this method to perform some user interface changes at this moment.
     * on MULTIPLE_ACTIVE_PLAYER states, you may want to call this function in onEnteringState using `this.onPlayerActivationChange(args, isCurrentPlayerActive)` at the end of onEnteringState.
     * If your state is not a MULTIPLE_ACTIVE_PLAYER one, you can delete this function.
     */
    onPlayerActivationChange(args, isCurrentPlayerActive) {
    }

    
    onMarketSpotClick(row,col) {
        console.log( 'onMarketSpotClick', row,col );

        this.bga.actions.performAction("actPlaceAuction", { 
                            'row': row,
                            'col': col,
        }).then(() =>  {               
        });
    }
    
    onBid(amount) {
        console.log( 'onBid', amount );

        this.bga.actions.performAction("actBid", { 
                            'n': amount,
        }).then(() =>  {
        });
    }
    
    onIncreaseBid(delta) {
        console.log( 'onIncreaseBid', delta,this.currentBid,this.selectedBid );
        //TODO JSA CHECK mIN/ max 
        //TODO JSA CHANGE Callback
        this.selectedBid = this.selectedBid + delta;
        //document.getElementById('goa_button_bid').innerHTML = this.currentBid;

        this.refreshBidButton();
    }

    refreshBidButton(){
        let button = document.getElementById('goa_button_bid');
        if(button) this.game.destroy(button);
        let icon_money = this.game.formatIcon('money');

        this.bga.statusBar.addActionButton(this.bga.gameui.format_string(  _('Bid + ${n} ${icon_money} = ${m} ${icon_money}') ,
                    { 'n': this.selectedBid, m: (this.currentBid + this.selectedBid), icon_money: icon_money}), 
                () => this.onBid(this.selectedBid) 
            , {'id':'goa_button_bid', destination: document.getElementById('customActions')}
            );
    }
}
