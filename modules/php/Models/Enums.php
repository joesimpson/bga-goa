<?php

namespace Bga\Games\Goa\Models;

enum CardLocation: string
{
  case EXP_DISCARD   = CARD_LOCATION_EXP_DISCARD;
  case EXP_DECK      = CARD_LOCATION_EXP_DECK;
  case BOARD         = CARD_LOCATION_BOARD;
  case HAND          = CARD_LOCATION_HAND;
  
  case AUTOMA_AUCTION_DAY_1_DECK  = '5_deck_1_1';
  case AUTOMA_AUCTION_DAY_1_BOARD = '5_board_1_1';
  case AUTOMA_AUCTION_DAY_2_DECK  = '5_deck_1_2';
  case AUTOMA_AUCTION_DAY_2_BOARD = '5_board_1_2';
  case AUTOMA_AUCTION_DAY_3_DECK  = '5_deck_1_3';
  case AUTOMA_AUCTION_DAY_3_BOARD = '5_board_1_3';
  
  case AUTOMA_AUCTION_NIGHT_1_DECK  = '5_deck_2_1';
  case AUTOMA_AUCTION_NIGHT_1_BOARD = '5_board_2_1';
  case AUTOMA_AUCTION_NIGHT_2_DECK  = '5_deck_2_2';
  case AUTOMA_AUCTION_NIGHT_2_BOARD = '5_board_2_2';
  case AUTOMA_AUCTION_NIGHT_3_DECK  = '5_deck_2_3';
  case AUTOMA_AUCTION_NIGHT_3_BOARD = '5_board_2_3';

  case AUTOMA_ACTION_DECK_A  = 'deck_act_a';
  case AUTOMA_ACTION_DECK_B  = 'deck_act_b';
  case AUTOMA_ACTION_BOARD = 'board_act';
}

enum MarketPlayType: int
{
    //TODO JSA reuse this enum when possible
    case DEPOT              = MARKET_TYPE_PLANTATION;
    case ACTION_REUSEABLE   = MARKET_TYPE_ACTION_REUSEABLE;
    case ACTION_DIRECT      = MARKET_TYPE_ACTION_DIRECT;
    case ACTION_ONCE        = MARKET_TYPE_ACTION_ONCE;
    case SCORING            = MARKET_TYPE_SCORING;
}

enum AutomaAuctionStartType: int
{
    case TOP_LEFT       = 1;
    case TOP_RIGHT      = 2;
    case BOTTOM_RIGHT   = 3;
    case BOTTOM_LEFT    = 4;
}
enum AutomaAuctionDirectionType: int
{
    case CLOCKWISE          = 1;
    case COUNTER_LOCKWISE   = 2;
}

enum AutomaActionType: int
{
    case GAIN_MONEY_1          = 1;
    case GAIN_MONEY_2          = 2;
    case GAIN_MONEY_5          = 3;
    case GAIN_MONEY_10         = 4;

    case NOGAIN_MONEY_2        = 6;
    case NOGAIN_MONEY_5        = 7;
    case NOGAIN_MONEY_10       = 8;

    case DISCARD_2_KOLLAM      = 9;
    case DISCARD_2_MADRAS      = 10;

}