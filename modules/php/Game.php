<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Goa implementation : © joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\Goa;

require_once 'constants.inc.php';
require_once 'Models/Enums.php';

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\AutomaCards;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\States\NewSession;

class Game extends \Bga\GameFramework\Table
{
    use DebugTrait;
    
    public static $instance = null;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If you want to store any type instead of int, use $this->globals instead.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();
        self::$instance = $this;
        $this->initGameStateLabels([
            'logging' => 10,
        ]);
    }

    public static function get() : Game{
        return self::$instance;
    }
    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        $maxSessions = 1;
        if(Globals::getOptionCampaign() == OPTION_CAMPAIGN_ON){
            $maxSessions = NB_SESSIONS_CAMPAIGN;
        }
        $maxRounds = NB_ROUNDS_DAY + NB_ROUNDS_NIGHT;
        $maxRoundSteps = count(ALL_ROUND_STEPS);
            
        $session = Globals::getSession();//From 1 to $maxSessions
        $round = Globals::getRound();//From 1 to $maxRounds
        $roundStep = Globals::getRoundStep();//From 1 to $maxRoundSteps

        //Compute mathematical progress between 0 and 1
        $currentStepProgression = 1;
        if($roundStep === ROUND_STEP_PLAYER_ACTIONS){
            //count actions done :
            $players = Players::getAll();
            $nbActionsInReserve = 0;
            $nbActionsTOTAL = count($players) * NB_MIN_ACTIONS_PER_ROUND;
            foreach($players as $player){
                //don't consider compass for simplicity
                $nbActionsInReserve += NB_MIN_ACTIONS_PER_ROUND - $player->getNbActions();
            }
            $currentStepProgression = ($nbActionsTOTAL - $nbActionsInReserve) / $nbActionsTOTAL;
        }
        $currentRoundProgression = ($roundStep - 1) / $maxRoundSteps + 1 / $maxRoundSteps * $currentStepProgression;
        $currentSessionProgression = ($round - 1) / $maxRounds + 1 / $maxRounds * $currentRoundProgression;
        $progress = ($session - 1) / $maxSessions + 1 / $maxSessions * $currentSessionProgression;

        return $progress * 100;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
//       if ($from_version <= 1404301345)
//       {
//            // ! important ! Use `DBPREFIX_<table_name>` for all tables
//
//            $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
//
//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use `DBPREFIX_<table_name>` for all tables
//
//            $sql = "CREATE TABLE `DBPREFIX_xxxxxxx` ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    public function getAllDatas(int $currentPlayerId): array
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = $currentPlayerId;

        $result["turn"] = Globals::getTurn();
        $result["round"] = Globals::getRound();
        $result["session"] = Globals::getSession();
        $result["players"] = Players::getUiData($current_player_id);
        $result["cards"] = [
            'all' => Cards::getUiData($current_player_id),
            'private' => Cards::getPrivateDatas($current_player_id),
            'deck_size' => Cards::getAllDeckSizes(),
            'automa' => AutomaCards::getUiData($current_player_id),
        ];
        $result["tiles"] = Tiles::getUiData($current_player_id);
        $result["material"] = [
            'tiles' => [
                TILE_SUBTTYPE_OUTPOST => Tiles::getOutpostTilesTypes(),
                TILE_SUBTTYPE_MARKET => Tiles::getMarketTilesTypes(),
            ],
            'cards' => [
                CARD_SUBTTYPE_EXPLORATION => Cards::getExplorationCardsTypes(),
                CARD_SUBTTYPE_AUTOMA_AUCTION => AutomaCards::getAutomaAuctionCardsTypes(),
                CARD_SUBTTYPE_AUTOMA_ACTION => AutomaCards::getAutomaActionCardsTypes(),
            ],
        ];
        $result["tokens"] = Tokens::getUiData($current_player_id);
        
        $result["endScoring"] = Globals::getEndScoring();

        $result["constants"] = ALL_MY_UI_CONSTANTS;
        $result["version"] = Utils::gameVersion();

        return $result;
    }

    /**
     * Returns an array of user preference colors to game colors.
     * Game colors must be among those which are passed to `Table::reattributeColorsBasedOnPreferences()`.
     *
     * Each game color can be an array of suitable colors, or a single color:
     *
     * ```
     * [
     *    // The first available color chosen:
     *    'ff0000' => ['990000', 'aa1122'],
     *    // This color is chosen, if available
     *    '0000ff' => '000099',
     * ]
     * ```
     *
     * If no color can be matched from this array, then the default implementation is used.
     *
     * @return array<string, ?string>
     */
    function getSpecificColorPairings(): array {
        return array(
            "ff0000" /* Red */         => 'e14a9b',
            "008000" /* Green */       => '84cba2',
            "0000ff" /* Blue */        => '0090ba',
            "ffa500" /* Yellow */      => 'feb713',
            "e94190" /* Pink */        => 'e14a9b',
            "982fff" /* Purple */      => 'e14a9b',
            "72c3b1" /* Cyan */        => '84cba2',
            "f07f16" /* Orange */      => 'feb713',
            "bdd002" /* Khaki green */ => 'feb713',
            "7b7b7b" /* Gray */        => ['84cba2', '0090ba','feb713', 'e14a9b'],
            "000000" /* Black */       => ['84cba2', '0090ba','feb713', 'e14a9b'],
            "ffffff" /* White */       => ['84cba2', '0090ba','feb713', 'e14a9b'],
        );
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        Globals::setupNewGame($players, $options);
        $playersDatas = Players::setupNewGame($players, $options);
        Stats::setupNewGame($playersDatas);
        Cards::setupNewGame($playersDatas,$options);
        Tiles::setupNewGame($playersDatas,$options);
        Tokens::setupNewGame($playersDatas,$options);

        // Init global values with their initial values.
        $this->setGameStateInitialValue('logging', 1); 

        return NewSession::class;
    }

}
