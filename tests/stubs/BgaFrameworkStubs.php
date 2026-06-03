<?php

declare(strict_types=1);

namespace Bga\GameFramework;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Models\CardLocation;
use Exception;
use Tests\Utils\TestDatas;

class UserException extends \Exception
{
    /**
     * @param string|NotificationMessage $message Error message to be surrounded by `clienttranslate`, with optional arguments.
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

enum StateType: string
{
    case ACTIVE_PLAYER = 'activeplayer';
    case MULTIPLE_ACTIVE_PLAYER = 'multipleactiveplayer';
    case PRIVATE = 'private';
    case GAME = 'game';
    case MANAGER = 'manager';
}

class GamestateMachine
{
    public function changeActivePlayer(int $playerId): void
    {
    }
}

#[\AllowDynamicProperties]
class GameMock extends Game {

    public function __construct()
    {
        parent::__construct();
        $this->gamestate = new GamestateMachineMock();
        $this->notify = new Notify($this);
        $this->bga = new Bga($this);
        //DB_Manager::startLog();
        
        TestDatas::resetPlayers();
        TestDatas::resetCards();
        TestDatas::resetTiles();
        TestDatas::resetTokens();
        TestDatas::resetEffects();
        TestDatas::resetStats();
        TestDatas::resetLogs();
        
    }

    public function getStatTypes(){
        $stats = json_decode('{
                "table": {

                },
                "player": {
                "score": {
                    "id": 11,
                    "name": "Score",
                    "type": "int"
                },
                "merchantRank": {
                    "id": 12,
                    "name": "Merchant rank",
                    "type": "int"
                },
                "turnOrder": {
                    "id": 13,
                    "name": "Turn order",
                    "type": "int"
                }
                
                ,"totalMoney": {
                    "id": 15,
                    "name": "Total Pardaos coins",
                    "type": "int"
                }
                ,"totalShips": {
                    "id": 16,
                    "name": "Total Ships",
                    "type": "int"
                }
                ,"totalContracts": {
                    "id": 17,
                    "name": "Total Contracts",
                    "type": "int"
                }
                ,"totalCompasses": {
                    "id": 18,
                    "name": "Total Compasses",
                    "type": "int"
                }
                
                ,"progressShips": {
                    "id": 21,
                    "name": "Progress row : Build Ships",
                    "type": "int"
                }
                ,"progressHarvest": {
                    "id": 22,
                    "name": "Progress row : Produce",
                    "type": "int"
                }
                ,"progressTaxes": {
                    "id": 23,
                    "name": "Progress row : Collect Taxes",
                    "type": "int"
                }
                ,"progressExploration": {
                    "id": 24,
                    "name": "Progress row : Explore",
                    "type": "int"
                }
                ,"progressFound": {
                    "id": 25,
                    "name": "Progress row : Establish an Outpost",
                    "type": "int"
                }
                
                ,"auctionsWon": {
                    "id": 30,
                    "name": "Auctions won",
                    "type": "int"
                }
                
                ,"playedPlantations": {
                    "id": 31,
                    "name": "Played depots",
                    "type": "int"
                }
                ,"playedOutposts": {
                    "id": 32,
                    "name": "Played outposts",
                    "type": "int"
                }
                
                ,"receivedSpices": {
                    "id": 33,
                    "name": "Received goods on depots/outposts fields",
                    "type": "int"
                }
                
                ,"playedTiles": {
                    "id": 34,
                    "name": "Played other tiles",
                    "type": "int"
                }
                
                ,"playedExplorations": {
                    "id": 35,
                    "name": "Played Exploration cards",
                    "type": "int"
                }

                ,"actionA": {
                    "id": 40,
                    "name": "Progress",
                    "type": "int"
                }
                ,"actionB": {
                    "id": 41,
                    "name": "Build Ships",
                    "type": "int"
                }
                ,"actionC": {
                    "id": 42,
                    "name": "Produce",
                    "type": "int"
                }
                ,"actionD": {
                    "id": 43,
                    "name": "Collect Taxes",
                    "type": "int"
                }
                ,"actionE": {
                    "id": 44,
                    "name": "Explore",
                    "type": "int"
                }
                ,"actionF": {
                    "id": 45,
                    "name": "Establish an Outpost",
                    "type": "int"
                }
                
                },
                "value_labels": { 
                }
            }', true);
        //$this->dump("StatsTypes : ", $stats);
        return $stats;
    }
}

class GamestateMachineMock extends \Bga\GameFramework\GamestateMachine
{
    public function getCurrentMainStateId(): ?int {
        return ST_PLAYER_TURN;
    }
    
}

abstract class Table
{
    public function __construct()
    {
    }

    public function error(string $message): void
    {
        logForTests($message, "ERROR");
    }
    
    public function dump(string $message, mixed $object): void
    {
        logForTests($message, "DUMP");
        logForTests($object, "DUMP");
    }
    final public function debug(string $message): void
    {
        logForTests($message, "DEBUG");
    }
    final public function trace(string $message): void
    {
        logForTests($message, "TRACE");
    }
    protected function initGameStateLabels(array $stateLabels): void
    {
    }
    public function getGameStateValue(string $label, ?int $default = null): int|string
    {
        switch($label){
            case 'logging' : return 0;
        }
        return '0';
    }

    public function activeNextPlayer(): int|string
    {
        TestDatas::$test_activePlayerId = 2;
        return TestDatas::$test_activePlayerId;
    }

    public function getPlayerAfter(int $playerId): int
    {
        return 0;
    }

    public function giveExtraTime(int $playerId, ?int $specificTime = null): void
    {
    }

    public static function getUniqueValueFromDb(string $sql): mixed
    {
        logForTests("getUniqueValueFromDb: $sql");
        if (preg_match("/^SELECT COUNT\(\*\) FROM `tokens` WHERE `subtype` = (?P<subtype>.*) AND \(`token_row` IN \((?P<token_rows>.*)\)\)$/", $sql, $matches) == 1) {
            $token_rows = explode(',', str_replace("'","",$matches['token_rows']) );
            $subtype = $matches['subtype'];
            $count = count(array_filter(TestDatas::$tokens,function ($token) use($subtype, $token_rows ) {return $token['subtype'] == $subtype && in_array($token['token_row'], $token_rows );}));
            logForTests("getUniqueValueFromDb: count tokens is $count for subtype $subtype and rows ".json_encode($token_rows));
            return $count;
        }
        if (preg_match("/^SELECT COUNT\(\*\) FROM `tokens` WHERE `subtype` = (?P<subtype>.*) AND \(`token_row` IN \((?P<token_rows>.*)\)\) AND `token_col` = (?P<token_col>.*)$/", $sql, $matches) == 1) {
            $token_rows = explode(',', str_replace("'","",$matches['token_rows']) );
            $subtype = $matches['subtype'];
            $token_col = intval($matches['token_col']);
            $count = count(array_filter(TestDatas::$tokens,function ($token) use($subtype, $token_rows, $token_col ) {return $token['subtype'] == $subtype && $token['token_col'] == $token_col && in_array($token['token_row'], $token_rows );}));
            logForTests("getUniqueValueFromDb: count tokens is $count for subtype $subtype and rows ".json_encode($token_rows));
            return $count;
        }
        if (preg_match("/^SELECT COUNT\(\*\) FROM `cards` WHERE `subType` = (?P<subtype>.*) AND `card_location` = '(?P<card_location>.*)'$/", $sql, $matches) == 1) {
            $subtype = $matches['subtype'];
            $card_location = $matches['card_location'];
            $count = count(array_filter(TestDatas::$cards,function ($card) use( $card_location, $subtype ) {return $card['card_location'] == $card_location && $card['subtype'] == $subtype ;}));
            logForTests("getUniqueValueFromDb: count is $count  ");
            return $count;
        }
        logForTests("getUniqueValueFromDb: /?\ ");
        return null;
    }

    public function getCollectionFromDb(string $sql, bool $singleColumn = false): array
    {
        logForTests("getCollectionFromDb: $sql");
        logForTests(" /?\ __ don't know what to return for this request __ /?\ [$sql] ");
        return [];
    }
    public static function getObjectListFromDB(string $sql, bool $bUniqueValue = false): array
    {
        logForTests("getObjectListFromDB: $sql");
        $tokens = TestDatas::$tokens;
        $cards = TestDatas::$cards;
        $tiles = TestDatas::$tiles;
        
        $effects = TestDatas::$effects;
        
        switch($sql){
            case 'SELECT name AS `result_associative_index` , `value` , `name` FROM `my_global_variables`':
                logForTests("MOCK select globals");
                return [
                    ['name' => 'session', 'value' => 1, 'result_associative_index' => 'session'],
                    ['name' => 'round', 'value' => 1, 'result_associative_index' => 'round'],
                    ['name' => 'roundStep', 'value' => 1, 'result_associative_index' => 'roundStep'],
                    ['name' => 'endScoring', 'value' => '[]', 'result_associative_index' => 'endScoring'],
                    ['name' => 'automaColor', 'value' => '000000', 'result_associative_index' => 'automaColor'],
                    ['name' => 'automaActive', 'value' => false, 'result_associative_index' => 'automaActive'],
                    ['name' => 'automaNbActions', 'value' => 0, 'result_associative_index' => 'automaNbActions'],
                    ['name' => 'automaPassed', 'value' => false, 'result_associative_index' => 'automaPassed'],
                    ['name' => 'automaActionPlayed', 'value' => false, 'result_associative_index' => 'automaActionPlayed'],
                ];
            case "SELECT value,name FROM `my_global_variables` WHERE  `name` = 'automaColor'":
                return [
                    ['name' => 'automaColor', 'value' => 'aaa', 'result_associative_index' => 'automaColor'],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = ".TOKEN_TYPE_FIRSTAUCTION_MARKER." AND `subType` = ".TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER:
                logForTests("MOCK select first  token");
                return [
                    $tokens[1],
                ];
            //case "SELECT token_id AS `result_associative_index` , `token_id` , `token_location` , `token_state` , `player_id` , `type` , `subtype` , `token_row` , `token_col` FROM `tokens` WHERE (`token_id` IN ('1'))":
            //    return [
            //        $tokens[1],
            //    ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 1 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[11] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 2 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[12] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 3 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[13] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 4 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[14] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 5 AND `subtype` = 1 AND `player_id` = 1":
                return [ $tokens[15] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 1 AND `player_id` = 1":
                $result = [
                    $tokens[11],
                    $tokens[12],
                    $tokens[13],
                    $tokens[14],
                    $tokens[15],
                ];
                logForTests("MOCK select player 1 progress tokens ".json_encode($result));
                return $result;
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 1 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[16] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 2 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[17] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 3 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[18] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 4 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[19] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `type` = 5 AND `subtype` = 1 AND `player_id` = 2":
                return [ $tokens[20] ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 1 AND `player_id` = 2":
                logForTests("MOCK select player 2 progress tokens ");
                return [
                    $tokens[16],
                    $tokens[17],
                    $tokens[18],
                    $tokens[19],
                    $tokens[20],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 4 AND `player_id` = 1":
                logForTests("MOCK select spice tokens player 1 ");
                return [
                    $tokens[24],
                    $tokens[25],
                    $tokens[26],
                    $tokens[27],
                    $tokens[28],
                    $tokens[29],
                    $tokens[30],
                    $tokens[31],
                    $tokens[32],
                ];
            case "SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subtype` = 4 AND `player_id` = 2":
                logForTests("MOCK select spice tokens player 2 ");
                return [
                    $tokens[21],
                    $tokens[22],
                    $tokens[23],
                ];
            case 'SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = 1 LIMIT 1': 
                logForTests("MOCK select  player one ");
                return [
                    TestDatas::$test_players[1],
                    ];
            case "SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = '0' LIMIT 1":
            case 'SELECT *, player_id AS `result_associative_index` FROM `player` WHERE  `player_id` = 2 LIMIT 1': 
                logForTests("MOCK select  player two ");
                return [
                    TestDatas::$test_players[2],
                    ];
            case 'SELECT player_score,player_id FROM `player` WHERE `player_id` = 2':
                return [
                    TestDatas::$test_players[2],
                ];
            case 'SELECT stats_value,stats_id FROM `stats` WHERE `stats_type` = 11 AND `stats_player_id` = 2':
                return [
                    ['stats_id' => 1, 'stats_value' => 27 ],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_id` IN ('1'))":
                return [
                    $cards[1],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_id` IN ('2'))":
                return [
                    $cards[2],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_id` IN ('3'))":
                return [
                    $cards[3],
                ];
            case "SELECT card_id AS `result_associative_index` , `card_id` , `card_location` , `card_state` , `player_id` , `type` , `subtype` FROM `cards` WHERE (`card_location` = '".CardLocation::AUTOMA_AUCTION_DAY_1_DECK->value."') ORDER BY card_state DESC LIMIT 1":
                return [
                    $cards[100],
                ];
            case "SELECT *, card_id AS `result_associative_index` FROM `cards` WHERE `subType` = 7 AND `card_location` = '".CardLocation::AUTOMA_MONEY_DECK->value."'":
                return [
                    $cards[200],
                ];
            case "SELECT player_score,player_id FROM `player` WHERE `player_id` = 1":
            case "SELECT player_id AS `result_associative_index` , `player_score` FROM `player` WHERE `player_id` = 1":
                return[
                    TestDatas::$test_players[1],
                ];
            case "SELECT player_score,player_id FROM `player` WHERE `player_id` = 2":
            case "SELECT player_id AS `result_associative_index` , `player_score` FROM `player` WHERE `player_id` = 2":
                return[
                    TestDatas::$test_players[2],
                ];
            case "SELECT tile_id AS `result_associative_index` , `tile_id` , `tile_location` , `tile_state` , `player_id` , `type` , `subtype` , `tile_start_location` , `tile_row` , `tile_col` FROM `tiles` WHERE (`tile_id` IN ('1'))":
                return [
                        $tiles[1],
                    ];
            case "SELECT effect_id AS `result_associative_index` , `effect_id` , `effect_location` , `effect_state` , `player_id` , `effect_parent_id` , `effect_type` , `effect_qty` , `effect_source_tile` , `effect_source_action` , `effect_result` FROM `effects` WHERE (`effect_id` IN ('1'))":
                return [
                        $effects[1],
                    ];
            //case "SELECT *, card_id AS `result_associative_index` FROM `cards` WHERE `subType` = 1 AND //`card_location` = 'hand' AND `player_id` = 1":
            //    $subType = 1;
            //    $card_location = 'hand';
            //    $player_id = 1;
            //    $filtered = array_filter(TestDatas::$cards,function ($card) use ($card_location, $subType,//$player_id   ){return $card['card_location'] == $card_location && $card['subtype'] == //$subType && $card['player_id'] == $player_id ;});
            //    $ncards = count($filtered);
            //    logForTests("MOCK direct filtered cards $ncards : ".json_encode($filtered));
            //    return $filtered;
        }
        if (preg_match("/^SELECT .* FROM `cards` WHERE `subType` = (?P<subtype>.*) AND `card_location` = '(?P<card_location>.*)' AND `player_id` = (?P<player_id>.*)$/", $sql, $matches) == 1) {
            $subType = intval($matches['subtype']);
            $card_location = $matches['card_location'];
            $player_id = intval($matches['player_id']);
            $filtered = array_filter(TestDatas::$cards,function ($card) use ($card_location, $subType,$player_id   ){return $card['card_location'] == $card_location && $card['subtype'] == $subType && $card['player_id'] == $player_id ;});
            $ncards = count($filtered);
            logForTests("MOCK select filtered cards $ncards : ".json_encode($filtered));
            return $filtered;
        }
        if (preg_match("/^SELECT .* FROM `cards` WHERE \(`card_location` = '(?P<card_location>.*)'\)$/", $sql, $matches) == 1) {
            $card_location = $matches['card_location'];
            $filtered = array_filter(TestDatas::$cards,function ($card) use ($card_location, ){return $card['card_location'] == $card_location ;});
            return $filtered;
        }
        
        if (preg_match("/^SELECT .* FROM `cards` WHERE \(`card_location` = '(?P<card_location>.*)'\)( ORDER BY card_state (ASC|DESC))?( LIMIT (?P<limit>\d+))?$/", $sql, $matches) == 1) {
            //TODO SORT card_state
            $card_location = $matches['card_location'];
            $limit = array_key_exists('limit',$matches) ? intval($matches['limit']) : null;
            $filtered = array_filter(TestDatas::$cards,function ($card) use ($card_location,){return  $card['card_location'] == $card_location;});
            if(isset($limit)) $filtered = array_slice($filtered, 0, $limit, true);
            return $filtered;
        }
        if (preg_match("/^SELECT (.*) FROM `cards` WHERE \(`card_id` IN \((?P<card_ids>.*)\)\)$/", $sql, $matches) == 1) {
            $filtered = [];
            $card_ids = explode(',',str_replace("'","",$matches['card_ids']));
            foreach($card_ids as $cardIdString){
                $card_id = intval($cardIdString);
                if(!array_key_exists($card_id,TestDatas::$cards)) continue;
                $filtered[] = TestDatas::$cards[$card_id];
            }
            logForTests("MOCK select cards with ids ".json_encode($card_ids).": ".json_encode($filtered));
            return $filtered;
        }
        if( str_starts_with( $sql, 'SELECT *, player_id AS `result_associative_index` FROM `player`' )){
            logForTests("MOCK select players");
            return [
                TestDatas::$test_players[1],
                TestDatas::$test_players[2],
            ];
        }
        if( str_starts_with( $sql,'SELECT *, token_id AS `result_associative_index` FROM `tokens` WHERE `subType` = '.TOKEN_SUBTTYPE_AUCTION_MARKER)){
            logForTests("MOCK select token to auction ");
            return [
                    $tokens[2],
                    $tokens[3],
                ];
        }
        //if( str_starts_with( $sql,'SELECT *, tile_id AS `result_associative_index` FROM `tiles` WHERE `subtype` = '.TILE_SUBTTYPE_OUTPOST)){
        //    logForTests("MOCK select tiles ");
        //    return [
        //            $tiles[1],
        //        ];
        //}

        logForTests(" /?\ __ don't know what to return for this request __ /?\ ");
        return [];
    }

    public function getCurrentPlayerId(bool $bReturnNullIfNotLogged = false): string|int
    {
        return TestDatas::$test_activePlayerId; 
    }
    public function getDoubleKeyCollectionFromDB(string $sql, bool $nullIfEmpty = false): array
    {
        logForTests("getDoubleKeyCollectionFromDB: $sql");
        logForTests(" /?\ __ don't know what to return for this request __ /?\ [$sql] ");
        return [];
    }
    public static function DbAffectedRow(): int
    {
        return 1;
    }
    public static function DbGetLastId(): int
    {
        return 1;
    }
    public static function DbQuery(string $sql): null|\mysqli_result|bool
    {
        logForTests("DbQuery: $sql");
        if (preg_match("/^UPDATE `tokens` SET `token_row` = '(?P<token_row>\d+)' WHERE  `token_id` = (?P<token_id>\d+)$/", $sql, $matches) == 1) {
            $token_row = intval($matches['token_row']);
            $token_id = $matches['token_id'];
            logForTests("DbQuery --- updated row for token $token_id : $token_row");
            TestDatas::$tokens[$token_id]['token_row'] = ($token_row);
            return true;
        }
        if (preg_match("/^UPDATE `cards` SET `card_location` = '(?P<card_location>.*)',`card_state` = '(?P<card_state>.*)' WHERE \(`card_id` IN \((?P<card_ids>.*)\)\)$/", $sql, $matches) == 1) {
            $card_location = $matches['card_location'];
            $card_state = $matches['card_state'];
            $card_ids = explode(',', str_replace("'","",$matches['card_ids']));
            foreach($card_ids as $cardIdString){
                $card_id = intval($cardIdString);
                if(!array_key_exists($card_id,TestDatas::$cards)) continue;
                logForTests("DbQuery --- updated card_location, card_state for card $card_id : $card_location, $card_state");
                TestDatas::$cards[$card_id]['card_location'] = $card_location;
                TestDatas::$cards[$card_id]['card_state'] = intval($card_state);
            }
            return true;
        }
        if (preg_match("/^UPDATE `cards` SET `card_location` = '(?P<card_location>.*)',`card_state` = '(?P<card_state>.*)' WHERE \(`card_location` = '(?P<from_location>.*)'\)$/", $sql, $matches) == 1) {
            $card_location = $matches['card_location'];
            $from_location = $matches['from_location'];
            $card_state = $matches['card_state'];
            foreach(TestDatas::$cards as $card_id => &$card){
                if($card['card_location'] != $from_location) continue;
                logForTests("DbQuery --- updated card_location, card_state for card $card_id : $card_location, $card_state");
                $card['card_location'] = $card_location;
                $card['card_state'] = intval($card_state);
            }
            return true;
        }
        if (preg_match("/^UPDATE `cards` SET `player_id` = '(?P<player_id>.*)' WHERE  `card_id` = (?P<card_id>\d+)$/", $sql, $matches) == 1) {
            $player_id = $matches['player_id'];
            $card_id = $matches['card_id'];
            logForTests("DbQuery --- updated player_id for card $card_id : $player_id");
            TestDatas::$cards[$card_id]['player_id'] = intval($player_id);
            return true;
        }
        if (preg_match("/^UPDATE `player` SET `contracts` = '+(?P<contracts>.*)'+ WHERE (\s*)`player_id` = (?P<pid>[\d|\-]+)$/", $sql, $matches) == 1) {
            $contracts = intval($matches['contracts']);
            $pid = $matches['pid'];
            logForTests("DbQuery --- set contracts for player $pid ... $contracts");
            TestDatas::$test_players[$pid]['contracts'] = ($contracts);
            return true;
        }
        if (preg_match("/^UPDATE `player` SET `ships` = '+(?P<ships>.*)'+ WHERE (\s*)`player_id` = (?P<pid>[\d|\-]+)$/", $sql, $matches) == 1) {
            $ships = intval($matches['ships']);
            $pid = $matches['pid'];
            logForTests("DbQuery --- set ships for player $pid ... $ships");
            TestDatas::$test_players[$pid]['ships'] = ($ships);
            return true;
        }
        if (preg_match("/^UPDATE `player` SET `player_score` = '+(?P<player_score>.*)'+ WHERE (\s*)`player_id` = (?P<pid>[\d|\-]+)$/", $sql, $matches) == 1) {
            $player_score = intval($matches['player_score']);
            $pid = $matches['pid'];
            logForTests("DbQuery --- set player_score for player $pid ... $player_score");
            TestDatas::$test_players[$pid]['player_score'] = ($player_score);
            return true;
        }
        if (preg_match("/^UPDATE `player` SET `player_score` = `player_score` \+ (?P<player_score>.*) WHERE (\s*)`player_id` = (?P<pid>[\d|\-]+)$/", $sql, $matches) == 1) {
            $player_score = $matches['player_score'];
            $pid = $matches['pid'];
            logForTests("DbQuery --- INC player_score for player $pid ... '$player_score'");
            TestDatas::$test_players[$pid]['player_score'] += intval($player_score);
            return true;
        }
        logForTests("DbQuery --- nothing done");
        return null;
    }
    public function getNextPlayerTable(): array
    {
        return [ 1 => 2, 2 => 1, 0 => 1,];
    }
    public function getActivePlayerId(): string|int
    {
        return TestDatas::$test_activePlayerId; 
    }
}
 class TableOptions {
    public function get(int $optionId): ?int {
        switch($optionId){
            case BGA_GAMESTATE_GAMEVERSION: return 999999;
        }
        return 0;
    }

    function isTurnBased(): bool {
        return false;
    }
    function isRealTime(): bool {
        return false;
    }
}
class Bga {
        public Db\Globals $globals;
        public Notify $notify;
        public Legacy $legacy;
        public Tournament $tournament;
        public TableOptions $tableOptions;
        public UserPreferences $userPreferences;
        public TableStats $tableStats;
        public PlayerStats $playerStats;
        public Components\DeckFactory $deckFactory;
        public Components\Counters\CounterFactory $counterFactory;
        public Debug $debug;
        
        public Components\Counters\PlayerCounter $playerScore;
        public Components\Counters\PlayerCounter $playerScoreAux;
            
        public function __construct(
            $game,
        ) {
            $this->tableOptions = new TableOptions();
        }
    }
class Notify {
    public function player(int $playerId, string $notifName, string $message = '', array $args = []): void {
        logForTests("Notify_player $playerId $notifName : $message, with args ".json_encode($args)."","NOTIF");
    }
    public function all(string $notifName, string $message = '', array $args = []): void {
        logForTests("Notify_ALL $notifName : $message, with args ".json_encode($args)."","NOTIF");
    }
    public function __construct(
        $game,
    ) {
    }
}
class NotificationMessage {
    public function __construct(
        public string $message = '',
        public array $args = [],
    ) {}
}

namespace Bga\GameFramework\States;

use Bga\GameFramework\StateType;

#[\Attribute]
class PossibleAction
{
}

abstract class GameState
{
    public function __construct(
        $game,
        public int $id,
        public StateType $type,
        public ?string $name = null,
        public string $description = '',
        public string $descriptionMyTurn = '',
        public array $transitions = [],
        public bool $updateGameProgression = false,
        public ?int $initialPrivate = null,
    ) {
    }
}
class GameStateInMem extends GameState {}
