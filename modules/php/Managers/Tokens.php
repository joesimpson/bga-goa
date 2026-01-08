<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\Token;

/* Class to manage all the tokens */

class Tokens extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'tokens';
  protected static $prefix = 'token_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type'];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $data = self::getTokensTypes()[$type];
    return new Token($row, $data);
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all tokens visible by this player
   */
  public static function getUiData($currentPlayerId)
  {

    return 
      self::getInLocation(TOKEN_LOCATION_BOARD)
      //->merge(self::getInLocation(TOKEN_LOCATION_HAND))
      ->map(function ($token) {
        return $token->getUiData();
      })
      ->toArray();
  } 
 
  /**
   * Return all BOARD tokens 
   * @param int $player_id (Optional) filter player tokens
   * @return Collection
   */
  public static function getBoardTokens($player_id = null)
  {
    return self::getFilteredQuery($player_id,TOKEN_LOCATION_BOARD)->get();
  }

  /**
   * @param int $pId
   * @param string $location (optional)
   * @return int number of ALL tokens owned by that player and in that $location,
   *   or ALL tokens owned by that player if location not given
   */
  public static function countPlayerTokens($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location)->count();
  }
  
  /**
   * Return all HAND tokens of this player
   * @param int $pId
   * @return Collection
   */
  public static function getPlayerHand($pId)
  {
    return self::getFilteredQuery($pId, TOKEN_LOCATION_HAND)->get();
  }

  ///////////////////////////////////////////////////////////////////////////////
  
  /** Creation of the tokens */
  public static function setupNewGame($players, $options)
  {
    $tokens = [];

    foreach (self::getTokensTypes() as $type => $token) {
      $tokens[] = [
        'location' => TOKEN_LOCATION_DECK,
        'type' => $type,
        'nbr' => $token['nbr'],
      ];
    } 

    self::create($tokens);
  }
  
  /**
   * @return array of all the different types of Tokens
   */
  public static function getTokensTypes()
  {
    $f = function ($t) {
      return [
        'nbr' => $t[0],
      ];
    };
    return [
      1 => $f([ 10, ]), 
      2 => $f([ 10, ]), 
    ];
  }
}
