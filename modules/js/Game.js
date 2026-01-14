/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Goa implementation : © joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.js
 *
 * Goa user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

const BgaAnimations = await importEsmLib('bga-animations', '1.x');
const BgaCards = await importEsmLib('bga-cards', '1.x');

//Used Constants copied from PHP file
const CARD_SUBTTYPE_EXPLORATION = 1;

const TILE_SUBTTYPE_OUTPOST = 1;
const TILE_SUBTTYPE_MARKET = 2;

const MARKET_GRID_SIZE = 5;

const CARD_LOCATION_EXP_DISCARD = '1_discard';
const CARD_LOCATION_HAND = 'hand';

const TILE_LOCATION_BOARD_MARKET = 'market';
const TILE_LOCATION_BOARD_OUTPOST = 'board_c';

const TOKEN_LOCATION_BOARD = 'board';
const TOKEN_LOCATION_PLAYER_BOARD = 'p_board';

const TOKEN_SUBTTYPE_PROGRESS_MARKER = 1;
const TOKEN_SUBTTYPE_AUCTION_MARKER = 2;
const TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER = 3;

export class Game {
    
    constructor(bga) {
        console.log('goa constructor');
        this.bga = bga;
            
        // Here, you can init the global variables of your user interface
        // Example:
        // this.myGlobalValue = 0;
        this._counters = {};

    }
    
    /*
        setup:
        
        This method must set up the game user interface according to current game situation specified
        in parameters.
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
        
        "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
    */
    
