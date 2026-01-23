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
const [BgaHelp,BgaZoom] = await globalThis.importDojoLibs([g_gamethemeurl + "modules/js/ext/bga-help.js", g_gamethemeurl + "modules/js/ext/bga-zoom.js"]);

//import * as bgz from "./ext/bga-zoom.js";
//import * as bgh from "./ext/bga-help.js";

//Used Constants copied from PHP file
const CARD_SUBTTYPE_EXPLORATION = 1;

const TILE_SUBTTYPE_OUTPOST = 1;
const TILE_SUBTTYPE_MARKET = 2;

const COLONY_KOLLAM    = 1;
const COLONY_KOCHI     = 2;
const COLONY_MADRAS    = 3;
const COLONY_KOZHIKODE = 4;

const ACTION_MONEY               = 1;
const ACTION_CONTRACT            = 2;
const ACTION_SHIP                = 3;
const ACTION_COMPASS             = 4;
const ACTION_EXPLORATION         = 5;
const ACTION_SHIPS_OR_CONTRACTS  = 6;
const ACTION_SPICE               = 7;
const ACTION_FAVOR               = 8;
const ACTION_SWAP                = 9;
const ACTION_RESUPPLY            = 10;
const ACTION_HARVEST             = 11;
const ACTION_ESPIONAGE           = 12;
const ACTION_VICEKING            = 13;

const MARKET_GRID_SIZE = 5;

const MARKET_TYPE_PLANTATION      = 1;
const MARKET_TYPE_ACTION_REUSEABLE = 2;
const MARKET_TYPE_ACTION_DIRECT   = 4;
const MARKET_TYPE_ACTION_ONCE     = 5;
const MARKET_TYPE_SCORING         = 6;

const CARD_LOCATION_EXP_DISCARD = '1_discard';
const CARD_LOCATION_HAND = 'hand';

const TILE_LOCATION_BOARD_MARKET = 'market';
const TILE_LOCATION_BOARD_OUTPOST = 'board_c';
const TILE_LOCATION_PLAYER_BOARD = 'p_board';

const TOKEN_LOCATION_BOARD = 'board';
const TOKEN_LOCATION_PLAYER_BOARD = 'p_board';
const TOKEN_LOCATION_TILE = 'tile-';

const TOKEN_SUBTTYPE_PROGRESS_MARKER = 1;
const TOKEN_SUBTTYPE_AUCTION_MARKER = 2;
const TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER = 3;
const TOKEN_SUBTTYPE_SPICE = 4;

import { ConfirmTurn } from "./states/ConfirmTurn.js";
import { PlayerChoice, ClientSelectTileAction } from "./states/PlayerChoice.js";
import { PlayerTurn } from "./states/PlayerTurn.js";

export class Game {
    
