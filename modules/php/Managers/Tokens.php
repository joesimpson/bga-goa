<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\AuctionMarker;
use Bga\Games\Goa\Models\FirstAuctionMarker;
use Bga\Games\Goa\Models\Player;
use Bga\Games\Goa\Models\ProgressMarker;
use Bga\Games\Goa\Models\Tile;
use Bga\Games\Goa\Models\Token;
use Bga\Games\Goa\Models\TokenSpice;

/* Class to manage all the tokens */

class Tokens extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'tokens';
  protected static $prefix = 'token_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type', 'subtype', 'row', 'col'];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $subtype = isset($row['subtype']) ? $row['subtype'] : null;
    switch ($subtype) {
      case TOKEN_SUBTTYPE_PROGRESS_MARKER:
        $data = self::getTokensTypes()[$type];
        return new ProgressMarker($row, $data);
      case TOKEN_SUBTTYPE_AUCTION_MARKER:
        $data = self::getTokensTypes()[$type];
        return new AuctionMarker($row, $data);
      case TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER:
        $data = self::getTokensTypes()[$type];
        return new FirstAuctionMarker($row, $data);
      case TOKEN_SUBTTYPE_SPICE:
        $data = self::getTokensSpiceTypes()[$type];
        return new TokenSpice($row, $data);
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
      ->merge(self::getInLocationOrdered(TOKEN_LOCATION_TILE.'%'))
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
  
  public static function countSpices(int $pId, int $spiceTokenType)
  {
    return self::DB()
      ->where( 'type', $spiceTokenType)
      ->where( 'subType', TOKEN_SUBTTYPE_SPICE)
      ->wherePlayer($pId)
      ->count();
  }
  /**
   * Return all Board tokens of this player
   * @param int $pId
   * @return Collection
   */
  public static function getPlayerBoard(int $pId)
  {
    return self::getFilteredQuery($pId, TOKEN_LOCATION_PLAYER_BOARD)->get();
  }
  
  public static function delete(int $tokenId)
  {
    return self::DB()->delete($tokenId);
  }
  
  public static function addSpiceOnTile(Tile $tile,Player $player,int $tokenType,int $position): TokenSpice
  {
    $token = [
      'type' => $tokenType,
      'subtype' => TOKEN_SUBTTYPE_SPICE,
      'location' => TOKEN_LOCATION_TILE.$tile->getId(),
      'player_id' => $player->getId(),
      'state' => $position,
    ];
    $elt = self::singleCreate($token);
    Notifications::newSpiceOnTile($player,$elt,$tile);
    return $elt;
  }
  
  public static function placeFirstAuctionMarker(Player $player,int $row,int $col): FirstAuctionMarker
  {
    $token = self::getFirstPlayerToken();
    $token->setRow($row);
    $token->setCol($col);
    $token->setPId($player->getId());
    $token->setLocation(TOKEN_LOCATION_BOARD);
    return $token;
  }
  
  public static function getFirstPlayerToken(): FirstAuctionMarker
  {
    $token = self::DB()
      ->where( 'type', TOKEN_TYPE_FIRSTAUCTION_MARKER)
      ->where( 'subType', TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER)
      ->get()
      ->first()
      ;
    return $token;
  }

  public static function giveFirstPlayerToken(Player $player): FirstAuctionMarker
  {
    $token = self::getFirstPlayerToken();
    $token->setPId($player->getId());
    $token->setLocation(TOKEN_LOCATION_PLAYER_BOARD);
    Notifications::giveFirstPlayerToken($player, $token);
    return $token;
  }
  public static function placeAuction(Player $player,int $row,int $col, int $type): AuctionMarker
  {
    $token = self::DB()
      ->where( 'type', $type)
      ->where( 'subType', TOKEN_SUBTTYPE_AUCTION_MARKER)
      ->wherePlayer($player->getId())
      ->get()
      ->first()
      ;
    $token->setRow($row);
    $token->setCol($col);
    $token->setLocation(TOKEN_LOCATION_BOARD);
    return $token;
  }

  public static function getNextAuctionToken(Player $player): AuctionMarker|null
  {
    $placedTokens = self::DB()
      ->where( 'subType', TOKEN_SUBTTYPE_AUCTION_MARKER)
      ->where( static::$prefix . 'location', TOKEN_LOCATION_BOARD)
      ->get()
      ;

    $placedTypes = $placedTokens->map(function ($token) { return $token->getType(); })->toArray();
    $nextAuctionType = null;
    foreach (TOKEN_TYPE_AUCTION_MARKERS as $k => $type){
      if(in_array($type,$placedTypes )) continue;
      $nextAuctionType = $type;
      break;
    }
    if(!isset($nextAuctionType)){
      return null;
    }
    $token = self::DB()
      ->where( 'type', $nextAuctionType)
      ->where( 'subType', TOKEN_SUBTTYPE_AUCTION_MARKER)
      ->wherePlayer($player->getId())
      ->get()
      ->first()
      ;
    return $token;
  }

  public static function getCurrentAuctionToken(): AuctionMarker|null
  {
    $placedTokens = self::DB()
      ->where( 'subType', TOKEN_SUBTTYPE_AUCTION_MARKER)
      ->where( static::$prefix . 'location', TOKEN_LOCATION_BOARD)
      ->orderBy('type')
      ->get()
      ;
    //We suppose the first low type still on board is to be auctioned
    $token = $placedTokens->first();
    return $token;
  }

  public static function returnAuctionMarkerToPlayer(Player $player, AuctionMarker $token) {
    $token->setLocation(TOKEN_LOCATION_PLAYER_BOARD);
    Notifications::auctionMarkerBack($player,$token);
  }

  
  public static function getTokenSpiceTypeFromSpice(int $spiceType) : int | null
  {
    $types = Tokens::getTokensSpiceTypes();
    foreach($types as $tokenType => $tokenTypeDatas){
      if($tokenTypeDatas['spice'] == $spiceType) return $tokenType;
    }
    return null;
  }
  ///////////////////////////////////////////////////////////////////////////////
  
  /** Creation of the tokens */
  public static function setupNewGame($players, $options)
  {
    $tokens = [];
  
    //create first player marker, because it is 1 per game
    $tokens[]= [
      'type' => TOKEN_TYPE_FIRSTAUCTION_MARKER,
      'subtype' => TOKEN_SUBTTYPE_FIRSTAUCTION_MARKER,
      'location' => TOKEN_LOCATION_PLAYER_BOARD,
      'player_id' => Globals::getFirstPlayer(),
      'nbr' => 1,
    ];
    
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

      for($k = 0; $k < count(TOKEN_TYPE_PROGRESS_MARKERS); $k++){
        $tokens[] = [
          'location' => TOKEN_LOCATION_PLAYER_BOARD,
          'type' => TOKEN_TYPE_PROGRESS_MARKERS[$k],
          'subtype' => TOKEN_SUBTTYPE_PROGRESS_MARKER,
          'player_id' => $pId,
          'nbr' => 1,
          'state' =>1,//row 1
        ];
      }
    }

    self::create($tokens);
  }
  
  
  public static function getTokensSpiceTypes()
  {
    $f = function ($t) {
      return [
        'spice' => $t[0],
      ];
    };
    return [
      TOKEN_TYPE_SPICE_WHITE    => $f([ SPICE_WHITE  ]), 
      TOKEN_TYPE_SPICE_BLACK    => $f([ SPICE_BLACK  ]), 
      TOKEN_TYPE_SPICE_YELLOW   => $f([ SPICE_YELLOW ]), 
      TOKEN_TYPE_SPICE_PINK     => $f([ SPICE_PINK   ]), 
      TOKEN_TYPE_SPICE_GREEN    => $f([ SPICE_GREEN  ]), 
    ];
  }

  /**
   * @return array of all the different types of Tokens
   */
  public static function getTokensTypes()
  {
    $f = function ($t) {
      return [
        //'nbr' => $t[0],
      ];
    };
    return [
      TOKEN_TYPE_PROGRESS_MARKER_SHIPS   => $f([  ]), 
      TOKEN_TYPE_PROGRESS_MARKER_HARVEST => $f([  ]), 
      TOKEN_TYPE_PROGRESS_MARKER_TAXES   => $f([  ]), 
      TOKEN_TYPE_PROGRESS_MARKER_EXP     => $f([  ]), 
      TOKEN_TYPE_PROGRESS_MARKER_OUTPOST => $f([  ]), 
      TOKEN_TYPE_AUCTION_MARKER_1 => $f([  ]), 
      TOKEN_TYPE_AUCTION_MARKER_2 => $f([  ]), 
      TOKEN_TYPE_AUCTION_MARKER_3 => $f([  ]), 
      TOKEN_TYPE_AUCTION_MARKER_4 => $f([  ]), 
      TOKEN_TYPE_AUCTION_MARKER_5 => $f([  ]), 
      TOKEN_TYPE_FIRSTAUCTION_MARKER => $f([  ]), 
    ];
  }
  
}
