<?php

namespace Bga\Games\Goa\Core;

use Bga\GameFramework\Table;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Models\Player;

/*
 * Statistics
 */

class Stats extends \Bga\Games\Goa\Helpers\DB_Manager
{
  protected static $table = 'stats';
  protected static $primary = 'stats_id';
  protected static function cast($row)
  {
    return [
      'id' => $row['stats_id'],
      'type' => $row['stats_type'],
      'pId' => $row['stats_player_id'],
      'value' => $row['stats_value'],
    ];
  }

  /** Setter wrapper  */
  public static function set(string $name, Player|int|null $player, $value)
  {
    $pId = is_null($player) ? null : (is_int($player) ? $player : $player->getId());
    $setter = "set".ucfirst($name);
    self::$setter($pId,$value);
  }
  /** Inc wrapper whith a 1 default increment */
  public static function inc(string $name, Player|int|null $player = null, int $value = 1)
  {
    $pId = is_null($player) ? null : (is_int($player) ? $player : $player->getId());
    $incrementerName = "inc".ucfirst($name);
    self::$incrementerName($pId,$value);
  }

  /*
   * Create and store a stat declared but not present in DB yet
   *  (only happens when adding stats while a game is running)
   */
  public static function checkExistence()
  {
    $default = [
      'int' => 0,
      'float' => 0,
      'bool' => false,
      'str' => '',
    ];

    // Fetch existing stats, all stats
    $stats = Game::get()->getStatTypes();
    $existingStats = self::DB()
      ->get()
      ->map(function ($stat) {
        return $stat['type'] . ',' . ($stat['pId'] == null ? 'table' : 'player');
      })
      ->toArray();

    $values = [];
    // Deal with table stats first
    foreach ($stats['table'] as $stat) {
      if ($stat['id'] < 10) {
        continue;
      }
      if (!in_array($stat['id'] . ',table', $existingStats)) {
        $values[] = [
          'stats_type' => $stat['id'],
          'stats_player_id' => null,
          'stats_value' => $default[$stat['type']],
        ];
      }
    }

    // Deal with player stats
    $playerIds = Players::getAll()->getIds();
    foreach ($stats['player'] as $stat) {
      if ($stat['id'] < 10) {
        continue;
      }
      if (!in_array($stat['id'] . ',player', $existingStats)) {
        foreach ($playerIds as $i => $pId) {
          $value = $default[$stat['type']];

          $values[] = [
            'stats_type' => $stat['id'],
            'stats_player_id' => $pId,
            'stats_value' => $value,
          ];
        }
      }
    }

    // Insert if needed
    if (!empty($values)) {
      self::DB()
        ->multipleInsert(['stats_type', 'stats_player_id', 'stats_value'])
        ->values($values);
    }
  }
  
  protected static function getFilteredQuery($id, $pId)
  {
    $query = self::DB()->where('stats_type', $id);
    if (is_null($pId)) {
      $query = $query->whereNull('stats_player_id');
    } else {
      $query = $query->where('stats_player_id', is_int($pId) ? $pId : $pId->getId());
    }
    return $query;
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (preg_match('/^([gs]et|inc)([A-Z])(.*)$/', $method, $match)) {
      $stats = Game::get()->getStatTypes();

      // Sanity check : does the name correspond to a declared variable ?
      $name = mb_strtolower($match[2]) . $match[3];
      $isTableStat = \array_key_exists($name, $stats['table']);
      $isPlayerStat = \array_key_exists($name, $stats['player']);
      if (!$isTableStat && !$isPlayerStat) {
        throw new \InvalidArgumentException("Statistic {$name} doesn't exist");
      }

      if ($match[1] == 'get') {
        // Basic getters
        $id = null;
        $pId = null;
        if ($isTableStat) {
          $id = $stats['table'][$name]['id'];
        } else {
          if (empty($args)) {
            throw new \InvalidArgumentException("You need to specify the player for the stat {$name}");
          }
          $id = $stats['player'][$name]['id'];
          $pId = $args[0];
        }

        $row = self::getFilteredQuery($id, $pId)->get(true);
        return $row['value'];
      } elseif ($match[1] == 'set') {
        // Setters in DB and update cache
        $id = null;
        $pId = null;
        $value = null;

        if ($isTableStat) {
          $id = $stats['table'][$name]['id'];
          $value = $args[0];
        } else {
          if (count($args) < 2) {
            throw new \InvalidArgumentException("You need to specify the player for the stat {$name}");
          }
          $id = $stats['player'][$name]['id'];
          $pId = $args[0];
          $value = $args[1];
        }

        self::getFilteredQuery($id, $pId)
          ->update(['stats_value' => $value])
          ->run();
        return $value;
      } elseif ($match[1] == 'inc') {
        $id = null;
        $pId = null;
        $value = null;

        if ($isTableStat) {
          $id = $stats['table'][$name]['id'];
          $value = $args[0] ?? 1;
        } else {
          if (count($args) < 1) {
            throw new \InvalidArgumentException("You need to specify the player for the stat {$name}");
          }
          $id = $stats['player'][$name]['id'];
          $pId = $args[0];
          $value = $args[1] ?? 1;
        }

        self::getFilteredQuery($id, $pId)
          ->inc(['stats_value' => $value])
          ->run();
        return $value;
      }
    }
    return null;
  }

  /*********************
   **********************
   *********************/
  /**
   * @param Collection $players
   */
  public static function setupNewGame($players)
  {
    Stats::checkExistence();
    
    if(! Utils::isGameWithAutoma()) {
      Stats::unsetAutomatonStats();
    }
  }

  
  public static function setProgressStat(int $pId, int $progressType, int $value){
    switch($progressType){
      case TOKEN_TYPE_PROGRESS_MARKER_SHIPS:      $progressStat = "progressShips"; break;
      case TOKEN_TYPE_PROGRESS_MARKER_HARVEST:    $progressStat = "progressHarvest"; break;
      case TOKEN_TYPE_PROGRESS_MARKER_TAXES:      $progressStat = "progressTaxes"; break;
      case TOKEN_TYPE_PROGRESS_MARKER_EXP:        $progressStat = "progressExploration"; break;
      case TOKEN_TYPE_PROGRESS_MARKER_OUTPOST:    $progressStat = "progressFound"; break;
    }
    if(isset($progressStat)) {
      Stats::set( $progressStat, $pId, $value);
    }
  }
  public static function setAllProgressStat(int $value){

    $players = Players::getAll();
    foreach($players as $pId => $player){
      foreach(TOKEN_TYPE_PROGRESS_MARKERS as $type){
        Stats::setProgressStat($pId, $type, $value);
      }
    }
  }
  
  /** TO BE USED ON games with no automaton but after call to checkExistence which create useless stat */
  public static function unsetAutomatonStats(){
    //automaAuctionsWon
    Table::DbQuery("DELETE FROM `stats` where stats_type = 10 and stats_player_id is NULL");
  }

  
  public static function registerNewAction(Player $player, int $actionType){
    $statName = null;
    switch($actionType){
      case ACTION_TYPE_PROGRESS    : $statName = "actionA"; break;
      case ACTION_TYPE_BUILD_SHIPS : $statName = "actionB"; break;
      case ACTION_TYPE_PRODUCE     : $statName = "actionC"; break;
      case ACTION_TYPE_TAXES       : $statName = "actionD"; break;
      case ACTION_TYPE_EXP         : $statName = "actionE"; break;
      case ACTION_TYPE_FOUND       : $statName = "actionF"; break;
    }
    if(isset($statName)) {
      Stats::inc($statName,$player);
    }
  }
}
