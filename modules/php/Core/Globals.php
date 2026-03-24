<?php

namespace Bga\Games\Goa\Core;

use Bga\GameFramework\SystemException;
use Bga\GameFramework\Table;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Utils;
use Exception;

/** 
 * Globals to reuse the same Globals as BGA framework but with Tisaac boilerplate Logger and cache
 */

class Globals extends \Bga\Games\Goa\Helpers\DB_Manager
{
  protected static $table = 'my_global_variables';
  protected static $primary = 'name';

  protected static $initialized = false;
  protected static $variables = [
    //session for campaign of multiple sessions
    'session' => 'int',
    'round' => 'int',
    //keep a trace of changes insides the same round in roundStep (1,2,3)
    'roundStep' => 'int',
    'turn' => 'int',
    'firstPlayer' => 'int',
    'currentBid' => 'int',
    'bidTurns' => 'int',
    'highBidPlayer' => 'int',

    'automaColor' => 'str',
    'automaActive' => 'bool',
    'automaNbActions' => 'int',
    'automaPassed' => 'bool',
    'automaActionPlayed' => 'bool',
    
    'endScoring' => 'obj',

    //For Undo log module
    'choices' => 'int',

    //Start OPTIONS
    'optionQuickStarter' => 'int',
    'optionBonanza' => 'int',
    'optionPlanned' => 'int',
    'optionStories' => 'int',
    'optionCaptains' => 'int',
    'optionGambit' => 'int',
    'optionAutomaDifficulty' => 'int',
    'optionCampaign' => 'int',
  ];
 
  /*
   * Setup new game
   */
  public static function setupNewGame($players, $options)
  {

    self::setSession(0);
    self::setRound(0);
    self::setTurn(0);
    self::setBidTurns(0);
    self::setHighBidPlayer(null);
    self::setEndScoring([]);
    self::setAutomaActive(true);
    self::setAutomaNbActions(0);
    self::setAutomaActionPlayed(false);
    self::setAutomaPassed(false);

    //Randomize first player selection
    $randomPId = array_rand($players);
    self::setFirstPlayer($randomPId);

    //              --------------------------------------------
    //GAME OPTIONS  --------------------------------------------
    //              --------------------------------------------
    //No specific filter on options, lets retrieve BGA options values :
    $optionQuickStarter = OPTION_QUICK_STARTER_OFF;
    Utils::updateDataFromArray($options,OPTION_QUICK_STARTER,$optionQuickStarter);
    self::setOptionQuickStarter($optionQuickStarter);
    
    $optionBonanza = OPTION_BONANZA_OFF;
    Utils::updateDataFromArray($options,OPTION_BONANZA,$optionBonanza);
    self::setOptionBonanza($optionBonanza);
    
    $optionPlanned = OPTION_PLANNED_OFF;
    Utils::updateDataFromArray($options,OPTION_PLANNED,$optionPlanned);
    self::setOptionPlanned($optionPlanned);

    $optionStories = OPTION_STORIES_OFF;
    Utils::updateDataFromArray($options,OPTION_STORIES,$optionStories);
    self::setOptionStories($optionStories);
    
    $optionValue = OPTION_CAPTAINS_OFF;
    Utils::updateDataFromArray($options,OPTION_CAPTAINS,$optionValue);
    self::setOptionCaptains($optionValue);
    
    $optionValue = OPTION_GAMBIT_OFF;
    Utils::updateDataFromArray($options,OPTION_GAMBIT,$optionValue);
    self::setOptionGambit($optionValue);
    
    $optionValue = OPTION_AUTOMA_DIFFICULTY_OFF;
    //displaycondition for solo only :
    if(count($players) == 1){
      Utils::updateDataFromArray($options,OPTION_AUTOMA_DIFFICULTY,$optionValue);
      if($optionValue == OPTION_AUTOMA_DIFFICULTY_OFF){
        //AUTOMA is mandatory with only 1 player -> see startCondition
        $optionValue = OPTION_AUTOMA_DIFFICULTY_RANDOM;
      }
    }
    if($optionValue == OPTION_AUTOMA_DIFFICULTY_RANDOM){
      $optionValue = OPTION_AUTOMA_DIFFICULTIES[array_rand(OPTION_AUTOMA_DIFFICULTIES, 1)];
    }
    self::setOptionAutomaDifficulty($optionValue);

    $optionValue = OPTION_CAMPAIGN_OFF;
    //displaycondition for solo only :
    if(count($players) == 1){
      Utils::updateDataFromArray($options,OPTION_CAMPAIGN,$optionValue);
    }
    self::setOptionCampaign($optionValue);

  }
   