    constructor(bga) {
        console.log('goa constructor');
        this.bga = bga;

        // Declare the State classes
        this.playerTurn = new PlayerTurn(this, bga);
        this.bga.states.register('PlayerTurn', this.playerTurn);
        this.confirmTurn = new ConfirmTurn(this, bga);
        this.bga.states.register('ConfirmTurn', this.confirmTurn);
        this.playerChoice = new PlayerChoice(this, bga);
        this.bga.states.register('PlayerChoice', this.playerChoice);
        this.clientSelectTileAction = new ClientSelectTileAction(this, bga);
        this.bga.states.register('ClientSelectTileAction', this.clientSelectTileAction);

        // Uncomment the next line to show debug informations about state changes in the console. Remove before going to production!
        this.bga.states.logger = console.log;

        // Here, you can init the global variables of your user interface
        // Example:
        // this.myGlobalValue = 0;
        this._counters = {};
        this._selectableNodes = [];
        this._connections = [];
        this._notif_uid_to_log_id = {};
        this._notif_uid_to_mobile_log_id = {};

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
        console.log( "Starting game setup" , gamedatas);
        this.gamedatas = gamedatas;

        this.animationManager = new BgaAnimations.Manager({
            animationsActive: () => this.bga.gameui.bgaAnimationsActive(),
        });

        this.const = gamedatas.constants;

        // add a div on the game area
        this.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
            <div id="goa_overall_background"></div>
            <div id="goa_top_zone" class="whiteblock goa_no_display"></div>
            <div id="goa_game_container">
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
                        ${this.tplResourceCounter(player,'compasses')}
                    </div>
                    <div class='goa_player_counters_line'>
                        ${this.tplResourceCounter(player,'spices_1')}
                        ${this.tplResourceCounter(player,'spices_2')}
                        ${this.tplResourceCounter(player,'spices_3')}
                        ${this.tplResourceCounter(player,'spices_4')}
                        ${this.tplResourceCounter(player,'spices_5')}
                    </div>
                    <div class='goa_player_counters_line'  ${ (currentPlayer && player.id == currentPlayer.id) ? '': 'style="display:none;"'}>
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_1')}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_2')}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_3')}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_4')}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_5')}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_6')}
                    </div>
                    <div class='goa_player_counters_line goa_player_counters_lastline'>
                        <div id="goa_first_player_container_${player.id}" class="goa_first_player_container"></div>
                    </div>
                </div>
            `);
            this.bga.gameui.addTooltipHtmlToClass(`goa_first_player_container`, _('Starting player'));

            this._counters[player.id] = {};
            this.createCounter(player.id,'exploration_hand_size',player.exploration_hand_size);
            this.createCounter(player.id,'ships',player.ships);
            this.createCounter(player.id,'contracts',player.contracts);
            this.createCounter(player.id,'compasses',player.compasses);
            if (currentPlayer && player.id == currentPlayer.id){
                this.createCounter(player.id,'money',player.money);
                //counters on hand symbols :
                this.createCounter(player.id,'explo_symbol_tiny_1' , 0);
                this.createCounter(player.id,'explo_symbol_tiny_2' , 0);
                this.createCounter(player.id,'explo_symbol_tiny_3' , 0);
                this.createCounter(player.id,'explo_symbol_tiny_4' , 0);
                this.createCounter(player.id,'explo_symbol_tiny_5' , 0);
                this.createCounter(player.id,'explo_symbol_tiny_6' , 0);
                
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_explo_symbol_tiny_1`, this.getCardSymbolName(1));
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_explo_symbol_tiny_2`, this.getCardSymbolName(2));
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_explo_symbol_tiny_3`, this.getCardSymbolName(3));
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_explo_symbol_tiny_4`, this.getCardSymbolName(4));
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_explo_symbol_tiny_5`, this.getCardSymbolName(5));
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_explo_symbol_tiny_6`, this.getCardSymbolName(6));
            } 
            else {
                document.getElementById(`goa_counter_${player.id}_money`).innerHTML = '???';
            }

            this.const.SPICES_ALL.forEach((spice) => {
                this.createCounter(player.id,'spices_'+spice ,player.spices[spice]);
            });

            this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_exploration_hand_size`, _('Exploration cards'));
            this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_money`, _('Number of Gold Pardau coins on money cards'));
            this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_ships`, _('Number of Ships on ship cards'));
            this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_contracts`, _('Number of Contracts on contract cards'));
            this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_compasses`, _('Compass cards'));

            
            this.const.SPICES_ALL.forEach((spice) => {
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_spices_${spice}`, _(`Spices`));
            });

            // example of adding a div for each player
            document.getElementById('goa_main_zone').insertAdjacentHTML('beforeend', `
                <div id="goa_player_table-${player.id}" class="goa_player_table whiteblock" data-pid=${player.id} data-color='${player.color}'>
                    <div class='goa_player_table_title_name' >${this.bga.gameui.format_string(('${player_name}'), { player_name:this.coloredPlayerName(player.name)}) }</div>
                    <div id="goa_player_tiles-${player.id}" class="goa_player_tiles"></div>
                    <div class='goa_resizable_playerboard'>
                        <div class='goa_playerboard_container'>
                            <div class="goa_playerboard">
                                <div class="goa_playerboard_background"></div>
                                <div id="goa_player_plantations-${player.id}" class="goa_player_plantations"></div>
                                <div id="goa_player_outposts-${player.id}" class="goa_player_outposts"></div>
                                <div id="goa_player_auction_markers-${player.id}" class="goa_player_auction_markers">
                                </div>
                                <div id="goa_player_progress_headers-${player.id}" class="goa_player_progress_headers"></div>
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
        
        this.setupTiles();
        this.setupTokens();
        this.setupCards();

        this.setupRoundMarker();

        document.getElementById(`generalactions`).insertAdjacentHTML('afterend', "<div id='customActions' style='display:inline-block'></div>");
        document.getElementById(`customActions`).insertAdjacentHTML('afterend', "<div id='restartActions' style='display:inline-block'></div>");

        this.zoomManager = new ZoomManager({
            element: document.getElementById('goa_game_container'),
            localStorageZoomKey: 'goa-zoom',
            zoomControls: {
                color: 'white',
            },
        });
        this.helpManager = new HelpManager(this.gamegui, {
            buttons: [
                new BgaHelpPopinButton({
                    title: _("Player aid"),
                    html: `<div id='goa_first_help_popin'>
                        ${this.formatTilesHelp()}
                        ${this.formatCardsHelp()}
                    </div>`,
                })
            ],
        });

        this.bga.userPreferences.onChange = (pref_id, pref_value) => this.myGamePreferenceChanged(pref_id, pref_value);

        // Setup game notifications to handle (see "setupNotifications" method below)
        this.setupNotifications();

        console.log( "Ending game setup" );
    }
    

    ///////////////////////////////////////////////////
    //// Utility methods
    /*
        Here, you can defines some utility methods that you can use everywhere in your javascript
        script. Typically, functions that are used in multiple state classes or outside a state class.
    */
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
            if('token' in args && 'token_icon' in args) {
                args.token_icon = this.tplToken(args.token, '_log');
            }
            if('tile' in args && 'tile_icon' in args) {
                args.tile_icon = this.tplTile(args.tile, '_log');
            }
            if('icon_money' in args) {
                args.icon_money = this.formatIcon('money');
            }
            if('icon_ships' in args) {
                args.icon_ships = this.formatIcon('ships');
            }
            if('icon_contracts' in args) {
                args.icon_contracts = this.formatIcon('contracts');
            }
            if('icon_compass' in args) {
                args.icon_compass = this.formatIcon('compasses');
            }
            if('icon_explo' in args) {
                args.icon_explo = this.formatIcon('exploration_hand_size');
            }
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return { log, args };
    }
    
    removeAllActionButtons() {
        this.empty('customActions');
        this.empty('restartActions');
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
    
    clearSelection() {
        console.log('clearSelection()' );
        this._connections.forEach((i) => {
            let node = i[0];
            let action = i[1];
            console.log('clearSelection()... disconnect ' ,node, action,);
            this.bga.gameui.disconnect(node,action);
        });
        this._connections = [];
        this._selectableNodes.forEach((node) => {
            if ($(node)){
                $(node).classList.remove('selectable');
                $(node).classList.remove('selected');
            }
        });
        this._selectableNodes = [];
        [...document.getElementsByClassName('selectable')].forEach((node) => {
            if ($(node)){
                $(node).classList.remove('selectable')
            }
        });
        [...document.getElementsByClassName('selected')].forEach((node) => {
            if ($(node)){
                $(node).classList.remove('selected')
            }
        });
        this.empty(document.getElementById('goa_top_zone'));
        document.getElementById('goa_top_zone').classList.add('goa_no_display');
    }

    onClick(node, callback) {
        let safeCallback = (evt) => {
            evt.stopPropagation();
            //if (this.isInterfaceLocked()) return false;
            callback(evt);
        };

        let con = this.bga.gameui.connect( $(node), 'click', safeCallback )
        this._connections.push( [$(node), 'click', safeCallback ]);
        $(node).classList.add('selectable')
        this._selectableNodes.push(node);
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

    createCounter(player_id, resourceName, initValue){
        let counter = new ebg.counter();
        counter.create(`goa_counter_${player_id}_${resourceName}`, {
            value: initValue,
            playerCounter: resourceName,
            playerId: player_id
        });
        this._counters[player_id][resourceName] = counter;
    }

    onToggleExpandable(div){
        let parent = div.parentNode;
        let expandable = parent.querySelector('.goa_expandablecontent');
        let icon = parent.querySelector('.expandablearrow div');
        if (
            expandable.classList.contains("goa_expand_off")
        ) {
            parent.classList.add("goa_expand_on");
            expandable.classList.remove("goa_expand_off");
            icon.classList.add("icon20_collapse");
            icon.classList.remove("icon20_expand");
        } else {
            expandable.classList.add("goa_expand_off");
            parent.classList.remove("goa_expand_on");
            icon.classList.add("icon20_expand");
            icon.classList.remove("icon20_collapse");
        }
    }
    
    refreshPlayersDatas(players){
        console.log("refreshPlayersDatas()",players);
        //Erasing this array would erase some BGA datas (ex color_back)-> we should erase only datas in parameter array
        //this.gamedatas.players = players;
        Object.values(players).forEach((player) => {
            let pid = player.id;
            for (const property in player) {
                this.gamedatas.players[pid][property] = player[property];
            }
        });
    }
    
    cancelLogs(notifIds) {
      notifIds.forEach((uid) => {
        if (this._notif_uid_to_log_id.hasOwnProperty(uid)) {
          let logId = this._notif_uid_to_log_id[uid];
          //if ($('log_' + logId)) dojo.addClass('log_' + logId, 'cancel');
          document.getElementById('log_' + logId)?.classList.add('cancel');
        }
        if (this._notif_uid_to_mobile_log_id.hasOwnProperty(uid)) {
          let mobileLogId = this._notif_uid_to_mobile_log_id[uid];
          //if ($('dockedlog_' + mobileLogId)) dojo.addClass('dockedlog_' + mobileLogId, 'cancel');
          document.getElementById('dockedlog_' + mobileLogId)?.classList.add('cancel');
        }
      });
    }

    myGamePreferenceChanged(pref_id, pref_value) {
        switch (pref_id) {
            case this.const.PREF_CONFIRM: 
                //We could hide pref "undo button style", but We also use undo button during a player turn before the action of confirm
                //this.bga.userPreferences.toggleVisibility(this.const.PREF_UNDO_STYLE, pref_value != this.const.PREF_CONFIRM_DISABLED);
                break;
        }
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
        
        
        const originalOnPlaceLogOnChannel = this.bga.gameui.onPlaceLogOnChannel.bind(this.bga.gameui);
        this.bga.gameui.onPlaceLogOnChannel = (msg) => {
            var currentLogId = this.bga.gameui.notifqueue.next_log_id;
            var currentMobileLogId = this.bga.gameui.next_log_id;
            var res = originalOnPlaceLogOnChannel(msg);
            this._notif_uid_to_log_id[msg.uid] = currentLogId;
            this._notif_uid_to_mobile_log_id[msg.uid] = currentMobileLogId;
            return res;
            
        };

        // automatically listen to the notifications, based on the `notif_xxx` function on this class. 
        // Uncomment the logger param to see debug information in the console about notifications.
        this.bga.notifications.setupPromiseNotifications({
            logger: console.log,
            onStart: (notifName, msg, args) => {
                this.clearSelection();
            }, 
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
    
    notif_clearTurn(n) {
        console.log('notif_clearTurn: restarting turn/step', n);
        this.cancelLogs(n.notifIds);
    }

    async notif_refreshUI(notif){
        console.log("notif_refreshUI",notif);
        this.refreshPlayersDatas(notif.datas['players']);
        ['cards', 'tiles', 'tokens'].forEach((value) => {
            this.gamedatas[value] = notif.datas[value];
        });
        this.gamedatas._private = notif._private;

        let currentPlayer = this.bga.players.getCurrentPlayer();
        Object.values(this.gamedatas.players).forEach((player) => {
            let pId = player.id;
            //UPDATE ALL PLAYER COUNTERS
            this.bga.playerPanels.getScoreCounter(pId).toValue(player.score);
            
            this._counters[pId]['exploration_hand_size'].toValue(player.exploration_hand_size);
            this._counters[pId]['ships'].toValue(player.ships);
            this._counters[pId]['contracts'].toValue(player.contracts);
            this._counters[pId]['compasses'].toValue(player.compasses);

            if (currentPlayer && player.id == currentPlayer.id){

                this._counters[pId].money.toValue(player.money);
                Object.values([1,2,3,4,5,6]).forEach((i) => {
                    this._counters[pId]['explo_symbol_tiny_'+i].toValue(0);//will be updated by setupCards
                });
            }
            
            this.const.SPICES_ALL.forEach((spice) => {
                this._counters[pId]['spices_'+spice ].toValue(player.spices[spice]);
            });
        });
        
        await this.setupCards(false);
        await this.setupTiles(false);
        await this.setupTokens(false);
    }
    
    async notif_setupMarket(notif) {
        console.log("notif_setupMarket",notif);
        let tiles = notif.tiles;
        let voidDiv = document.getElementById('goa_top_zone');
        
        let marketDiv = document.getElementById('goa_board_market_grid');
        let marketTilesDiv = [...marketDiv.querySelectorAll('.goa_tile')];
        let animationsCleanMarket = marketTilesDiv.map(tileDiv => (() => {
            this.animationManager.slideOutAndDestroy(tileDiv, voidDiv, {duration: 1000});
        }));
        await this.animationManager.playInterval(animationsCleanMarket);

        let animationsRefill = Object.values(tiles).map(tile => (() => {
            this.animationManager.slideIn(this.addTile(tile), voidDiv, {duration: 1000});
        }));
        await this.animationManager.playInterval(animationsRefill);
    }

    async notif_newRound(notif) {
        console.log("notif_newRound",notif);
        this.gamedatas.round = notif.n;
        this.setupRoundMarker();
    }
    
    async notif_auctionPlaced(notif) {
        console.log("notif_auctionPlaced",notif);
        let token = notif.token;
        //let tokenDiv = document.getElementById(`goa_token-${token.id}`);
        let tokenDiv = this.addToken(token);
        let destinationDiv = document.getElementById(`goa_market_grid_space-${token.row}-${token.col}`);
        await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
    }
    
    async notif_auctionMarkerBack(notif) {
        console.log("notif_auctionMarkerBack",notif);
        let token = notif.token;
        let tokenDiv = this.addToken(token);
        let destinationDiv = document.getElementById(this.getTokenContainer(token));
        await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
    }
    
    async notif_giveFirstPlayerToken(notif) {
        console.log("notif_giveFirstPlayerToken",notif);
        let token = notif.token;
        let tokenDiv = this.addToken(token);
        let destinationDiv = document.getElementById(this.getTokenContainer(token));
        await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
    }
    
    async notif_winsAuction(notif) {
        console.log("notif_winsAuction",notif);
        let amount = notif.n;
        let fromPlayer = notif.player_id;
        let fromElement = document.getElementById(`goa_reserve_${fromPlayer}_money`);
        let toPlayer = notif.player_id2;
        let toElement = document.getElementById(`goa_reserve_${toPlayer}_money`);

        const floatingPieceAnimationSettings = {
            duration: 2000,
            ignoreScale: false,
            ignoreRotation: true,
        };
        let animations = new Array(amount).fill(0).map(index => (() => {
            this.animationManager.slideFloatingElement(this.createCoin(), fromElement, toElement, floatingPieceAnimationSettings)
        }));
        await this.animationManager.playInterval(animations);

        let currentPlayer = this.bga.players.getCurrentPlayer();
        if (currentPlayer) {
            if( fromPlayer == currentPlayer.id){
                this._counters[fromPlayer].money.incValue(-amount);
            } else if( toPlayer == currentPlayer.id){
                this._counters[toPlayer].money.incValue(amount);
            }
        }  
    }
    async notif_winsAuctionBank(notif) {
        console.log("notif_winsAuctionBank",notif);
        let amount = notif.n;
        let fromPlayer = notif.player_id;
        let fromElement = document.getElementById(`goa_reserve_${fromPlayer}_money`);
        let toElement = document.getElementById(`goa_money_stacks`);

        const floatingPieceAnimationSettings = {
            duration: 2000,
            ignoreScale: false,
            ignoreRotation: true,
        };
        let animations = new Array(amount).fill(0).map(index => (() => {
            this.animationManager.slideFloatingElement(this.createCoin(), fromElement, toElement, floatingPieceAnimationSettings)
        }));
        await this.animationManager.playInterval(animations);

        let currentPlayer = this.bga.players.getCurrentPlayer();
        if (currentPlayer) {
            if( fromPlayer == currentPlayer.id){
                this._counters[fromPlayer].money.incValue(-amount);
            } 
        } 
    }
    
    async notif_getContracts(notif) {
        console.log('notif_getContracts', notif);
        this._counters[notif.player_id]['contracts'].incValue(notif.n);
    }
    async notif_getShips(notif) {
        console.log('notif_getShips', notif);
        this._counters[notif.player_id]['ships'].incValue(notif.n);
    }
    async notif_getCompass(notif) {
        console.log('notif_getCompass', notif);
        this._counters[notif.player_id]['compasses'].incValue(notif.n);
    }
    
    async notif_giveTile(notif) {
        console.log("notif_giveTile",notif);
        let tile = notif.tile;
        let tileDiv = this.addTile(tile);
        let destinationDiv = document.getElementById(this.getTileContainer(tile));
        await this.animationManager.slideAndAttach(tileDiv, destinationDiv);
    }
    
    async notif_discardTile(notif) {
        console.log("notif_discardTile",notif);
        let tile = notif.tile;
        let tileDiv = document.getElementById(`goa_tile-${tile.id}`);
        if(tileDiv){
            let voidDiv = document.getElementById('goa_top_zone');
            await this.animationManager.slideOutAndDestroy(tileDiv, voidDiv, {duration: 1000});
        }
    }
    
    async notif_newSpiceOnTile(notif) {
        console.log("notif_newSpiceOnTile",notif);
        let token = notif.token;
        let tokenDiv = this.addToken(token);
        //let destinationDiv = document.getElementById(this.getTokenContainer(token));
        //await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
        let voidDiv = document.getElementById('goa_top_zone');
        await this.animationManager.slideIn(tokenDiv, voidDiv, {duration: 1000});

        let spice_type = token.spice;
        this._counters[notif.player_id]['spices_'+spice_type ].incValue(1);
        
    }

    async notif_reshuffleExplorationDeck(notif) {
        console.log("notif_reshuffleExplorationDeck",notif);
        //this.explorationDeck.addCards(this.explorationDiscard.getCards());
        await this.explorationDeck.addCards(this.explorationDiscard.getCards().map(card => ({ id: card.id, })) );
        await this.explorationDeck.shuffle().then(() => console.log('shuffle ended'));
        //this.explorationDeck.removeAll();
        await this.explorationDeck.setCardNumber(notif.deck_size);
    }
    async notif_drawAndDiscardExploration(notif) {
        console.log("notif_drawAndDiscardExploration",notif);
        this.discardExCard(notif.card);
    }
    async notif_giveExplorationCardsToPlayer(notif) {
        console.log("notif_giveExplorationCardsToPlayer",notif);

        this._counters[notif.player_id]['exploration_hand_size'].incValue( notif.n);
        let currentPlayer = this.bga.players.getCurrentPlayer();
        if (currentPlayer && currentPlayer.id == notif.player_id && notif._private) {
            this.explorationHand.addCards(notif._private.cards, { fromStock: this.explorationDeck});
            Object.values(notif._private.cards).forEach((card) => {
                this._counters[currentPlayer.id]['explo_symbol_tiny_'+card.symbol].incValue(1);
            });
        }
        else {
            //TODO JSA ANIMATE BACK CARD ?
            await this.explorationDeck.setCardNumber(this.explorationDeck.getCardNumber() - notif.n );
        }
        
    }

    ////////////////////////////////////////////////////////
    //  _____ _ _
    // |_   _(_) | ___  ___
    //   | | | | |/ _ \/ __|
    //   | | | | |  __/\__ \
    //   |_| |_|_|\___||___/
    //////////////////////////////////////////////////////////
 
    async setupTiles(init = true) {
        console.log("setupTiles"); 
        
        if(!init){
            //Manual remove all stocks in order to be able to remove all tiles
            this.outpostsTilesStocks.forEach((stock) => { 
                //stock.remove();//DESTROy STOCK  KO because it removes the div holder
                stock.getCards().forEach((card) => {
                    stock.cardRemoved(card);
                });
                stock.removeAll();
            });
        }
        if(init){
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
            // create a card manager
            this.outpostsTilesManager = new BgaCards.Manager({
                animationManager: this.animationManager,
                type: 'goa_outpost',
                getId: (card) => card.id,
                setupFrontDiv: (card, div) => {
                    div.dataset.type = card.type; 
                    this.bga.gameui.addTooltipHtml(div.id, this.getTileTooltip(card));
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
        }
        
        //Destroy existing tiles
        document.querySelectorAll('.goa_tile[id^="goa_tile-"], .goa_outpost[id^="goa_outpost-"]').forEach((oTile) => {
            this.destroy(oTile);
        });

        let tileIds = this.gamedatas.tiles.map((oTile) => {
            this.addTile(oTile);
            return oTile.id;
        });
    }
    getTileContainer(tile) {
        if(tile.subtype == TILE_SUBTTYPE_OUTPOST){
            if (tile.location.startsWith(TILE_LOCATION_BOARD_OUTPOST) ) {
                return `goa_board_outposts_${tile.colony}`;
            }
            if (tile.location == (TILE_LOCATION_PLAYER_BOARD) ) {
                return `goa_player_outposts-${tile.pId}`;
            }
        }
        
        if(tile.subtype == TILE_SUBTTYPE_MARKET){
            if (tile.location == TILE_LOCATION_BOARD_MARKET ) {
                if(tile.row>0 && tile.col>0 && tile.row<=MARKET_GRID_SIZE && tile.col<=MARKET_GRID_SIZE ){
                    return `goa_market_grid_space-${tile.row}-${tile.col}`;
                }
            }
            if (tile.location == (TILE_LOCATION_PLAYER_BOARD) ) {
                if (tile.play_type == MARKET_TYPE_PLANTATION ) {
                    return `goa_player_plantations-${tile.pId}`;
                }
                return `goa_player_tiles-${tile.pId}`;
            }
        }
        console.error('Trying to get container of a tile', tile);
        return 'goa_game_container';
    }
    
    addTile(tile, location = null) {
        console.log('addTile',tile,location);
        let tileDiv = document.getElementById(`goa_tile-${tile.id}`) ;
        if (!tileDiv){
            let container = location == null ? this.getTileContainer(tile) : location;
            
            if(container == `goa_board_outposts_${tile.colony}`){
                //use bga-cards stock
                this.outpostsTilesStocks[tile.colony].addCard(tile);
            }
            else {//manual add
                document.getElementById(container).insertAdjacentHTML('beforeend', this.tplTile(tile));
            }
            this.bga.gameui.addTooltipHtml(`goa_tile-${tile.id}`, this.getTileTooltip(tile));

            tileDiv = document.getElementById(`goa_tile-${tile.id}`) ;
        }
        else {
            tileDiv.dataset.state = tile.state;//refresh state if already exists
        }

        return tileDiv;
    }
    getTileTooltip(tile) {
        let cardDatas = tile;
        let phaseA = false, phaseB = false;
        if(this.gamedatas.material.tiles[tile.subtype][tile.type].nbr_A > 0) phaseA = true;
        if(this.gamedatas.material.tiles[tile.subtype][tile.type].nbr_B > 0) phaseB = true;
        let phaseLabel ='';
        if(phaseA && phaseB) phaseLabel = _('Day') + ' / '+ _('Night');
        else if(phaseA) phaseLabel = _('Day');
        else if(phaseB) phaseLabel = _('Night');
        let textLine1 = this.bga.gameui.format_string( 
            bga_format(_('Phase deck : *${x}*'), {
                            '*': (t) => '<b><i>' + t + '</i></b>',
                        }),
             {'x': phaseLabel }
        );
        if(tile.subtype == TILE_SUBTTYPE_OUTPOST){
            textLine1 = this.bga.gameui.format_string( 
                _('Requires ${n} ${resource_name} : ${icon_resource}'),
                {'n': this.getNbContractsForColony(tile.colony) , resource_name: _('Contracts'), icon_resource: this.formatIcon('contracts')}
            );
        }
        let textAction = this.formatTileActionText( tile );
        let div = this.tplTile(cardDatas,'_tmp'); 
        return [`<div class='goa_tile_tooltip'>
                <div class="goa_tooltip_block1">
                    <h1 class="goa_tile_title">${this.formatTileTitle( tile )}</h1>
                </div>
                <div class="goa_tooltip_block2">
                    <div class="goa_tile_img_wrap">${div}</div>
                    <div class="goa_tile_text">
                        ${textLine1 == '' ? '' : '<span class="goa_tile_text_line1">'+textLine1+'</span>'}
                        ${textAction == '' ? '' : '<hr><span class="goa_tile_text_action">'+textAction+'</span>'}
                        <hr>
                        <span class="goa_tile_text_playType" data-playType="${tile.play_type}">${this.formatTilePlayTypeText( tile )}</span>
                        
                    </div>
                </div>
            </div>`];

    }
    formatTileTitle (tile) {
        let descriptionMap = new Map([
            [MARKET_TYPE_PLANTATION, this.bga.gameui.format_string(_("Plantation"),{ })] ,
            [MARKET_TYPE_ACTION_REUSEABLE, this.bga.gameui.format_string( _('Multi-use action'),{ } )],
            [MARKET_TYPE_ACTION_DIRECT, this.bga.gameui.format_string(_("Immediate gain"),{ })] ,
            [MARKET_TYPE_ACTION_ONCE, this.bga.gameui.format_string(_("Single use Action"),{ })] ,
            [MARKET_TYPE_SCORING, this.bga.gameui.format_string(_("Scoring"),{ })] ,
            
        ]);
        let description = ''; 
        if(tile.subtype == TILE_SUBTTYPE_OUTPOST){
            return this.bga.gameui.format_string(_("Outpost : ${name}"),{ name : this.getColonyName(tile.colony)});
        }
        else description = descriptionMap.get(tile.play_type);
        let text = '<b><i>' + description + '</i></b>';
        return text;
    }
    formatTilePlayTypeText (tile) {
        let descriptionMap;
        switch (tile.subtype )
        {
            case TILE_SUBTTYPE_OUTPOST:
                return this.bga.gameui.format_string(_("When you found an outpost, place the outpost tile on the matching space of your personal board. After founding an outpost, fill it with the appropriate spices from the supply. To found an outpost with the appropriate action, a player needs to recruit a minimum number of contracts: ${n} contracts for this one."),{ n : this.getNbContractsForColony(tile.colony) });
            case TILE_SUBTTYPE_MARKET: 
                descriptionMap = new Map([
                    [MARKET_TYPE_PLANTATION, this.bga.gameui.format_string(_("When you build a plantation, put it face up in one of the upper spaces of your personal board. Plantations have 1 to 3 spice fields; this one have ${x}. After building a plantation, fill the field(s) with the appropriate spice token(s). If you already own 4 plantations on your personal board, you must first discard 1 plantation, and any spice tokens still on it, before placing the 5th one. If the discarded plantation has a scoring symbol on it, keep the plantation face down in your play area. This plantation is worth ${n} points at the end of the game, whether it is face up on your personal board or face down in your play area."),{ x: tile.spice_slots.length , n : tile.end_score })] ,
                    [MARKET_TYPE_ACTION_REUSEABLE, this.bga.gameui.format_string( _(' For this tile, players must put it face up in their play area. They can use it before, during or after their action. This tile can be played during a future action, but only once per round. After using it, the player must flip it and may no longer use it for the rest of the round. At the beginning of the following round, players flip the tile face up and can use it again.'),{ } )],
                    [MARKET_TYPE_ACTION_DIRECT, this.bga.gameui.format_string(_("When a player acquires this tile, they immediately take the appropriate resources. After being used, the tile is discarded."),{ })] ,
                    [MARKET_TYPE_ACTION_ONCE, this.bga.gameui.format_string(_("This tile may only be used once during the game. Once used, the tile is discarded."),{ })] ,
                    [MARKET_TYPE_SCORING, this.formatScoringTileText(tile)] ,
                ]);
                break;
            default : return '';
        }
        let text = descriptionMap.get(tile.play_type);
        return text;
    }
    formatScoringTileText (tile) {
        let descriptionMap = new Map([
            [37, this.bga.gameui.format_string(_('At the end of the game, your single Plantation tiles are worth ${n} points instead of ${x} point.'),{n:3, x:1  })] ,
            [38, this.bga.gameui.format_string(_('You can score ${n} victory points by returning to the supply ${x} spice tokens placed on your outposts/plantations. Immediately after, flip this tile face down until the final scoring. At the end of the game, reveal this tile to add ${n} points to your score.'),{ n:5, x:6 } )],
            [39, this.bga.gameui.format_string(_('At the end of the game, this tile is worth ${n} victory points.'),{ 'n':tile.end_score })] ,
            [40, this.bga.gameui.format_string(_('At the end of the game, this tile is worth ${n} victory points.'),{ 'n':tile.end_score})] ,
        ]);
        let text = descriptionMap.get(tile.type);
        return text;
    }
    formatTileSpicesText (tile) {
        let number_label = tile.spice_slots.length;  
        let icons = '';
        let treatArray = null;
        Object.values( tile.spice_slots ).forEach((spice_type) => {
            icons += this.formatIcon('spices_'+spice_type);
            if(Array.isArray(spice_type)){
                treatArray = spice_type;
            }
        });
        let text = '';
        if(treatArray){
            Object.values( tile.spice_slots ).forEach((spice_type) => {
                icons = '';
                Object.values( spice_type ).forEach((spice_type_listElement) => {
                    icons += this.formatIcon('spices_'+spice_type_listElement);
                });
                text += '<li>' + this.bga.gameui.format_string(
                    _("Take ${n} ${resource_name} : ${icon_resource}.")
                    ,{n:1, 'resource_name': _('Spices'), icon_resource : icons}
                ) + '</li>';
            });
        }
        else {
            text = this.bga.gameui.format_string(
                _("Take ${n} ${resource_name} : ${icon_resource}.")
                ,{n:number_label, 'resource_name': _('Spices'), icon_resource : icons}
            );
        }
                  
        return text;
    }
    formatTileActionText (tile) {
        console.log("formatTileActionText",tile);
        let description = '';
        switch (tile.subtype )
        {
            case TILE_SUBTTYPE_OUTPOST:
                return this.formatTileSpicesText(tile);
            case TILE_SUBTTYPE_MARKET: 
                switch(this.gamedatas.material.tiles[tile.subtype][tile.type].play_type){
                    case MARKET_TYPE_PLANTATION:
                        return this.formatTileSpicesText(tile);
                    case MARKET_TYPE_ACTION_REUSEABLE:
                    case MARKET_TYPE_ACTION_DIRECT: 
                        let  returnAfterThis = false;
                        Object.entries( this.gamedatas.material.tiles[tile.subtype][tile.type].actions ).forEach(([action_type,number]) => {
                            let resource_name = '', icon_name='';
                            let number_label = number;
                            switch(parseInt(action_type)){
                                case ACTION_SPICE:
                                    returnAfterThis = true;
                                    description = this.formatTileSpicesText(tile);
                                case ACTION_MONEY:
                                    resource_name = _('Gold Pardau cards');
                                    icon_name = this.formatIcon('money');
                                    break;
                                case ACTION_CONTRACT:
                                    resource_name = _('Contracts');
                                    icon_name = this.formatIcon('contracts');
                                    break;
                                case ACTION_SHIP:
                                    resource_name = _('Ships');
                                    icon_name = this.formatIcon('ships');
                                    break;
                                case ACTION_COMPASS:
                                    resource_name = _('Compass cards');
                                    icon_name = this.formatIcon('compasses');
                                    break;
                                case ACTION_EXPLORATION:
                                    resource_name = _('Exploration cards');
                                    icon_name = this.formatIcon('exploration_card');
                                    break;
                                case ACTION_SHIPS_OR_CONTRACTS:
                                    resource_name = _('Ships or Contracts');
                                    icon_name = this.formatIcon('ships') + ' '+  this.formatIcon('contracts');
                                    break;
                                case ACTION_FAVOR: 
                                    description = this.bga.gameui.format_string(
                                        _("Look through the ${n} tiles that were returned to the box during setup for phases Day and Night. Take the tile of your choice among them. You may use it immediately if the tile allows it.")
                                        ,{n: 8}
                                    );
                                    returnAfterThis = true;
                                default: break;
                            }
                            if(!returnAfterThis){
                                description += '<li>'
                                    + this.bga.gameui.format_string(
                                        _("Take ${n} ${resource_name} : ${icon_resource}.")
                                        ,{n:number_label, 'resource_name': resource_name, icon_resource : icon_name}
                                    )
                                    +'</li>';
                            }
                        });
                        break;
                    case MARKET_TYPE_ACTION_ONCE: 
                        let actionDescriptionMap = new Map([
                                [ACTION_SWAP, this.bga.gameui.format_string(_('When you buy the Swap tile, place it face up in your play area. During one of your later action turns, you may take 1 tile of your choice from the board and put it face up in your play area. Put the Swap tile, face up, in the space previously occupied by the tile you just took. You may use the tile that you obtained with the Swap tile as soon as you acquire it or later in the game. The Swap tile can be auctioned again, as per the normal rules. '),{}) + '<br/>' + _('Bonanza variant: If the Swap tile is used in one of the corners, you also take the Bonanza tile.') ],
                                [ACTION_RESUPPLY, this.bga.gameui.format_string(_('Reveal ${n} Exploration cards and add the contracts shown on these cards. Then, take this sum in spices, contracts or ships. You choose the resource type after seeing the result. You can only choose one type of resource. Discard the Exploration cards after.'),{n:2})],
                                [ACTION_HARVEST, this.bga.gameui.format_string(_('Fill up to ${n} of your plantations and/or outposts with the appropriate spices.'),{n :3})],
                                [ACTION_ESPIONAGE, this.bga.gameui.format_string(
                                    bga_format( _("Choose an opponent and do one of these actions, using the position of this player's Progress marker in the appropriate column: _*Build ships,* *Harvest,* *Taxes,* *Found an outpost*_ "), {
                                        '*': (t) => '<li>' + t + '</li>',
                                        '_': (t) => '<ul>' + t + '</ul>',
                                    })
                                    ,{ }) + '<br/>' + _('That tile cannot be used to obtain Exploration cards.')],
                                [ACTION_VICEKING, this.bga.gameui.format_string(_('Without paying any resources, move your least advanced Progress marker (the one closest to the top) down on your Development board. If you have more than one marker in this row, move one of these markers, your choice.'),{})],
                            ]);
                        Object.entries( this.gamedatas.material.tiles[tile.subtype][tile.type].actions ).forEach(([action_type,number]) => {
                            description += actionDescriptionMap.get(parseInt(action_type));
                        });
                        break;
                    default: break;
                }
                break;
            default: break;
        }
        return description;
    }
    tplTile(tile, prefix ='') {
        if(tile.subtype == TILE_SUBTTYPE_OUTPOST) 
            return `<div class="goa_tile goa_tile${prefix} goa_outpost" id="goa_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                data-colony="${tile.colony}" data-spots="${tile.spice_slots ? tile.spice_slots.length : 0}">
            </div>`;
        if(tile.subtype == TILE_SUBTTYPE_MARKET) 
            return `<div class="goa_tile goa_tile${prefix} goa_market" id="goa_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                data-play_type="${tile.play_type}" data-state="${tile.state}" data-spots="${tile.spice_slots ? tile.spice_slots.length : 0}"
                data-end_score="${tile.end_score}" 
                >
            </div>`;
        return '';
    }

    tplMarketGridSpace(datas){
        return `<div class="goa_grid_space" id="goa_market_grid_space-${datas.row}-${datas.col}"
            data-row="${datas.row}" data-col="${datas.col}">
        </div>`;
    }

    formatTilesHelp(){
        let list1 = "";
        let j = 0;
        Object.entries(this.gamedatas.material.tiles[TILE_SUBTTYPE_MARKET]).forEach(([type,tile_type]) => {
            let resourceDatas = tile_type;
            resourceDatas.id = 0;
            resourceDatas.state = 0;
            resourceDatas.type = parseInt(type);
            resourceDatas.subtype = TILE_SUBTTYPE_MARKET;
            if(j>0){
                list1 = list1.concat('<hr>');
            }
            list1 = list1.concat( this.formatMarketTileHelp(resourceDatas));
            j++; 
        });
        let list2 = "";
        let k = 0;
        Object.entries(this.gamedatas.material.tiles[TILE_SUBTTYPE_OUTPOST]).forEach(([type,tile_type]) => {
            let resourceDatas = tile_type;
            resourceDatas.id = 0;
            resourceDatas.state = 0;
            resourceDatas.type = parseInt(type);
            resourceDatas.subtype = TILE_SUBTTYPE_OUTPOST;
            if(k>0){
                list2 = list2.concat('<hr>');
            }
            list2 = list2.concat( this.getTileTooltip(resourceDatas));
            k++; 
        });
        return `
        <div class="expandable">
            <div id="goa_marketTilesHelp_toggle" class="expandabletoggle expandablearrow" onclick="gameui.GameModule.onToggleExpandable(this)">
                <div class="icon20 icon20_expand"></div>
                <h1 class='goa_marketTilesHelp_title'>${ this.bga.gameui.format_string( _("Market Tiles (${n})"), {n:j} ) }</h1>
                <div class="goa_expand_ellipsis">...</div>
            </div>
            <div class='goa_marketTilesHelp goa_expandablecontent goa_expand_on'>
                ${list1}
            </div>
        </div>
        <div class="expandable">
            <div id="goa_outpostTilesHelp_toggle" class="expandabletoggle expandablearrow" onclick="gameui.GameModule.onToggleExpandable(this)">
                <div class="icon20 icon20_expand"></div>
                <h1 class='goa_outpostTilesHelp_title'>${this.bga.gameui.format_string( _("Outpost Tiles (${n})"), {n:k} ) }</h1>
                <div class="goa_expand_ellipsis">...</div>
            </div>
            <div class='goa_outpostTilesHelp goa_expandablecontent goa_expand_on'>
                ${list2}
            </div>
        </div>
        `;
    }
    formatMarketTileHelp(tileTypeDatas) {
        let tooltipHtml = this.getTileTooltip(tileTypeDatas);
        return tooltipHtml;
    }

    getNbContractsForColony(colony){
        switch(colony){
            case COLONY_KOLLAM: return 6;
            case COLONY_KOCHI:  return 8;
            case COLONY_MADRAS: return 10;
            case COLONY_KOZHIKODE: return 12;
            default: return 0;
        }
    }
    getColonyName(colony){
        switch(colony){
            case COLONY_KOLLAM: return _('Kollam');
            case COLONY_KOCHI:  return _('Kochi');
            case COLONY_MADRAS: return _('Madras');
            case COLONY_KOZHIKODE: return _('Kozhikode');
            default: return '';
        }
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
    setupTokens(init = true){
        console.log('setupTokens');
        document.querySelectorAll('.goa_token[id^="goa_token-"]').forEach((oToken) => {
            this.destroy(oToken);
        });

        if(init){
            Object.values(this.gamedatas.players).forEach(player => {
                for(let track=1;track<=5;track++){
                    if(!$(`goa_progress_header_${track}_${player.id}`)) document.getElementById(`goa_player_progress_headers-${player.id}`).insertAdjacentHTML('beforeend', this.tplProgressHeader({'track':track, 'pId':player.id}));
                    this.bga.gameui.addTooltipHtmlToClass(`goa_progress_header_${track}`, this.getActionBoardTooltip(track));

                    let progress_track = `goa_progress_track_${track}_${player.id}`;
                    if(!$(progress_track)) document.getElementById(`goa_player_progress_tracks-${player.id}`).insertAdjacentHTML('beforeend', this.tplProgressTrack({'track':track, 'pId':player.id}));
                    for(let k=1;k<=5;k++){
                        let progress_track_space = `goa_progress_track_space_${track}_${k}_${player.id}`;
                        if(!$(progress_track_space)) document.getElementById(progress_track).insertAdjacentHTML('beforeend', this.tplProgressTrackSpace({'track':track, 'space':k, 'pId':player.id}));
                        this.empty(progress_track_space);
                    }
                }
            });
        }

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
            if(token.subtype == TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER){
                //return `goa_player_auction_markers-${token.pId}`;
                return `goa_first_player_container_${token.pId}`;
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
        if( token.location.startsWith(TOKEN_LOCATION_TILE)) {
            let tile_id = token.location.split('-')[1];
            return `goa_tile-${tile_id}`;
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
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_progress_marker" data-track='${token.track}' data-row='${token.state}' data-color='${this.gamedatas.players[token.pId].color}' data-pid='${token.pId}'></div>`;
        if(token.subtype == TOKEN_SUBTTYPE_AUCTION_MARKER) 
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_auction_marker" data-value='${token.auctionOrder}' data-color='${this.gamedatas.players[token.pId].color}' data-pid='${token.pId}'></div>`;
        if(token.subtype == TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER) 
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_first_auction_marker" ></div>`;
        if(token.subtype == TOKEN_SUBTTYPE_SPICE) 
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_spice" data-type="${token.type}" data-pid="${token.pId}" data-state="${token.state}"></div>`;
        return '';
    }
    tplProgressTrack(datas) {
        return `<div class="goa_progress_track" id="goa_progress_track_${datas.track}_${datas.pId}" data-track='${datas.track}'></div>`;
    }
    tplProgressTrackSpace(datas) {
        return `<div class="goa_progress_track_space" id="goa_progress_track_space_${datas.track}_${datas.space}_${datas.pId}" data-track='${datas.track}' data-pos='${datas.space}'></div>`;
    }
    tplProgressHeader(datas) {
        return `
            <div class="goa_progress_header goa_progress_header_${datas.track}" id="goa_progress_header_${datas.track}_${datas.pId}" data-track='${datas.track}'>
            </div>`;
    }
    getActionBoardTooltip(track){
        let action_label = '';
        let action_desc = '';
        let action_gains = [];
        switch(track){
            case 1: 
                action_label = _('Build Ships');
                action_desc = this.bga.gameui.format_string(
                            _("Take n ${resource_name} ${icon_resource} where n is indicated on the row of your progress marker on this column.")
                            ,{
                                //n:_('n (n is the indicated on the row of your progress marker on this column)'),
                                'resource_name': _('Ships'), 
                                icon_resource : this.formatIcon('ships')
                            }
                        );
                action_gains = [1,2,3,5,6];
                break;
            case 2:  
                action_label = _('Harvest');
                action_gains = [1,2,4,6,8];
                action_desc = this.bga.gameui.format_string(
                            _("Take n ${resource_name} ${icon_resource} where n is indicated on the row of your progress marker on this column.")
                            ,{
                                'resource_name': _('Spices (any spice matching an empty space on your own plantations or outposts)'), 
                                icon_resource : this.formatIcon('spice_any')
                            }
                        );
                break;
            case 3:  
                action_label = _('Taxes');
                action_gains = [4,6,8,10,12];
                action_desc = this.bga.gameui.format_string(
                            _("Take n ${resource_name} ${icon_resource} where n is indicated on the row of your progress marker on this column.")
                            ,{
                                'resource_name': _('Coins'), 
                                icon_resource : this.formatIcon('money')
                            }
                        );
                break;
            case 4:  
                action_label = _('Exploration');
                action_desc = this.bga.gameui.format_string(
                            _("Draw Exploration cards from the supply. Each row of the Exploration column shows two numbers. The number on the left is the maximum number of cards a player can draw whereas the one on the right is the player's hand limit, i.e., the maximum number of Exploration cards a player can have in their hand at any time when doing the Exploration action.")
                            ,{ }
                        );
                action_gains = ['1 -> 1','1 -> 2','2 -> 3','2 -> 4','3 -> 5'];
                break;
            case 5: 
                action_label = _('Found Outpost');
                action_gains = [0,2,4,5,6];

                let action_gains_desc = '<ul>';
                action_gains.forEach(element => {
                    action_gains_desc += '<li>'+element+'</li>';
                });
                action_gains_desc += '</ul>';
                
                action_desc = '<ul>'
                            + '<li>' + this.bga.gameui.format_string( _("Choose an unfounded outpost."),{  } ) + '</li>'
                            + '<li>' + this.bga.gameui.format_string( _("The row of your progress marker will give a base number of contracts."),{  } ) + '</li>'
                            + '<li>' + action_gains_desc + '</li>' 
                            + '<li>' + this.bga.gameui.format_string( _("Flip ${n} exploration cards and add their contracts to those on your player board."),{ n: 2 } ) + '</li>'
                            + '<li>' + this.bga.gameui.format_string( _("You may add ay number of contracts from your hand."),{  } ) + '</li>'
                            + '<li>' + this.bga.gameui.format_string( _("If you successfully meet the requirement of the chosen outpost, take it and place it on your board, filling the resources spots on it; if not you draw ${n} ${icon_card}"),{ n:1, icon_card: this.formatIcon('contracts') } ) + '</li>'
                        + '</ul>'
                        ;

                action_gains = [];//reset to empty to avoid having it again in the common desc
                break;

        }
        action_label = this.bga.gameui.format_string( 
            _('Player action ${action_label}'),
             {'action_label': '<b><u>'+ action_label +'</u></b>'}
        );
        action_desc += '<ul>';
        action_gains.forEach(element => {
            action_desc += '<li>'+element+'</li>';
        });
        action_desc += '</ul>';

        return `<div class="goa_custom_tooltip">
            <div class="goa_action_label">${action_label}</div>
            <div class="goa_action_desc">${action_desc}</div>
            </div>
        `;
    }

    ////////////////////////////////////////////////////////
    //    ____              _
    //   / ___|__ _ _ __ __| |___
    //  | |   / _` | '__/ _` / __|
    //  | |__| (_| | | | (_| \__ \
    //   \____\__,_|_|  \__,_|___/
    //////////////////////////////////////////////////////////
    /**
     * This function is refreshUI compatible
     * @param {*} init 
     */
    async setupCards(init = true) {
        console.log("setupCards");
        
        if(init){
            this.explorationCardsManager = new BgaCards.Manager({
                animationManager: this.animationManager,
                type: 'goa_exploration_card',
                getId: (card) => card.id,
                setupFrontDiv: (card, div) => {
                    div.dataset.type = card.type; 
                    this.bga.gameui.addTooltipHtml(div.id, this.getCardTooltip(card));
                },
                cardWidth: 131,
                cardHeight: 200,
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
                    //sort: BgaCards.sort('type'),
                    sort: BgaCards.sort('action','contracts'),
                });
            }
            this.explorationDeck = new BgaCards.Deck(this.explorationCardsManager, document.getElementById('goa_exploration_deck'), {
                cardNumber: this.gamedatas.cards.deck_size.exploration,
                counter: {
                    position: 'center',
                    extraClasses: 'text-shadow',
                },
            });
        }
        
        await this.explorationDeck.setCardNumber(this.gamedatas.cards.deck_size.exploration);
        
        // This function is refreshUI compatible
        //destroy previous cards
        if(this.explorationHand) this.explorationHand.removeAll();
        this.explorationDiscard.removeAll();
        document.querySelectorAll('.goa_card[id^="goa_card-"]').forEach((pCard) => {
            this.destroy(pCard);
        });
        
        let cardIds = this.gamedatas.cards.all.map((card) => {
            this.addCard(card);
            return card.id;
        });
        let hand = Object.values( [] );
        if(init){
            hand = Object.values(this.gamedatas.cards.private.hand);
        }
        else if(this.gamedatas._private && this.gamedatas._private.private){
            hand = Object.values(this.gamedatas._private.private.hand);
        }
        hand.forEach((card) => {
            this.addCard(card);
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
                let currentPlayer = this.bga.players.getCurrentPlayer();
                if (currentPlayer) {
                    this._counters[currentPlayer.id]['explo_symbol_tiny_'+card.symbol].incValue(1);
                }
            }
            else {//manual add
                document.getElementById(container).insertAdjacentHTML('beforeend', this.tplCard(card));
            }

        }
        return $(`goa_card-${card.id}`);
    }
    
    tplCard(card, prefix ='') {
        if(card.subtype == CARD_SUBTTYPE_EXPLORATION) 
            return `<div id="goa_card${prefix}-${card.id}" class="goa_card goa_exploration_card goa_card${prefix}" data-type='${card.type}' data-pid='${card.pId}'></div>`;
        return '';
    }
    // draw + discard from deck
    discardExCard(card_datas) {
        let cardNumber = this.explorationDeck.getCardNumber();

        if (cardNumber >= 1) {
            this.explorationDiscard.addCard(card_datas, { fromStock: this.explorationDeck });
        }
    }
    getCardTooltip(cardDatas) {
        let textLine1 = ''
        let title = '';
        let textSymbols = '';
        if(cardDatas.subtype == CARD_SUBTTYPE_EXPLORATION){
            let iconsContracts = this.formatIcon('contracts');
            for(let k=2;k<=cardDatas.contracts;k++) iconsContracts += this.formatIcon('contracts');
            textLine1 = this.bga.gameui.format_string( 
                _('When revealing and discarding this card to found an oupost, this gives ${n} ${resource_name} : ${icon_resource}'),
                {'n': cardDatas.contracts , resource_name: _('Contracts'), icon_resource: iconsContracts}
            );
            title = _('Exploration card');
            textSymbols = this.bga.gameui.format_string( 
                _('Gives the symbol ${s_icon} (${s_name})'),
                { s_name: this.getCardSymbolName( cardDatas.symbol ), s_icon: this.formatIcon('explo_symbol_'+cardDatas.symbol)}
            );
            textSymbols += '<br>'+_('Victory points at the end of the game (if not played) : ');
            textSymbols += '<ul>';
            textSymbols += '<li>'+this.bga.gameui.format_string( _(' ${n} identical symbols => ${x} points '), {n:1,x:1} ) +  '</li>'; 
            textSymbols += '<li>'+this.bga.gameui.format_string( _(' ${n} identical symbols => ${x} points '), {n:2,x:3} ) +  '</li>'; 
            textSymbols += '<li>'+this.bga.gameui.format_string( _(' ${n} identical symbols => ${x} points '), {n:3,x:6} ) +  '</li>'; 
            textSymbols += '<li>'+this.bga.gameui.format_string( _(' ${n} identical symbols => ${x} points '), {n:4,x:10} ) + '</li>'; 
            textSymbols += '<li>'+this.bga.gameui.format_string( _(' ${n} identical symbols => ${x} points '), {n:5,x:15} ) + '</li>'; 
            textSymbols += '</ul>'
        }
        let textAction = this.formatCardActionText( cardDatas );
        let div = this.tplCard(cardDatas,'_tmp'); 
        return [`<div class='goa_card_tooltip'>
                <div class="goa_tooltip_block1">
                    <h1 class="goa_card_title">${title}</h1>
                </div>
                <div class="goa_tooltip_block2">
                    <div class="goa_card_img_wrap">${div}</div>
                    <div class="goa_card_text">
                        ${textAction == '' ? '' : '<hr><span class="goa_card_text_action">'+textAction+'</span>'}
                        ${textLine1 == '' ? '' : '<hr><span class="goa_card_text_line1">'+textLine1+'</span>'}
                        ${textSymbols == '' ? '' : '<hr><span class="goa_card_symbols">'+textSymbols+'</span>'}
                        
                    </div>
                </div>
            </div>`];

    }

    formatCardActionText(card){
        switch(card.action){
            case this.const.CARD_ACTION_PROGRESS_NO_SPICE:  return _('Type a: When performing the Progress action, you only need to pay the Ships, you can ignore the Resource costs.');
            case this.const.CARD_ACTION_PROGRESS_NO_SHIP:   return _('Type b: When performing the Progress action, you only need to pay the Resources, you can ignore the Ship costs.');
            case this.const.CARD_ACTION_HARVEST_AND_CARDS:  return _('Type c: When performing the Harvest action, you may take any combination of Resources, Ships, and/or Contracts instead of only Resources.');
            case this.const.CARD_ACTION_OUTPOST_EXTRA_CONTRACTS : return _('Type d: When performing the Found Outpost action, you may draw one extra exploration card and add its Contracts to your total.');
            case this.const.CARD_ACTION_RESSOURCES_TO_COINS  : return _('Type e: You may spend 1/2/3/4 Resources to gain 3/6/9/12 Pardaos.');
            case this.const.CARD_ACTION_2_SPICES             : return this.bga.gameui.format_string(_('Type ${x} : ${gain}') ,
                    { 'x':'f', 'gain': this.formatTileSpicesText({'spice_slots':[this.const.SPICES_ALL,this.const.SPICES_ALL]}) }
                );
            case this.const.CARD_ACTION_2_CONTRACTS          : return this.bga.gameui.format_string(_('Type ${x} : ${gain}') ,
                    { 'x':'g', 'gain': this.bga.gameui.format_string(
                            _("Take ${n} ${resource_name} : ${icon_resource}.")
                            ,{n:2, 'resource_name': _('Contracts'), icon_resource : this.formatIcon('contracts')}
                        )
                    }
                );
            case this.const.CARD_ACTION_2_SHIPS              : return this.bga.gameui.format_string(_('Type ${x} : ${gain}') ,
                    { 'x':'h', 'gain': this.bga.gameui.format_string(
                            _("Take ${n} ${resource_name} : ${icon_resource}.")
                            ,{n:2, 'resource_name': _('Ships'), icon_resource : this.formatIcon('ships')}
                        )
                    }
                );
            case this.const.CARD_ACTION_5_COINS              : return this.bga.gameui.format_string(_('Type ${x} : ${gain}') ,
             { 'x':'i', 'gain': this.bga.gameui.format_string(
                    _("Take ${n} ${resource_name} : ${icon_resource}.")
                    ,{n:5, 'resource_name': _('Coins'), icon_resource : this.formatIcon('money')}
                )
             }
            );
            case this.const.CARD_ACTION_PROGRESS_WITH_COINS  : return _('Type j: Spend 6/12/18/24 Pardaos to advance a Progress marker of your choice to the 2nd/3rd/4th/5th row.');
            
        }
        return '';
    }
    getCardSymbolName(card_symbol){
        switch(card_symbol){
            case this.const.CARD_SYMBOL_ELEPHANT: return _('Elephant');
            case this.const.CARD_SYMBOL_TIGER: return _('Tiger');
            case this.const.CARD_SYMBOL_URN: return _('Urn');
            case this.const.CARD_SYMBOL_PALMTREE: return _('Palm tree');
            case this.const.CARD_SYMBOL_SHELL: return _('Shell');
            case this.const.CARD_SYMBOL_STATUE: return _('Statue');
        }
        return '';
    }
    
    formatCardsHelp(){
        let list1 = "";
        let j = 0;
        Object.entries(this.gamedatas.material.cards[CARD_SUBTTYPE_EXPLORATION]).forEach(([type,card_type]) => {
            let resourceDatas = card_type;
            resourceDatas.id = 0;
            resourceDatas.state = 0;
            resourceDatas.type = parseInt(type);
            resourceDatas.subtype = CARD_SUBTTYPE_EXPLORATION;
            if(j>0){
                list1 = list1.concat('<hr>');
            }
            list1 = list1.concat( this.getCardTooltip(resourceDatas));
            j++; 
        });
        return `
        <div class="expandable">
            <div id="goa_ExplorationCardsHelp_toggle" class="expandabletoggle expandablearrow" onclick="gameui.GameModule.onToggleExpandable(this)">
                <div class="icon20 icon20_expand"></div>
                <h1 class='goa_ExplorationCardsHelp_title'>${ this.bga.gameui.format_string( _("Exploration Cards (${n})"), {n:j} ) }</h1>
                <div class="goa_expand_ellipsis">...</div>
            </div>
            <div class='goa_ExplorationCardsHelp goa_expandablecontent goa_expand_on'>
                ${list1}
            </div>
        </div>
        `;
    }
    
    /**
     * Create a coin div to animate as a temporary piece (for example for scoring animation).
     */
    createCoin() {
        const div = document.createElement('div');
        div.innerHTML = `<p></p>`;
        div.classList.add('goa_icon_money');
        div.style.pointerEvents = 'all';
        return div;
    }
}
