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

//Used Constants copied from PHP file

const TILE_SUBTTYPE_OUTPOST = 1;
const TILE_SUBTTYPE_MARKET = 2;

const TILE_LOCATION_BOARD_OUTPOST = 'board_c';

const TOKEN_LOCATION_PLAYER_BOARD = 'p_board';

const TOKEN_SUBTTYPE_PROGRESS_MARKER = 1;
const TOKEN_SUBTTYPE_AUCTION_MARKER = 2;

export class Game {
    
    constructor(bga) {
        console.log('goa constructor');
        this.bga = bga;
            
        // Here, you can init the global variables of your user interface
        // Example:
        // this.myGlobalValue = 0;

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

        // Example to add a div on the game area
        this.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
            <div id="goa_game_container">
                <div id="goa_overall_background"></div>
                <div id="goa_main_zone">
                    <div id='goa_resizable_board'>
                        <div id='goa_board_container'>
                            <div id="goa_board">
                                <div id="goa_board_background"></div>
                                <div id="goa_board_tiles">
                                    <div id="goa_board_outposts">
                                        <div id="goa_board_outposts_1"></div>
                                        <div id="goa_board_outposts_2"></div>
                                        <div id="goa_board_outposts_3"></div>
                                        <div id="goa_board_outposts_4"></div>
                                    </div>
                                    <div id="goa_board_market"></div>
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
            // example of setting up players boards
            this.bga.playerPanels.getElement(player.id).insertAdjacentHTML('beforeend', `
                <span id="energy-player-counter-${player.id}"></span> Energy
            `);
            const counter = new ebg.counter();
            counter.create(`energy-player-counter-${player.id}`, {
                value: player.energy,
                playerCounter: 'energy',
                playerId: player.id
            });

            // example of adding a div for each player
            document.getElementById('goa_main_zone').insertAdjacentHTML('beforeend', `
                <div id="goa_player_table-${player.id}" class="goa_player_table" data-pid=${player.id} data-color='${player.color}'>
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
        this.setupTiles();
        this.setupTokens();

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
        
        console.error('Trying to get container of a tile', tile);
        return 'goa_game_container';
    }
    
    addTile(tile, location = null) {
        console.log('addTile',tile,location);
        if (!$(`goa_tile-${tile.id}`)){
            let container = location == null ? this.getTileContainer(tile) : location;
            document.getElementById(container).insertAdjacentHTML('beforeend', this.tplTile(tile));
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
        return '';
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
        return '';
    }
    tplProgressTrack(datas) {
        return `<div class="goa_progress_track" id="goa_progress_track_${datas.track}_${datas.pId}" data-track='${datas.track}'></div>`;
    }
    tplProgressTrackSpace(datas) {
        return `<div class="goa_progress_track_space" id="goa_progress_track_space_${datas.track}_${datas.space}_${datas.pId}" data-track='${datas.track}' data-pos='${datas.space}'></div>`;
    }
}
