<?php
 
const BGA_GAMESTATE_GAMEVERSION = 300;

/*
 * Game Constants
 */


const SPICE_WHITE     = 1;
const SPICE_BLACK     = 2;
const SPICE_YELLOW    = 3;
const SPICE_PINK      = 4;
const SPICE_GREEN     = 5;

const ALL_SPICES     = [
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

const TILE_LOCATION_DECK = 'deck';
const TILE_LOCATION_BOARD = 'board';
const TILE_LOCATION_PLAYER_BOARD = 'p_board';

const TILE_LOCATION_BOARD_OUTPOST = 'board_c';
const TILE_LOCATION_BOARD_OUTPOSTS = [
   COLONY_KOLLAM    => TILE_LOCATION_BOARD_OUTPOST.'1',
   COLONY_KOCHI     => TILE_LOCATION_BOARD_OUTPOST.'2',
   COLONY_MADRAS    => TILE_LOCATION_BOARD_OUTPOST.'3',
   COLONY_KOZHIKODE => TILE_LOCATION_BOARD_OUTPOST.'4',
];

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
 