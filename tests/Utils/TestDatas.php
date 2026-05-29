<?php

declare(strict_types=1);

namespace Tests\Utils;

class TestDatas {
    static int $test_activePlayerId = 1;
    static int $lastInsertedId = 1;
    static array $test_players = [
    ];
    public static function resetPlayers(){
        TestDatas::$test_players = [
           1 => ['player_id' => 1, 'money' => 87 ,'ships' => 12 ,'player_name' => 'Player_1', 'player_score' => 19, 'contracts' => 1, 'compasses' => 0, 'nbActions' => 0, 'passed' => 0, 'actionPlayed' => 0, 'cardsPlayed' => '[]','result_associative_index' => 1, ],
           2 => ['player_id' => 2, 'money' => 88 ,'ships' => 15 ,'player_name' => 'Player_2', 'player_score' => 27, 'contracts' => 1, 'compasses' => 0, 'nbActions' => 0, 'passed' => 0, 'actionPlayed' => 0, 'cardsPlayed' => '[]','result_associative_index' => 2, ],
        ];
    }
    
    static array $tokens = [
    ];
    public static function resetTokens(){
        TestDatas::$tokens = [
            1 => ['token_id' => 1, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER, 'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,'token_row' => 3, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 1,  ],
            2 => ['token_id' => 2, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_1, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 2,  ],
            3 => ['token_id' => 3, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_2, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 3,  'player_id' => 1,  'result_associative_index' => 3,  ],
            4 => ['token_id' => 4, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_3, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => 1,  'result_associative_index' => 4,  ],

            5 => ['token_id' => 5, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_AUTOMA_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_1, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => AUTOMA_PLAYER_ID,  'result_associative_index' => 5,  ],
            6 => ['token_id' => 6, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_2, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 1, 'token_col' => 1,  'player_id' => AUTOMA_PLAYER_ID,  'result_associative_index' => 6,  ],
            7 => ['token_id' => 7, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_AUTOMA_BOARD,'type' => TOKEN_TYPE_AUCTION_MARKER_3, 'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => AUTOMA_PLAYER_ID,  'result_associative_index' => 7,  ],

            11 => ['token_id' => 11, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_SHIPS, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 4, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 11,  ],
            12 => ['token_id' => 12, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_HARVEST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 2, 'token_col' => 2,  'player_id' => 1,  'result_associative_index' => 12,  ],
            13 => ['token_id' => 13, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_TAXES, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 3,  'player_id' => 1,  'result_associative_index' => 13,  ],
            14 => ['token_id' => 14, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_EXP, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => 1,  'result_associative_index' => 14,  ],
            15 => ['token_id' => 15, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 5,  'player_id' => 1,  'result_associative_index' => 15,  ],
                
            16 =>   ['token_id' => 16, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_SHIPS, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 16,  ],
            17 =>   ['token_id' => 17, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_HARVEST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 2,  'player_id' => 2,  'result_associative_index' => 17,  ],
            18 =>   ['token_id' => 18, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_TAXES, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 3,  'player_id' => 2,  'result_associative_index' => 18,  ],
            19 =>   ['token_id' => 19, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_EXP, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 2, 'token_col' => 4,  'player_id' => 2,  'result_associative_index' => 19,  ],
            20 =>   ['token_id' => 20, 'token_state' => 0, 'token_location'=>TOKEN_LOCATION_PLAYER_BOARD,'type' => TOKEN_TYPE_PROGRESS_MARKER_OUTPOST, 'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,'token_row' => 1, 'token_col' => 5,  'player_id' => 2,  'result_associative_index' => 20,  ],

            21 =>   ['token_id' => 21, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'1' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 21,  ],
            22 =>   ['token_id' => 22, 'token_state' => 2, 'token_location'=> TOKEN_LOCATION_TILE.'2' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 2,  'player_id' => 2,  'result_associative_index' => 22,  ],
            23 =>   ['token_id' => 23, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'3' ,'type' => TOKEN_TYPE_SPICE_PINK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 2,  'result_associative_index' => 23,  ],
            
            24 =>   ['token_id' => 24, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'4' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 24,  ],
            25 =>   ['token_id' => 25, 'token_state' => 2, 'token_location'=> TOKEN_LOCATION_TILE.'5' ,'type' => TOKEN_TYPE_SPICE_GREEN, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' => 25,  ],
            26 =>   ['token_id' => 26, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_PINK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  26,  ],
            27 =>   ['token_id' => 27, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_WHITE, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  27,  ],
            28 =>   ['token_id' => 28, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_WHITE, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  28,  ],
            29 =>   ['token_id' => 29, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_YELLOW, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  29,  ],
            30 =>   ['token_id' => 30, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_BLACK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  30,  ],
            31 =>   ['token_id' => 31, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_BLACK, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  31,  ],
            32 =>   ['token_id' => 32, 'token_state' => 1, 'token_location'=> TOKEN_LOCATION_TILE.'6' ,'type' => TOKEN_TYPE_SPICE_WHITE, 'subtype' => TOKEN_SUBTTYPE_SPICE,'token_row' => 1, 'token_col' => 1,  'player_id' => 1,  'result_associative_index' =>  32,  ],
                
        ];
    }

    static array $cards = [
    ];

    public static function resetCards(){
        TestDatas::$cards = [
           1 => ['result_associative_index' => 1,'card_id' => 1, 'card_location' => CARD_LOCATION_HAND, 'card_state' => 0, 'player_id' => 1, 'type' => 10, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],
           2 => ['result_associative_index' => 2,'card_id' => 2, 'card_location' => CARD_LOCATION_HAND, 'card_state' => 0, 'player_id' => 2, 'type' => 11, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],
           3 => ['result_associative_index' => 3,'card_id' => 3, 'card_location' => CARD_LOCATION_HAND, 'card_state' => 0, 'player_id' => 2, 'type' => 11, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],
           
           50 => ['result_associative_index' => 50,'card_id' => 50, 'card_location' => CARD_LOCATION_EXP_DECK, 'card_state' => 0, 'player_id' => null, 'type' => 12, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],
           51 => ['result_associative_index' => 51,'card_id' => 51, 'card_location' => CARD_LOCATION_EXP_DECK, 'card_state' => 1, 'player_id' => null, 'type' => 13, 'subtype' => CARD_SUBTTYPE_EXPLORATION,],

           100 => ['result_associative_index' => 100,'card_id' => 100, 'card_location' => CARD_LOCATION_AUTOMA_AUCTION_DAY_1_DECK, 'card_state' => 0, 'player_id' => AUTOMA_PLAYER_ID, 'type' => 1, 'subtype' => CARD_SUBTTYPE_AUTOMA_AUCTION,],

           200 => ['result_associative_index' => 200,'card_id' => 200, 'card_location' => CARD_LOCATION_AUTOMA_MONEY_DECK, 'card_state' => 0, 'player_id' => AUTOMA_PLAYER_ID, 'type' => 1, 'subtype' => CARD_SUBTTYPE_MONEY,],
        ];
    }

    static array $tiles = [
    ];
    public static function resetTiles(){
        TestDatas::$tiles = [
           1 => ['result_associative_index' => 1,'tile_id' => 1, 'tile_location' => TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOLLAM], 'tile_state' => 0, 'player_id' => null, 'type' => 1, 'subtype' => TILE_SUBTTYPE_OUTPOST, 'tile_start_location' => null , 'tile_row' => 1 , 'tile_col' => 1],
        ];
    }
    
    static array $effects = [
    ];
    public static function resetEffects(){
        TestDatas::$effects = [
            //SELECT effect_id AS `result_associative_index` , `effect_id` , `effect_location` , `effect_state` , `player_id` , `effect_parent_id` , `effect_type` , `effect_qty` , `effect_source_tile` , `effect_source_action` , `effect_result` FROM `effects` WHERE (`effect_id` IN ('1'))
           1 => ['result_associative_index' => 1,'effect_id' => 1, 'effect_location' => EFFECT_LOCATION_TO_RESOLVE, 'effect_state' => 0, 'player_id' => null, 'effect_parent_id' => null, 'effect_type' => 1, 'effect_qty' => 1, 'effect_source_tile' => null , 'effect_source_action' => null , 'effect_result' => '[]'],
        ];
    }

    static array $stats = [];
    public static function resetStats(){
        TestDatas::$stats = [
        ];
    }
    
    static array $logs = [];
    public static function resetLogs(){
        TestDatas::$logs = [
            
        ];
    }
}
