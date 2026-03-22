<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\Effect;
use Bga\Games\Goa\Models\Player;

/* Class to manage all the tiles/cards/actions effects to resolve (saved in DB) */

class Effects extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'effects';
  protected static $prefix = 'effect_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = [
    'player_id', 
    'effect_parent_id',
    'effect_type', 
    'effect_qty',  
    'effect_source_tile',
    'effect_source_action',  
    'effect_result',
  ];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $data = [];
    return new Effect($row, $data);
  }
  
  public static function delete(int $effectId)
  {
    return self::DB()->delete($effectId);
  }

  public static function resolve(int $effectId)  : int {
    return self::DB()
      ->where(static::$prefix . 'id', $effectId)
      ->update([
        static::$prefix .'state' => EFFECT_STATE_SOLVED,
        static::$prefix .'location' => EFFECT_LOCATION_SOLVED ,
      ])
      ->run();
  }

  public static function ignore(int $effectId)  : int {
    return self::DB()
      ->where(static::$prefix . 'id', $effectId)
      ->update([
        static::$prefix .'state' => EFFECT_STATE_IGNORED,
        static::$prefix .'location' => EFFECT_LOCATION_IGNORED,
      ])
      ->run();
  }
  
  public static function addEffectOnTile(
    Player $player,
    int $tile_id,
    int $type,
    int|null $quantity,
    array $result,
    int|null $source_action = null,
    int|null $fromEffectId = null,
  ): Effect
  {
    $effect = [
      'player_id' => $player->getId(),
      'effect_parent_id' => $fromEffectId,
      'effect_type' => $type,
      'location' => EFFECT_LOCATION_TO_RESOLVE, //for the class Pieces
      static::$prefix .'state' => EFFECT_STATE_TO_SOLVE,
      'effect_qty' => $quantity,
      'effect_source_tile' => $tile_id,
      'effect_source_action' => $source_action,
      'effect_result' => json_encode($result),
    ];
    $elt = self::singleCreate($effect);
    return $elt;
  }
  
  public static function addEffectOnAction(
    Player $player,
    int $action_id,
    int $type,
    int|null $quantity,
    array $result,
  ): Effect
  {
    $effect = [
      'player_id' => $player->getId(),
      'effect_parent_id' => null,
      'effect_type' => $type,
      'location' => EFFECT_LOCATION_TO_RESOLVE, //for the class Pieces
      static::$prefix .'state' => EFFECT_STATE_TO_SOLVE,
      'effect_qty' => $quantity,
      'effect_source_tile' => null,
      'effect_source_action' => $action_id,
      'effect_result' => json_encode($result),
    ];
    $elt = self::singleCreate($effect);
    return $elt;
  }

  /**
   * @return Collection list of effects to resolve for param player
   */
  public static function listToResolve(int $playerId)  : Collection {
    //return self::getFilteredQuery($player->getId(), EFFECT_LOCATION_TO_RESOLVE)->get();
    return self::DB()
      ->wherePlayer($playerId)
      ->where(static::$prefix .'location', EFFECT_LOCATION_TO_RESOLVE)
      ->where(static::$prefix .'state', EFFECT_STATE_TO_SOLVE)
      ->get();
  }
  
  /**
   * @return int|null next player id with effects to resolve
   */
  public static function nextPlayerToResolve() : int|null {
    return self::DB()
      ->where(static::$prefix .'location', EFFECT_LOCATION_TO_RESOLVE)
      ->where(static::$prefix .'state', EFFECT_STATE_TO_SOLVE)
      ->get()
      ->map(function($effect) { return $effect->getPId();})
      ->first();
  }
  
  public static function countPlayerEffectsToResolve(int $playerId)  : int {
    return self::DB()
      ->where(static::$prefix .'state', EFFECT_STATE_TO_SOLVE)
      ->wherePlayer($playerId)
      ->count();
  }
   
  public static function countPlayerEffectsToResolveByTypeAndQ(int $playerId, int $type, int $quantity, int $tile_id): int {
    return self::DB()
      ->where('effect_type', $type)
      ->where('effect_source_tile', $tile_id)
      ->where('effect_qty', $quantity)
      ->where(static::$prefix .'state', EFFECT_STATE_TO_SOLVE)
      ->wherePlayer($playerId)
      ->count();
  }
  /**
   * @return Collection (should be 0 or 1 elements)
   */
  public static function getEffectToResolveByType(int $effectType, int $playerId) : Collection
  {
    return self::DB()
      ->where('effect_type', $effectType)
      ->where(static::$prefix .'state', EFFECT_STATE_TO_SOLVE)
      ->wherePlayer($playerId)
      ->get();
  }

}
