<?php
namespace Bga\Games\Goa;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\QueryBuilder;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;

/**
 * Debugging functions to be called in BGA Studio game window
 */
trait DebugTrait
{
    
    ////////////////////////////////////////////////////
    /** 
     * RESET ALL GAME DATAS in order to restart the game without recreating a new table spoiling server space etc...
    */
    function debug_ReSetup(bool $option1 = true,bool $option2 = true,){
        Game::get()->trace("debug_ReSetup - START ////////////////////////////////////////////////////");
        $players = self::loadPlayersBasicInfos();
        $player = Players::getCurrent();
        
        $options = [
            "DEBUG_SETUP"=> true,
            //101 => ($option1 ? 2 :1) ,
            //102 => ($option2 ? 2 :1) ,
        ];
        
        Game::get()->initGameStateLabels([
            'logging' => 10,
        ]);

        $this->debug_ClearLogs();
        Log::disable();

        //CLEAR DATAS
        self::DbQuery("DELETE FROM `global` where global_id >= 10 AND global_id < 100 or global_id in( 101,102) ");
        //self::DbQuery("INSERT INTO `global` (global_id,global_value) VALUES ( 101,".$options[101]." )"); //NOT ENOUGH because of cache
        //self::DbQuery("INSERT INTO `global` (global_id,global_value) VALUES ( 102,".$options[102]." )"); //NOT ENOUGH because of cache
        //self::setGameStateValue( 'myoption1', $options[101]);
        //self::setGameStateValue( 'myoption2', $options[102]);
        
        /*
        self::DbQuery("DELETE FROM `stats` where stats_type >= 10 ");
        self::DbQuery("DELETE FROM `bga_globals`");
        self::DbQuery("DELETE FROM `player`");
        */    
        Stats::DB()->delete()->run();
        Cards::DB()->delete()->run();
        Tokens::DB()->delete()->run();
        Tiles::DB()->delete()->run();
        Globals::DB()->delete()->run();
        Players::DB()->delete()->run();

        self::DbQuery("DELETE FROM `gamelog` where gamelog_packet_id > 1");
        
        //self::DbQuery("DELETE FROM `bga_player_counters`");
        //self::DbQuery("ALTER TABLE `bga_player_counters` DROP `energy`");
        //self::DbQuery("DROP TABLE `bga_player_counters`");

        Game::get()->setupNewGame($players,$options);
        
        Log::enable();

        $players = self::loadPlayersBasicInfos(); 
        $this->debug_refreshUI();

        Game::get()->gamestate->jumpToState(ST_NEW_SESSION);//first state 
        
        Game::get()->trace("debug_ReSetup - END ////////////////////////////////////////////////////");

    }

    ////////////////////////////////////////////////////
   
    /**
     * Example of debug function.
     * Here, jump to a state you want to test (by default, jump to next player state)
     * You can trigger it on Studio using the Debug button on the right of the top bar.
     */
    public function debug_goToState(int $state = 3) {
        $this->gamestate->jumpToState($state);
    }

    /**
     * Another example of debug function, to easily test the zombie code.
     */
    public function debug_playOneMove() {
        $this->debug->playUntil(fn(int $count) => $count == 1);
    }
    
    ////////////////////////////////////////////////////
    function debug_refreshUI(){
        $player_id = Players::getCurrent()->getId();
        //Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
        //Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
        Notifications::refreshUI(Game::get()->getAllDatas($player_id));
    }
    function debug_changeRound(int $round = 1){
        Globals::setRound($round);
        $this->debug_refreshUI();
    }
    function debug_Globals(){
        Globals::setChoiceTiles([]);
        $tile = Tiles::getByType(26,TILE_SUBTTYPE_MARKET)->first();
        Globals::addChoiceTile($tile,ACTION_SHIPS_OR_CONTRACTS, 3, [ACTION_SHIP, ACTION_CONTRACT]);
        //simulate 2 choices
        Globals::addChoiceTile($tile,ACTION_SHIPS_OR_CONTRACTS, 1, [ACTION_SHIP, ACTION_CONTRACT]);
        Globals::addChoiceTile($tile,ACTION_SHIPS_OR_CONTRACTS, 2, [ACTION_SHIP, ACTION_CONTRACT]);

        //TEST removing an element of the list by index
        $allChoices = Globals::getChoiceTiles();
        unset($allChoices[1]);
        Globals::setChoiceTiles($allChoices);

        //FAVOR
        $tile = Tiles::getByType(30,TILE_SUBTTYPE_MARKET)->first();
        $tilesToChoose = Tiles::getTilesForFavor()
            ->merge(Tiles::getByType(2,TILE_SUBTTYPE_MARKET))//+ Test chaining with SPICES_ALL
            ->map(function ($tile) {
                return $tile->getType();
            })->toAssoc();
        
        Globals::addChoiceTile($tile,ACTION_FAVOR, 1, $tilesToChoose,CHOICE_PICK_TILE);
        

        Game::get()->gamestate->jumpToState(ST_PLAYER_CHOICE);
    }