  /**
   * Setup new game turn
   */
  public static function setupNewTurn()
  {
    self::incTurn(1);
    
    //Stats::inc("turns_number");
  }


  //////////////////////////////////////////////////////////////////////////////////////
  protected static function cast($row)
  {
    $val = json_decode(\stripslashes($row['value']), true);
    if(!isset(self::$variables[$row['name']])){
      //ERROR  Uncaught ErrorException: Undefined array key "_BGA_INITIAL_STATE_ID" 
      Game::get()->error(__CLASS__.".".__FUNCTION__."() no defined global variable ".json_encode($row['name']));
      return null;
    }
    return self::$variables[$row['name']] == 'int' ? ((int) $val) : $val;
  }

  /*
   * Fetch all existings variables from DB
   */
  protected static $data = [];
  public static function fetch()
  {
    // Turn of LOG to avoid infinite loop (Globals::isLogging() calling itself for fetching)
    $tmp = self::$log;
    self::$log = false;

    //try{
    //Migrate from 'bga_globals' to 'my_global_variables', like I do in upgradeTableDb, but this method is called before
    //$version = Utils::gameVersion();
    //if($version <= 2603110809 && $version != 999999999){
    //  Game::get()->error(__CLASS__.".".__FUNCTION__."() Version $version needs a new globals table ");
    //  $sql = "CREATE TABLE IF NOT EXISTS  `my_global_variables` (
    //              `name` varchar(50) NOT NULL,
    //              `value` JSON,
    //              PRIMARY KEY (`name`)
    //          ) ENGINE = InnoDB DEFAULT CHARSET = UTF8MB4
    //          SELECT * FROM `bga_globals`";
    //  Table::DbQuery($sql);
    //}

    foreach (self::DB()
        ->select(['value', 'name'])
        ->get(false)
      as $name => $variable) {
      if (\array_key_exists($name, self::$variables)) {
        self::$data[$name] = $variable;
      }
    }
    //} catch(Exception $e){
    //  $class = $e::class;
    //  Game::get()->error(__CLASS__.".".__FUNCTION__."() Exception $class: ".json_encode($e));
    //  throw $e;
    //}
    self::$initialized = true;
    self::$log = $tmp;
  }

  /*
   * Create and store a global variable declared in this file but not present in DB yet
   *  (only happens when adding globals while a game is running)
   */
  public static function create($name)
  {
    if (!\array_key_exists($name, self::$variables)) {
      return;
    }

    $default = [
      'int' => 0,
      'obj' => [],
      'bool' => false,
      'str' => '',
    ];
    $val = $default[self::$variables[$name]];
    self::DB()->insert(
      [
        'name' => $name,
        'value' => \json_encode($val),
      ],
      true
    );
    self::$data[$name] = $val;
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (!self::$initialized) {
      self::fetch();
    }

    if (preg_match('/^([gs]et|inc|is)([A-Z])(.*)$/', $method, $match)) {
      // Sanity check : does the name correspond to a declared variable ?
      $name = strtolower($match[2]) . $match[3];
      if (!\array_key_exists($name, self::$variables)) {
        throw new \InvalidArgumentException("Property {$name} doesn't exist");
      }

      // Create in DB if don't exist yet
      if (!\array_key_exists($name, self::$data)) {
        self::create($name);
      }

      if ($match[1] == 'get') {
        // Basic getters
        return self::$data[$name];
      } elseif ($match[1] == 'is') {
        // Boolean getter
        if (self::$variables[$name] != 'bool') {
          throw new \InvalidArgumentException("Property {$name} is not of type bool");
        }
        return (bool) self::$data[$name];
      } elseif ($match[1] == 'set') {
        // Setters in DB and update cache
        $value = $args[0];
        if (self::$variables[$name] == 'int') {
          $value = (int) $value;
        }
        if (self::$variables[$name] == 'bool') {
          $value = (bool) $value;
        }

        self::$data[$name] = $value;
        self::DB()->update(['value' => \addslashes(\json_encode($value))], $name);
        return $value;
      } elseif ($match[1] == 'inc') {
        if (self::$variables[$name] != 'int') {
          throw new \InvalidArgumentException("Trying to increase {$name} which is not an int");
        }

        $getter = 'get' . $match[2] . $match[3];
        $setter = 'set' . $match[2] . $match[3];
        return self::$setter(self::$getter() + (empty($args) ? 1 : $args[0]));
      }
    } else {
      throw new SystemException('unknown method ' . $method);
      return null;
    }
    // return undefined;
  }
}