    setup( gamedatas ) {
        console.log( "Starting game setup" );
        this.gamedatas = gamedatas;

        this.animationManager = new BgaAnimations.Manager({
            animationsActive: () => this.bga.gameui.bgaAnimationsActive(),
        });

        // add a div on the game area
        this.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
            <div id="goa_game_container">
                <div id="goa_overall_background"></div>
                <div id="goa_main_zone">
                    <div id='goa_resizable_board'>
                        <div id='goa_board_container'>
                            <div id="goa_board">
                                <img id="goa_board_background"></img>
                                <div id="goa_board_components">
                                    <div id="goa_board_outposts">
                                        <div id="goa_board_outposts_1" class="goa_board_outposts_stock"></div>
                                        <div id="goa_board_outposts_2" class="goa_board_outposts_stock"></div>
                                        <div id="goa_board_outposts_3" class="goa_board_outposts_stock"></div>
                                        <div id="goa_board_outposts_4" class="goa_board_outposts_stock"></div>
                                    </div>
                                    <div id="goa_board_market">
                                        <div id="goa_board_market_frame">
                                            <div id="goa_board_market_top"></div>
                                            <div id="goa_board_market_left"></div>
                                            <div id="goa_board_market_right"></div>
                                            <div id="goa_board_market_bottom"></div>
                                        </div>
                                        <div id="goa_board_market_grid"></div>
                                    </div>
                                    <div id="goa_board_rounds">
                                        <div id="goa_board_round_marker"></div>
                                        <div id="goa_board_round_cell_1" class="goa_board_round_cell"></div>
                                        <div id="goa_board_round_cell_2" class="goa_board_round_cell"></div>
                                        <div id="goa_board_round_cell_3" class="goa_board_round_cell"></div>
                                        <div id="goa_board_round_cell_4" class="goa_board_round_cell"></div>
                                        <div id="goa_board_round_cell_5" class="goa_board_round_cell"></div>
                                        <div id="goa_board_round_cell_6" class="goa_board_round_cell"></div>
                                        <div id="goa_board_round_cell_7" class="goa_board_round_cell"></div>
                                        <div id="goa_board_round_cell_8" class="goa_board_round_cell"></div>
                                    </div>
                                    <div id="goa_money_stacks">
                                        <div class="goa_money_card" data-value='1'></div>
                                        <div class="goa_money_card" data-value='2'></div>
                                        <div class="goa_money_card" data-value='5'></div>
                                        <div class="goa_money_card" data-value='10'></div>
                                    </div>
                                    <div id="goa_exploration_deck"></div>
                                    <div id="goa_exploration_discard"></div>
                                </div>
                                <div class="goa_board_filler">&nbsp;<br/></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        let currentPlayerNo = 1;
        let nPlayers = 0;
        let currentPlayer = this.bga.players.getCurrentPlayer();
        // Setting up player boards
        Object.values(gamedatas.players).forEach(player => {
            this.bga.playerPanels.getElement(player.id).insertAdjacentHTML('beforeend', `
                <div class='goa_panel'>
                    <div class='goa_player_counters_line'>
                        ${this.tplResourceCounter(player,'exploration_hand_size')}
                        ${this.tplResourceCounter(player,'money')}
                        ${this.tplResourceCounter(player,'ships')}
                        ${this.tplResourceCounter(player,'contracts')}
                        ${this.tplResourceCounter(player,'compass')}
                    </div>
                </div>
            `);
            this._counters[player.id] = {};
            let counter = new ebg.counter();
            counter.create(`goa_counter_${player.id}_exploration_hand_size`, {
                value: player.exploration_hand_size,
                playerCounter: 'exploration_hand_size',
                playerId: player.id
            });
            this._counters[player.id]['exploration_hand_size'] = counter;

            counter = new ebg.counter();
            counter.create(`goa_counter_${player.id}_money`, {
                value: player.money,
                playerCounter: 'money',
                playerId: player.id
            });
            this._counters[player.id]['money'] = counter;
            
            counter = new ebg.counter();
            counter.create(`goa_counter_${player.id}_ships`, {
                value: player.ships,
                playerCounter: 'ships',
                playerId: player.id
            });
            this._counters[player.id]['ships'] = counter;
            
            counter = new ebg.counter();
            counter.create(`goa_counter_${player.id}_contracts`, {
                value: player.contracts,
                playerCounter: 'contracts',
                playerId: player.id
            });
            this._counters[player.id]['contracts'] = counter;
            
            counter = new ebg.counter();
            counter.create(`goa_counter_${player.id}_compass`, {
                value: player.compass,
                playerCounter: 'compass',
                playerId: player.id
            });
            this._counters[player.id]['compass'] = counter;

            // example of adding a div for each player
            document.getElementById('goa_main_zone').insertAdjacentHTML('beforeend', `
                <div id="goa_player_table-${player.id}" class="goa_player_table whiteblock" data-pid=${player.id} data-color='${player.color}'>
                    <div class='goa_player_table_title_name' >${this.bga.gameui.format_string(('${player_name}'), { player_name:this.coloredPlayerName(player.name)}) }</div>
                    <div class='goa_resizable_playerboard'>
                        <div class='goa_playerboard_container'>
                            <div class="goa_playerboard">
                                <div class="goa_playerboard_background"></div>
                                <div id="goa_player_tiles-${player.id}" class="goa_player_tiles"></div>
                                <div id="goa_player_auction_markers-${player.id}" class="goa_player_auction_markers">
                                </div>
                                <div id="goa_player_progress_tracks-${player.id}" class="goa_player_progress_tracks">
                                </div>
                                <div class="goa_board_filler">&nbsp;<br/></div>
                            </div>
                        </div>
                    </div>
                    <div id="goa_exploration_hand-${player.id}" class="exploration_hand"></div>
                </div>
            `);
            
            nPlayers++;
            if (currentPlayer && player.id == currentPlayer.id) currentPlayerNo = player.no;
        });
        // Order players
        Object.values(gamedatas.players).forEach(player => {
            //let 2 spaces for other boards
            let order = 1;
            if (currentPlayer && player.id == currentPlayer.id) order = 2;
            else {
                //order = 3;
                order = ((player.no - currentPlayerNo + nPlayers) % nPlayers) + 2;
            }
            $(`goa_player_table-${player.id}`).style.order = order;
        });
        
        // TODO: Set up your game interface here, according to "gamedatas"
        
        // create a card manager
        this.outpostsTilesManager = new BgaCards.Manager({
            animationManager: this.animationManager,
            type: 'goa_outpost',
            getId: (card) => card.id,
            setupFrontDiv: (card, div) => {
                div.dataset.type = card.type; 
                this.bga.gameui.addTooltipHtml(div.id, `TODO JSA tooltip of ${card.type}`);
            },
            cardWidth: 108,
            cardHeight: 108,
        });
        this.outpostsTilesStocks = [];
        this.outpostsTilesStocks[1] = new BgaCards.AllVisibleDeck(
            this.outpostsTilesManager, 
            document.getElementById('goa_board_outposts_1'),
            { direction:"vertical", },
        );
        this.outpostsTilesStocks[2] = new BgaCards.AllVisibleDeck(
            this.outpostsTilesManager, 
            document.getElementById('goa_board_outposts_2'),
            { direction:"vertical", },
        );
        this.outpostsTilesStocks[3] = new BgaCards.AllVisibleDeck(
            this.outpostsTilesManager, 
            document.getElementById('goa_board_outposts_3'),
            { direction:"vertical", },
        );
        this.outpostsTilesStocks[4] = new BgaCards.AllVisibleDeck(
            this.outpostsTilesManager, 
            document.getElementById('goa_board_outposts_4'),
            { direction:"vertical", },
        );
        this.setupTiles();
        this.setupTokens();
        this.setupCards();

        this.setupRoundMarker();

        // Setup game notifications to handle (see "setupNotifications" method below)
        this.setupNotifications();

        console.log( "Ending game setup" );
    }
    

