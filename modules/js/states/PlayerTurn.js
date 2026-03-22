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
        let icon_money = this.game.formatIcon('money',_('Pardaos'));
        let activePlayer = this.bga.players.getActivePlayerId();

        switch(roundStep){
            case this.game.const.ROUND_STEP_PLACE_AUCTIONS: 
                var token_icon = (args.token) ? this.game.tplToken(args.token, '_log') : '';
                this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                    _('${you} must place your auction marker ${token_icon}').replace('${token_icon}', token_icon) :
                    _('${actplayer} must place the auction marker ${token_icon}').replace('${token_icon}', token_icon)
                );
                break;
            case this.game.const.ROUND_STEP_TILE_AUCTIONS: 
                let currentBid = args.bid;
                let auctionToken = args.token;
                let auctionTokenDiv =  document.getElementById(`goa_token-${auctionToken.id}`);
                if(auctionTokenDiv) {
                    //HIGHLIGHT ZONE on grid
                    auctionTokenDiv.parentNode.classList.add('selected');
                }
                var token_icon = (auctionToken) ? this.game.tplToken(auctionToken, '_log') : '';
                this.bga.statusBar.setTitle(isCurrentPlayerActive ? 
                    _('Auction ${token_icon} : ${you} may pass or raise the current bid of ${bid} ${icon_money}').replace('${bid}', currentBid).replace('${icon_money}', icon_money).replace('${token_icon}', token_icon) :
                    _('Auction ${token_icon} : ${actplayer} may pass or raise the current bid of ${bid} ${icon_money}').replace('${bid}', currentBid).replace('${icon_money}', icon_money).replace('${token_icon}', token_icon)
                );
                break;
            case this.game.const.ROUND_STEP_PLAYER_ACTIONS: 
                let iconAction = this.game.formatIcon('nbActions');
                //let titleActive =  _('${you} must select an action (${n} done in this round, min ${x} to pass)').replace('${n}', args.nbActions).replace('${x}', args.minActions);
                let titleActive =  _('${you} must select an action ${icon_action}').replace('${icon_action}',iconAction);
                let titleOthers =  _('${actplayer} must select an action ${icon_action}').replace('${icon_action}',iconAction);
                if(args.actionPlayed){
                    titleActive =  _('${you} may play a card or tile after your action');
                    titleOthers =  _('${actplayer} may play a card or tile after their action');
                }
                if(possibleActions.includes('actPassAction')){
                    titleActive =  _('${you} may pass or spend 1 ${compass_card} for an action ${icon_action}').replace('${compass_card}',this.game.formatIcon('compasses',_('Compass card'))).replace('${icon_action}',iconAction);
                    titleOthers =  _('${actplayer} may pass or spend 1 ${compass_card} for an action ${icon_action}').replace('${compass_card}',this.game.formatIcon('compasses',_('Compass card'))).replace('${icon_action}',iconAction);
                }

                this.bga.statusBar.setTitle(isCurrentPlayerActive ? titleActive : titleOthers);
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
                    marketSpace.setAttribute("title",_('Select this spot for the auction'));
                }); 
                this.game.autoScrollTo('goa_board_market');
            }
            
            if(possibleActions.includes('actBid')){
                this.game.autoScrollTo('goa_top_zone');
                this.currentBid = args.bid ; //- args.bidRebate;
                this.selectedBid = args.minBid; // - args.bidRebate;
                
                let auctionToken = args.token;

                let hintLine1 = '';
                if(auctionToken.type == this.game.const.TOKEN_TYPE_AUCTION_MARKER_1){
                    hintLine1 = this.bga.gameui.format_string(  _('Highest bid takes the Nazar Stone ${token_icon} + ${n} ${card_icon}.') ,{'n':1, 'card_icon':this.game.formatIcon('compasses', _('Compass cards')), 'token_icon': this.game.formatIcon('nazar_stone', _('Nazar Stone (Starting player)')) });
                }
                else {
                    hintLine1 = this.bga.gameui.format_string(  _('Highest bid takes the tile(s) under the auction marker.') ,{});
                }
                let hintLineMin = this.bga.gameui.format_string(  _('Minimum bid : ${n} ${icon_money}') ,{ n:args.minBid , icon_money: icon_money});
                let hintLineMax = this.bga.gameui.format_string(  _('Maximum bid : ${n} ${icon_money}') ,{ n:args.maxBid , icon_money:  icon_money});
                
                document.getElementById('goa_top_zone').classList.add('goa_display');
                this.game.empty(document.getElementById('goa_top_zone_content'));
                document.getElementById('goa_top_zone_content').insertAdjacentHTML('beforeend', `
                        <div id="goa_bid_hints">
                            <span class="goa_bid_hint_text">${hintLine1 }</span>
                            <div>
                                <div id='goa_bid_reset_min'></div>
                                <div id='goa_bid_inc' class='goa_bid'></div>
                                <div id='goa_bid_reset_max'></div>
                            </div>
                            <span class="goa_bid_hint_text" id="goa_bid_remaining" data-start="${args.currentMoney}"></span>
                        </div>
                    `);
                    
                this.refreshBidButton();

                this.bga.statusBar.addActionButton(this.bga.gameui.format_string(  hintLineMin ,{ n:args.minBid, m: (this.currentBid + args.minBid) , icon_money: icon_money}), 
                        () => {
                            //this.onBid(args.minBid);
                            this.selectedBid = args.minBid;
                            this.refreshBidButton();
                        }
                    ,{ 'title':_('Set to Minimum bid'),destination:document.getElementById('goa_bid_reset_min'), 'color':'secondary' }
                );
                this.bga.statusBar.addActionButton(this.bga.gameui.format_string(  hintLineMax ,{ n:args.minBid, m: (this.currentBid + args.minBid) , icon_money: icon_money}), 
                        () => {
                            //this.onBid(args.maxBid);
                            this.selectedBid = args.maxBid;
                            this.refreshBidButton();
                        } 
                    ,{ 'title':_('Set to Maximum bid'),destination: document.getElementById('goa_bid_reset_max') , 'color':'secondary'}
                );

                this.bga.statusBar.addActionButton('-1', 
                        () => this.onIncreaseBid(-1, args.minBid, args.maxBid) 
                    ,{ 'id':'goa_button_bid_dec','title':_('Decrease bid'),destination: document.getElementById('goa_bid_inc'), 'color':'secondary'  }
                );
                
                this.bga.statusBar.addActionButton('+1', 
                        () => this.onIncreaseBid(1, args.minBid, args.maxBid) 
                    ,{ 'id':'goa_button_bid_inc','title':_('Increase bid'),destination: document.getElementById('goa_bid_inc'), 'color':'secondary'  }
                );

            }
            
            if(possibleActions.includes('actProgress') && args.progressTracks && args.progressTracks.length>0){
                let actionName = this.game.formatActionName(this.game.const.ACTION_TYPE_PROGRESS);
                let actionButton = this.game.formatActionButton(0, actionName);
                let actionHint = this.bga.gameui.format_string(_('Player action : ${name}'),{'name': actionName});
                actionHint += "\r\n"+ _('Progressing on your Development board has 3 benefits: ');
                actionHint += "\r\n"+ _('At the end of the game, you score points according to the position of your Progress markers, more progress means more points.');
                actionHint += "\r\n"+ _('Moving your Progress markers down increases the resources you receive or can use with the other actions.');
                actionHint += "\r\n"+ _('Progressing also allows you to obtain some Compasses and Exploration cards as bonuses.');
                this.bga.statusBar.addActionButton(actionButton, () => {
                    this.bga.states.setClientState("ClientSelectProgress", {
                        descriptionmyturn: '',
                        //description : _("${actplayer} must select a progress track to improve on his board"),
                        args: {'progressTracks': args.progressTracks },
                    });
                }, { 'id':`goa_button_action_${0}`, 'title':actionHint,'destination': document.getElementById('customPrimary') }); 
            }

            if(possibleActions.includes('actBuildShips')){
                let actionName = this.game.formatActionName(this.game.const.ACTION_TYPE_BUILD_SHIPS);
                let actionButton = this.game.formatActionButton(1, actionName);
                let actionHint = this.bga.gameui.format_string(_('Player action : ${name}'),{'name': actionName});
                this.bga.statusBar.addActionButton(actionButton, () => this.game.customPerformAction("actBuildShips"), { 'id':`goa_button_action_${1}`,'title':actionHint,'destination': document.getElementById('customPrimary') }); 
                //+ CLICK ON BOARD section
                this.game.addClickHandlerOnProgressMarker(1,actionHint,activePlayer,() => this.game.customPerformAction("actBuildShips"));
            }
            
            if(possibleActions.includes('actHarvest') && args.harvestSpots &&  Object.keys(args.harvestSpots).length > 0){
                let actionName = this.game.formatActionName(this.game.const.ACTION_TYPE_PRODUCE);
                let actionButton = this.game.formatActionButton(2, actionName);
                let actionHint = this.bga.gameui.format_string(_('Player action : ${name}'),{'name': actionName});
                let goToNext = () => {
                    this.bga.states.setClientState("ClientHarvestResources", {
                        descriptionmyturn: '',
                        args: {
                            'countAction': true, 
                            'harvestSpots': args.harvestSpots , 
                            'min': 1,
                            'max': args.playerBaseHarvest,
                        },
                    });
                };
                this.bga.statusBar.addActionButton(actionButton, () => goToNext(), {'id':`goa_button_action_${2}`, 'title':actionHint,'destination': document.getElementById('customPrimary') }); 
                //+ CLICK ON BOARD section
                this.game.addClickHandlerOnProgressMarker(2,actionHint,activePlayer,() => goToNext());
            }
            if(possibleActions.includes('actTaxes')){
                let actionName = this.game.formatActionName(this.game.const.ACTION_TYPE_TAXES);
                let actionButton = this.game.formatActionButton(3, actionName);
                let actionHint = this.bga.gameui.format_string(_('Player action : ${name}'),{'name': actionName});
                this.bga.statusBar.addActionButton(actionButton, () => this.game.customPerformAction("actTaxes"), { 'id':`goa_button_action_${3}`,'title':actionHint,'destination': document.getElementById('customPrimary') }); 
                //+ CLICK ON BOARD section
                this.game.addClickHandlerOnProgressMarker(3,actionHint,activePlayer,() => this.game.customPerformAction("actTaxes"));
            }
            
            if(possibleActions.includes('actExploration') && args.nbCardsExploration>0){
                let actionName = this.game.formatActionName(this.game.const.ACTION_TYPE_EXP);
                let actionButton = this.game.formatActionButton(4, actionName);
                let actionHint = this.bga.gameui.format_string(_('Player action : ${name}'),{'name': actionName});
                this.bga.statusBar.addActionButton(
                    actionButton,
                    /*this.bga.gameui.format_string( 
                        _('Explore : +${n} ${icon_explo}'),
                        {'n' : args.nbCardsExploration, 'icon_explo': this.game.formatIcon('exploration_card',_('Exploration card'))},
                    ), */
                    () => this.game.customPerformAction("actExploration"), { 'id':`goa_button_action_${4}`, 'title':actionHint,'destination': document.getElementById('customPrimary') }); 
                //+ CLICK ON BOARD section
                this.game.addClickHandlerOnProgressMarker(4,actionHint,activePlayer,() => this.game.customPerformAction("actExploration"));
            }
            
            if(possibleActions.includes('actFoundOutpost') && args.outpostsToFound.length>0){
                let actionName = this.game.formatActionName(this.game.const.ACTION_TYPE_FOUND);
                let actionButton = this.game.formatActionButton(5, actionName);
                let actionHint = this.bga.gameui.format_string(_('Player action : ${name}'),{'name': actionName});
                let goToNext = () => {
                    this.bga.states.setClientState("ClientFoundOutpost", {
                        descriptionmyturn: '',
                        args: {
                            'outpostsToFound': args.outpostsToFound , 
                            'playerBaseContracts':args.playerBaseContracts
                        },
                    });
                };
                this.bga.statusBar.addActionButton(actionButton, () => goToNext()
                    , { 'id':`goa_button_action_${5}`, 'title':actionHint,'destination': document.getElementById('customPrimary') }); 
                //+ CLICK ON BOARD section
                this.game.addClickHandlerOnProgressMarker(5,actionHint,activePlayer,() => goToNext());
            }
            
            this.game.updateHandSelectable(args);

            if(possibleActions.includes('actPassBid')){
                this.bga.statusBar.addActionButton(_('Pass'), () => this.game.customPerformAction("actPassBid"), { 'id':'goa_button_pass_bid', 'title':_('Pass the bid'),'destination': document.getElementById('customSecondary') }); 
            }
            
            if(possibleActions.includes('actPassAction')){
                this.bga.statusBar.addActionButton(_('Pass'), () => this.game.customPerformAction("actPassAction"), { 'id':'goa_button_pass_action', 'title':_('Pass to play no actions until next round'),'color':'secondary','destination': document.getElementById('customSecondary') }); 
            }
            
            if(possibleActions.includes('actStopAction')){
                this.bga.statusBar.addActionButton(_('Stop'), () => this.game.customPerformAction("actStopAction"), { 'id':'goa_button_stop_action', 'title':_('Stop actions until next turn'),'color':'primary','destination': document.getElementById('customSecondary') }); 
            }

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

    /**
     * This method is called each time the current player becomes active or inactive in a MULTIPLE_ACTIVE_PLAYER state. You can use this method to perform some user interface changes at this moment.
     * on MULTIPLE_ACTIVE_PLAYER states, you may want to call this function in onEnteringState using `this.onPlayerActivationChange(args, isCurrentPlayerActive)` at the end of onEnteringState.
     * If your state is not a MULTIPLE_ACTIVE_PLAYER one, you can delete this function.
     */
    onPlayerActivationChange(args, isCurrentPlayerActive) {
    }

    
    onMarketSpotClick(row,col) {
        this.game.customDebug( 'onMarketSpotClick', row,col );

        this.game.customPerformAction("actPlaceAuction", { 
                            'row': row,
                            'col': col,
        }).then(() =>  {               
        });
    }
    
    onBid(amount) {
        this.game.customDebug( 'onBid', amount );

        this.game.customPerformAction("actBid", { 
                            'n': amount,
        }).then(() =>  {
        });
    }
    
    onIncreaseBid(delta,minBid,maxBid) {
        this.game.customDebug( 'onIncreaseBid', delta,this.currentBid,this.selectedBid,minBid,maxBid );
        let nextBid = this.selectedBid + delta;
        if ( nextBid < minBid) {
            return;
        }
        if ( nextBid > maxBid) {
            return;
        }
        this.selectedBid = nextBid;

        this.refreshBidButton();
    }

    refreshBidButton(){
        let button = document.getElementById('goa_button_bid');
        if(button) this.game.destroy(button);
        let icon_money = this.game.formatIcon('money',null);
        let icon_arrow = '<i class="fa6 fa6-arrow-right goa_icon_arrow"></i>';

        this.bga.statusBar.addActionButton(this.bga.gameui.format_string(  _('Bid ${n} ${icon_money} ${icon_arrow} ${m} ${icon_money}') ,
                    { 'n': this.selectedBid, m: (this.currentBid + this.selectedBid), icon_money: icon_money,icon_arrow}), 
                () => this.onBid(this.selectedBid) 
            , {'id':'goa_button_bid', 'title':_('Confirm the bid'),destination: document.getElementById('customPrimary')}
            );
            
        let remainingDiv = document.getElementById('goa_bid_remaining');
        let startingMoney = remainingDiv.dataset.start;
        remainingDiv.innerHTML = this.bga.gameui.format_string(  _('Remaining Pardaos if I overbid this amount : ${n} ${icon_money}') ,{ n: Math.min(startingMoney, startingMoney - (this.currentBid + this.selectedBid))  , icon_money:  icon_money});

    }
}
