<?php
namespace Bga\Games\Goa;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Helpers\Log;
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
        
        self::DbQuery("DELETE FROM `bga_player_counters`");
        self::DbQuery("ALTER TABLE `bga_player_counters` DROP `energy`");
        self::DbQuery("DROP TABLE `bga_player_counters`");

        Game::get()->setupNewGame($players,$options);
        
        Log::enable();

        $players = self::loadPlayersBasicInfos(); 
        Game::get()->gamestate->jumpToState(ST_NEXT_TURN);//first state 
        
        //$this->notify->all('reloadPage', "/!\ : Refresh page to see game has restarted...", []);
        Game::get()->notify->player($player->getId(),'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
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

    /*
    Another example of debug function, to easily create situations you want to test.
    Here, put a card you want to test in your hand (assuming you use the Deck component).

    public function debug_setCardInHand(int $cardType, int $playerId) {
        $card = array_values($this->cards->getCardsOfType($cardType))[0];
        $this->cards->moveCard($card['id'], 'hand', $playerId);
    }
    */


    
    ////////////////////////////////////////////////////
    function debug_changeRound(int $round = 1){
        Globals::setRound($round);
        Game::get()->notify->all('reloadPage', "/!\ : Refresh page to see game has restarted...", []);
    }

    // Test board UI : create fake tokens on board
    function debug_BoardTokens(){
        $player_id = Players::getCurrent()->getId();

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

        Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
    }

    
    function debug_shuffleCards(){
        $player_id = Players::getCurrent()->getId();
        Cards::moveAllInLocation(CARD_LOCATION_BOARD,CARD_LOCATION_EXP_DECK);
        Cards::moveAllInLocation(CARD_LOCATION_EXP_DISCARD,CARD_LOCATION_EXP_DECK);
        Cards::shuffle(CARD_LOCATION_EXP_DECK);
        Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
    }
    // Test board UI : 
    function debug_boardCards(){
        $player_id = Players::getCurrent()->getId();

        $cards = Cards::moveAllInLocation(CARD_LOCATION_EXP_DECK,CARD_LOCATION_EXP_DISCARD);
        Cards::shuffle(CARD_LOCATION_EXP_DISCARD);
        Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
    }
    function debug_drawAndDiscardExplo(){
        $player_id = Players::getCurrent()->getId();

        $card = Cards::pickOneForLocation(CARD_LOCATION_EXP_DECK,CARD_LOCATION_EXP_DISCARD,);
        Notifications::drawAndDiscardExploration($player_id, $card);
    }
    function debug_HandExplo(int $size = 1){
        $player = Players::getCurrent();

        Cards::drawExplorationCardsToPlayer($player, $size);
       
    }
    
    function debug_changePlayerCurrencies(int $money = 15,int $ships = 5,int $contracts = 3,int $compasses = 1,){
        $player = Players::getCurrent();
        $player_id = $player->getId();

        $player->setMoney($money);
        $player->setShips($ships);
        $player->setContracts($contracts);
        $player->setCompasses($compasses);
        Game::get()->notify->player($player_id,'reloadPage', "/!\ : Refresh page to see game has restarted...", []);
    }
    ////////////////////////////////////////////////////

}