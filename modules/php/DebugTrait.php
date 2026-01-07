<?php
namespace Bga\Games\Goa;

/**
 * Debugging functions to be called in BGA Studio game window
 */
trait DebugTrait
{
    
    ////////////////////////////////////////////////////

    function debug_ReSetup(bool $option1 = true,bool $option2 = true,){
        $this->trace("debug_ReSetup - START ////////////////////////////////////////////////////");
        $players = self::loadPlayersBasicInfos();
        
        $options = [
            "DEBUG_SETUP"=> true,
            //101 => ($option1 ? 2 :1) ,
            //102 => ($option2 ? 2 :1) ,
        ];
        //CLEAR DATAS
        self::DbQuery("DELETE FROM `stats` where stats_type >= 10 ");
        //self::DbQuery("DELETE FROM `card`");
        self::DbQuery("DELETE FROM `global` where global_id >= 10 AND global_id < 100 or global_id in( 101,102) ");
        //self::DbQuery("INSERT INTO `global` (global_id,global_value) VALUES ( 101,".$options[101]." )"); //NOT ENOUGH because of cache
        //self::DbQuery("INSERT INTO `global` (global_id,global_value) VALUES ( 102,".$options[102]." )"); //NOT ENOUGH because of cache
        //self::setGameStateValue( 'myoption1', $options[101]);
        //self::setGameStateValue( 'myoption2', $options[102]);
        self::DbQuery("DELETE FROM `bga_globals`");

        self::DbQuery("DELETE FROM `player`");

        self::DbQuery("DELETE FROM `gamelog` where gamelog_packet_id > 1");
        
        self::DbQuery("DELETE FROM `bga_player_counters`");
        self::DbQuery("ALTER TABLE `bga_player_counters` DROP `energy`");
        self::DbQuery("DROP TABLE `bga_player_counters`");

        $this->setupNewGame($players,$options);

        $players = self::loadPlayersBasicInfos(); 
        $this->gamestate->jumpToState(10);//first state (playerTurn)
        
        $this->notify->all('reloadPage', "/!\ : Refresh page to see game has restarted...", []);
        $this->trace("debug_ReSetup - END ////////////////////////////////////////////////////");

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

}