    ///////////////////////////////////////////////////
    //// Game & client states
    
    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    onEnteringState( stateName, args ) {
        console.log( 'Entering state: '+stateName, args );
        
        switch( stateName ) {
        
        /* Example:
        
        case 'myGameState':
        
            // Show some HTML block at this game state
            document.getElementById('my_html_block_id').style.display = 'block';
            
            break;
        */
        
        
        case 'dummy':
            break;
        }
    }

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState( stateName )
    {
        console.log( 'Leaving state: '+stateName );
        
        switch( stateName ) {
        
        /* Example:
        
        case 'myGameState':
        
            // Hide the HTML block we are displaying only during this game state
            document.getElementById('my_html_block_id').style.display = 'none';
            
            break;
        */
        
        
        case 'dummy':
            break;
        }               
    }

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //        
    onUpdateActionButtons( stateName, args ) {
        console.log( 'onUpdateActionButtons: '+stateName, args );
                    
        if (this.bga.gameui.isCurrentPlayerActive()) {            
            switch( stateName ) {
                case 'PlayerTurn':    
                const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn

                // Add test action buttons in the action status bar, simulating a card click:
                playableCardsIds.forEach(
                    cardId => this.bga.statusBar.addActionButton(_('Play card with id ${card_id}').replace('${card_id}', cardId), () => this.onCardClick(cardId))
                ); 

                this.bga.statusBar.addActionButton(_('Pass'), () => this.bga.actions.performAction("actPass"), { color: 'secondary' }); 
                break;
            }
        }
    }

    ///////////////////////////////////////////////////
    //// Utility methods

    coloredPlayerName(name, specifiedColor = null) {
      const player = Object.values(this.gamedatas.players).find((player) => player.name == name);
      if (player == undefined) return `<!--PNS--><span class="playername playername_wrapper_${specifiedColor}">${name}</span><!--PNE-->`;

      const color = specifiedColor ? specifiedColor : player.color;
      const color_bg = player.color_back ? 'background-color:#' + player.color_back + ';' : '';
      return `<!--PNS--><span class="playername playername_wrapper_${color}" style="color:#${color};${color_bg}">${name}</span><!--PNE-->`;
    }
    