    // Test board UI : create fake tokens on board
    function debug_BoardTokens(){
        $player_id = Players::getCurrent()->getId();
        Log::addStep();

        $tokens = Tokens::getInLocation(TOKEN_LOCATION_BOARD);
        foreach($tokens as $t) Tokens::delete($t->getId());

        $tokens = [];
        for($col=1;$col<=MARKET_GRID_SIZE;$col++){
            $row = 0;
            $tokens[] = [
                'location' => TOKEN_LOCATION_BOARD,
                'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER,
                'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,
                'row' => $row,
                'col' => $col,
                'nbr' => 1,
            ];
            $row = MARKET_GRID_SIZE+1;
            $tokens[] = [
                'location' => TOKEN_LOCATION_BOARD,
                'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER,
                'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,
                'row' => $row,
                'col' => $col,
                'nbr' => 1,
            ];
        }
        for($row=1;$row<=MARKET_GRID_SIZE;$row++){
            $col = 0;
            $tokens[] = [
                'location' => TOKEN_LOCATION_BOARD,
                'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER,
                'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,
                'row' => $row,
                'col' => $col,
                'nbr' => 1,
            ];
            $col = MARKET_GRID_SIZE+1;
            $tokens[] = [
                'location' => TOKEN_LOCATION_BOARD,
                'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER,
                'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,
                'row' => $row,
                'col' => $col,
                'nbr' => 1,
            ];
        }
        
        for($col=1;$col<=MARKET_GRID_SIZE;$col++){
            for($row=1;$row<=MARKET_GRID_SIZE;$row++){
                $tokens[] = [
                    'location' => TOKEN_LOCATION_BOARD,
                    'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER,
                    'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,
                    'row' => $row,
                    'col' => $col,
                    'nbr' => 1,
                ];
            }
        }

        $players = Players::getAll();
        $index = 0;
        foreach ($players as $pId => $player) {
            $index++;
            /*$tokens[] = [
                'location' => TOKEN_LOCATION_BOARD,
                'type' => TOKEN_TYPE_AUCTION_MARKER_1,
                'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,
                'player_id' => $pId,
                'row' => $index,
                'col' => $index,
                'nbr' => 1,
            ];*/
            
            for($k = 0; $k < count(TOKEN_TYPE_PROGRESS_MARKERS); $k++){
                $tokens[] = [
                    'location' => TOKEN_LOCATION_BOARD,
                    'type' => TOKEN_TYPE_AUCTION_MARKERS[$k],
                    'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,
                    'player_id' => $pId,
                    'row' => $index + $k,
                    'col' => $index,
                    'nbr' => 1,
                ];
            }
        }

        Tokens::create($tokens);

        $this->debug_refreshUI();
    }

    
    function debug_shuffleCards(){
        $player_id = Players::getCurrent()->getId();
        Cards::moveAllInLocation(CARD_LOCATION_BOARD,CARD_LOCATION_EXP_DECK);
        Cards::moveAllInLocation(CARD_LOCATION_EXP_DISCARD,CARD_LOCATION_EXP_DECK);
        Cards::shuffle(CARD_LOCATION_EXP_DECK);
        $this->debug_refreshUI();
    }
    // Test board UI : 
    function debug_boardCards(bool $keepOne = true){
        $player_id = Players::getCurrent()->getId();
        Log::addStep();

        Cards::moveAllInLocation(CARD_LOCATION_EXP_DECK,CARD_LOCATION_EXP_DISCARD);
        //
        if($keepOne) Cards::getTopOf(CARD_LOCATION_EXP_DISCARD)->setLocation(CARD_LOCATION_EXP_DECK);
        Cards::shuffle(CARD_LOCATION_EXP_DISCARD);
        $this->debug_refreshUI();
    }
    function debug_drawAndDiscardExplo(int $nb = 1){
        $player = Players::getCurrent();
        Log::addStep();

        Cards::drawAndDiscardExploration($player, $nb);
    }
    function debug_HandExplo(int $size = 1){
        $player = Players::getCurrent();
        Log::addStep();
        Cards::drawExplorationCardsToPlayer($player, $size);
       
    }
    
    function debug_changePlayerCurrencies(int $money = 15,int $ships = 5,int $contracts = 3,int $compasses = 1,){
        $player = Players::getCurrent();
        $player_id = $player->getId();
        Log::addStep();

        $player->setMoney($money);
        $player->setShips($ships);
        $player->setContracts($contracts);
        $player->setCompasses($compasses);
        $this->debug_refreshUI();
        $this->debug_goToState(ST_PLAYER_TURN);
    }

    function debug_playerTiles(){
        $player = Players::getCurrent();
        $player_id = $player->getId();
        Log::addStep();

        //resetup outposts
        $outposts = Tiles::getOutpostTilesTypes();
        foreach ($outposts as $type => $outpost) {
            $tiles = Tiles::getByType($type, TILE_SUBTTYPE_OUTPOST);
            foreach ($tiles as $tile_id => $tile) {
                $tile->setLocation(TILE_LOCATION_BOARD_OUTPOSTS[$outpost['colony']]);
            }
        }

        //Test outposts :
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOLLAM]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_WHITE, 1);
        
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOCHI]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_MADRAS]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOZHIKODE]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_WHITE, 1);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_GREEN, 2);

        //Test plantations :
        $tile = Tiles::getByType(20, TILE_SUBTTYPE_MARKET)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        $tile->setRow(null);
        $tile->setCol(null);
        $tile->setState(2);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_BLACK, 1);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_YELLOW, 2);

        $tile = Tiles::getByType(2, TILE_SUBTTYPE_MARKET)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        $tile->setRow(null);
        $tile->setCol(null);
        $tile->setState(4);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_PINK, 1);

        $tile = Tiles::getByType(4, TILE_SUBTTYPE_MARKET)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        $tile->setRow(null);
        $tile->setCol(null);
        $tile->setState(3);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_YELLOW, 1);

        $this->debug_refreshUI();
    }
    
    //TEst different notif at a time
    function debug_sendNotif(){
        $players = Players::getAll()->toArray();
        //Notifications::winsAuction($players[0], 12,$players[1]);
        Notifications::winsAuction($players[0], 15,);
    }

    ////////////////////////////////////////////////////
        
    //Clear logs
    function debug_ClearLogs(){
        $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');
        $query->delete()->run();
        $query = new QueryBuilder('log', null, 'id');
        $query->delete()->run();
    }
}