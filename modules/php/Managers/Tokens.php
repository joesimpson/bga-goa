<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\AuctionMarker;
use Bga\Games\Goa\Models\Token;

/* Class to manage all the tokens */

class Tokens extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'tokens';
  protected static $prefix = 'token_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type', 'subtype'];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $subtype = isset($row['subtype']) ? $row['subtype'] : null;
    switch ($subtype) {
      case TOKEN_SUBTTYPE_PROGRESS_MARKER:
        //TODO JSA
      case TOKEN_SUBTTYPE_AUCTION_MARKER:
        $data = self::getTokensTypes()[$type];
        return new AuctionMarker($row, $data);
    }

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
      ->merge(self::getInLocation(TOKEN_LOCATION_PLAYER_BOARD))
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
   * Return all Board tokens of this player
   * @param int $pId
   * @return Collection
   */
  public static function getPlayerBoard($pId)
  {
    return self::getFilteredQuery($pId, TOKEN_LOCATION_PLAYER_BOARD)->get();
  }

  ///////////////////////////////////////////////////////////////////////////////
  
  /** Creation of the tokens */
  public static function setupNewGame($players, $options)
  {
    $tokens = [];
    
    //foreach (self::getTokensTypes() as $type => $token) {
    //  $tokens[] = [
    //    'location' => TOKEN_LOCATION_DECK,
    //    'type' => $type,
    //    'nbr' => $token['nbr'],
    //  ];
    //} 

    foreach ($players as $pId => $player) {
      for($k = 0; $k < count($players) +1; $k++){
        $tokens[] = [
          'location' => TOKEN_LOCATION_PLAYER_BOARD,
          'type' => TOKEN_TYPE_AUCTION_MARKERS[$k],
          'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,
          'player_id' => $pId,
          'nbr' => 1,
        ];
      } 
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
      TOKEN_TYPE_AUCTION_MARKER_1 => $f([ 1, ]), 
      TOKEN_TYPE_AUCTION_MARKER_2 => $f([ 1, ]), 
      TOKEN_TYPE_AUCTION_MARKER_3 => $f([ 1, ]), 
      TOKEN_TYPE_AUCTION_MARKER_4 => $f([ 1, ]), 
      TOKEN_TYPE_AUCTION_MARKER_5 => $f([ 1, ]), 
    ];
  }
}
