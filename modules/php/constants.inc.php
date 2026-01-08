<?php
 
const BGA_GAMESTATE_GAMEVERSION = 300;

/*
 * Game Constants
 */
  
/////////////////////////////////////////////////////////
//          CARDS
/////////////////////////////////////////////////////////

const CARD_LOCATION_DISCARD = 'discard';
const CARD_LOCATION_DECK = 'deck';
const CARD_LOCATION_BOARD = 'board';
const CARD_LOCATION_HAND = 'hand';

/////////////////////////////////////////////////////////
//          MEEPLES
/////////////////////////////////////////////////////////
const TOKEN_LOCATION_DECK = 'deck';
const TOKEN_LOCATION_BOARD = 'board';
const TOKEN_LOCATION_PLAYER_BOARD = 'p_board';

const TOKEN_SUBTTYPE_PROGRESS_MARKER = 1;
const TOKEN_SUBTTYPE_AUCTION_MARKER = 2;
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
 