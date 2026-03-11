<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Models\AuctionMarker;
use Bga\Games\Goa\Models\Effect;
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
  protected static $customFields = ['player_id', 'type', 'subtype',  'token_row', 'token_col'];
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
      ->merge(self::getInLocation(TOKEN_LOCATION_AUTOMA_BOARD))
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
  
  public static function addSpiceOnTile(Tile $tile,Player $player,int $tokenType,int $position, bool $sendNotif = true): TokenSpice
  {
    $token = [
      'type' => $tokenType,
      'subtype' => TOKEN_SUBTTYPE_SPICE,
      'location' => TOKEN_LOCATION_TILE.$tile->getId(),
      'player_id' => $player->getId(),
      'state' => $position,
      'token_row' => $position,
    ];
    $elt = self::singleCreate($token);
    if($sendNotif) Notifications::newSpiceOnTile($player,$elt,$tile);
    Stats::inc("receivedSpices", $player);
    Game::get()->trace("addSpiceOnTile($tokenType,$position) result = ".json_encode($elt));
    return $elt;
  }
  
  public static function removeSpices(Collection $tokens,Player $player,)
  { 
    foreach($tokens as $spice){
      self::DB()->delete($spice->getId());
    }
    Notifications::removeSpices($player, $tokens);
  }

  public static function removeSpicesOnTile(Tile $tile,Player $player,)
  { 
    $tokens = self::DB()
      ->where( 'subType', TOKEN_SUBTTYPE_SPICE)
      ->where( static::$prefix . 'location', TOKEN_LOCATION_TILE.$tile->getId())
      ->get();
    foreach($tokens as $token){
      self::DB()->delete($token->getId());
    } 
    Notifications::removeTileSpices($player,$tile, $tokens);
  }
  
  public static function removeSpicesOnAllTiles()
  { 
    $tokens = self::getInLocationOrdered(TOKEN_LOCATION_TILE.'%');
    foreach($tokens as $token){
      self::DB()->delete($token->getId());
    } 
    //TODO JSA  for option CAMPAIGN NOTIFY removeSpicesOnAllTiles + update UI counters
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
  public static function getPlayerSpices(int $playerId) : Collection
  {
    return self::DB()
      ->where('subtype', TOKEN_SUBTTYPE_SPICE)
      ->wherePlayer($playerId)
      ->get();
  }
  
  public static function countSpicesOnTile(int $tile_id, int|null $spotIndex = null) : Collection
  {

    $query = self::DB()
      ->where('subtype', TOKEN_SUBTTYPE_SPICE)
      ->where( static::$prefix . 'location', TOKEN_LOCATION_TILE.$tile_id);
    if($spotIndex) $query = $query->where( static::$prefix . 'state', $spotIndex);
    return $query->get();
  }

  public static function countPendingSpiceOnTileSpot(int $playerId,int $tile_id, int $spotIndex) : int
  {
    return Effects::countPlayerEffectsToResolveByTypeAndQ($playerId, EFFECT_TYPE_PICK_SPICE,$spotIndex, $tile_id,);
  }
  
  
  public static function getProgressMarkerByType(int $progressType, int $playerId) : ProgressMarker
  {
    return self::DB()
      ->where('type', $progressType)
      ->where('subtype', TOKEN_SUBTTYPE_PROGRESS_MARKER)
      ->wherePlayer($playerId)
      ->get()->first();
  }
  
  public static function getProgressMarkers( int $playerId) : Collection
  {
    return self::DB()
      ->where('subtype', TOKEN_SUBTTYPE_PROGRESS_MARKER)
      ->wherePlayer($playerId)
      ->get();
  }
  public static function getUpgradableProgressMarkers( Player $player, bool $withSpices = true, bool $withShips = true) : array
  {
    $allMarkers = Tokens::getProgressMarkers($player->getId());
    $ships = $player->getShips();
    $playerSpices = Tokens::getPlayerSpices($player->getId());

    return $allMarkers->map(function ($token) use ($playerSpices,$withSpices, $withShips){ 
                $map = [
                  'track' => $token->getTrackIndex(),
                  'row' => $token->getRow(),
                  'withSpices' => $withSpices,
                  'withShips' => $withShips,
                  'withCoins' => false,
                ];

                $currentRow = $token->getRow();
                $trackSpiceCosts = PROGRESS_COST_AS_SPICES[$token->getType()];
                if( array_key_exists($currentRow+1, $trackSpiceCosts)){
                  $spiceCosts = $trackSpiceCosts[$currentRow+1];
                  $map['costShips'] = count($spiceCosts);
                  $map['costSpices'] = [];
                  $map['spicesSets'] = [];//[[1022,1027],[1022,1028],[1023,1027],[1023,1028]];
                  foreach($spiceCosts as $spice){
                    $spiceCost = count(array_keys($spiceCosts,$spice));
                    $spicesFromTokenIds = $playerSpices->filter(function ($playerSpice) use ($spice) {return $spice == $playerSpice->getSpice();})->getIds();

                    $map['costSpices'][$spice] = ['n' => $spiceCost, 'from' => $spicesFromTokenIds];
                  }

                }
                return $map;
              })
            ->filter(function ($tokenDatas) use ($ships, $withSpices, $withShips) {
                if(!isset($tokenDatas['costShips'])) return false;
                if($ships < $tokenDatas['costShips'] && $withShips){
                  return false;
                }
                foreach($tokenDatas['costSpices'] as $spice => $costDatas){
                  $spiceCost = $costDatas['n'];
                  $nbPlayerSpices = count($costDatas['from']);
                  if($nbPlayerSpices < $spiceCost && $withSpices){
                    return false;
                  }
                }
                return true;
            })
            ->map(function ($map) use ( $withSpices){
              //FIX StackOverflow: Don't precompute all combinations because it won't work with many spices of same type (>8)
              return $map;

              $possibleSets = [];
              //Save each possible set of spices to pay this progress
              if( $withSpices){
                foreach($map['costSpices'] as $spice => $datas){
                  $possibleSets[$spice] = [];
                  $perms = Utils::computePermutations(array_values($datas['from']));
                  //take n elements from array of each entry
                  foreach($perms as $p){
                    $possibleSets[$spice][] = array_values(array_slice($p,0,$datas['n']));
                  }
                  $possibleSets[$spice] = Utils::array_of_uniquearrays($possibleSets[$spice]);
                  
                }
              }

              //Game::get()->trace(__FUNCTION__.'() : possibleSets = '.json_encode($possibleSets));
              $map['spicesSets'] = Utils::array_cartesian($possibleSets);
              //Game::get()->trace(__FUNCTION__.'() : spicesSets = '.json_encode($map['spicesSets']));

              return $map;
            })
          ->toArray();
  }

  public static function getUpgradableProgressMarkersWithCoins( Player $player, bool $free = false, bool $onlyHighest = false) : array
  {
    $allMarkers = Tokens::getProgressMarkers($player->getId());
    $playerMoney = $player->getMoney();

    $maxRow = max(array_keys(PROGRESS_COST_AS_COINS));
    $minRow = $maxRow;

    return $allMarkers->map(function ($token) use ($free, &$minRow){ 
                $map = [
                  'track' => $token->getTrackIndex(),
                  'row' => $token->getRow(),
                  'withSpices' => false,
                  'withShips' => false,
                  'withCoins' => true,
                  'costMoney' => 0,
                ];
                $currentRow = $token->getRow();
                $costs = PROGRESS_COST_AS_COINS;
                $map['costMoney'] = 0;
                if( array_key_exists($currentRow+1, $costs)){
                  $map['costMoney'] = $costs[$currentRow+1];
                  if($free) $map['costMoney'] = 0;
                }
                if($minRow > $currentRow){
                  $minRow = $currentRow;
                }
                return $map;
              })
            ->filter(function ($tokenDatas) use ($playerMoney , $onlyHighest, &$minRow ) {
                if($playerMoney < $tokenDatas['costMoney'] ){
                  return false;
                }
                if($onlyHighest && $tokenDatas['row'] > $minRow) return false;

                return true;
            }) 
          ->toArray();
  }

  public static function countProgressMarkersBelowRow( int $row) : int
  {
    $maxProgress = count(PROGRESS_GAINS_SHIPS);
    $rowsBelow = [];
    for($k =$row;$k<=$maxProgress ;$k++){
      $rowsBelow[] = $k;
    }
    return self::DB()
      ->where('subtype', TOKEN_SUBTTYPE_PROGRESS_MARKER)
      ->whereIn('token_row', $rowsBelow)
      ->count();
  }
  
  /**
   * @throws UnexpectedException
   */
  public static function progressOnTrack(int $progressType, int $playerId) : ProgressMarker
  {
    $token = Tokens::getProgressMarkerByType($progressType, $playerId);
    $row = $token->getRow();
    $maxProgress = count(PROGRESS_GAINS_SHIPS);
    if($row == $maxProgress)
      throw new UnexpectedException(150,"Impossible to progress more on this track ($progressType)");

    $token->setRow($row+1);
    
    $player = Players::get($playerId);
    Notifications::progress($player, $token);
    Stats::setProgressStat($playerId, $progressType, $token->getRow());

    return $token;
  }

  public static function setupSession(int $session)
  {
    // replace all progress markers on top
    self::DB()
      ->where('subtype', TOKEN_SUBTTYPE_PROGRESS_MARKER)
      ->update(['token_row' => 1])
      ->run();

    Stats::setAllProgressStat(1);

    //NOTIFY MARKERS ? for now I catch newSession notif
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
          'token_row' => 1,
          'token_col' => $k+1,
        ];
      }
    }

    $automaColor = Globals::getAutomaColor();
    if(isset($automaColor)){
      //INIT AUTOMA Auction tokens
      
      for($k = 0; $k < count($players) +1; $k++){
        $tokens[] = [
          'location' => TOKEN_LOCATION_AUTOMA_BOARD,
          'type' => TOKEN_TYPE_AUCTION_MARKERS[$k],
          'subtype' => TOKEN_SUBTTYPE_AUCTION_MARKER,
          'nbr' => 1,
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
