<?php
 
const BGA_GAMESTATE_GAMEVERSION = 300;

/*
 * Game Constants
 */

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

const SPICE_WHITE     = 1;
const SPICE_BLACK     = 2;
const SPICE_YELLOW    = 3;
const SPICE_PINK      = 4;
const SPICE_GREEN     = 5;

const SPICES_ALL     = [
   SPICE_WHITE ,
   SPICE_BLACK ,
   SPICE_YELLOW,
   SPICE_PINK  ,
   SPICE_GREEN ,
 ];

const COLONY_KOLLAM    = 1;
const COLONY_KOCHI     = 2;
const COLONY_MADRAS    = 3;
const COLONY_KOZHIKODE = 4;

const MARKET_GRID_SIZE = 5;
 
/////////////////////////////////////////////////////////
//          CARDS
/////////////////////////////////////////////////////////

const CARD_LOCATION_DISCARD = 'discard';
const CARD_LOCATION_DECK = 'deck';
const CARD_LOCATION_BOARD = 'board';
const CARD_LOCATION_HAND = 'hand';

/////////////////////////////////////////////////////////
//          TILES
/////////////////////////////////////////////////////////

const TILE_SUBTTYPE_OUTPOST = 1;
const TILE_SUBTTYPE_MARKET = 2;

const TILE_LOCATION_DECK_A = 'deck_a';
const TILE_LOCATION_DECK_B = 'deck_b';
const TILE_LOCATION_BOARD_MARKET = 'market';
const TILE_LOCATION_PLAYER_BOARD = 'p_board';
/** For tiles we return to the game box (not the tiles we play and discard) */
const TILE_LOCATION_BOX = 'box';

const TILE_LOCATION_BOARD_OUTPOST = 'board_c';
const TILE_LOCATION_BOARD_OUTPOSTS = [
   COLONY_KOLLAM    => TILE_LOCATION_BOARD_OUTPOST.'1',
   COLONY_KOCHI     => TILE_LOCATION_BOARD_OUTPOST.'2',
   COLONY_MADRAS    => TILE_LOCATION_BOARD_OUTPOST.'3',
   COLONY_KOZHIKODE => TILE_LOCATION_BOARD_OUTPOST.'4',
];

const MARKET_TYPE_PLANTATION      = 1;
const MARKET_TYPE_ACTION_REUSEABLE = 2;
const MARKET_TYPE_RESOURCES       = 3;
const MARKET_TYPE_ACTION_DIRECT   = 4;
const MARKET_TYPE_ACTION_ONCE     = 5;
const MARKET_TYPE_SCORING         = 6;

/////////////////////////////////////////////////////////
//          MEEPLES
/////////////////////////////////////////////////////////
const TOKEN_LOCATION_DECK = 'deck';
const TOKEN_LOCATION_BOARD = 'board';
const TOKEN_LOCATION_PLAYER_BOARD = 'p_board';

const TOKEN_SUBTTYPE_PROGRESS_MARKER = 1;
const TOKEN_SUBTTYPE_AUCTION_MARKER = 2;

const TOKEN_TYPE_PROGRESS_MARKER_SHIPS    = 1;
const TOKEN_TYPE_PROGRESS_MARKER_HARVEST  = 2;
const TOKEN_TYPE_PROGRESS_MARKER_TAXES    = 3;
const TOKEN_TYPE_PROGRESS_MARKER_EXP      = 4;
const TOKEN_TYPE_PROGRESS_MARKER_OUTPOST  = 5;
const TOKEN_TYPE_AUCTION_MARKER_1 = 11;
const TOKEN_TYPE_AUCTION_MARKER_2 = 12;
const TOKEN_TYPE_AUCTION_MARKER_3 = 13;
const TOKEN_TYPE_AUCTION_MARKER_4 = 14;
const TOKEN_TYPE_AUCTION_MARKER_5 = 15;

const TOKEN_TYPE_AUCTION_MARKERS = [
   TOKEN_TYPE_AUCTION_MARKER_1,
   TOKEN_TYPE_AUCTION_MARKER_2,
   TOKEN_TYPE_AUCTION_MARKER_3,
   TOKEN_TYPE_AUCTION_MARKER_4,
   TOKEN_TYPE_AUCTION_MARKER_5,
];

const TOKEN_TYPE_PROGRESS_MARKERS = [
   TOKEN_TYPE_PROGRESS_MARKER_SHIPS,
   TOKEN_TYPE_PROGRESS_MARKER_HARVEST,
   TOKEN_TYPE_PROGRESS_MARKER_TAXES,
   TOKEN_TYPE_PROGRESS_MARKER_EXP,
   TOKEN_TYPE_PROGRESS_MARKER_OUTPOST,
];

/////////////////////////////////////////////////////////
//          Game options
/////////////////////////////////////////////////////////  



/////////////////////////////////////////////////////////
//          User preferences
/////////////////////////////////////////////////////////  


/////////////////////////////////////////////////////////
//          GAME STATES
/////////////////////////////////////////////////////////  
const ST_START_CARD = 2;

const ST_NEXT_TURN = 9;
const ST_PLAYER_TURN = 10;


const ST_CONFIRM_CHOICES = 70;
const ST_CONFIRM_TURN = 71;
const ST_END_TURN = 80;

const ST_END_SCORING = 90;
const ST_PRE_END_OF_GAME = 98;
const ST_END_GAME = 99;
 