    bgaFormatText(log, args) {
      try {
        if (log && args) {

            let playernames_keys = Object.keys(args).filter((key) => key.startsWith( 'player_name'));
            for(let k=0; k<playernames_keys.length; k++){
                let key = playernames_keys[k];
                args[key] = this.coloredPlayerName(args[key]);
            }

            if('card' in args && 'card_icon' in args) {
                args.card_icon = this.tplCard(args.card, '_log');
            }
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return { log, args };
    }
    
    destroyTooltip(elem) {
        /*TODO JSA destroyTooltip ?
        if (this.tooltips[elem.id]) {
            clearTimeout(this.tooltips[elem.id].showTimeout);
            this.tooltips[elem.id].close();
            this.tooltips[elem.id].destroy();
            delete this.tooltips[elem.id];
        }
        */
    }

    destroy(elem, delayRemove = false) {
        //debug(`destroy ${elem.id}`,elem);
        this.destroyTooltip(elem);
        this.empty(elem);
        if(!delayRemove) elem.remove();
    }
    
    empty(container) {
        //debug("empty",container);
        container = $(container);
        container.childNodes.forEach((node) => {
            //!! destroy node makes gap in LOOP because of removing them
            this.destroy(node,true);
        });
        container.childNodes.forEach((node) => {
            node.remove();
        });
        container.innerHTML = '';
    }

    async setupRoundMarker(){
        let round = this.gamedatas.round;
        console.log("setupRoundMarker", round);
        if(round>0){
            const meepleDiv = document.getElementById(`goa_board_round_marker`);
            const destinationDiv = document.getElementById(`goa_board_round_cell_${round}`);
            await this.animationManager.slideAndAttach(meepleDiv, destinationDiv);
        }
        else {
            //TODO JSA reset round between sessions ?
        }
    }
    
    formatIcon(name, n = null) {
        let type = name;
        let text = n == null ? '' : `<span class='goa_icon_qty' data-value="${n}">${n}</span>`;
        return `<div class="goa_icon_container goa_icon_container_${type}">
            <div class="goa_icon goa_icon_${type}">${text}</div>
            </div>`;
    }
    /**
     * Use this tpl for any counters that represent qty of tokens
     */
    tplResourceCounter(player, res) {
        return `
            <div class='goa_player_resource goa_resource_holder_${res}'>
                ${this.formatIcon(res, null)}
                <span id='goa_counter_${player.id}_${res}' class='goa_resource_${res}'></span>
                <div class='goa_reserve' id='goa_reserve_${player.id}_${res}'></div>
            </div>
            `;
    }

    ///////////////////////////////////////////////////
    //// Player's action
    
    /*
    
        Here, you are defining methods to handle player's action (ex: results of mouse click on 
        game objects).
        
        Most of the time, these methods:
        _ check the action is possible at this game state.
        _ make a call to the game server
    
    */
    
    // Example:
    
    onCardClick( card_id ) {
        console.log( 'onCardClick', card_id );

        this.bga.actions.performAction("actPlayCard", { 
            card_id,
        }).then(() =>  {                
            // What to do after the server call if it succeeded
            // (most of the time, nothing, as the game will react to notifs / change of state instead)
        });        
    }

    
    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
        setupNotifications:
        
        In this method, you associate each of your game notifications with your local method to handle it.
        
        Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                your goa.game.php file.
    
    */
    setupNotifications() {
        console.log( 'notifications subscriptions setup' );
        
        // automatically listen to the notifications, based on the `notif_xxx` function on this class. 
        // Uncomment the logger param to see debug information in the console about notifications.
        this.bga.notifications.setupPromiseNotifications({
            // logger: console.log
        });
    }
    
    // TODO: from this point and below, you can write your game notifications handling methods
    
    /*
    Example:
    async notif_cardPlayed( args ) {
        // Note: args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
        
        // TODO: play the card in the user interface.
    }
    */

    //For debugging :
    notif_reloadPage(notif) {
        window.location.reload(true);
    }
    
    async notif_drawAndDiscardExploration(notif) {
        console.log("notif_drawAndDiscardExploration",notif);
        this.discardExCard(notif.card);
    }
    async notif_giveExplorationCardsToPlayer(notif) {
        console.log("notif_giveExplorationCardsToPlayer",notif);


        this.explorationHand.addCards(notif.cards, { fromStock: this.explorationDeck});
    }

    ////////////////////////////////////////////////////////
    //  _____ _ _
    // |_   _(_) | ___  ___
    //   | | | | |/ _ \/ __|
    //   | | | | |  __/\__ \
    //   |_| |_|_|\___||___/
    //////////////////////////////////////////////////////////
 
    setupTiles() {
        console.log("setupTiles"); 
        //Destroy previous tiles
        document.querySelectorAll('.goa_tile[id^="goa_tile-"]').forEach((oCard) => {
            this.destroy(oCard);
        });

        //market Grid cells
        for(let row=1;row<=MARKET_GRID_SIZE;row++){
            for(let col=1;col<=MARKET_GRID_SIZE;col++){
                let grid_cell = `goa_market_grid_space-${row}-${col}`;
                if(!$(grid_cell)) document.getElementById(`goa_board_market_grid`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':row, 'col':col}));
                //this.empty(grid_cell);
            }
        }
        //---------------------------------
        //grid frame
        for(let col=1;col<=MARKET_GRID_SIZE;col++){
            let grid_cell = `goa_market_grid_space-${0}-${col}`;
            if(!$(grid_cell)) document.getElementById(`goa_board_market_top`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':0, 'col':col}));

            grid_cell = `goa_market_grid_space-${(MARKET_GRID_SIZE+1)}-${col}`;
            if(!$(grid_cell)) document.getElementById(`goa_board_market_bottom`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':(MARKET_GRID_SIZE+1), 'col':col}));


        }
        for(let row=1;row<=MARKET_GRID_SIZE;row++){
            let grid_cell = `goa_market_grid_space-${row}-${0}`;
            if(!$(grid_cell)) document.getElementById(`goa_board_market_left`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':row, 'col':0}));

            grid_cell = `goa_market_grid_space-${row}-${(MARKET_GRID_SIZE+1)}`;
            if(!$(grid_cell)) document.getElementById(`goa_board_market_right`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':row, 'col':(MARKET_GRID_SIZE+1)}));
        }
        //---------------------------------

        let cardIds = this.gamedatas.tiles.map((card) => {
            this.addTile(card);
            return card.id;
        });
    }
    getTileContainer(tile) {
        if(tile.subtype == TILE_SUBTTYPE_OUTPOST){
            if (tile.location.startsWith(TILE_LOCATION_BOARD_OUTPOST) ) {
                return `goa_board_outposts_${tile.colony}`;
            }
        }
        
        if(tile.subtype == TILE_SUBTTYPE_MARKET){
            if (tile.location == TILE_LOCATION_BOARD_MARKET ) {
                if(tile.row>0 && tile.col>0 && tile.row<=MARKET_GRID_SIZE && tile.col<=MARKET_GRID_SIZE ){
                    return `goa_market_grid_space-${tile.row}-${tile.col}`;
                }
            }
        }
        console.error('Trying to get container of a tile', tile);
        return 'goa_game_container';
    }
    
    addTile(tile, location = null) {
        console.log('addTile',tile,location);
        if (!$(`goa_tile-${tile.id}`)){
            let container = location == null ? this.getTileContainer(tile) : location;
            
            if(container == `goa_board_outposts_${tile.colony}`){
                //use bga-cards stock
                this.outpostsTilesStocks[tile.colony].addCard(tile);
            }
            else {//manual add
                document.getElementById(container).insertAdjacentHTML('beforeend', this.tplTile(tile));
            }

        }
        return $(`goa_tile-${tile.id}`);
    }
    getTileTooltip(tile) {
        let cardDatas = tile;
        let title = "";
        let div = this.tplTile(cardDatas,'_tmp'); 
        return [`<div class='goa_tile_tooltip'>
                <div class="goa_h1">${title}</div>
                <hr/>
                ${div}
            </div>`];

    }
    tplTile(tile, prefix ='') {
        if(tile.subtype == TILE_SUBTTYPE_OUTPOST) 
            return `<div class="goa_tile goa_tile${prefix} goa_outpost" id="goa_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                data-colony="${tile.colony}" >
            </div>`;
        if(tile.subtype == TILE_SUBTTYPE_MARKET) 
            return `<div class="goa_tile goa_tile${prefix} goa_market" id="goa_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                data-play_type="${tile.play_type}" >
            </div>`;
        return '';
    }

    tplMarketGridSpace(datas){
        return `<div class="goa_grid_space" id="goa_market_grid_space-${datas.row}-${datas.col}"
            data-row="${datas.row}" data-col="${datas.col}">
        </div>`;
    }

    ////////////////////////////////////////
    //  _______    _                   
    // |__   __|  | |                  
    //    | | ___ | | _____ _ __  ___  
    //    | |/ _ \| |/ / _ \ '_ \/ __| 
    //    | | (_) |   <  __/ | | \__ \ 
    //    |_|\___/|_|\_\___|_| |_|___/ 
    //                                
    ////////////////////////////////////////
    setupTokens(){
        console.log('setupTokens');
        document.querySelectorAll('.goa_token[id^="goa_token-"]').forEach((oToken) => {
            this.destroy(oToken);
        });

        Object.values(this.gamedatas.players).forEach(player => {
            for(let track=1;track<=5;track++){
                let progress_track = `goa_progress_track_${track}_${player.id}`;
                if(!$(progress_track)) document.getElementById(`goa_player_progress_tracks-${player.id}`).insertAdjacentHTML('beforeend', this.tplProgressTrack({'track':track, 'pId':player.id}));
                for(let k=1;k<=5;k++){
                    let progress_track_space = `goa_progress_track_space_${track}_${k}_${player.id}`;
                    if(!$(progress_track_space)) document.getElementById(progress_track).insertAdjacentHTML('beforeend', this.tplProgressTrackSpace({'track':track, 'space':k, 'pId':player.id}));
                    this.empty(progress_track_space);
                }
            }
        });

        let tokenIds = this.gamedatas.tokens.map((token) => {
            this.addToken(token);
            return token.id;
        });
    }
    getTokenContainer(token) { 
        if( TOKEN_LOCATION_PLAYER_BOARD == token.location) {
            if(token.subtype == TOKEN_SUBTTYPE_PROGRESS_MARKER){
                return `goa_progress_track_space_${token.track}_${token.state}_${token.pId}`;
            }
            if(token.subtype == TOKEN_SUBTTYPE_AUCTION_MARKER){
                return `goa_player_auction_markers-${token.pId}`;
            }
        } 
        if( TOKEN_LOCATION_BOARD == token.location) {
            if(token.subtype == TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER){
                if(token.row>=0 && token.col>=0 && token.row<=1+MARKET_GRID_SIZE && token.col<=1+MARKET_GRID_SIZE ){
                    return `goa_market_grid_space-${token.row}-${token.col}`;
                }
            }
            if(token.subtype == TOKEN_SUBTTYPE_AUCTION_MARKER){
                if(token.row>=0 && token.col>=0 && token.row<=1+MARKET_GRID_SIZE && token.col<=1+MARKET_GRID_SIZE ){
                    return `goa_market_grid_space-${token.row}-${token.col}`;
                }
            }
        } 
        
        console.error('Trying to get container of a token', token);
        return 'goa_game_container';
    }
    
    addToken(token, location = null) { 
        console.log("addToken",token,location); 
        if (!$(`goa_token-${token.id}`)){
            let container = location == null ? this.getTokenContainer(token) : location;
            document.getElementById(container).insertAdjacentHTML('beforeend', this.tplToken(token));
        }
        return $(`goa_token-${token.id}`);
    }
    
    tplToken(token, prefix ='') {
        if(token.subtype == TOKEN_SUBTTYPE_PROGRESS_MARKER) 
            return `<div id="goa_token-${token.id}" class="goa_token goa_progress_marker" data-track='${token.track}' data-row='${token.state}' data-color='${this.gamedatas.players[token.pId].color}' data-pid='${token.pId}'></div>`;
        if(token.subtype == TOKEN_SUBTTYPE_AUCTION_MARKER) 
            return `<div id="goa_token-${token.id}" class="goa_token goa_auction_marker" data-value='${token.auctionOrder}' data-color='${this.gamedatas.players[token.pId].color}' data-pid='${token.pId}'></div>`;
        if(token.subtype == TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER) 
            return `<div id="goa_token-${token.id}" class="goa_token goa_first_auction_marker" ></div>`;
        return '';
    }
    tplProgressTrack(datas) {
        return `<div class="goa_progress_track" id="goa_progress_track_${datas.track}_${datas.pId}" data-track='${datas.track}'></div>`;
    }
    tplProgressTrackSpace(datas) {
        return `<div class="goa_progress_track_space" id="goa_progress_track_space_${datas.track}_${datas.space}_${datas.pId}" data-track='${datas.track}' data-pos='${datas.space}'></div>`;
    }

    ////////////////////////////////////////////////////////
    //    ____              _
    //   / ___|__ _ _ __ __| |___
    //  | |   / _` | '__/ _` / __|
    //  | |__| (_| | | | (_| \__ \
    //   \____\__,_|_|  \__,_|___/
    //////////////////////////////////////////////////////////
    setupCards() {
        console.log("setupCards");
        // This function is refreshUI compatible
        //destroy previous cards
        document.querySelectorAll('.goa_card[id^="goa_card-"]').forEach((pCard) => {
            this.destroy(pCard);
        });
        
        this.explorationCardsManager = new BgaCards.Manager({
            animationManager: this.animationManager,
            type: 'goa_exploration_card',
            getId: (card) => card.id,
            setupFrontDiv: (card, div) => {
                div.dataset.type = card.type; 
                this.bga.gameui.addTooltipHtml(div.id, `TODO JSA tooltip of ${card.type}`);
            },
            cardWidth: 131,
            cardHeight: 200,
        });
        this.explorationDeck = new BgaCards.Deck(this.explorationCardsManager, document.getElementById('goa_exploration_deck'), {
            cardNumber: this.gamedatas.deck_size.exploration,
            counter: {
                position: 'center',
                extraClasses: 'text-shadow',
            },
        });
        this.explorationDiscard = new BgaCards.DiscardDeck(this.explorationCardsManager, document.getElementById('goa_exploration_discard'), {
            shift: '0px',
            verticalShift: '0px',
            horizontalShift: '0px',
            maxHorizontalShift : 0,
            counter: {
                hideWhenEmpty: true,
            }, 
        });
        
        let currentPlayer = this.bga.players.getCurrentPlayer();
        if (currentPlayer) {
            this.explorationHand = new BgaCards.HandStock(this.explorationCardsManager, document.getElementById(`goa_exploration_hand-${currentPlayer.id}`), {
                sort: BgaCards.sort('type'),
            });
        }
        
        let cardIds = this.gamedatas.cards.map((card) => {
            this.addCard(card);
            return card.id;
        });
    }

    getCardContainer(card) {
        if(card.subtype == CARD_SUBTTYPE_EXPLORATION){
            if (card.location == (CARD_LOCATION_EXP_DISCARD) ) {
                return `goa_exploration_discard`;
            }
            if (card.location == (CARD_LOCATION_HAND) ) {
                return `goa_exploration_hand`;
            }
        }
        
        console.error('Trying to get container of a card', card);
        return 'goa_game_container';
    }
    
    
    addCard(card, location = null) {
        console.log('addCard',card,location);
        if (!$(`goa_card-${card.id}`)){
            let container = location == null ? this.getCardContainer(card) : location;
            
            if(container == `goa_exploration_discard`){
                //use bga-cards stock
                this.explorationDiscard.addCard(card);
            } else if(container.startsWith(`goa_exploration_hand`)){
                //use bga-cards stock
                this.explorationHand.addCard(card);
            }
            else {//manual add
                document.getElementById(container).insertAdjacentHTML('beforeend', this.tplCard(card));
            }

        }
        return $(`goa_card-${card.id}`);
    }
    
    tplCard(card, prefix ='') {
        if(card.subtype == CARD_SUBTTYPE_EXPLORATION) 
            return `<div id="goa_card-${card.id}${prefix}" class="goa_card goa_exploration_card goa_card${prefix}" data-type='${card.type}' data-pid='${card.pId}'></div>`;
        return '';
    }
    // draw + discard from deck
    discardExCard(card_datas) {
        let cardNumber = this.explorationDeck.getCardNumber();

        if (cardNumber >= 1) {
            this.explorationDiscard.addCard(card_datas, { fromStock: this.explorationDeck });
        }
    }
}
