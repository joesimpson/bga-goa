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

import { CleanHand } from "./states/CleanHand.js";
import { ConfirmTurn } from "./states/ConfirmTurn.js";
import { ClientCardAction } from "./states/ClientCardAction.js";
import { ClientHarvestResources } from "./states/ClientHarvestResources.js";
import { ClientSelectMarketPlace } from "./states/ClientSelectMarketPlace.js";
import { ClientSelectProgress } from "./states/ClientSelectProgress.js";
import { ClientSelectSpicesForProgress } from "./states/ClientSelectSpicesForProgress.js";
import { ClientSpendSpices } from "./states/ClientSpendSpices.js";
import { ClientFoundOutpost } from "./states/ClientFoundOutpost.js";
import { NextPlayer } from "./states/NextPlayer.js";
import { PlayerChoice, ClientSelectTileAction } from "./states/PlayerChoice.js";
import { PlayerTurn } from "./states/PlayerTurn.js";

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
    
export class Game {
    
    customDebug = isDebug ? console.log.bind(window.console) : function () {};

    constructor(bga) {
        this.customDebug('goa constructor');
        this.bga = bga;
        
        // Declare the State classes
        this.playerTurn = new PlayerTurn(this, bga);
        this.bga.states.register('PlayerTurn', this.playerTurn);
        this.confirmTurn = new ConfirmTurn(this, bga);
        this.bga.states.register('ConfirmTurn', this.confirmTurn);
        this.nextPlayer = new NextPlayer(this, bga);
        this.bga.states.register('NextPlayer', this.nextPlayer);
        this.playerChoice = new PlayerChoice(this, bga);
        this.bga.states.register('PlayerChoice', this.playerChoice);
        //CLIENT STATES :
        this.clientCardAction = new ClientCardAction(this, bga);
        this.bga.states.register('ClientCardAction', this.clientCardAction);
        this.clientSelectTileAction = new ClientSelectTileAction(this, bga);
        this.bga.states.register('ClientSelectTileAction', this.clientSelectTileAction);
        this.clientSelectMarketPlace = new ClientSelectMarketPlace(this, bga);
        this.bga.states.register('ClientSelectMarketPlace', this.clientSelectMarketPlace);
        this.clientSelectProgress = new ClientSelectProgress(this, bga);
        this.bga.states.register('ClientSelectProgress', this.clientSelectProgress);
        this.clientSelectSpicesForProgress = new ClientSelectSpicesForProgress(this, bga);
        this.bga.states.register('ClientSelectSpicesForProgress', this.clientSelectSpicesForProgress);
        this.clientSpendSpices = new ClientSpendSpices(this, bga);
        this.bga.states.register('ClientSpendSpices', this.clientSpendSpices);
        this.clientFoundOutpost = new ClientFoundOutpost(this, bga);
        this.bga.states.register('ClientFoundOutpost', this.clientFoundOutpost);
        this.clientHarvestResources = new ClientHarvestResources(this, bga);
        this.bga.states.register('ClientHarvestResources', this.clientHarvestResources);
        this.cleanHand = new CleanHand(this, bga);
        this.bga.states.register('CleanHand', this.cleanHand);

        // Uncomment the next line to show debug informations about state changes in the console. Remove before going to production!
        this.bga.states.logger = this.customDebug;

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
        this.customDebug( "Starting game setup" , gamedatas);
        this.gamedatas = gamedatas;

        this.animationManager = new BgaAnimations.Manager({
            animationsActive: () => this.bga.gameui.bgaAnimationsActive(),
        });

        this.const = gamedatas.constants;

        // add a div on the game area
        this.bga.gameArea.getElement().insertAdjacentHTML('beforeend', `
            <div id="goa_overall_background"></div>
            <div id="goa_top_zone" class="whiteblock">
                <div></div>
                <div id="goa_top_zone_content" ></div>
            </div>
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
                <div id="goa_box_tiles_container" class="whiteblock">
                    <span>${ _("Tiles in the game box for the Favor tile action")}
                    </span>
                    <div id="goa_box_tiles">
                    </div>
                </div>
            </div>
        `);

         for(let round=1;round<=this.const.NB_ROUNDS_DAY ;round++){
            let text = this.bga.gameui.format_string( 
                _('Place for round marker during round #${n} (${dayOrNight})'),
                { 'n': '<span>'+round +'</span>', 'dayOrNight': '<span>'+_('Day')+'</span>'}
            );
            let tooltip =  `<div class="goa_custom_tooltip goa_round_tooltip">
                    <div>${text}</div>
                </div>
            `;
            this.bga.gameui.addTooltipHtml(`goa_board_round_cell_${round}`, tooltip);
        }
        for(let round=this.const.NB_ROUNDS_DAY + 1;round<=this.const.NB_ROUNDS_DAY +this.const.NB_ROUNDS_NIGHT ;round++){
            let text = this.bga.gameui.format_string( 
                _('Place for round marker during round #${n} (${dayOrNight})'),
                { 'n': '<span>'+round +'</span>', 'dayOrNight': '<span>'+_('Night')+'</span>'}
            );
            let tooltip =  `<div class="goa_custom_tooltip goa_round_tooltip">
                    <div>${text}</div>
                </div>
            `;
            this.bga.gameui.addTooltipHtml(`goa_board_round_cell_${round}`, tooltip);
        }
        
        let currentPlayerNo = 1;
        let nPlayers = 0;
        let currentPlayer = this.bga.players.getCurrentPlayer();
        // Setting up player boards
        Object.values(gamedatas.players).forEach(player => {
            this.bga.playerPanels.getElement(player.id).insertAdjacentHTML('beforeend', `
                <div class='goa_panel'>
                    <div class='goa_player_counters_line goa_player_counters_line_cards'>
                        ${this.tplResourceCounter(player,'exploration_hand_size',_('Exploration card'))}
                        ${this.tplResourceCounter(player,'money',_('Pardaos'))}
                        ${this.tplResourceCounter(player,'ships',_('Ships'))}
                        ${this.tplResourceCounter(player,'contracts',_('Contracts'))}
                        ${this.tplResourceCounter(player,'compasses',_('Compasses'))}
                    </div>
                    <div class='goa_player_counters_line goa_player_counters_line_spices'>
                        ${this.tplResourceCounter(player,'spices_'+this.const.SPICE_WHITE, this.getGoodsName(this.const.SPICE_WHITE))}
                        ${this.tplResourceCounter(player,'spices_'+this.const.SPICE_YELLOW,this.getGoodsName(this.const.SPICE_YELLOW))}
                        ${this.tplResourceCounter(player,'spices_'+this.const.SPICE_BLACK ,this.getGoodsName(this.const.SPICE_BLACK ))}
                        ${this.tplResourceCounter(player,'spices_'+this.const.SPICE_PINK  ,this.getGoodsName(this.const.SPICE_PINK  ))}
                        ${this.tplResourceCounter(player,'spices_'+this.const.SPICE_GREEN ,this.getGoodsName(this.const.SPICE_GREEN ))}
                    </div>
                    <div class='goa_player_counters_line goa_player_counters_line_symbols'  ${ (currentPlayer && player.id == currentPlayer.id) ? '': 'style="display:none;"'}>
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_1',this.getCardSymbolName(1))}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_2',this.getCardSymbolName(2))}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_3',this.getCardSymbolName(3))}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_4',this.getCardSymbolName(4))}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_5',this.getCardSymbolName(5))}
                        ${this.tplResourceCounter(player,'explo_symbol_tiny_6',this.getCardSymbolName(6))}
                    </div>
                    <div class='goa_player_counters_line goa_player_counters_lastline'>
                        ${this.tplResourceCounter(player,'nbActions',_('Round Actions'))}
                        <div id="goa_first_player_container_${player.id}" class="goa_first_player_container" alt="${_('Nazar Stone (Starting player)')}" title="${_('Nazar Stone (Starting player)')}"></div>
                    </div>
                </div>
            `);
            this.bga.gameui.addTooltipHtmlToClass(`goa_first_player_container`, _('Nazar Stone (Starting player)'));

            this._counters[player.id] = {};
            this.createCounter(player.id,'exploration_hand_size',player.exploration_hand_size);
            this.createCounter(player.id,'ships',player.ships);
            this.createCounter(player.id,'contracts',player.contracts);
            this.createCounter(player.id,'compasses',player.compasses);
            this.createCounter(player.id,'nbActions',player.nbActions);
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
            this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_nbActions`, _('Round Actions'));

            
            this.const.SPICES_ALL.forEach((spice) => {
                this.bga.gameui.addTooltipHtml(`goa_reserve_${player.id}_spices_${spice}`, this.getGoodsName(spice));
            });

            let playerColoredName = this.bga.gameui.format_string(('${player_name}'), { player_name:this.coloredPlayerName(player.name)}) ;
            let playerAvatarURL = this.bga.players.getPlayerAvatarUrl(player.id, 50);
            // example of adding a div for each player
            document.getElementById('goa_main_zone').insertAdjacentHTML('beforeend', `
                <div id="goa_player_table-${player.id}" class="goa_player_table whiteblock" data-pid=${player.id} data-color='${player.color}'>
                    <div class='goa_player_table_title_name'>
                        <div><span class='goa_player_table_title_content'>
                        <img id="goa_player_avatar_${player.id}" class="emblem goa_player_avatar" src="${playerAvatarURL}" alt="avatar">
                        ${playerColoredName}
                        </span></div>
                    </div>
                    <div id="goa_player_tiles-${player.id}" class="goa_player_tiles"></div>
                    <div class='goa_resizable_playerboard'>
                        <div class='goa_playerboard_container'>
                            <div class="goa_playerboard">
                                <div class="goa_playerboard_background"></div>
                                <div id="goa_playerboard_top_zone-${player.id}" class="goa_playerboard_top_zone">
                                    <div></div>
                                    <div class="goa_harvest_zone"></div>
                                </div>
                                <div id="goa_player_plantations-${player.id}" class="goa_player_plantations"></div>
                                <div id="goa_player_outposts-${player.id}" class="goa_player_outposts"></div>
                                <div id="goa_player_auction_markers-${player.id}" class="goa_player_auction_markers">
                                </div>
                                <div id="goa_player_progress_headers-${player.id}" class="goa_player_progress_headers"></div>
                                <div id="goa_player_progress_row_headers-${player.id}" class="goa_player_progress_row_headers"></div>
                                <div id="goa_player_progress_row_ends-${player.id}" class="goa_player_progress_row_ends"></div>
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
       
        Promise.all (
            [   this.setupTiles(), 
                this.setupTokens(), 
                this.setupCards(),
            ]      
        ).then( 
            this.customDebug("SETUP phases")
        );

        this.setupRoundMarker();

        document.getElementById(`generalactions`).insertAdjacentHTML('afterend', "<div id='customPrimary' style='display:inline-block'></div>");
        document.getElementById(`customPrimary`).insertAdjacentHTML('afterend', "<div id='customSecondary' style='display:inline-block'></div>");
        document.getElementById(`customSecondary`).insertAdjacentHTML('afterend', "<div id='restartActions' style='display:inline-block'></div>");

        this.zoomManager = new ZoomManager({
            element: document.getElementById('goa_game_container'),
            localStorageZoomKey: 'goa-zoom',
            zoomControls: {
                color: 'black',
            },
            zoomLevels :[0.5, 0.625, 0.75, 0.875, 1, 1.25, 1.5],
        });
        //Add a reset zoom button 
        this.zoomReset = document.createElement('button');
        this.zoomReset.id = "goa_zoom_reset";
        this.zoomReset.type = 'button';
        var _this = this;
        this.zoomReset.addEventListener('click', function () { 
            return _this.zoomManager.setZoom(1); 
        });
        this.zoomReset.innerHTML = `<i class="fa6 fa6-undo goa_icon_reset"></i>` ;
        this.zoomReset.setAttribute('title',_('Reset zoom'));
        document.getElementById('bga-zoom-controls').appendChild(this.zoomReset);

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

        this.customDebug( "Ending game setup" );
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
            let color_keys = Object.keys(args).filter((key) => key.startsWith('player_color'));
            for(let k=0; k<playernames_keys.length; k++){
                let key = playernames_keys[k];
                let colorKey = color_keys[k];
                args[key] = this.coloredPlayerName(args[key],args[colorKey]);
            }

            if('card' in args && 'card_icon' in args) {
                args.card_icon = this.tplCard(args.card, '_log');
                //tooltip won't work because element is not added yet
                //this.bga.gameui.addTooltipHtmlToClass(`goa_card_log_${args.card.type}`, this.getCardTooltip(args.card));
            }
            if('token' in args && 'token_icon' in args) {
                args.token_icon = this.tplToken(args.token, '_log');
            }
            if('tokens' in args && 'token_icons' in args){
                let icons = '';
                Object.values(args.tokens).forEach((token) => {
                    icons += this.tplToken(token, '_log');
                });
                args.token_icons = icons;
            }
            if('tile' in args && 'tile_icon' in args) {
                args.tile_icon = this.tplTile(args.tile, '_log');
            }
            if('icon_money' in args) {
                args.icon_money = this.formatIcon('money', _('Pardaos'));
            }
            if('icon_ships' in args) {
                args.icon_ships = this.formatIcon('ships', _('Ships'));
            }
            if('icon_contracts' in args) {
                args.icon_contracts = this.formatIcon('contracts', _('Contracts'));
            }
            if('icon_compass' in args) {
                args.icon_compass = this.formatIcon('compasses', _('Compass card'));
            }
            if('icon_explo' in args) {
                args.icon_explo = this.formatIcon('exploration_hand_size', _('Exploration card'));
            }
            if('icon_symbol' in args && 'symbolType' in args) {
                args.icon_symbol = this.formatSymbolIcon(args.symbolType, true);
            }
            
            if('action_name' in args) {
                args.action_name = `<span class='goa_log_action_name' data-type='${args.action_type}'>${_(args.action_name)}</span>`;
            }
            if('step_icon' in args && args.n){
                args.step_icon = this.formatIcon('round_step_'+args.n);
            }
            if('icon_action' in args){
                args.icon_action = this.formatIcon('nbActions');
            }
            
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return { log, args };
    }
    
    removeAllActionButtons() {
        this.empty('customPrimary');
        this.empty('customSecondary');
        this.empty('restartActions');
    }

    addButtonCloseClientState(args){
        let callbackOnClose = () =>{
            this.restorePreviousState(args);
        };
        this.bga.statusBar.addActionButton( _('Return'), () => {
            callbackOnClose();
        }, { 
            'color':'secondary',
            'title':_('Return'), 
            'destination': document.getElementById('customSecondary') 
        }); 
        return callbackOnClose;
    }
    
    destroyTooltip(elem) {
        /* 
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
    
    clearSelection(withClientState = false) {
        this.customDebug('clearSelection()' , withClientState);
        this._connections.forEach((i) => {
            let node = i[0];
            let action = i[1];
            this.customDebug('clearSelection()... disconnect ' ,node, action,);
            this.bga.gameui.disconnect(node,action);
        });
        this._connections = [];
        this._selectableNodes.forEach((node) => {
            if ($(node)){
                $(node).removeAttribute("title");//title hint depends on current action
                $(node).classList.remove('selectable');
                $(node).classList.remove('selected');
            }
        });
        this._selectableNodes = [];
        let gameContainer = document.getElementById('goa_game_container');
        [...gameContainer.getElementsByClassName('selectable')].forEach((node) => {
            if ($(node)){
                $(node).classList.remove('selectable');
                $(node).removeAttribute("title");//title hint depends on current action
            }
        });
        [...gameContainer.getElementsByClassName('selected')].forEach((node) => {
            if ($(node)){
                $(node).classList.remove('selected');
            }
        });
        if(withClientState) {
            [...gameContainer.getElementsByClassName('selected_for_client_state')].forEach((node) => {
                if ($(node)){
                    $(node).classList.remove('selected_for_client_state');
                }
            });
        }
        this.outpostsTilesStocks.forEach((stock) => { 
            stock.setSelectionMode('none');
            stock.removeLastPlayedCardsClass();
        });

        document.getElementById('goa_top_zone').classList.remove('goa_display');
        //Empty it only when displayed again in order to have smooth transition
        //this.empty(document.getElementById('goa_top_zone_content'));
        
        [...gameContainer.getElementsByClassName('goa_playerboard_top_zone')].forEach((node) => {
            $(node).classList.remove('goa_display');
            [...node.getElementsByClassName('goa_harvest_zone')].forEach((h) => {
                this.empty(h);
            });
        });

        [...gameContainer.getElementsByClassName('goa_token_tmp_for_harvest')].forEach((node) => {
            this.destroy(node);
        });
        
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
    
    /** Useful When we want to select mutliple elements at the same time.
     * 
     * Optional parameter "listPossibleSets" will make selection impossible if not in list of sets/arrays.
     * Optional parameter "autoConfirm" will make selection callback as soon as we have a possible set
    */
    onSelectN(elements, maxElements, callbackOnSelectionEnd, listPossibleSets =null, autoConfirm = false , confirmText = _('Confirm'), selectionHint = null, min = null) {
      let selectedElements = [];
      let minElements = (min) ? min : maxElements;
      let n = maxElements;
      
        let countElements = 0;
        let allElementsIds = [];
        Object.keys(elements).forEach((id) => {
            countElements++;
            allElementsIds.push(id);
        });

        let autoSelectOnOpen = false;
        if(!listPossibleSets && autoConfirm && (minElements == maxElements) && (countElements == minElements) ) {
            //AUTO SELECT ALL when all ARE needed
            autoSelectOnOpen = true;
            listPossibleSets = [allElementsIds];
        }
        
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

        if(listPossibleSets!=null){
          let includesAll = (arr, target) => target.every(v => arr.includes(v));
          //Check each element not currently selected
          Object.keys(elements).forEach((id2) => {
            let elt2 = elements[id2];
            let selected2 = selectedElements.includes(parseInt(id2));
            if(selected2) return;
            let tmp = Array.from(selectedElements);
            tmp.push(parseInt(id2));
            let possibleSet = false;
            Object.values(listPossibleSets).forEach((pSet) => {
              if( includesAll(pSet,tmp)){
                possibleSet = true;
              }
            });
            
            elt2.classList.toggle('selectable', selected2 || possibleSet && selectedElements.length < n);
            
          });

          let nbSelectables = 0;
          Object.keys(elements).forEach((id) => {
            let elt = elements[id];
            if(elt.classList.contains("selectable")) nbSelectables++;
          });
          if(nbSelectables == n){
            //If only 1 match in fixed possible sets, let's auto select it
            this.customDebug("onSelectN... auto select ",nbSelectables);
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
            this.customDebug("onSelectN...CLICK",id);
          if(!elt.classList.contains("selectable")) return;

          let index = selectedElements.findIndex((t) => t == id);

          if (index === -1) {
            if (selectedElements.length >= n) return;
            selectedElements.push(parseInt(id));
          } else {
            selectedElements.splice(index, 1);
          }
          updateStatus();
          checkPossibleSets();
        };
        this.onClick(elt, clickAction);
        if(autoSelectOnOpen) clickAction();
      });
      
    }

    addClickHandlerOnProgressMarker(track, title, playerId, callBack){
        this.customDebug("addClickHandlerOnProgressMarker", track, title, playerId);
        let progressTrack = document.getElementById(`goa_progress_track_${track}_${playerId}`);
        let progressMarker = progressTrack.getElementsByClassName('goa_progress_marker')[0];
        this.onClick(progressMarker.parentNode,callBack);
        progressMarker.parentNode.setAttribute("title",title);
    }
    
    async setupRoundMarker(){
        let round = this.gamedatas.round;
        this.customDebug("setupRoundMarker", round);
        if(round>0){
            const meepleDiv = document.getElementById(`goa_board_round_marker`);
            meepleDiv.setAttribute('title', _('Round marker'));
            const destinationDiv = document.getElementById(`goa_board_round_cell_${round}`);
            await this.animationManager.slideAndAttach(meepleDiv, destinationDiv);
        }
        else {
            //TODO JSA reset round between sessions ?
        }
    }
    
    formatIcon(name, alt = null, n = null) {
        let type = name;
        let altText = alt == null ? '' : `alt="${alt}" title="${alt}"`;
        let text = n == null ? '' : `<span class='goa_icon_qty' data-value="${n}">${n}</span>`;
        return `<div class="goa_icon_container goa_icon_container_${type}">
            <div class="goa_icon goa_icon_${type}" ${altText}>${text}</div>
            </div>`;
    }

    formatSymbolIcon(symbolType, tiny = false){
        let suffix = '';
        if(tiny) suffix = 'tiny_';
        return this.formatIcon( 'explo_symbol_'+suffix+symbolType, this.getCardSymbolName( symbolType ));
    }
    formatSpiceIcon(spiceType,){
        return this.formatIcon('spices_'+spiceType,this.getGoodsName(spiceType));
    }
    /**
     * Use this tpl for any counters that represent qty of tokens
     */
    tplResourceCounter(player, res, altText = null, ) {
        return `
            <div class='goa_player_resource goa_resource_holder_${res}'>
                ${this.formatIcon(res, altText, null)}
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

    getCurrentClientStateDatas(){
        let isOnClientState = this.bga.states.isOnClientState();
        let currentClientState = isOnClientState ? this.bga.states.getCurrentMainStateClass():null;
        if(currentClientState){
            let stateName = currentClientState.constructor.name;
            return { 
                'name' : stateName,
                'args' : currentClientState.args,
            }
        }
        return null;
    }
    restorePreviousState(args, exceptStateName =null){
        this.customDebug("restorePreviousState",args, exceptStateName);
        let previousClientState = args.previousClientState;
        if(previousClientState && previousClientState.name && exceptStateName!=previousClientState.name){
            //descriptionmyturn useless if title is set on enteringstate
            this.bga.states.setClientState(previousClientState.name, { 'descriptionmyturn':'', 'args': previousClientState.args});
        }
        else{
            this.bga.states.restoreServerGameState();
        }
    }

    updateHandSelectableList(args){
        this.customDebug("updateHandSelectableList",args);
        let possibleActions = args.possibleActions;
        //Manage various actions on hand cards with a unique handler
        let possibleDiscards = args.possibleDiscards; 
        let possiblePlayCards = args.possiblePlayCards; 
        let possibleActionDiscard = possibleActions && possibleActions.includes('actDiscard');
        let possibleActionPlay = possibleActions && possibleActions.includes('actPlayCard');
        let possibleActionRightNow = (
                possibleActionDiscard && possibleDiscards 
            || possibleActionPlay && possiblePlayCards
        );
        let handCards = this.explorationHand.getCards();
        let selectableCards = handCards.filter((card, index) => 
            possibleDiscards && possibleDiscards.includes(card.id) && possibleActionDiscard
         || possiblePlayCards && Object.keys(possiblePlayCards).includes(""+card.id) && possibleActionPlay
        );
        if(selectableCards.length>0 || handCards.length==0 && possibleActionRightNow){
            this.explorationHand.setSelectionMode('single');
            this.explorationHand.setSelectableCards(selectableCards);
        } else {
            //!\\ manage reload page = > cards are added in hand after this
            this.explorationHand.setSelectionMode('none');
        }

    }

    updateHandSelectable(args, updateTiles = true){
        this.customDebug("updateHandSelectable",args, updateTiles);
        let possibleActions = args.possibleActions;
        if(!possibleActions) possibleActions = [];
        //Manage various actions on hand cards with a unique handler
        let possibleDiscards = args.possibleDiscards; 
        let possiblePlayCards = args.possiblePlayCards; 

        this.updateHandSelectableList(args);

        this.explorationHand.onSelectionChange = (x, card) => {
            this.customDebug("HAND onSelectionChange",card,);
            let isSelected = this.explorationHand.isSelected(card);
            this.explorationHand.removeLastPlayedCardsClass();
            if(!card) return;
            let card_id = card.id;
            let cardDatas = card;
            let isDiscardableCard = (possibleActions.includes('actDiscard') && possibleDiscards.includes(card_id)) ? true : false;
            let isPlayableCard = (possibleActions.includes('actPlayCard') && Object.keys(possiblePlayCards).includes(""+card_id)) ? true : false;
            let callbackCardSelection = (evt) => {
                let clientState = this.getCurrentClientStateDatas();
                //ignore ClientCardAction because it doesnot represent an action state, only card selection with multiple changes
                if(clientState && clientState.name == 'ClientCardAction') 
                    clientState = clientState.args.previousClientState;
                this.bga.states.setClientState("ClientCardAction", {
                    descriptionmyturn: '',
                    args: { 
                        'card': cardDatas,
                        'isPlayableCard': isPlayableCard,
                        'isDiscardableCard': isDiscardableCard,
                        'possibleDiscards': possibleDiscards,
                        'possiblePlayCards': possiblePlayCards,
                        'possibleActions': possibleActions,
                        //keep previous state datas in order to go back when cancel 
                        'previousClientState' : clientState,
                    },
                });
            };
            if(isSelected){
                if(isDiscardableCard || isPlayableCard) {
                    callbackCardSelection();
                }
                /*else {
                    this.updateHandSelectableList(args);
                }
                */
            }
            else {
                //this.updateHandSelectableList(args);
                let clientState = this.getCurrentClientStateDatas();
                if(clientState && clientState.name=='ClientCardAction') {
                    this.clientCardAction.restorePreviousState();
                }
                else {
                    this.restorePreviousState(args);
                }
            }
        };  

        if(updateTiles) this.updateTilesHandSelectable(args);

    }
    updateTilesHandSelectable(args){
        this.customDebug("updateTilesHandSelectable",args);

        if(!args) return;

        let possibleActions = args.possibleActions;
        let activePlayer = this.bga.players.getActivePlayerId();
        //--------------------------------------------
        //--------------------- TILES
        //--------------------------------------------
        let possiblePlayTiles = args.possiblePlayTiles; 
        let playerTilesContainer = document.getElementById(`goa_player_tiles-${activePlayer}`);
        let playerTiles = playerTilesContainer.querySelectorAll('.goa_tile');
        playerTiles.forEach((tileDiv) => {
            let tile_id = tileDiv.dataset.id;
            let isPlayableTile = (possibleActions && possibleActions.includes('actPlayTile') && Object.keys(possiblePlayTiles).includes(""+tile_id)) ? true : false;
            if(isPlayableTile){
                let play_datas = possiblePlayTiles[tile_id].play_datas;
                this.onClick(tileDiv, () => {
                    if(play_datas.spendSpices){
                        this.bga.states.setClientState("ClientSpendSpices", {
                            descriptionmyturn: '',
                            args: {
                                'spendSpices': play_datas.spendSpices,
                                'withTile':tile_id,
                                'previousClientState' : this.getCurrentClientStateDatas(),
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
                                'maxTiles' : play_datas.harvest.maxTiles,
                                'withTile':tile_id,
                                'previousClientState' : this.getCurrentClientStateDatas(),
                            },
                        });
                    } 
                    else if(play_datas.progressTracks){
                        this.bga.states.setClientState("ClientSelectProgress", {
                            args: {
                                'progressTracks': play_datas.progressTracks, 
                                'withTile':tile_id,
                                'previousClientState' : this.getCurrentClientStateDatas(),
                            },
                        });
                    }
                    else if(play_datas.market_spots){
                        this.bga.states.setClientState("ClientSelectMarketPlace", {
                            descriptionmyturn: '',
                            args: {
                                'spots': play_datas.market_spots,
                                'withTile':tile_id,
                                'previousClientState' : this.getCurrentClientStateDatas(),
                            },
                        });
                    }
                    else { //DIRECT TILE PLAY
                        this.customPerformAction('actPlayTile', { 'tile_id':tile_id });
                    }
                });
                tileDiv.setAttribute("title",_('Play this tile'));
            }
        });
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
        this.customDebug("refreshPlayersDatas()",players);
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
    
    customPerformAction(action, args, options) {
      this.customDebug('customPerformAction()',action, args, options );
      args = args || {};
      options = options || {};
      //options.ignoreDefaultErrorHandler = true;
      args.v = this.gamedatas.version;
      return new Promise((resolve, reject) => {
        let call = this.bga.actions.performAction(action, args, options);
        if(call) call.then( resolve() )
            .catch(({message, finalArgs})=>{ 
                console.error('Error when calling customPerformAction()',action, message, args, options );
                if (message == "!!!checkVersion") {
                    this.bga.gameui.infoDialog(  _("A new version of this game is now available"),_("Reload Required"), () => {window.location.reload(true);},true);
                }
                else reject(message);
            });
        else reject('No server call');
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
        this.customDebug( 'notifications subscriptions setup' );
        
        
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
            logger: this.customDebug,
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
        this.customDebug('notif_clearTurn: restarting turn/step', n);
        this.cancelLogs(n.notifIds);
    }

    async notif_refreshUI(notif){
        this.customDebug("notif_refreshUI",notif);
        this.refreshPlayersDatas(notif.datas['players']);
        ['cards', 'tiles', 'tokens'].forEach((value) => {
            this.gamedatas[value] = notif.datas[value];
        });
        this.gamedatas._private = notif._private;

        this.clearSelection(true);

        let currentPlayer = this.bga.players.getCurrentPlayer();
        Object.values(this.gamedatas.players).forEach((player) => {
            let pId = player.id;
            //UPDATE ALL PLAYER COUNTERS
            this.bga.playerPanels.getScoreCounter(pId).toValue(player.score);
            
            this._counters[pId]['exploration_hand_size'].toValue(player.exploration_hand_size);
            this._counters[pId]['ships'].toValue(player.ships);
            this._counters[pId]['contracts'].toValue(player.contracts);
            this._counters[pId]['compasses'].toValue(player.compasses);
            this._counters[pId]['nbActions'].toValue(player.nbActions);

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
    
    async notif_newSession(notif) {
        this.customDebug("notif_newSession",notif);
        this.gamedatas.session = notif.n;
     
        //RESET Players progress :
        let row = 1;
        Object.values(this.gamedatas.players).forEach(async (player) => {
            let pId = player.id;
            Object.values([1,2,3,4,5]).forEach(async (track) => {
                let progressTrack = document.getElementById(`goa_progress_track_${track}_${pId}`);
                let tokenDiv = progressTrack.getElementsByClassName('goa_progress_marker')[0];
                let destinationDiv = document.getElementById(`goa_progress_track_space_${track}_${row}_${pId}`);
                await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
            });
        });

    }
    
    async notif_resetCurrencies(notif) {
        this.customDebug("notif_resetCurrencies",notif);
        let currentPlayerId = this.bga.players.getCurrentPlayerId();
        Object.values(this.gamedatas.players).forEach((player) => {
            let pId = player.id;
            this._counters[pId]['ships'].toValue(0);
            this._counters[pId]['contracts'].toValue(0);
            this._counters[pId]['compasses'].toValue(0);

            if (player.id == currentPlayerId){
                this._counters[pId].money.toValue(0);
            }
        });

    }
    async notif_setupMarket(notif) {
        this.customDebug("notif_setupMarket",notif);
        let tiles = notif.tiles;
        let boxTiles = notif.boxTiles;
        let voidDiv = document.getElementById('goa_top_zone');
        
        let marketDiv = document.getElementById('goa_board_market_grid');
        let marketTilesDiv = [...marketDiv.querySelectorAll('.goa_tile')];
        let animationsCleanMarket = marketTilesDiv.map(tileDiv => (() => {
            this.animationManager.slideOutAndDestroy(tileDiv, voidDiv, {duration: 1000});
        }));
        await this.animationManager.playInterval(animationsCleanMarket);
        //-----------------------
        let boxTilesContainer = document.getElementById('goa_box_tiles_container');
        let boxTilesDivs = [...boxTilesContainer.querySelectorAll('.goa_tile')];
        let boxTilesDivsIds = boxTilesDivs.map(tileDiv => {
            return parseInt( tileDiv.dataset.id);
        });
        let animationsCleanBox = boxTilesDivs.map(tileDiv => (() => {
            if(Object.keys(boxTiles).includes(tileDiv.dataset.id)) return;//keep tile
            this.animationManager.slideOutAndDestroy(tileDiv, voidDiv, {duration: 800});
        }));
        await this.animationManager.playInterval(animationsCleanBox);
        //-----------------------

        let animationsRefill = Object.values(tiles).map(tile => (() => {
            this.animationManager.slideIn(this.addTile(tile), voidDiv, {duration: 1000});
        }));
        await this.animationManager.playInterval(animationsRefill);
        //-----------------------
        let animationsBox = Object.values(boxTiles).map(tile => (() => {
            if(boxTilesDivsIds.includes(parseInt(tile.id))) return;//keep tile
            this.animationManager.slideIn(this.addTile(tile), voidDiv, {duration: 800});
        }));
        await this.animationManager.playInterval(animationsBox);

        //TODO JSA clean player tiles
    }

    async notif_newRound(notif) {
        this.customDebug("notif_newRound",notif);
        this.gamedatas.round = notif.n;
        this.setupRoundMarker();
        
        Object.values(this.gamedatas.players).forEach(player => {
            this._counters[player.id]['nbActions'].toValue(0);
        });
    }
    async notif_endRound(notif) {
        this.customDebug("notif_endRound",notif);
    }
    async notif_lineSeparator(notif) {
        this.customDebug("notif_lineSeparator",notif);
        //showMessage is not enough for mobile view and other things, lets use "<hr>" in message
        //this.bga.dialogs.showMessage('<hr>','only_to_log');
    }
    async notif_resetActionsPhase(notif) {
        this.customDebug("notif_resetActionsPhase",notif);
        let newState = notif.newState;
        let tiles_ids = notif.tiles_ids;
        Object.values(tiles_ids).forEach( (tile_id) => {
            let tileDiv = document.getElementById(`goa_tile-${tile_id}`);
            if(tileDiv){
                tileDiv.dataset.state = newState;
            }
        });
    }
    
    async notif_auctionPlaced(notif) {
        this.customDebug("notif_auctionPlaced",notif);
        let token = notif.token;
        //let tokenDiv = document.getElementById(`goa_token-${token.id}`);
        let tokenDiv = this.addToken(token);
        let destinationDiv = document.getElementById(`goa_market_grid_space-${token.row}-${token.col}`);
        await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
    }
    
    async notif_auctionMarkerBack(notif) {
        this.customDebug("notif_auctionMarkerBack",notif);
        let token = notif.token;
        let tokenDiv = this.addToken(token);
        let destinationDiv = document.getElementById(this.getTokenContainer(token));
        await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
    }
    
    async notif_giveFirstPlayerToken(notif) {
        this.customDebug("notif_giveFirstPlayerToken",notif);
        let token = notif.token;
        let tokenDiv = this.addToken(token);
        let destinationDiv = document.getElementById(this.getTokenContainer(token));
        await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
    }
    
    async notif_winsAuction(notif) {
        this.customDebug("notif_winsAuction",notif);
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
        this.customDebug("notif_winsAuctionBank",notif);
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
        this.customDebug('notif_getContracts', notif);
        this._counters[notif.player_id]['contracts'].incValue(notif.n);
    }
    async notif_spendContracts(notif) {
        this.customDebug('notif_spendContracts', notif);
        this._counters[notif.player_id]['contracts'].incValue(-notif.n);
    }
    async notif_getShips(notif) {
        this.customDebug('notif_getShips', notif);
        this._counters[notif.player_id]['ships'].incValue(notif.n);
    }
    async notif_spendShips(notif) {
        this.customDebug('notif_spendShips', notif);
        this._counters[notif.player_id]['ships'].incValue(- notif.n);
    }
    async notif_getCompass(notif) {
        this.customDebug('notif_getCompass', notif);
        this._counters[notif.player_id]['compasses'].incValue(notif.n);
    }
    async notif_spendCompass(notif) {
        this.customDebug('notif_spendCompass', notif);
        this._counters[notif.player_id]['compasses'].incValue(-notif.n);
    }
    async notif_getMoney(notif) {
        this.customDebug('notif_getMoney', notif);
        await this.animateGainMoney(notif.player_id, notif.n);
    }
    async notif_spendMoney(notif) {
        this.customDebug('notif_spendMoney', notif);
        await this.animateGainMoney(notif.player_id, -notif.n);
    }
    
    async notif_giveTile(notif) {
        this.customDebug("notif_giveTile",notif);
        let tile = notif.tile;
        
        let tileDiv = document.getElementById(`goa_tile-${tile.id}`);
        let isOutpost = tile.subtype == this.const.TILE_SUBTTYPE_OUTPOST;
        if(isOutpost){
            tileDiv = document.getElementById(`goa_outpost-${tile.id}`);
        }
        if(! tileDiv){
            tileDiv = this.addTile(tile);
            let voidDiv = document.getElementById('goa_top_zone');
            await this.animationManager.slideIn(tileDiv, voidDiv, {duration: 1000});
        } else if(isOutpost) {
            let stock = this.outpostsTilesStocks[tile.colony];
            tileDiv = this.addTile(tile);
            await this.animationManager.slideIn(tileDiv, stock.element, {duration: 800});
        } else {
            tileDiv = this.addTile(tile);//refresh tile dataset
            let destinationDiv = document.getElementById(this.getTileContainer(tile));
            await this.animationManager.slideAndAttach(tileDiv, destinationDiv);
        }
    }
    
    async notif_replaceTile(notif) {
        this.customDebug("notif_replaceTile",notif);
        let tile = notif.tile;
        
        let tileDiv = this.addTile(tile);//refresh tile dataset
        let destinationDiv = document.getElementById(this.getTileContainer(tile));
        await this.animationManager.slideAndAttach(tileDiv, destinationDiv);
    }
    
    async notif_discardTile(notif) {
        this.customDebug("notif_discardTile",notif);
        let tile = notif.tile;
        let tileDiv = document.getElementById(`goa_tile-${tile.id}`);
        if(tileDiv){
            let voidDiv = document.getElementById('goa_top_zone');
            await this.animationManager.slideOutAndDestroy(tileDiv, voidDiv, {duration: 1000});
        }
    }
    async notif_playTile(notif) {
        this.customDebug("notif_playTile",notif);
        let tile = notif.tile;
        let tileDiv = document.getElementById(`goa_tile-${tile.id}`);
        if(tileDiv){
            tileDiv.dataset.state = tile.state;
        }
    }
    async notif_placeTile(notif) {
        this.customDebug("notif_placeTile",notif);
        let tile = notif.tile;
        
        let tileDiv = this.addTile(tile);//refresh tile dataset
        let destinationDiv = document.getElementById(this.getTileContainer(tile));
        await this.animationManager.slideAndAttach(tileDiv, destinationDiv);
    }
    
    async notif_newSpiceOnTile(notif) {
        this.customDebug("notif_newSpiceOnTile",notif);
        let token = notif.token;
        let tokenDiv = this.addToken(token);
        //let destinationDiv = document.getElementById(this.getTokenContainer(token));
        //await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
        let voidDiv = document.getElementById('goa_top_zone');
        await this.animationManager.slideIn(tokenDiv, voidDiv, {duration: 1000});

        let spice_type = token.spice;
        this._counters[notif.player_id]['spices_'+spice_type ].incValue(1);
        
    }
    async notif_removeTileSpices(notif) {
        this.customDebug("notif_removeTileSpices",notif);
        let tile_id = notif.tile_id;
        let tileDiv = document.getElementById(`goa_tile-${tile_id}`);
        if(tileDiv){
            let tokenDivs = [...tileDiv.querySelectorAll('.goa_spice')];
            let voidDiv = document.getElementById('goa_top_zone');
            let animationsCleanTile = tokenDivs.map(tokenDiv => (() => {
                let spice_type = tokenDiv.dataset.spice;
                this._counters[notif.player_id]['spices_'+spice_type ].incValue(-1);
                this.animationManager.slideOutAndDestroy(tokenDiv, voidDiv, {duration: 1000});
            }));
            await this.animationManager.playInterval(animationsCleanTile);
        }
    }
    async notif_removeSpices(notif) {
        this.customDebug("notif_removeSpices",notif);
        let tokens = notif.tokens;
        let tokenDivs = [];
        Object.values(tokens).forEach((token) => {
            tokenDivs.push( $(`goa_token-${token.id}`) );
        });
        let voidDiv = document.getElementById('goa_top_zone');
        let animationsCleanTile = tokenDivs.map(tokenDiv => (() => {
            let spice_type = tokenDiv.dataset.spice;
            this._counters[notif.player_id]['spices_'+spice_type ].incValue(-1);
            this.animationManager.slideOutAndDestroy(tokenDiv, voidDiv, {duration: 1000});
        }));
        await this.animationManager.playInterval(animationsCleanTile);

    }

    async notif_reshuffleExplorationDeck(notif) {
        this.customDebug("notif_reshuffleExplorationDeck",notif);
        //this.explorationDeck.addCards(this.explorationDiscard.getCards());
        await this.explorationDeck.addCards(this.explorationDiscard.getCards().map(card => ({ id: card.id, })) );
        await this.explorationDeck.shuffle().then(() => this.customDebug('shuffle ended'));
        //this.explorationDeck.removeAll();
        await this.explorationDeck.setCardNumber(notif.deck_size);
    }
    async notif_discardAllExploration(notif) {
        this.customDebug("notif_discardAllExploration",notif);
        let currentPlayerId = this.bga.players.getCurrentPlayerId();
        
        await Promise.all (
            Object.values(this.gamedatas.players).map(async (player) => {
            
                let addCardSettings = { 
                    fromElement: document.getElementById(`goa_reserve_${player.id}_exploration_hand_size`)
                };
                if (currentPlayerId == player.id) {
                    addCardSettings = { 
                        fromStock: this.explorationHand
                    };
                    this._counters[currentPlayerId]['exploration_hand_size'].toValue(0);

                    Array.from([1,2,3,4,5,6]).forEach((s) => {
                        this._counters[currentPlayerId]['explo_symbol_tiny_'+s].toValue(0);
                    });
                                
                    let handCards = this.explorationHand.getCards().forEach(async (c) => {
                        await this.explorationDiscard.addCard(c, addCardSettings);
                    });
                }
                else {
                    //We don't have the card data, and maybe don't need it
                    //await this.explorationDiscard.addCard(card, addCardSettings);
                }
            }),
        );
    }
    async notif_drawAndDiscardExploration(notif) {
        this.customDebug("notif_drawAndDiscardExploration",notif);
        await this.discardExCard(notif.card);
    }
    async notif_discardExploration(notif) {
        this.customDebug("notif_discardExploration",notif);
        this._counters[notif.player_id]['exploration_hand_size'].incValue( -1);
        let currentPlayerId = this.bga.players.getCurrentPlayerId();
        let addCardSettings = { 
                fromElement: document.getElementById(`goa_reserve_${notif.player_id}_exploration_hand_size`)
            };
        if (currentPlayerId == notif.player_id) {
            addCardSettings = { 
                fromStock: this.explorationHand
            };
            this._counters[currentPlayerId]['explo_symbol_tiny_'+notif.card.symbol].incValue(-1);
        }
        await this.explorationDiscard.addCard(notif.card, addCardSettings);
    }
    
    async notif_giveExplorationCardsToPlayer(notif) {
        this.customDebug("notif_giveExplorationCardsToPlayer",notif);

        this._counters[notif.player_id]['exploration_hand_size'].incValue( notif.n);
        let currentPlayer = this.bga.players.getCurrentPlayer();
        if (currentPlayer && currentPlayer.id == notif.player_id && notif._private) {
            this.explorationHand.addCards(notif._private.cards, { fromStock: this.explorationDeck});
            Object.values(notif._private.cards).forEach((card) => {
                this._counters[currentPlayer.id]['explo_symbol_tiny_'+card.symbol].incValue(1);
            });
        }
        else {
            let amount = notif.n;
            await this.explorationDeck.setCardNumber(this.explorationDeck.getCardNumber() - amount );
            let fromElement = this.explorationDeck.element;
            let toElement = document.getElementById(`goa_reserve_${notif.player_id}_exploration_hand_size`);
            let animations = new Array(amount).fill(0).map(index => (() => {
                this.animationManager.slideFloatingElement(this.createTempElement(this.formatIcon('exploration_hand_size', _('Exploration card'))), fromElement, toElement, {
                    duration: 800,
                    ignoreScale: false,
                })
                }));
            await this.animationManager.playInterval(animations);
        }
        
    }
    
    async notif_playAction(notif) {
        this.customDebug("notif_playAction : ",notif);
        let pId = notif.player_id;
        this._counters[pId]['nbActions'].incValue(1);
    }

    async notif_progress(notif) {
        this.customDebug("notif_progress : move token 1 space down",notif);
        let token = notif.token;
        let tokenDiv = this.addToken(token);
        let destinationDiv = document.getElementById(this.getTokenContainer(token));
        await this.animationManager.slideAndAttach(tokenDiv, destinationDiv);
    }
    
    async notif_addPoints (notif) {
        this.customDebug( 'notif_addPoints' , notif);
        let player_id = notif.player_id;
        let player_color = this.gamedatas.players[player_id].color;
        let amount = notif.n;
        let scoreCounter = this.bga.playerPanels.getScoreCounter(player_id);
        //animate scoring on panel icons
        this.bga.gameui.displayScoring(scoreCounter.span, player_color, amount,800);
        await this.bga.gameui.wait(800); 
        scoreCounter.incValue(amount);
    }
    
    async notif_addPointsWithTile (notif) {
        this.customDebug( 'notif_addPointsWithTile' , notif);
        let player_id = notif.player_id;
        let player_color = this.gamedatas.players[player_id].color;
        let amount = notif.n;
        let tile = notif.tile;
        let scoreCounter = this.bga.playerPanels.getScoreCounter(player_id);
        let tileDiv = document.getElementById(`goa_tile-${tile.id}`);
        this.bga.gameui.displayScoring(tileDiv, player_color, amount,800);
        await this.bga.gameui.wait(800); 
        scoreCounter.incValue(amount);
    }
    
    async notif_addPointsWithSymbols (notif) {
        this.customDebug( 'notif_addPointsWithSymbols' , notif);
        await this.notif_addPoints(notif);
    }
    async notif_addPointsWithMoney (notif) {
        this.customDebug( 'notif_addPointsWithMoney' , notif);
        await this.notif_addPoints(notif);
    }

    ////////////////////////////////////////////////////////
    //  _____ _ _
    // |_   _(_) | ___  ___
    //   | | | | |/ _ \/ __|
    //   | | | | |  __/\__ \
    //   |_| |_|_|\___||___/
    //////////////////////////////////////////////////////////
 
    async setupTiles(init = true) {
        this.customDebug("setupTiles"); 
        
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
            for(let row=1;row<=this.const.MARKET_GRID_SIZE;row++){
                for(let col=1;col<=this.const.MARKET_GRID_SIZE;col++){
                    let grid_cell = `goa_market_grid_space-${row}-${col}`;
                    if(!$(grid_cell)) document.getElementById(`goa_board_market_grid`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':row, 'col':col}));
                    //this.empty(grid_cell);
                }
            }
            //---------------------------------
            //grid frame
            for(let col=1;col<=this.const.MARKET_GRID_SIZE;col++){
                let grid_cell = `goa_market_grid_space-${0}-${col}`;
                if(!$(grid_cell)) document.getElementById(`goa_board_market_top`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':0, 'col':col}));

                grid_cell = `goa_market_grid_space-${(this.const.MARKET_GRID_SIZE+1)}-${col}`;
                if(!$(grid_cell)) document.getElementById(`goa_board_market_bottom`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':(this.const.MARKET_GRID_SIZE+1), 'col':col}));


            }
            for(let row=1;row<=this.const.MARKET_GRID_SIZE;row++){
                let grid_cell = `goa_market_grid_space-${row}-${0}`;
                if(!$(grid_cell)) document.getElementById(`goa_board_market_left`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':row, 'col':0}));

                grid_cell = `goa_market_grid_space-${row}-${(this.const.MARKET_GRID_SIZE+1)}`;
                if(!$(grid_cell)) document.getElementById(`goa_board_market_right`).insertAdjacentHTML('beforeend', this.tplMarketGridSpace({'row':row, 'col':(this.const.MARKET_GRID_SIZE+1)}));
            }
            //---------------------------------
            // create a card manager
            this.outpostsTilesManager = new BgaCards.Manager({
                animationManager: this.animationManager,
                type: 'goa_outpost',
                getId: (card) => card.id,
                setupFrontDiv: (card, div) => {
                    div.dataset.id = card.id; 
                    div.dataset.type = card.type; 
                    div.dataset.colony = card.colony; 
                    this.bga.gameui.addTooltipHtml(div.id, this.getTileTooltip(card), 0);
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
        if(tile.subtype == this.const.TILE_SUBTTYPE_OUTPOST){
            if (tile.location.startsWith(this.const.TILE_LOCATION_BOARD_OUTPOST) ) {
                return `goa_board_outposts_${tile.colony}`;
            }
            if (tile.location == (this.const.TILE_LOCATION_PLAYER_BOARD) ) {
                return `goa_player_outposts-${tile.pId}`;
            }
        }
        
        if(tile.subtype == this.const.TILE_SUBTTYPE_MARKET){
            if (tile.location == this.const.TILE_LOCATION_BOARD_MARKET ) {
                if(tile.row>0 && tile.col>0 && tile.row<=this.const.MARKET_GRID_SIZE && tile.col<=this.const.MARKET_GRID_SIZE ){
                    return `goa_market_grid_space-${tile.row}-${tile.col}`;
                }
            }
            if (tile.location == (this.const.TILE_LOCATION_PLAYER_BOARD) ) {
                if (tile.play_type == this.const.MARKET_TYPE_PLANTATION && [1,2,3,4].includes(tile.state) ) {
                    return `goa_player_plantations-${tile.pId}`;
                }
                return `goa_player_tiles-${tile.pId}`;
            }
            if (tile.location == (this.const.TILE_LOCATION_BOX) ) {
                return `goa_box_tiles`;
            }
        }
        console.error('Trying to get container of a tile', tile);
        return 'goa_game_container';
    }
    
    addTile(tile, location = null) {
        this.customDebug('addTile',tile,location);
        let tileMaterial = this.gamedatas.material.tiles[tile.subtype][tile.type];
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
            this.bga.gameui.addTooltipHtml(`goa_tile-${tile.id}`, this.getTileTooltip(tile),0);

            tileDiv = document.getElementById(`goa_tile-${tile.id}`) ;
                
            if(tile.subtype == this.const.TILE_SUBTTYPE_MARKET){
                tileDiv.dataset.name = _(tileMaterial.name);
            }
        }
        else {
            tileDiv.dataset.state = tile.state;//refresh state if already exists
        }

        return tileDiv;
    }
    getTileTooltip(tile) {
        let tileMaterial = this.gamedatas.material.tiles[tile.subtype][tile.type];
        let cardDatas = tile;
        let phaseA = false, phaseB = false;
        if(tileMaterial.nbr_A > 0) phaseA = true;
        if(tileMaterial.nbr_B > 0) phaseB = true;
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
        if(tile.subtype == this.const.TILE_SUBTTYPE_OUTPOST){
            textLine1 = this.bga.gameui.format_string( 
                _('Requires ${n} ${resource_name} : ${icon_resource}'),
                {'n': this.getNbContractsForColony(tile.colony) , resource_name: _('Contracts'), icon_resource: this.formatIcon('contracts', _('Contracts'))}
            );
        }
        let textAction = this.formatTileActionText( tile );
        let div = this.tplTile(cardDatas,'_tmp'); 
        return [`<div class='goa_tile_tooltip' data-subtype="${tile.subtype}">
                <div class="goa_tooltip_block1">
                    <span class="goa_tile_title">${this.formatTileTitle( tile )}</span>
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
            [this.const.MARKET_TYPE_PLANTATION, this.bga.gameui.format_string(_("Depot"),{ })] ,
            [this.const.MARKET_TYPE_ACTION_REUSEABLE, this.bga.gameui.format_string( _('Multi-use action'),{ } )],
            [this.const.MARKET_TYPE_ACTION_DIRECT, this.bga.gameui.format_string(_("Immediate bonuses"),{ })] ,
            [this.const.MARKET_TYPE_ACTION_ONCE, this.bga.gameui.format_string(_("Single use Action"),{ })] ,
            [this.const.MARKET_TYPE_SCORING, this.bga.gameui.format_string(_("Scoring"),{ })] ,
            
        ]);
        let description = ''; 
        if(tile.subtype == this.const.TILE_SUBTTYPE_OUTPOST){
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
            case this.const.TILE_SUBTTYPE_OUTPOST:
                return this.bga.gameui.format_string(_("When you establish an outpost, place the outpost tile on the matching space of your personal board. After establishing an outpost, fill it with the appropriate goods from the supply. To establish an outpost with the appropriate action, a player needs to recruit a minimum number of contracts: ${n} contracts for this one."),{ n : this.getNbContractsForColony(tile.colony) });
            case this.const.TILE_SUBTTYPE_MARKET: 
                descriptionMap = new Map([
                    [this.const.MARKET_TYPE_PLANTATION, this.bga.gameui.format_string(_("When you gain a Depot, place it face up in an empty upper space on your Player Board. Immediately fill these fields with the matching Goods from the supply : this one have ${x} fields. If you already have four Depots on your board, you must first discard one Depot (along with any Goods on it) before placing the new one. If the discarded Depot shows a VP symbol, keep it face down in your play area. It is worth ${n} Victory Points at the end of the game."),{ x: tile.spice_slots.length , n : tile.end_score })] ,
                    [this.const.MARKET_TYPE_ACTION_REUSEABLE, this.bga.gameui.format_string( _('Blue Tile : players must put it face up in their play area. They can use it before, during or after their action. This tile can be played during a future action, but only once per round. After using it, the player must flip it and may no longer use it for the rest of the round. At the beginning of the following round, players flip the tile face up and can use it again.'),{ } )],
                    [this.const.MARKET_TYPE_ACTION_DIRECT, this.bga.gameui.format_string(_("Teal Tile : When a player acquires this tile, they immediately take the appropriate resources. After being used, the tile is discarded."),{ })] ,
                    [this.const.MARKET_TYPE_ACTION_ONCE, this.bga.gameui.format_string(_("Yellow Tile : This tile may only be used once during the game. Once used, the tile is discarded."),{ })] ,
                    [this.const.MARKET_TYPE_SCORING, this.formatScoringTileText(tile)] ,
                ]);
                break;
            default : return '';
        }
        let text = descriptionMap.get(tile.play_type);
        return text;
    }
    formatScoringTileText (tile) {
        let descriptionMap = new Map([
            [37, this.bga.gameui.format_string(_('At the end of the game, each of your single-field Depots is worth ${n} points instead of ${x} points.'),{'tile_name':_('Foreman'), 'n':3, 'x':1 })] ,
            [38, this.bga.gameui.format_string(_('Remove any ${x} Goods from your Outposts and/or Depots and return them to the supply. Then place the Duty tile face down in front of you. At the end of the game, each completed Duty tile is worth ${n} VP.'),{ 'tile_name':_('Duty'), 'n':5, 'x':6 } )],
            [39, this.bga.gameui.format_string(_('At the end of the game, this tile is worth ${n} victory points.'),{ 'tile_name':_('Small Mission'), 'n':tile.end_score })] ,
            [40, this.bga.gameui.format_string(_('At the end of the game, this tile is worth ${n} victory points.'),{ 'tile_name':_('Large Mission'), 'n':tile.end_score})] ,
        ]);
        let text = descriptionMap.get(tile.type);
        return text;
    }
    formatTileSpicesText (tile) {
        let number_label = tile.spice_slots.length;  
        let icons = '';
        let treatArray = null;
        Object.values( tile.spice_slots ).forEach((spice_type) => {
            icons += this.formatSpiceIcon(spice_type);
            if(Array.isArray(spice_type)){
                treatArray = spice_type;
            }
        });
        let text = '';
        if(treatArray){
            Object.values( tile.spice_slots ).forEach((spice_type) => {
                icons = '';
                Object.values( spice_type ).forEach((spice_type_listElement) => {
                    icons += this.formatSpiceIcon(spice_type_listElement);
                });
                text += '<li>' + this.bga.gameui.format_string(
                    _("Take ${n} ${resource_name} : ${icon_resource}.")
                    ,{n:1, 'resource_name': _('Goods'), icon_resource : icons}
                ) + '</li>';
            });
        }
        else {
            text = this.bga.gameui.format_string(
                _("Take ${n} ${resource_name} : ${icon_resource}.")
                ,{n:number_label, 'resource_name': _('Goods'), icon_resource : icons}
            );
        }
                  
        return text;
    }
    formatTileActionText (tile) {
        this.customDebug("formatTileActionText",tile);
        let description = '';
        let tileMaterial = this.gamedatas.material.tiles[tile.subtype][tile.type];
        switch (tile.subtype )
        {
            case this.const.TILE_SUBTTYPE_OUTPOST:
                return this.formatTileSpicesText(tile);
            case this.const.TILE_SUBTTYPE_MARKET: 
                switch(tileMaterial.play_type){
                    case this.const.MARKET_TYPE_PLANTATION:
                        return this.formatTileSpicesText(tile);
                    case this.const.MARKET_TYPE_ACTION_REUSEABLE:
                    case this.const.MARKET_TYPE_ACTION_DIRECT: 
                        let  returnAfterThis = false;
                        Object.entries( tileMaterial.actions ).forEach(([action_type,number]) => {
                            let resource_name = '', icon_name='';
                            let number_label = number;
                            switch(parseInt(action_type)){
                                case this.const.ACTION_SPICE:
                                    returnAfterThis = true;
                                    description = this.formatTileSpicesText(tile);
                                case this.const.ACTION_MONEY:
                                    resource_name = _('Gold Pardau cards');
                                    icon_name = this.formatIcon('money', resource_name);
                                    break;
                                case this.const.ACTION_CONTRACT:
                                    resource_name = _('Contracts');
                                    icon_name = this.formatIcon('contracts', resource_name);
                                    break;
                                case this.const.ACTION_SHIP:
                                    resource_name = _('Ships');
                                    icon_name = this.formatIcon('ships',resource_name);
                                    break;
                                case this.const.ACTION_COMPASS:
                                    resource_name = _('Compass cards');
                                    icon_name = this.formatIcon('compasses',resource_name);
                                    break;
                                case this.const.ACTION_EXPLORATION:
                                    resource_name = _('Exploration cards');
                                    icon_name = this.formatIcon('exploration_card',resource_name);
                                    break;
                                case this.const.ACTION_SHIPS_OR_CONTRACTS:
                                    resource_name = _('Ships or Contracts');
                                    icon_name = this.formatIcon('ships', _('Ships')) + ' '+  this.formatIcon('contracts', _('Contracts'));
                                    break;
                                case this.const.ACTION_FAVOR: 
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
                    case this.const.MARKET_TYPE_ACTION_ONCE: 
                        let actionDescriptionMap = new Map([
                                [this.const.ACTION_SWAP, this.bga.gameui.format_string(_('When you buy the Exchange tile, place it face up in your play area. During one of your later action turns, you may take 1 tile of your choice from the market and put it face up in your play area. Put the Exchange tile, face up, in the space previously occupied by the tile you just took. You may use the tile that you obtained with the Exchange tile as soon as you acquire it or later in the game. The Exchange tile can be auctioned again, as per the normal rules. '),{}) + '<br/>' + _('Bonanza variant: If the Exchange tile is used in one of the corners, you also take the Bonanza tile.') ],
                                [this.const.ACTION_RESUPPLY, this.bga.gameui.format_string(_('Reveal ${n} Exploration cards. Add together their Contract values, then gain that many Goods, Contracts, or Ships (your choice). Discard the Exploration Cards afterward.'),{n:2})],
                                [this.const.ACTION_HARVEST, this.bga.gameui.format_string(_('Fill up to ${n} of your Depots and/or Outposts with the appropriate Goods from the supply.'),{n :3})],
                                [this.const.ACTION_ESPIONAGE, this.bga.gameui.format_string(
                                     _("Choose an opponent and do one of these actions, using the position of this player's Progress marker in the appropriate column: ${actions} "), 
                                    { 
                                        'actions': '<ul>' 
                                            +  '<li>' + _('Build Ships') + '</li>'
                                            +  '<li>' + _('Produce') + '</li>'
                                            +  '<li>' + _('Collect Taxes') + '</li>'
                                            +  '<li>' + _('Establish an Outpost') + '</li>'
                                            + '</ul>', 
                                    }) 
                                    + '<br/>' 
                                    + _('That tile cannot be used to obtain Exploration cards.')],
                                [this.const.ACTION_VICEKING, this.bga.gameui.format_string(_('Viceroy: Move your highest Progress Marker down one row for free. If several markers are tied for highest, choose one.'),{})],
                            ]);
                        Object.entries( tileMaterial.actions ).forEach(([action_type,number]) => {
                            description += actionDescriptionMap.get(parseInt(action_type));
                        });
                        break;
                    default: break;
                }
                let tile_name = tileMaterial.name;
                description = this.bga.gameui.format_string(
                                _("${tile_name} : ${tile_desc}")
                                ,{ 'tile_name': "<b>"+tile_name+"</b>", 'tile_desc': description, }
                            );
                break;
            default: break;
        }
        return description;
    }
    tplTile(tile, prefix ='') {
        let spotsDiv = '';
        let tileMaterial = this.gamedatas.material.tiles[tile.subtype][tile.type];
        if(tileMaterial.spice_slots){
            Object.entries(tileMaterial.spice_slots).forEach(([i,t]) =>{
                spotsDiv += this.tplTileSpot(tile.id, 1+ parseInt(i), t , prefix);
            });
        }

        if(tile.subtype == this.const.TILE_SUBTTYPE_OUTPOST) {
            return `<div class="goa_tile goa_tile${prefix} goa_outpost" id="goa_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                data-id="${tile.id}"
                data-colony="${tile.colony}" data-spots="${tile.spice_slots ? tile.spice_slots.length : 0}">
                ${spotsDiv}
            </div>`;   
        }
        if(tile.subtype == this.const.TILE_SUBTTYPE_MARKET) {
            return `<div class="goa_tile goa_tile${prefix} goa_market" id="goa_tile${prefix}-${tile.id}" data-id="${tile.id}" data-type="${tile.type}"
                data-id="${tile.id}"
                data-play_type="${tile.play_type}" data-state="${tile.state}" data-spots="${tile.spice_slots ? tile.spice_slots.length : 0}"
                data-end_score="${tile.end_score}" 
                >
                ${spotsDiv}
                    ${this.formatIcon('greenCheck')}
            </div>`;
        }
        return '';
    }
    
    tplTileSpot(tile_id, index, spotContent, prefix ='') {
        return `<div class="goa_tile_spot goa_tile_spot${prefix}" id="goa_tile_spot${prefix}-${tile_id}-${index}" data-type="${spotContent}" data-index="${index}">
            </div>`; 
    }
    
    tplTileFromType(tileType, prefix ='') {
        let tileDatas = {};
        tileDatas.type = parseInt(tileType);
        //SUPPOSE ALL this.const.TILE_SUBTTYPE_MARKET
        let material = this.gamedatas.material.tiles[this.const.TILE_SUBTTYPE_MARKET][tileDatas.type];
        tileDatas = material;

        return this.tplTile(tileDatas, prefix);
    }

    tplMarketGridSpace(datas){
        return `<div class="goa_grid_space" id="goa_market_grid_space-${datas.row}-${datas.col}"
            data-row="${datas.row}" data-col="${datas.col}">
        </div>`;
    }

    formatTilesHelp(){
        let list1 = "";
        let j = 0;
        Object.entries(this.gamedatas.material.tiles[this.const.TILE_SUBTTYPE_MARKET])
        .sort(([,a],[,b]) => a.play_type - b.play_type) 
        .forEach(([type,tile_type]) => {
            let resourceDatas = tile_type;
            resourceDatas.id = 0;
            resourceDatas.state = 0;
            resourceDatas.type = parseInt(type);
            resourceDatas.subtype = this.const.TILE_SUBTTYPE_MARKET;
            if(j>0){
                list1 = list1.concat('<hr>');
            }
            list1 = list1.concat( this.formatMarketTileHelp(resourceDatas));
            j++; 
        });
        let list2 = "";
        let k = 0;
        Object.entries(this.gamedatas.material.tiles[this.const.TILE_SUBTTYPE_OUTPOST]).forEach(([type,tile_type]) => {
            let resourceDatas = tile_type;
            resourceDatas.id = 0;
            resourceDatas.state = 0;
            resourceDatas.type = parseInt(type);
            resourceDatas.subtype = this.const.TILE_SUBTTYPE_OUTPOST;
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
        let value = parseInt(colony);
        switch(value){
            case this.const.COLONY_KOLLAM: 
            case this.const.COLONY_KOCHI:  
            case this.const.COLONY_MADRAS: 
            case this.const.COLONY_KOZHIKODE: 
                return this.const.COLONIES_CONTRACTS[value];
            default: return 0;
        }
    }
    getColonyName(colony){
        switch(parseInt(colony)){
            case this.const.COLONY_KOLLAM: return _('Kollam');
            case this.const.COLONY_KOCHI:  return _('Kochi');
            case this.const.COLONY_MADRAS: return _('Madras');
            case this.const.COLONY_KOZHIKODE: return _('Kozhikode');
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
        this.customDebug('setupTokens');
        document.querySelectorAll('.goa_token[id^="goa_token-"]').forEach((oToken) => {
            this.destroy(oToken);
        });

        if(init){
            Object.values(this.gamedatas.players).forEach(player => {
                
                for(let row=1;row<=5;row++){
                    if(!$(`goa_progress_row_header_${row}_${player.id}`)){
                        document.getElementById(`goa_player_progress_row_headers-${player.id}`).insertAdjacentHTML('beforeend', this.tplProgressRowHeader({'row':row, 'pId':player.id}));
                        this.bga.gameui.addTooltipHtmlToClass(`goa_progress_row_header_${row}`, this.getBoardRowHeaderTooltip(row));
                    }
                    if(!$(`goa_progress_row_end_${row}_${player.id}`)){
                        document.getElementById(`goa_player_progress_row_ends-${player.id}`).insertAdjacentHTML('beforeend', this.tplProgressRowEnd({'row':row, 'pId':player.id}));
                        let tooltipText = this.getBoardRowEndTooltip(row);
                        if(tooltipText != '') this.bga.gameui.addTooltipHtmlToClass(`goa_progress_row_end_${row}`, tooltipText );
                        else $(`goa_progress_row_end_${row}_${player.id}`).classList.add('disabled');
                    }
                }

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
        if( this.const.TOKEN_LOCATION_PLAYER_BOARD == token.location) {
            if(token.subtype == this.const.TOKEN_SUBTTYPE_PROGRESS_MARKER){
                return `goa_progress_track_space_${token.track}_${token.row}_${token.pId}`;
            }
            if(token.subtype == this.const.TOKEN_SUBTTYPE_AUCTION_MARKER){
                return `goa_player_auction_markers-${token.pId}`;
            }
            if(token.subtype == this.const.TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER){
                //return `goa_player_auction_markers-${token.pId}`;
                return `goa_first_player_container_${token.pId}`;
            }
        } 
        if( this.const.TOKEN_LOCATION_BOARD == token.location) {
            if(token.subtype == this.const.TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER){
                if(token.row>=0 && token.col>=0 && token.row<=1+this.const.MARKET_GRID_SIZE && token.col<=1+this.const.MARKET_GRID_SIZE ){
                    return `goa_market_grid_space-${token.row}-${token.col}`;
                }
            }
            if(token.subtype == this.const.TOKEN_SUBTTYPE_AUCTION_MARKER){
                if(token.row>=0 && token.col>=0 && token.row<=1+this.const.MARKET_GRID_SIZE && token.col<=1+this.const.MARKET_GRID_SIZE ){
                    return `goa_market_grid_space-${token.row}-${token.col}`;
                }
            }
        } 
        if( token.location.startsWith(this.const.TOKEN_LOCATION_TILE)) {
            let tile_id = token.location.split('-')[1];
            if(token.subtype == this.const.TOKEN_SUBTTYPE_SPICE){
                return `goa_tile_spot-${tile_id}-${token.state}`;
            }
            return `goa_tile-${tile_id}`;
        }
        
        console.error('Trying to get container of a token', token);
        return 'goa_game_container';
    }
    
    addToken(token, location = null, prefix ='') { 
        this.customDebug("addToken",token,location); 
        let tokenDiv = document.getElementById(`goa_token${prefix}-${token.id}`) ;
        if (!tokenDiv){
            let container = location == null ? this.getTokenContainer(token) : location;
            document.getElementById(container).insertAdjacentHTML('beforeend', this.tplToken(token,prefix));
            tokenDiv = document.getElementById(`goa_token${prefix}-${token.id}`) ;
            tokenDiv.dataset.id = token.id;
        }
        else {
            //refresh dataset if already exists
            tokenDiv.dataset.row = token.row;
            tokenDiv.dataset.state = token.state;
        }
        return tokenDiv;
    }
    
    tplToken(token, prefix ='') {
        if(token.subtype == this.const.TOKEN_SUBTTYPE_PROGRESS_MARKER) 
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_progress_marker" data-id="${token.id}" data-track='${token.track}' data-row='${token.row}' data-color='${this.gamedatas.players[token.pId].color}' data-pid='${token.pId}'></div>`;
        if(token.subtype == this.const.TOKEN_SUBTTYPE_AUCTION_MARKER) 
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_auction_marker" data-id="${token.id}" data-value='${token.auctionOrder}' data-color='${this.gamedatas.players[token.pId].color}' data-pid='${token.pId}'></div>`;
        if(token.subtype == this.const.TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER) 
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_first_auction_marker" data-id="${token.id}" ></div>`;
        if(token.subtype == this.const.TOKEN_SUBTTYPE_SPICE) 
            return `<div id="goa_token${prefix}-${token.id}" class="goa_token goa_token${prefix} goa_spice" data-id="${token.id}" data-type="${token.type}" data-pid="${token.pId}" data-state="${token.state}" data-spice="${token.spice}" title="${this.getGoodsName(token.spice)}"></div>`;
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
    tplProgressRowHeader(datas) {
        return `
            <div class="goa_progress_row_header goa_progress_row_header_${datas.row}" id="goa_progress_row_header_${datas.row}_${datas.pId}" data-row='${datas.row}'>
            </div>`;
    }
    tplProgressRowEnd(datas) {
        return `
            <div class="goa_progress_row_end goa_progress_row_end_${datas.row}" id="goa_progress_row_end_${datas.row}_${datas.pId}" data-row='${datas.row}'>
            </div>`;
    }
    getBoardRowHeaderTooltip(row){
        let rowScore = this.const.END_SCORE_PROGRESS[row];
        let text = this.bga.gameui.format_string( 
            _('Final scoring : ${n} points for each progress marker on this row'),
            { 'n': '<span>'+rowScore +'</span>'}
        );

        return `<div class="goa_custom_tooltip">
            <div class='goa_progress_row_tooltip'>${text}</div>
            </div>
        `;
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
                                icon_resource : this.formatIcon('ships',_('Ships'))
                            }
                        );
                action_gains = this.const.PROGRESS_GAINS_SHIPS;
                break;
            case 2:  
                action_label = _('Produce');
                action_gains = this.const.PROGRESS_GAINS_HARVEST;
                action_desc = this.bga.gameui.format_string(
                            _("Take n ${resource_name} ${icon_resource} where n is indicated on the row of your progress marker on this column.")
                            ,{
                                'resource_name': _('Goods (any good matching an empty space on your own depots or outposts)'), 
                                icon_resource : this.formatIcon('good_any')
                            }
                        );
                break;
            case 3:  
                action_label = _('Collect Taxes');
                action_gains = this.const.PROGRESS_GAINS_TAXES;
                action_desc = this.bga.gameui.format_string(
                            _("Take n ${resource_name} ${icon_resource} where n is indicated on the row of your progress marker on this column.")
                            ,{
                                'resource_name': _('Pardaos'), 
                                icon_resource : this.formatIcon('money',_('Pardaos'))
                            }
                        );
                break;
            case 4:  
                action_label = _('Exploration');
                action_desc = this.bga.gameui.format_string(
                            _("Draw Exploration cards from the supply. Each row of the Exploration column shows two numbers. The number on the left is the maximum number of cards a player can draw whereas the one on the right is the player's hand limit, i.e., the maximum number of Exploration cards a player can have in their hand at any time when doing the Exploration action.")
                            ,{ }
                        );
                //['1 -> 1','1 -> 2','2 -> 3','2 -> 4','3 -> 5']
                action_gains = [];
                this.const.PROGRESS_GAINS_EXPLO.forEach(element => {
                    action_gains.push('<span class="goa_plus">'+element.plus+'</span>' 
                                    +'-> <span class="goa_max">'+element.max+'</span>');
                });
                break;
            case 5: 
                action_label = _('Establish Outpost');
                action_gains = this.const.PROGRESS_GAINS_OUTPOST;

                let action_gains_desc = '<ul>';
                action_gains.forEach(element => {
                    action_gains_desc += '<li>'+element+'</li>';
                });
                action_gains_desc += '</ul>';
                
                action_desc = '<ul>'
                            + '<li>' + this.bga.gameui.format_string( _("First, choose one of the four Outposts. You may not choose an Outpost you have already Established"),{  } ) + '</li>'
                            + '<li>' + this.bga.gameui.format_string( _("The row of your progress marker will give a base number of contracts."),{  } ) + '</li>'
                            + '<li>' + action_gains_desc + '</li>' 
                            + '<li>' + this.bga.gameui.format_string( _("Draw ${n} Exploration Cards and add together the Contracts shown in the bottom-left corner of those cards."),{ n: 2 } ) + '</li>'
                            + '<li>' + this.bga.gameui.format_string( _("You may add any number of contracts from your hand."),{  } ) + '</li>'
                            + '<li>' + this.bga.gameui.format_string( _("If you successfully meet the requirement of the chosen outpost, take it and place it on your board, filling the Goods fields on it; If you cannot (or choose not to) provide enough Contracts, the attempt fails : Take ${n} ${icon_contracts}"),{ 'n':1, 'icon_contracts': this.formatIcon('contracts',_('Contracts')) } ) + '</li>'
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
    getBoardRowEndTooltip(row){
        let line1 = '';
        let line2 = '';
        if(row <=1) return '';
        if(row>1){

            line1 = this.bga.gameui.format_string( 
                _('When the player has moved all of their markers to this row or above, they take ${n} Compass card.'),
                { 'n': '<span>'+1 +'</span>'}
            );
        }
        if(row>=4){
            line2 = this.bga.gameui.format_string( 
                _('The first player to reach this row before the other players draws ${n} Exploration card.'),
                { 'n': '<span>'+1 +'</span>'}
            );
        }

        return `<div class="goa_custom_tooltip goa_progress_row_tooltip">
                <div>${line1}</div>
                <div>${line2}</div>
            </div>
        `;
    }
    getGoodsName(goodType){
        switch(parseInt(goodType)){
            case this.const.SPICE_WHITE:    return _('Coconut Coir');
            case this.const.SPICE_YELLOW:   return _('Turmeric');
            case this.const.SPICE_BLACK:    return _('Pepper');
            case this.const.SPICE_PINK:     return _('Silk');
            case this.const.SPICE_GREEN:    return _('Dried fish');
            default: return '';
        }
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
        this.customDebug("setupCards");
        
        if(init){
            this.explorationCardsManager = new BgaCards.Manager({
                animationManager: this.animationManager,
                type: 'goa_exploration_card',
                getId: (card) => card.id,
                setupFrontDiv: (card, div) => {
                    div.dataset.type = card.type; 
                    div.dataset.state = card.state; 
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
                sort: BgaCards.sort('state'),
            });
            
            let currentPlayer = this.bga.players.getCurrentPlayer();
            if (currentPlayer) {
                this.explorationHand = new BgaCards.HandStock(this.explorationCardsManager, document.getElementById(`goa_exploration_hand-${currentPlayer.id}`), {
                    //sort: BgaCards.sort('type'),
                    sort: BgaCards.sort('action','contracts'),
                    //cardOverlap: -25,
                    //fanShaped: false,
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
        if(this.explorationHand) await this.explorationHand.removeAll();
        await this.explorationDiscard.removeAll();
        document.querySelectorAll('.goa_card[id^="goa_card-"]').forEach((pCard) => {
            this.destroy(pCard);
        });
        
        await Promise.all (
            this.gamedatas.cards.all.map( async (card) => {
                await this.addCard(card);
            }),
        );
        let hand = Object.values( [] );
        if(init){
            hand = Object.values(this.gamedatas.cards.private.hand);
        }
        else if(this.gamedatas._private && this.gamedatas._private.private){
            hand = Object.values(this.gamedatas._private.private.hand);
        }
        await Promise.all (
            hand.map( async (card) => {
                await this.addCard(card);
            }),
        );
    }

    getCardContainer(card) {
        if(card.subtype == this.const.CARD_SUBTTYPE_EXPLORATION){
            if (card.location == (this.const.CARD_LOCATION_EXP_DISCARD) ) {
                return `goa_exploration_discard`;
            }
            if (card.location == (this.const.CARD_LOCATION_HAND) ) {
                return `goa_exploration_hand`;
            }
        }
        
        console.error('Trying to get container of a card', card);
        return 'goa_game_container';
    }
    
    
    async addCard(card, location = null) {
        this.customDebug('addCard',card,location);
        let cardDiv = $(`goa_card-${card.id}`);
        if (!cardDiv){
            let container = location == null ? this.getCardContainer(card) : location;
            
            if(container == `goa_exploration_discard`){
                //use bga-cards stock
                await this.explorationDiscard.addCard(card);
            } else if(container.startsWith(`goa_exploration_hand`)){
                //use bga-cards stock
                await this.explorationHand.addCard(card);
                this.updateHandSelectable(this.gamedatas.gamestate.args, false);
                let currentPlayer = this.bga.players.getCurrentPlayer();
                if (currentPlayer) {
                    this._counters[currentPlayer.id]['explo_symbol_tiny_'+card.symbol].incValue(1);
                }
            }
            else {//manual add
                document.getElementById(container).insertAdjacentHTML('beforeend', this.tplCard(card));
            }
            cardDiv = document.getElementById(`goa_card-${card.id}`) ;

        }
        else {
            cardDiv.dataset.state = tile.state;//refresh state if already exists
        }
        return $(`goa_card-${card.id}`);
    }
    
    tplCard(card, prefix ='') {
        if(card.subtype == this.const.CARD_SUBTTYPE_EXPLORATION) 
            return `<div id="goa_card${prefix}-${card.id}" class="goa_card goa_exploration_card goa_card${prefix} goa_card${prefix}_${card.type}" data-type='${card.type}' data-pid='${card.pId}'></div>`;
        return '';
    }
    // draw + discard from deck
    async discardExCard(card_datas) {
        let cardNumber = this.explorationDeck.getCardNumber();

        if (cardNumber >= 1) {
            await this.explorationDiscard.addCard(card_datas, { fromStock: this.explorationDeck });
        }
    }
    getCardTooltipSymbolsInfos(cardDatas) {
        let textSymbols = '<div class="goa_card_symbols_score">';
        if(cardDatas.subtype == this.const.CARD_SUBTTYPE_EXPLORATION){
            textSymbols += _('Victory points at the end of the game (if in player hand) : ');
            textSymbols += '<ul>';
            Object.entries(this.const.END_SCORE_EXPLORATION).forEach(([nbCards,score]) => {
                textSymbols += '<li>'+this.bga.gameui.format_string( _(' ${n} identical Island Symbols => ${x} points '), {'n':nbCards, 'x': score} ) +  '</li>'; 
            });
            textSymbols += '</ul>';
        }
        textSymbols += '</div>';
        return textSymbols;
    }
    getCardTooltip(cardDatas) {
        let textLine1 = ''
        let title = '';
        let textSymbols = '';
        if(cardDatas.subtype == this.const.CARD_SUBTTYPE_EXPLORATION){
            let iconsContracts = this.formatIcon('contracts',_('Contracts'));
            for(let k=2;k<=cardDatas.contracts;k++) iconsContracts += this.formatIcon('contracts',_('Contracts'));
            textLine1 = this.bga.gameui.format_string( 
                _('When revealing and discarding this card to establish an outpost, this gives ${n} ${resource_name} : ${icon_resource}'),
                {'n': cardDatas.contracts , resource_name: _('Contracts'), icon_resource: iconsContracts}
            );
            title = _('Exploration card');
            textSymbols = this.bga.gameui.format_string( 
                _('Gives the Island Symbol ${s_icon} (${s_name})'),
                { s_name: this.getCardSymbolName( cardDatas.symbol ), s_icon: this.formatSymbolIcon(cardDatas.symbol)}
            );
            textSymbols += this.getCardTooltipSymbolsInfos(cardDatas);
        }
        let textAction = this.formatCardActionText( cardDatas );
        let div = this.tplCard(cardDatas,'_tmp'); 
        return [`<div class='goa_card_tooltip'>
                <div class="goa_tooltip_block1">
                    <span class="goa_card_title">${title}</span>
                </div>
                <div class="goa_tooltip_block2">
                    <div class="goa_card_img_wrap">${div}</div>
                    <div class="goa_card_text">
                        ${textAction == '' ? '' : '<hr><span class="goa_card_text_action">'+textAction+'</span>'}
                        ${textLine1 == '' ? '' : '<hr><span class="goa_card_text_line1">'+textLine1+'</span>'}
                        ${textSymbols == '' ? '' : '<span class="goa_card_symbols"><hr>'+textSymbols+'</span>'}
                        
                    </div>
                </div>
            </div>`];

    }

    formatCardActionText(card){
        switch(card.action){
            case this.const.CARD_ACTION_PROGRESS_NO_SPICE:  return _('Ability a: When performing the Progress action, you only need to pay the Ships, you can ignore the Goods costs');
            case this.const.CARD_ACTION_PROGRESS_NO_SHIP:   return _('Ability b: When performing the Progress action, you only need to pay the Goods, you can ignore the Ship costs');
            case this.const.CARD_ACTION_HARVEST_AND_CARDS:  return _('Ability c: When performing the Produce action, you may take any combination of Goods, Ships, and/or Contracts instead of only Goods.');
            case this.const.CARD_ACTION_OUTPOST_EXTRA_CONTRACTS : return _('Ability d: When performing the Establish an Outpost action, you may draw one extra Exploration Card and add its Contracts to your total. You may play this card after revealing the first 2 cards.');
            case this.const.CARD_ACTION_RESSOURCES_TO_COINS  : 
                let gains = this.const.CARD_GAINS_RESSOURCES_TO_COINS;
                let [a,b,c,d] =  Object.keys(gains);
                let [w,x,y,z] =  Object.values(gains);
                return this.bga.gameui.format_string(_('Ability e: You may spend ${a}/${b}/${c}/${d} Goods to gain ${w}/${x}/${y}/${z} Pardaos.') ,
                    { 'a':a,'b':b,'c':c,'d':d,  'w':w,'x':x,'y':y,'z':z, }
                );
            case this.const.CARD_ACTION_2_SPICES             : return this.bga.gameui.format_string(_('Ability ${x} : ${gain}') ,
                    { 'x':'f', 'gain': this.formatTileSpicesText({'spice_slots':[this.const.SPICES_ALL,this.const.SPICES_ALL]}) }
                );
            case this.const.CARD_ACTION_2_CONTRACTS          : return this.bga.gameui.format_string(_('Ability ${x} : ${gain}') ,
                    { 'x':'g', 'gain': this.bga.gameui.format_string(
                            _("Take ${n} ${resource_name} : ${icon_resource}.")
                            ,{n:2, 'resource_name': _('Contracts'), icon_resource : this.formatIcon('contracts',_('Contracts'))}
                        )
                    }
                );
            case this.const.CARD_ACTION_2_SHIPS              : return this.bga.gameui.format_string(_('Ability ${x} : ${gain}') ,
                    { 'x':'h', 'gain': this.bga.gameui.format_string(
                            _("Take ${n} ${resource_name} : ${icon_resource}.")
                            ,{n:2, 'resource_name': _('Ships'), icon_resource : this.formatIcon('ships',_('Ships'))}
                        )
                    }
                );
            case this.const.CARD_ACTION_5_COINS              : return this.bga.gameui.format_string(_('Ability ${x} : ${gain}') ,
             { 'x':'i', 'gain': this.bga.gameui.format_string(
                    _("Take ${n} ${resource_name} : ${icon_resource}.")
                    ,{n:5, 'resource_name': _('Pardaos'), icon_resource : this.formatIcon('money',_('Pardaos'))}
                )
             }
            );
            case this.const.CARD_ACTION_PROGRESS_WITH_COINS  : return this.bga.gameui.format_string(
                    _('Ability j: Spend ${a}/${b}/${c}/${d} Pardaos to advance a Progress marker of your choice to the 2nd/3rd/4th/5th row.')
                    , { 
                        a: this.const.PROGRESS_COST_AS_COINS[2],
                        b: this.const.PROGRESS_COST_AS_COINS[3],
                        c: this.const.PROGRESS_COST_AS_COINS[4],
                        d: this.const.PROGRESS_COST_AS_COINS[5],
                    }
                );
            
        }
        return '';
    }
    getCardSymbolName(card_symbol){
        //let symbols = this.const.CARD_SYMBOLS;
        //if(symbols[card_symbol]) return _(symbols[card_symbol]);
        //Client side def in order to use clienttranslate on server side
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
        let textSymbols = '';
        Object.entries(this.gamedatas.material.cards[this.const.CARD_SUBTTYPE_EXPLORATION])
        .sort(([,a],[,b]) => {
            const nameA = a.action.toUpperCase();
            const nameB = b.action.toUpperCase(); 
            if (nameA < nameB) {
                return -1;
            }
            if (nameA > nameB) {
                return 1;
            }
            return 0;
        }) 
        .forEach(([type,card_type]) => {
            let resourceDatas = card_type;
            resourceDatas.id = 0;
            resourceDatas.state = 0;
            resourceDatas.type = parseInt(type);
            resourceDatas.subtype = this.const.CARD_SUBTTYPE_EXPLORATION;
            if(j>0){
                list1 = list1.concat('<hr>');
            }
            list1 = list1.concat( this.getCardTooltip(resourceDatas));
            j++; 
            textSymbols = this.getCardTooltipSymbolsInfos(resourceDatas);
        });
        return `
        <div class="expandable">
            <div id="goa_ExplorationCardsHelp_toggle" class="expandabletoggle expandablearrow" onclick="gameui.GameModule.onToggleExpandable(this)">
                <div class="icon20 icon20_expand"></div>
                <h1 class='goa_ExplorationCardsHelp_title'>${ this.bga.gameui.format_string( _("Exploration Cards (${n})"), {n:j} ) }</h1>
                <div class="goa_expand_ellipsis">...</div>
            </div>
            <div class='goa_ExplorationCardsHelp goa_expandablecontent goa_expand_on'>
                ${textSymbols}
                <hr>
                ${list1}
            </div>
        </div>
        `;
    }

    async animateGainMoney(player_id, amount) {
        let toPlayer = player_id;
        let toElement = document.getElementById(`goa_reserve_${toPlayer}_money`);
        let fromElement = document.getElementById(`goa_money_stacks`);

        const floatingPieceAnimationSettings = {
            duration: 1000,
            ignoreScale: false,
            ignoreRotation: true,
        };
        let arrayAnim = null;
        if(amount<0){
            //Reverse dir (spend money)
            arrayAnim = new Array(-amount);
            let toElement2 = fromElement;
            fromElement = toElement;
            toElement = toElement2;
        }
        else {
            arrayAnim = new Array(amount);
        }
        let animations = arrayAnim.fill(0).map(index => (() => {
            this.animationManager.slideFloatingElement(this.createCoin(), fromElement, toElement, floatingPieceAnimationSettings)
        }));
        await this.animationManager.playInterval(animations);

        let currentPlayer = this.bga.players.getCurrentPlayer();
        if (currentPlayer && toPlayer == currentPlayer.id){
            this._counters[toPlayer].money.incValue(amount);
        }

    }
    /**
     * Create a coin div to animate as a temporary piece (for example for scoring animation).
     */
    createCoin() {
        return this.createTempElement(this.formatIcon('money', _('Pardaos')));
    }
    createTempElement(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        div.style.pointerEvents = 'all';
        return div;
    }
}
