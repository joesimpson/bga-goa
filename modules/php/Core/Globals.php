<?php

namespace Bga\Games\Goa\Core;

use Bga\GameFramework\SystemException;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Models\Tile;

/** 
 * Globals to reuse the same Globals as BGA framework but with Tisaac boilerplate Logger and cache
 */

class Globals extends \Bga\Games\Goa\Helpers\DB_Manager
{
  ////////// !! reuse the same table  as BGA framework but with boilerplate Logger and cache
  protected static $table = 'bga_globals';
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
    'actionsTurns' => 'int',
    'highBidPlayer' => 'int',
    
    'endScoring' => 'obj',

    //save infos about tiles between states
    'choiceTiles' => 'obj',

    //For Undo log module
    'choices' => 'int',

    //Start OPTIONS
    'optionQuickStarter' => 'int',
    'optionBonanza' => 'int',
    'optionStories' => 'int',
    'optionCaptains' => 'int',
    'optionGambit' => 'int',
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
    self::setActionsTurns(0);
    self::setHighBidPlayer(null);
    self::setChoiceTiles([]);
    self::setEndScoring([]);

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

    $optionStories = OPTION_STORIES_OFF;
    Utils::updateDataFromArray($options,OPTION_STORIES,$optionStories);
    self::setOptionStories($optionStories);
    
    $optionValue = OPTION_CAPTAINS_OFF;
    Utils::updateDataFromArray($options,OPTION_CAPTAINS,$optionValue);
    self::setOptionCaptains($optionValue);
    
    $optionValue = OPTION_GAMBIT_OFF;
    Utils::updateDataFromArray($options,OPTION_GAMBIT,$optionValue);
    self::setOptionGambit($optionValue);
    
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

  public static function addChoiceTile(Tile $tile, int $choiceType, int $choiceQuantity, array $choices, int $selectType = CHOICE_PICK_ACTION) : array
  {
    $choiceTiles = Globals::getChoiceTiles(); 
    $tileId = $tile->getId();
    if(!array_key_exists($tileId,$choiceTiles) )  $choiceTiles[$tileId] = [];
    //use tile id as key to keep track (in case of future multiples bonuses on same tile)
    $choiceTiles[$tileId][] = ['tile_id'=>$tileId, 'type' => $choiceType, 'n' => $choiceQuantity, 'choices' => $choices, 'selectType' => $selectType ];
    Globals::setChoiceTiles($choiceTiles);
    return $choiceTiles;
  }

  public static function removeChoiceTile(int $tileId, int $index) : array
  {
    $choiceTiles = Globals::getChoiceTiles(); 
    if(!array_key_exists($tileId,$choiceTiles) ) return $choiceTiles;
    unset($choiceTiles[$tileId][$index]);
    if(count($choiceTiles[$tileId]) == 0){
      unset($choiceTiles[$tileId]);
    }
    Globals::setChoiceTiles($choiceTiles);
    return $choiceTiles;
  }

  //////////////////////////////////////////////////////////////////////////////////////
  protected static function cast($row)
  {
    $val = json_decode(\stripslashes($row['value']), true);
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

    foreach (self::DB()
        ->select(['value', 'name'])
        ->get(false)
      as $name => $variable) {
      if (\array_key_exists($name, self::$variables)) {
        self::$data[$name] = $variable;
      }
    }
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
