<?php
namespace Bga\Games\Goa;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\QueryBuilder;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Effects;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\States\EndSession;

/**
 * Debugging functions to be called in BGA Studio game window
 */
trait DebugTrait
{
    
    ////////////////////////////////////////////////////
    /** 
     * RESET ALL GAME DATAS in order to restart the game without recreating a new table spoiling server space etc...
    */
    function debug_ReSetup(
        bool $quickStarter = true,
        bool $bonanza = true,
        bool $story = true,
        bool $captains = true,
        bool $gambit = true,
        bool $campaign = true,
    ){
        Game::get()->trace("debug_ReSetup - START ////////////////////////////////////////////////////");
        $players = self::loadPlayersBasicInfos();
        $player = Players::getCurrent();
        
        $options = [
            "DEBUG_SETUP"=> true,
            //OPTION_QUICK_STARTER => ($quickStarter ? OPTION_QUICK_STARTER_ON : OPTION_QUICK_STARTER_OFF) ,
            //OPTION_BONANZA => ($bonanza ? OPTION_BONANZA_ON : OPTION_BONANZA_OFF) ,
            //OPTION_STORIES => ($story ? OPTION_STORIES_RANDOM : OPTION_STORIES_OFF) ,
            //OPTION_CAPTAINS => ($captains ? OPTION_CAPTAINS_ON : OPTION_CAPTAINS_OFF) ,
            //OPTION_GAMBIT => ($gambit ? OPTION_GAMBIT_ON : OPTION_GAMBIT_OFF) ,
            //OPTION_CAMPAIGN => ($campaign ? OPTION_CAMPAIGN_ON : OPTION_CAMPAIGN_OFF) ,
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
        Effects::DB()->delete()->run();
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
    public function debug_playOneMove(int $nbMoves = 1) {
        $this->debug->playUntil(fn(int $count) => $count == $nbMoves);
    }
    
    ////////////////////////////////////////////////////
    function debug_refreshUI(){
        $player_id = Players::getCurrent()->getId();
        //Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
        //Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
        Notifications::refreshUI(Game::get()->getAllDatas($player_id));
    }
    function debug_changeRound(int $round = 1, int $roundStep = ROUND_STEP_PLAYER_ACTIONS){
        Globals::setRound($round);
        Globals::setRoundStep($roundStep);
        $this->debug_refreshUI();
        $this->debug_goToState(ST_PLAYER_TURN);
    }
    function debug_Effects(){
        $player = Players::getActive();
        Log::addStep();

        //clean all
        Effects::DB()->delete()->run();

        //Effect after Explore
        Effects::addEffectOnAction($player,ACTION_TYPE_EXP, EFFECT_TYPE_CLEAN_HAND, null, []);

        //Effect after Found
        $tile = Tiles::getByType(1,TILE_SUBTTYPE_OUTPOST)->first();
        //effect from action
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_FOUND_TILE, 2, [ACTION_FOUND_ABANDON, ACTION_FOUND_FORWARD], ACTION_TYPE_FOUND);
        //effect from Espionage found
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_FOUND_TILE, 6, [ACTION_FOUND_ABANDON, ], ACTION_TYPE_FOUND);
        
        $tile = Tiles::getByType(26,TILE_SUBTTYPE_MARKET)->first();
        $effects = [];
        $effects[] = Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_ACTION, 3, [ACTION_SHIP, ACTION_CONTRACT]);
        //simulate 2 choices
        $effects[] = Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_ACTION, 1, [ACTION_SHIP, ACTION_CONTRACT]);
        $effects[] = Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_ACTION, 2, [ACTION_SHIP, ACTION_CONTRACT]);

        //TEST removing an element of the list by index
        Effects::delete($effects[1]->getId());

        //FAVOR
        $tile = Tiles::getByType(30,TILE_SUBTTYPE_MARKET)->first();
        $tilesToChoose = Tiles::getTilesForFavor()
            ->merge(Tiles::getByType(2,TILE_SUBTTYPE_MARKET))//+ Test chaining with SPICES_ALL
            ->map(function ($tile) {
                return $tile->getType();
            })->getIds();
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_TILE, 1, $tilesToChoose);
        
        //Spices
        $tile = Tiles::getByType(2,TILE_SUBTTYPE_MARKET)->first();
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_SPICE, 1, SPICES_ALL);
        
        $tile = Tiles::getByType(12,TILE_SUBTTYPE_OUTPOST)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player->getId());
        Notifications::giveTile($player,$tile);
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_SPICE, 1, SPICES_ALL);
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_SPICE, 2, SPICES_ALL);

        //Plantation
        $tile = Tiles::getByType(2,TILE_SUBTTYPE_MARKET)->first();
        $playerPlantations = Tiles::getPlantations($player);
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_REPLACE_TILE, 1, $playerPlantations->getIds());

        //ACTION_RESUPPLY
        $tile = Tiles::getByType(32,TILE_SUBTTYPE_MARKET)->first();
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_HARVEST, 7, [ACTION_HARVEST, ACTION_CONTRACT, ACTION_SHIP],);

        //ACTION_ESPIONAGE
        $tile = Tiles::getByType(34,TILE_SUBTTYPE_MARKET)->first();
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_ESPIONAGE, 1, [ACTION_SHIP, ACTION_HARVEST, ACTION_MONEY, ACTION_FOUND_ABANDON]);

        //$this->debug_refreshUI();
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
            
            for($k = 0; $k < count(TOKEN_TYPE_AUCTION_MARKERS); $k++){
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
        $doCheckpoint = true;
        Cards::drawExplorationCardsToPlayer($player, $size, $doCheckpoint);
       
    }
    function debug_discardCards(int $nb = 10){
        $player = Players::getCurrent();
        $player_id = $player->getId();
        Log::addStep();

        $cards = Cards::getExplorationHand($player_id);
        $i = 0;
        foreach($cards as $card){
            $i++;
            if($i>$nb){
                break;
            }
            Cards::discardExploration($player,$card->getId());
        }

        $this->debug_refreshUI();
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
        $this->debug_goToState(Game::get()->gamestate->getCurrentMainStateId());
    }
    
    function debug_changeProgress(int $ships = 1,int $harvest = 2,int $taxes = 4,int $explo = 2,int $found = 1,){
        $player = Players::getCurrent();
        $player_id = $player->getId();
        Log::addStep();
        
        $token = Tokens::getProgressMarkerByType(1, $player_id);
        $token->setRow($ships);
        $token = Tokens::getProgressMarkerByType(2, $player_id);
        $token->setRow($harvest);
        $token = Tokens::getProgressMarkerByType(3, $player_id);
        $token->setRow($taxes);
        $token = Tokens::getProgressMarkerByType(4, $player_id);
        $token->setRow($explo);
        $token = Tokens::getProgressMarkerByType(5, $player_id);
        $token->setRow($found);

        $this->debug_refreshUI();
        $this->debug_goToState(Game::get()->gamestate->getCurrentMainStateId());
    }

    function debug_playerTiles(){
        $player = Players::getCurrent();
        $player_id = $player->getId();
        Log::addStep();

        Tokens::removeSpicesOnAllTiles();

        //resetup outposts
        $outposts = Tiles::getOutpostTilesTypes();
        foreach ($outposts as $type => $outpost) {
            $tiles = Tiles::getByType($type, TILE_SUBTTYPE_OUTPOST);
            foreach ($tiles as $tile_id => $tile) {
                Tokens::removeSpicesOnTile($tile,$player);
                $tile->setLocation(TILE_LOCATION_BOARD_OUTPOSTS[$outpost['colony']]);
            }
        }
        $this->debug_refreshUI();

        //Test outposts :
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOLLAM]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_WHITE, 1);
        
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOCHI]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_GREEN, 1);
        
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_MADRAS]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_YELLOW, 1);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_WHITE, 2);
        
        $tile = Tiles::getTopOf(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOZHIKODE]);
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_WHITE, 1);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_GREEN, 2);

        //Test plantations :
        $plantations = Tiles::getPlantations($player);
        foreach($plantations as $p){
            Tiles::discardTileFromPlayer($player, $p);
        }

        $tile = Tiles::getByType(20, TILE_SUBTTYPE_MARKET)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        $tile->setRow(null);
        $tile->setCol(null);
        $tile->setState(2);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_BLACK, 1);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_YELLOW, 2);

        $tile = Tiles::getByType(2, TILE_SUBTTYPE_MARKET)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        $tile->setRow(null);
        $tile->setCol(null);
        $tile->setState(4);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_PINK, 1);

        $tile = Tiles::getByType(4, TILE_SUBTTYPE_MARKET)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        $tile->setRow(null);
        $tile->setCol(null);
        $tile->setState(3);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_YELLOW, 1);
        
        $tile = Tiles::getByType(5, TILE_SUBTTYPE_MARKET)->first();
        $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        $tile->setPId($player_id);
        $tile->setRow(null);
        $tile->setCol(null);
        $tile->setState(1);
        Notifications::giveTile($player,$tile);
        Tokens::addSpiceOnTile($tile, $player, TOKEN_TYPE_SPICE_GREEN, 1);

        //TEST Tiles we may have in hand : 
        $useableTypes = Tiles::getTilesTypesByMarketPlayType(MARKET_TYPE_ACTION_ONCE);
        $useableTypes = array_merge($useableTypes , Tiles::getTilesTypesByMarketPlayType(MARKET_TYPE_ACTION_REUSEABLE));
        $useableTypes = array_merge($useableTypes , Tiles::getTilesTypesByMarketPlayType(MARKET_TYPE_SCORING));
        $useableTiles = Tiles::getMarketTilesByTypes($useableTypes);
        $doCheckpoint = false;
        foreach($useableTiles as $tile){
            Tiles::giveMarketTileToPlayer($player, $tile, $doCheckpoint);
        }
        
        //$this->debug_refreshUI();
        $this->debug_goToState(Game::get()->gamestate->getCurrentMainStateId());
    }
    
    function debug_fillTileWithSpices(int $tile_id){
        $tile = Tiles::get($tile_id);
        $pid = $tile->getPId();
        $player = Players::get($pid);
        Log::addStep();
        Tiles::fillPlayerTileWithSpices($player,$tile);
        $this->debug_goToState(Game::get()->gamestate->getCurrentMainStateId());
    }
    function debug_removeSpicesOnTile(int $tile_id){
        $tile = Tiles::get($tile_id);
        $pid = $tile->getPId();
        $player = Players::get($pid);
        Log::addStep();
        Tokens::removeSpicesOnTile($tile,$player);
        $this->debug_goToState(Game::get()->gamestate->getCurrentMainStateId());
    }
    
    function debug_discardTile(int $tile_id){
        $player = Players::getCurrent();
        $player_id = $player->getId();
        Log::addStep();

        $tile = Tiles::get($tile_id);
        //Tiles::discardTileFromPlayer($player, $tile);//Not supposed to be for outposts
        Tokens::removeSpicesOnTile($tile,$player,);
        $tile->setPId(null);
        $tile->setLocation(TILE_LOCATION_DISCARD);
        Notifications::discardTile($player,$tile);

        $this->debug_refreshUI();
    }
    function debug_scoring(){
        Log::addStep();
        $session = Globals::getSession();
        EndSession::computeScoring($session);
        
        $this->debug_goToState(Game::get()->gamestate->getCurrentMainStateId());
    }
    
    //TEst different notif at a time
    function debug_sendNotif(){
        $player = Players::getCurrent();
        $players = Players::getAll()->toArray();
        //Notifications::winsAuction($players[0], 12,$players[1]);
        //Notifications::winsAuction($players[0], 15,);
        Tokens::giveFirstPlayerToken($player);
    }

    //TEst different utils functions at a time
    function debug_Utils(){
        $possibleSets[1] =  [[15],];
        $sets = Utils::array_cartesian($possibleSets);
        Notifications::message("debug_Utils 1: ".json_encode($sets),['sets'=>$sets]);
        $possibleSets[2] =  [[50],[52]];
        $sets = Utils::array_cartesian($possibleSets);
        Notifications::message("debug_Utils 2: ".json_encode($sets),['sets'=>$sets]);
        $possibleSets[3] =  [[1034,1032,],[1035,1032],[1035,1034]];
        $sets = Utils::array_cartesian($possibleSets);
        Notifications::message("debug_Utils 3: ".json_encode($sets),['sets'=>$sets]);
        
        /*
        $possibleSets = [];
        $possibleSets[1] =  [[15],];
        $sets = Utils::array_cartesian_product($possibleSets);
        Notifications::message("debug_Utils A2: ".json_encode($sets),['sets'=>$sets]);
        $possibleSets[2] =  [[50],[52]];
        $sets = Utils::array_cartesian_product($possibleSets);
        Notifications::message("debug_Utils B2: ".json_encode($sets),['sets'=>$sets]);
        $possibleSets[3] =  [[1034,1032,],[1035,1032],[1035,1034]];
        $sets = Utils::array_cartesian_product($possibleSets);
        Notifications::message("debug_Utils C2: ".json_encode($sets),['sets'=>$sets]);
        */
        $possibleSets = [];
        $possibleSets[1] =  [[15],];
        $sets = Utils::array_cartesian_productV3($possibleSets);
        Notifications::message("debug_Utils A3: ".json_encode($sets),['sets'=>$sets]);
        $possibleSets[2] =  [[50],[52]];
        $sets = Utils::array_cartesian_productV3($possibleSets);
        Notifications::message("debug_Utils B3: ".json_encode($sets),['sets'=>$sets]);
        $possibleSets[3] =  [[1034,1032,],[1035,1032],[1035,1034]];
        $sets = Utils::array_cartesian_productV3($possibleSets);
        Notifications::message("debug_Utils C3: ".json_encode($sets),['sets'=>$sets]);
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