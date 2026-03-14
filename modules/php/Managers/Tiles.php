<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Exceptions\PrematureStateChangeException;
use Bga\Games\Goa\Exceptions\UnexpectedException;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Helpers\GridUtils;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Models\MarketTile;
use Bga\Games\Goa\Models\OutpostTile;
use Bga\Games\Goa\Models\Player;
use Bga\Games\Goa\Models\Tile;
use Bga\Games\Goa\States\PlayerChoice;
use InvalidArgumentException;

/* Class to manage all the Tiles */

class Tiles extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'tiles';
  protected static $prefix = 'tile_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = [
    'player_id', 
    'type', 
    'subtype', 
    'tile_start_location', 
    'tile_row', 
    'tile_col'
  ];
  protected static $autoreshuffle = false;

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $subtype = isset($row['subtype']) ? $row['subtype'] : null;
    switch ($subtype) {
      case TILE_SUBTTYPE_OUTPOST:
        $data = self::getOutpostTilesTypes()[$type];
        return new OutpostTile($row, $data);
      case TILE_SUBTTYPE_MARKET:
        $data = self::getMarketTilesTypes()[$type];
        return new MarketTile($row, $data);
    }

    throw new InvalidArgumentException("Tile $type, $subtype");
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all Tiles visible by this player
   */
  public static function getUiData($currentPlayerId)
  {

    return 
      self::getInLocation(TILE_LOCATION_BOARD_MARKET)
      ->merge(self::getInLocation(TILE_LOCATION_PLAYER_BOARD))
      ->merge(self::getInLocation(TILE_LOCATION_PLAYER_WAITING_PLANTATION))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOLLAM    ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOCHI     ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_MADRAS    ]))
      ->merge(self::getInLocation(TILE_LOCATION_BOARD_OUTPOSTS[COLONY_KOZHIKODE ]))
      ->merge(Tiles::getTilesForFavor())
      
      //for debug :
      //->merge(self::getInLocation(TILE_LOCATION_DECK_A))
      //->merge(self::getInLocation(TILE_LOCATION_DECK_B))

      ->map(function ($tile) {
        return $tile->getUiData();
      })
      ->toArray();
  } 
 
  /**
   * Return all BOARD Tiles 
   * @param int $player_id (Optional) filter player Tiles
   * @return Collection
   */
  public static function getBoardTiles($player_id = null)
  {
    return self::getFilteredQuery($player_id,TILE_LOCATION_BOARD_MARKET)->get();
  }

  /**
   * @param int $pId
   * @param string $location (optional)
   * @return int number of ALL Tiles owned by that player and in that $location,
   *   or ALL Tiles owned by that player if location not given
   */
  public static function countPlayerTiles($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location)->count();
  }
  
  public static function getByType(int $cardType, int $cardSubtype)
  {
    return self::DB()
      ->where('type', $cardType)
      ->where('subtype', $cardSubtype)
      ->get();
  }

  /**
   * Get the market tiles placed at [row, col]
   * then give it to player
   * @param array $blackListIds (optional) list of ids we don't want to give
   * 
   * @throws PrematureStateChangeException when caller will need to go to another state than default one
   * */
  public static function giveMarketTileToPlayerAt(Player $player, int $row, int $col, bool &$doCheckpoint, array $blackListIds = []):void
  {
    $tilesOnMarketSpace = self::DB()
      ->where( static::$prefix . 'location', TILE_LOCATION_BOARD_MARKET)
      ->where('tile_row', $row)
      ->where('tile_col', $col)
      ->whereNotIn(static::$prefix .'id', $blackListIds)
      ->get();
      //TODO JSA multiples if BONANZA + warning if Tile WILD spice, it adds another choice 

    $tile = $tilesOnMarketSpace->first();
    if(isset($tile)){
      if($player->getId() == AUTOMA_PLAYER_ID){
        //AUTOMA DISCARD every tiles
        Tiles::discardTileFromPlayer($player, $tile);
      }
      else {
        Tiles::giveMarketTileToPlayer($player, $tile, $doCheckpoint);
      }
    }
  }
  
  /**
   * Get the selected market tile
   * then give it to player
   * @throws PrematureStateChangeException when caller will need to go to another state than default one
   * */
  public static function giveMarketTileToPlayer(Player $player, MarketTile &$tile, bool &$doCheckpoint) :void
  {
    $stateExc = null;  
    
    if(isset($tile)){

      $updateTileDatas = function ( $player, &$tile, bool $waiting = false) {
        $tile->setPId($player->getId());
        $tile->setRow(null);
        $tile->setCol(null);
        if($waiting){
          $tile->setLocation(TILE_LOCATION_PLAYER_WAITING_PLANTATION);
        }
        else {
          $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
        }
        $tile->setState(null);
      };
      
      switch ($tile->getPlay_type()){
        case MARKET_TYPE_PLANTATION:
          $nextAvailableSpaceOnPlayerBoard = Tiles::getNextAvailablePlantationSpot($player);
          if(!isset($nextAvailableSpaceOnPlayerBoard)){
            // IF no more space ask player to discard 1  
            $updateTileDatas($player,$tile, true);
            Notifications::giveTile($player,$tile);
            $playerPlantations = Tiles::getPlantations($player);
            $replacableIds = $playerPlantations->getIds();
            $replacableIds[] = $tile->getId(); //allow to discard waiting tile
            Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_REPLACE_TILE, 1, $replacableIds);
            throw new PrematureStateChangeException (3,PlayerChoice::class, $player);
            //don't modify tile location before this exception !
          }
          $updateTileDatas($player,$tile, false);

          $tile->setState($nextAvailableSpaceOnPlayerBoard);
          Notifications::giveTile($player,$tile);
          Tiles::fillPlayerTileWithSpices($player,$tile);
          Stats::inc("playedPlantations", $player->getId());
          break;
        case MARKET_TYPE_ACTION_DIRECT:
          $updateTileDatas($player,$tile, false);
          Tiles::discardTileFromPlayer($player, $tile);
          Stats::inc("playedTiles", $player->getId());
          $gains = $tile->getActions();
          foreach($gains as $type => $quantity){
            try{
              Players::directGain($player,$type,$quantity, $tile, $doCheckpoint);
            }
            catch(PrematureStateChangeException $exc){
              $stateExc = $exc;
            }
          }
          break;
        default:
          $updateTileDatas($player,$tile, false);
          Notifications::giveTile($player,$tile);
          break;
      }

    }
    if(isset($stateExc)) {
        throw $stateExc;
    }
  }
  
  
  /**
   * Get the selected outpost tile
   * then give it to player
   * @throws PrematureStateChangeException when caller will need to go to another state than default one
   * */
  public static function giveOutpostToPlayer(Player $player, OutpostTile &$tile) :void
  {
    Game::get()->trace("giveOutpostToPlayer(".$player->getId().") : ".json_encode($tile));
    $updateTileDatas = function ( $player, &$tile,) {
      $tile->setPId($player->getId());
      $tile->setRow(null);
      $tile->setCol(null);
      $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
      $tile->setState(null);
    };

    $updateTileDatas($player,$tile);

    Stats::inc("playedOutposts", $player->getId());

    Notifications::giveTile($player,$tile);
    Tiles::fillPlayerTileWithSpices($player,$tile);
  }
  
  /**
   * @throws PrematureStateChangeException when caller will need to go to another state than default one
   * */
  public static function fillPlayerTileWithSpices(Player $player, Tile &$tile)
  {
    $stateExc = null;  
    //FILL tile spices_slots
    $slots = $tile->getSpice_slots();
    $slotPosition = 0;
    $tokenSpices = [];
    foreach($slots as $spice){
      $slotPosition++;
      
      if(is_array($spice)){
        //IF WILD SPICE, ASK PLAYER choice ! 
        //Save datas for next state
        Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_PICK_SPICE, $slotPosition, $spice);
        $stateExc = new PrematureStateChangeException (3,PlayerChoice::class, $player);
      }
      else {
        $spiceTokenType = Tokens::getTokenSpiceTypeFromSpice($spice);
        $tokenSpices[] = Tokens::addSpiceOnTile($tile, $player, $spiceTokenType, $slotPosition,false);
      }
    }
    Notifications::newSpices($player, new Collection($tokenSpices));
    
    if(isset($stateExc)) {
      throw $stateExc;
    }

  }

  public static function discardTileFromPlayer(Player $player, Tile &$tile)
  {
    Tokens::removeSpicesOnTile($tile,$player,);

    if($tile->getPlay_type() == MARKET_TYPE_PLANTATION 
    //&& $tile->getEnd_Score() > 0
    ){
      //KEEP PLANTATION in player tiles zone for end score
      $tile->setLocation(TILE_LOCATION_PLAYER_BOARD);
      $tile->setState(0);
      Notifications::replaceTile($player,$tile);
    }
    else {
      $tile->setPId(null);
      $tile->setLocation(TILE_LOCATION_DISCARD);
      Notifications::discardTile($player,$tile);
    }
  }

  /**
   * @return Collection list of player market tiles 
   */
  public static function getPlayerMarketTiles(Player $player) : Collection
  {
    $tiles = self::DB()
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->where( static::$prefix . 'location', TILE_LOCATION_PLAYER_BOARD)
      ->wherePlayer($player->getId())
      ->get();
 
    return $tiles;
  }
  /**
   * @return Collection list of player tiles not used to plant something (no plantation/outpost)
   */
  public static function getNotPlantations(Player $player) : Collection
  {
    $plantationTypes = Tiles::getTilesTypesByMarketPlayType(MARKET_TYPE_PLANTATION);
    $tiles = self::DB()
      ->whereNotIn('type', $plantationTypes)
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->where( static::$prefix . 'location', TILE_LOCATION_PLAYER_BOARD)
      ->wherePlayer($player->getId())
      ->get();
 
    return $tiles;
  }

  public static function getPlantations(Player $player) : Collection
  {
    $plantationTypes = Tiles::getTilesTypesByMarketPlayType(MARKET_TYPE_PLANTATION);
    $plantationsOnPlayerBoard = self::DB()
      ->whereIn('type', $plantationTypes)
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->whereNotIn( static::$prefix . 'state', [0, null])
      ->where( static::$prefix . 'location', TILE_LOCATION_PLAYER_BOARD)
      ->wherePlayer($player->getId())
      ->get();
 
    return $plantationsOnPlayerBoard;
  }

  public static function getNextAvailablePlantationSpot(Player $player,) : int |null
  {
    $plantationsOnPlayerBoard = self::getPlantations($player);

    $unavailablePositions = $plantationsOnPlayerBoard->map( function($tile) {
      return $tile->getState();
    })->toArray();

    if(count($unavailablePositions) == 0) return 1;

    $maxPos = max( $unavailablePositions);

    for($k=1; $k<=PLAYER_PLANTATIONS_MAX;$k++){
      if(!in_array($k, $unavailablePositions)) return $k;
    }
    Game::get()->trace("getNextAvailablePlantationSpot(".$player->getId().") : no space because unavailablePositions = ".json_encode($unavailablePositions));
    return null;
  }
  
  public static function getMarketTilesByTypes(array $types) : Collection
  {
    $tiles = self::DB()
      ->whereIn('type', $types)
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->get();
 
    return $tiles;
  }

  /**
   * @param int phase Default is 1 for DAY , 2 for night
   */
  public static function setupMarket(int $phase = 1){
    $deck = TILE_LOCATION_DECK_A;
    if($phase == 1){
      //RESET FROM THE BEGINNING 
      Tokens::removeSpicesOnAllTiles();
      Tiles::resetMarketTilesDeck();
    }
    else if($phase == 2){
      //EMPTY BOARD
      Tiles::moveAllInLocation(TILE_LOCATION_BOARD_MARKET, TILE_LOCATION_DISCARD);
      
      $deck = TILE_LOCATION_DECK_B;
    }
    
    //Sould be MARKET_GRID_SIZE*MARKET_GRID_SIZE + 4 without variants
    $deckSize = Tiles::countInLocation($deck);

    $nbTilesToRemove = $deckSize - (MARKET_GRID_SIZE*MARKET_GRID_SIZE);
    $nbRemovedPlantations = null;
    $boxTiles = null;
    while($nbTilesToRemove>0 && (!isset($nbRemovedPlantations) || $nbRemovedPlantations > 2)){
      if(isset($boxTiles)){
        // if more than 2 plantations, pick and replace ...
        foreach($boxTiles as $tile_id => &$tile){
          $tile->setLocation($deck);
        }
      }
      //SHUFFLE deck of current phase
      Tiles::shuffle($deck);
      $boxTiles = Tiles::pickForLocation($nbTilesToRemove,$deck,TILE_LOCATION_BOX);
      $nbRemovedPlantations = $boxTiles->filter((function ($tile){ return MARKET_TYPE_PLANTATION == $tile->getPlay_type();}))->count();

      //Game::get()->trace("setupMarket($phase), nbRemovedPlantations = $nbRemovedPlantations ...");
    }
    
    //DRAW TILES
    $deckPhase = self::getInLocationOrdered($deck);
    $counter = 0;
    foreach($deckPhase as $tile_id => &$tile){
      $tile->setLocation(TILE_LOCATION_BOARD_MARKET);
      $tile->setRow(1 + intval($counter / MARKET_GRID_SIZE) );
      $tile->setCol(1 + $counter - ($tile->getRow()-1)*MARKET_GRID_SIZE );
      $counter++;
    }

    //NOTIFY
    $allBoxTiles = Tiles::getInLocationOrdered(TILE_LOCATION_BOX);
    Notifications::setupMarket($phase,$deckPhase, $allBoxTiles);
  }
  
  /**
   * @param int $pType the type to search
   * @return array list of types
   */
  public static function getTilesTypesByMarketPlayType($pType){
    $types = [];
    $marketTiles = Tiles::getMarketTilesTypes();
    foreach ($marketTiles as $type => $tile) {
      if($pType == $tile['play_type']){
        $types[] = $type;
      }
    }
    return $types;
  }

  public static function getTilesForFavor(){
    return self::DB()
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->where( static::$prefix . 'location', TILE_LOCATION_BOX)
      ->get();
  }

  public static function getPlayerOutposts(Player $player){
    return self::DB()
      ->where('subtype', TILE_SUBTTYPE_OUTPOST)
      ->where( static::$prefix . 'location', TILE_LOCATION_PLAYER_BOARD)
      ->wherePlayer($player->getId())
      ->get();
  }
  /**
   * @param Player $player 
   * @return Collection list of OutpostTile that can be (tried to be) founded by this player
   */
  public static function getFoundableOutposts(Player $player): Collection{
    $playerTiles = self::getPlayerOutposts($player);
    $playerColonies = $playerTiles->filter(function($tile) {return ($tile instanceof OutpostTile);})
          ->map(function($tile) {return $tile->getColony();})
          ->toArray();

    $newOutposts = new Collection([]);
    foreach(TILE_LOCATION_BOARD_OUTPOSTS as $colony => $location) {
      if(in_array($colony, $playerColonies)) continue;
      $newOutposts = $newOutposts->merge(self::getInLocation($location));
    }
    //Game::get()->trace("getFoundableOutposts(".$player->getId()."), playerColonies = ".json_encode($playerColonies)." : newOutposts => ".json_encode($newOutposts));
    return $newOutposts;
  }

  public static function getPlayerAvailableSpotsForSpices(Player $player) : array {
    $allSpots = self::getPlayerOutposts($player);
    $allSpots = $allSpots->merge(self::getPlantations($player));
    $emptySpots = $allSpots->map(function($tile) use ($player) {
      $map = [
      ];
      $slots = $tile->getSpice_slots();
      foreach($slots as $index => $slot){
        //look at spot index (from 1 to N)
        $spiceIndex = 1 + $index;
        $tokensOnTileSpot = Tokens::countSpicesOnTile($tile->getId(), $spiceIndex);
        $pendingSpices = Tokens::countPendingSpiceOnTileSpot($player->getId(), $tile->getId(),$spiceIndex);
        if($tokensOnTileSpot->count() == 0 && $pendingSpices == 0){
          $id = $tile->getId() * 10 + $spiceIndex;
          $map[$id] = [
            'id' => $id, 
            'tile_id' => $tile->getId(), 
            'index' => $spiceIndex, 
            'spices' => is_array($slot) ? $slot : [$slot], 
          ];
        }
      }
      return $map;
    })->filter(function($map) {
      return count($map) >0;
    });
    $emptySpots = array_merge_recursive(...$emptySpots);
    $emptySpotsIds = [];
    foreach($emptySpots as $s){
      $emptySpotsIds[] = $s['id'];
    }
    $emptySpots = array_combine($emptySpotsIds, $emptySpots);
    return $emptySpots;
  }

  /**
   * @param Player $player
   * @param array &$args : this function may add some possible actions into it
   * @param array $actionsWhiteList : optional list of tile actions to keep
   */
  public static function getPossibleToPlay(Player $player, array &$args, array|null $actionsWhiteList = null,) : Collection
  {
    $hand = Tiles::getNotPlantations($player);
    $playTypes = [MARKET_TYPE_ACTION_REUSEABLE, MARKET_TYPE_ACTION_ONCE, MARKET_TYPE_SCORING];
        
    $playerSpices = Tokens::getPlayerSpices($player->getId());

    $list = $hand->filter(function($tile) use ($playTypes, $actionsWhiteList) {
      if(!in_array($tile->getPlay_type(),$playTypes)) return false;
      $actions = $tile->getActions();
      if(count($actions ) < 1) return false;
      if(count($actions ) > 1) return false;//we should not manage multiple actions here ...
      $action = array_key_first($tile->getActions());
      if(isset($actionsWhiteList) && !in_array($action,$actionsWhiteList)) return false;
      if($tile->alreadyPlayed()) return false;
      return true;
    });
    //Game::get()->trace(__FUNCTION__." -> filter = ".json_encode($list)."");
    $list = $list->map(function($tile) use ($playerSpices, $player, &$args){
      $playDatas = [];
      $possiblePlay = false;
      foreach($tile->getActions() as $action => $nb){
        switch($action){
          case ACTION_SPICE:
            if(!isset($args['harvestSpots'])){
              $harvestSpots = Tiles::getPlayerAvailableSpotsForSpices($player);
            }
            else {
              $harvestSpots = $args['harvestSpots'];
            }
            $possiblePlay = count($harvestSpots) > 0;
            if($possiblePlay){
              $playDatas['harvest'] = [
                'countAction' => false,
                'spots' => $harvestSpots,
                'min' => 1,
                'max' => 1,
              ];
              Utils::addPossibleActionInArgs('actHarvest', $args);
            }
            break;
          case ACTION_CONTRACT:
            $possiblePlay = true;
            break;
          case ACTION_SHIP:
            $possiblePlay = true;
            break;
          case ACTION_MONEY:
            $possiblePlay = true;
            break;
          case ACTION_SWAP: 
            $spots = GridUtils::listPossibleSpotsForSwap();
            $possiblePlay = count($spots) > 0;
            $playDatas['market_spots'] = $spots;
            break;
          case ACTION_RESUPPLY: 
            $possiblePlay = true;
            break;
          case ACTION_HARVEST: 
            if(!isset($args['harvestSpots'])){
              $harvestSpots = Tiles::getPlayerAvailableSpotsForSpices($player);
            }
            else {
              $harvestSpots = $args['harvestSpots'];
            }
            $possiblePlay = count($harvestSpots) > 0;
            if($possiblePlay){
              $playDatas['harvest'] = [
                'countAction' => false,
                'spots' => $harvestSpots,
                'min'=> 1,
                'maxTiles' => 3,
              ];
              Utils::addPossibleActionInArgs('actHarvest', $args);
            }
            break;
          case ACTION_ESPIONAGE: 
            $possiblePlay = true;
            break;
          case ACTION_VICEKING: 
            $progressTracks = Tokens::getUpgradableProgressMarkersWithCoins($player, true, true);
            $possiblePlay = count($progressTracks) > 0;
            if($possiblePlay) $playDatas['progressTracks'] = $progressTracks;
            break;
          case ACTION_EXPLORATION:
            $possiblePlay = true;
            break;
          case ACTION_SPEND_SPICES:
            if($playerSpices->count() >= $nb){
              $possiblePlay = true;
              $playDatas['spendSpices'] = [
                'min' => $nb, 
                'max' => $nb, 
                'forScore' => $tile->getEnd_score(), 
                't_ids' => $playerSpices->getIds(),
              ];
              Utils::addPossibleActionInArgs('actSpendSpices', $args);
            }
            break;
          default:
            break;
        }  

        return [
          'id' => $tile->getId(),
          'action' => $action,
          'play_datas' => $playDatas,
          'possiblePlay' => $possiblePlay,
        ];
      }
    })
    ->filter(function($map) {
        return $map['possiblePlay'];
    });
    //Game::get()->trace(__FUNCTION__." -> result = ".json_encode($list)."");
    return $list;
  }

  /**
   * Action of a tile in player actions turn
   * 
   * @param array|null $coords New coordinates to set on tile
   * @param PrematureStateChangeException &$stateChange : this function modify arg  directly instead of throw in order to go until the return value
   */
  public static function playTile(
    Player &$player,
    int $tileId, 
    array|null $coords, 
    bool &$doCheckpoint, 
    PrematureStateChangeException| null &$stateChange = null,
  ): Tile
  { 
    $tile = Tiles::get($tileId);
    $discard = false;
    
    if( !isset($tile) || !($tile instanceof MarketTile) ){
      throw new UnexpectedException(504,"This tile has no play type");
    }

    switch($tile->getPlay_type()){
      case MARKET_TYPE_PLANTATION:
      case MARKET_TYPE_ACTION_DIRECT:
        //Should not happen (done in giveMarketTileToPlayer)
        break;
      case MARKET_TYPE_ACTION_ONCE:
        $discard = true;
        break;
      case MARKET_TYPE_ACTION_REUSEABLE:
        break;
      case MARKET_TYPE_SCORING:
        break;
      default: break;
    }

    if(isset($coords)){
      $row = $coords['row'];
      $col = $coords['col'];
    }
    if(isset($row) && isset($col)){
      //Prepare to move the card
      $discard = false;

      $tile->setPId(null);
      $tile->setState(TILE_STATE_NOTPLAYED);
      $tile->setLocation(TILE_LOCATION_BOARD_MARKET);
      $tile->setRow($row);
      $tile->setCol($col);
      Notifications::placeTile( $player, $tile);
    }
    else if($discard) {
      //NOTIFY 
      Notifications::playTile( $player, $tile);
      Tiles::discardTileFromPlayer($player,$tile);
    }
    else //ELSE KEEP CARD
    {
      $tile->setState(TILE_STATE_PLAYED);
      Notifications::playTile( $player, $tile);
    }
    
    Stats::inc("playedTiles", $player->getId());
    
    foreach($tile->getActions() as $action => $nb){
      switch($action){
        case ACTION_SPICE:  //done in actHarvest
          break;
        case ACTION_CONTRACT:
          Players::giveContracts($player, $nb);
          break;
        case ACTION_SHIP:
          Players::giveShips($player, $nb);
          break;
        case ACTION_MONEY:
          Players::giveMoney($player, $nb);
          break;
        case ACTION_SWAP: 
          try{
            if(!isset($row) || !isset($col)){
              throw new UnexpectedException(122,("Missing coordinate to swap"));
            }
            //Don't forget to blacklist THE SWAP TILE itself because we just placed it in the same spot
            Tiles::giveMarketTileToPlayerAt($player, $row, $col, $doCheckpoint, [$tileId] );
          }
          catch(PrematureStateChangeException $exc){
            $stateChange = $exc;
          }
          break;
        case ACTION_RESUPPLY: 
          $cards = Cards::drawAndDiscardExploration($player, 2);
          $doCheckpoint = true;
          $sum = 0;
          foreach($cards as $card){
            $sum += $card->getContracts();
          }
          Notifications::resultDrawNCards($player,$sum);
          Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_HARVEST, $sum, [ACTION_HARVEST, ACTION_CONTRACT, ACTION_SHIP]);
          $stateChange = new PrematureStateChangeException (5,PlayerChoice::class,$player);
          break;
        case ACTION_HARVEST:  //done in actHarvest
          break;
        case ACTION_ESPIONAGE: 
          Effects::addEffectOnTile($player,$tile->getId(), EFFECT_TYPE_ESPIONAGE, 1, [ACTION_SHIP, ACTION_HARVEST, ACTION_MONEY, ACTION_FOUND_ABANDON]);
          $stateChange = new PrematureStateChangeException (5,PlayerChoice::class,$player);
          break;
        case ACTION_VICEKING: //done in actProgress
          break;
        case ACTION_EXPLORATION:
          Cards::drawExplorationCardsToPlayer($player, $nb, $doCheckpoint);
          break;
        case ACTION_SPEND_SPICES: //done in actSpendSpices
          break;
        default:
          break;
      }  
    }
    
    return $tile;
  }

  public static function resetActionsPhase()
  {
    //RESET ALL USED actionable TILES 
    $reusableTiles = Tiles::getTilesTypesByMarketPlayType(MARKET_TYPE_ACTION_REUSEABLE);
    $newState = TILE_STATE_NOTPLAYED;
    self::DB()
      ->whereIn('type', $reusableTiles)
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->where( static::$prefix . 'location', TILE_LOCATION_PLAYER_BOARD)
      ->update([static::$prefix .'state' => $newState])
      ->run();

    $reusableTilesIds = self::DB()
      ->whereIn('type', $reusableTiles)
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->where( static::$prefix . 'location', TILE_LOCATION_PLAYER_BOARD)
      ->get()
      ->getIds();
    Notifications::resetActionsPhase($reusableTilesIds, $newState );
  }
  
  public static function resetMarketTilesDeck()
  {
    self::DB()
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->where( 'tile_start_location', TILE_LOCATION_DECK_A)
      ->update([
        static::$prefix .'location' => TILE_LOCATION_DECK_A, 
        'player_id'=> null,
        'tile_row'=> null,
        'tile_col'=> null,
        'tile_state' => 0,
        ])
      ->run();
    self::shuffle(TILE_LOCATION_DECK_A);

    self::DB()
      ->where('subtype', TILE_SUBTTYPE_MARKET)
      ->where( 'tile_start_location', TILE_LOCATION_DECK_B)
      ->update([
        static::$prefix .'location' => TILE_LOCATION_DECK_B, 
        'player_id'=> null,
        'tile_row'=> null,
        'tile_col'=> null,
        'tile_state' => 0,
        ])
      ->run();
    self::shuffle(TILE_LOCATION_DECK_B);
  }

  ///////////////////////////////////////////////////////////////////////////////
  
  /** Creation of the Tiles */
  public static function setupNewGame($players, $options)
  {
    $tiles = [];
    $nbPlayers = count($players);

    $outposts = self::getOutpostTilesTypes();
    
    foreach ($outposts as $type => $outpost) {
        $location = TILE_LOCATION_BOARD_OUTPOSTS[$outpost['colony']];
        $tiles[] = [
          'location' => $location,
          'tile_start_location' => $location,
          'type' => $type,
          'subtype' => TILE_SUBTTYPE_OUTPOST,
          'nbr' => $outpost['nbr'],
        ];
    }

    $markets = self::getMarketTilesTypes();
    foreach ($markets as $type => $market) {
      if($nbPlayers == 1 && $type == MARKET_TYPE_ESPIONAGE){
        //SOLO RULE : no espionage to play with
        continue;
      }
        if($market['nbr_A']>0){
          $location = TILE_LOCATION_DECK_A;
          $tiles[] = [
            'location' => $location,
            'tile_start_location' => $location,
            'type' => $type,
            'subtype' => TILE_SUBTTYPE_MARKET,
            'nbr' => $market['nbr_A'],
          ];
        }
        if($market['nbr_B']>0){
          $location = TILE_LOCATION_DECK_B;
          $tiles[] = [
            'location' => $location,
            'tile_start_location' => $location,
            'type' => $type,
            'subtype' => TILE_SUBTTYPE_MARKET,
            'nbr' => $market['nbr_B'],
          ];
        }
    }

    self::create($tiles);
    self::shuffle(TILE_LOCATION_DECK_A);
    self::shuffle(TILE_LOCATION_DECK_B);

  }
   
  /**
   * @return array of all the different types of Outpost Tiles
   */
  public static function getOutpostTilesTypes()
  {
    //define the array keys for each entry in the next array
    $f = function ($t) {
      return [
        'nbr' => $t[0],
        'colony' => $t[1],
        'spice_slots' => $t[2],
      ];
    };
    return [
      1 => $f([ 1, COLONY_KOLLAM, [ [SPICE_WHITE, SPICE_YELLOW],   ]  ]), 
      2 => $f([ 1, COLONY_KOLLAM, [ [SPICE_BLACK, SPICE_PINK],     ]  ]), 
      3 => $f([ 1, COLONY_KOLLAM, [ [SPICE_GREEN, SPICE_WHITE],     ]  ]), 
      4 => $f([ 1, COLONY_KOLLAM, [ [SPICE_PINK, SPICE_GREEN],     ]  ]), 
      5 => $f([ 1, COLONY_KOLLAM, [ [SPICE_YELLOW, SPICE_BLACK],     ]  ]), 
      
      6 => $f([ 4, COLONY_KOCHI, [ SPICES_ALL,   ]  ]), 
      
      7 => $f([ 1,  COLONY_MADRAS, [ [SPICE_WHITE, SPICE_YELLOW], [SPICE_WHITE, SPICE_YELLOW]], ]), 
      8 => $f([ 1,  COLONY_MADRAS, [ [SPICE_BLACK, SPICE_PINK], [SPICE_BLACK, SPICE_PINK]],     ]), 
      9 => $f([ 1,  COLONY_MADRAS, [ [SPICE_GREEN, SPICE_WHITE], [SPICE_GREEN, SPICE_WHITE]], ]), 
      10 => $f([ 1, COLONY_MADRAS,  [ [SPICE_PINK, SPICE_GREEN], [SPICE_PINK, SPICE_GREEN]],     ]), 
      11 => $f([ 1, COLONY_MADRAS,  [ [SPICE_YELLOW, SPICE_BLACK], [SPICE_YELLOW, SPICE_BLACK]], ]), 
      
      12 => $f([ 4, COLONY_KOZHIKODE, [ SPICES_ALL, SPICES_ALL,   ]  ]), 
    ];
  }
  
  /**
   * @return array of all the different types of Market Tiles
   */
  public static function getMarketTilesTypes()
  {
    //define the array keys for each entry in the next array
    $f = function ($t) {
      return [
        //Number of same in deck A
        'nbr_A' => $t[0],
        //Number of same in deck B
        'nbr_B' => $t[1],
        'play_type' => $t[2],
        'spice_slots' => $t[3],
        'actions' => $t[4],
        'end_score' => $t[5],
        'name' => $t[6],
      ];
    };

    return [
      //I define the tiles in the same order as I made the sprite "market.jpg" :
      2 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICES_ALL, ], [], 0,  '', ]),  
      3 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_WHITE,  ], [], 1, '',  ]),  
      4 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_YELLOW, ], [], 1, '',  ]),  
      5 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_GREEN,  ], [], 1, '',  ]),  
      6 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_BLACK,  ], [], 1, '',  ]),  
      7 => $f([ 1, 0, MARKET_TYPE_PLANTATION, [ SPICE_PINK,   ], [], 1, '',  ]),  
      
      8 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [SPICES_ALL], [ACTION_SPICE => 1 ], 0,  clienttranslate('Bountiful'),   ]),
      9 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_CONTRACT => 1 ], 0,         clienttranslate('Negotiator'),  ]),
      
      10 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_WHITE,  SPICE_WHITE   ], [], 0, '',  ]),  
      11 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_YELLOW, SPICE_YELLOW, ], [], 0, '',  ]),  
      12 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_GREEN,  SPICE_GREEN,  ], [], 0, '',  ]),  
      13 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_BLACK,  SPICE_BLACK,  ], [], 0, '', ]),  
      14 => $f([ 2, 1, MARKET_TYPE_PLANTATION, [ SPICE_PINK,   SPICE_PINK,   ], [], 0, '', ]),  

      15 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_SHIP => 1 ], 0,          clienttranslate('Mariner'),  ]),
      16 => $f([ 1, 0, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_MONEY => 3 ], 0,         clienttranslate('Magnate'),  ]),

      17 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_WHITE,  SPICE_WHITE,  SPICE_WHITE   ], [], 0,  '', ]),  
      18 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_YELLOW, SPICE_YELLOW, SPICE_YELLOW, ], [], 0,  '', ]),  
      19 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_GREEN,  SPICE_GREEN,  SPICE_GREEN,  ], [], 0,  '', ]),  
      20 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_BLACK,  SPICE_BLACK,  SPICE_BLACK,  ], [], 0,  '', ]),  
      21 => $f([ 0, 1, MARKET_TYPE_PLANTATION, [ SPICE_PINK,   SPICE_PINK,   SPICE_PINK,   ], [], 0,  '', ]),  

      
      23 => $f([ 1, 2, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_CONTRACT => 1,  ACTION_COMPASS => 1, ACTION_SHIP => 1, ], 0,  clienttranslate('Settlement'),  ]),
      24 => $f([ 1, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_CONTRACT => 4, ], 0,      clienttranslate('Major Deal'),  ]),
      25 => $f([ 1, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_SHIP => 4, ], 0,          clienttranslate('Shipowner'),  ]),
      26 => $f([ 2, 2, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_SHIPS_OR_CONTRACTS => 3, ], 0,   clienttranslate('Endeavor'),  ]),
      27 => $f([ 1, 2, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_COMPASS => 2, ], 0,       clienttranslate('Right Direction'),  ]),
      28 => $f([ 1, 0, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_EXPLORATION => 2, ], 0,   clienttranslate('Small voyage'),  ]),
      29 => $f([ 0, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_EXPLORATION => 3, ], 0,   clienttranslate('Large Voyage'),  ]),
      30 => $f([ 0, 1, MARKET_TYPE_ACTION_DIRECT, [], [ACTION_FAVOR => 1 ], 0,          clienttranslate('Favor'),  ]),

      31 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_SWAP => 1 ], 0,       clienttranslate('Exchange'),  ]),
      32 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_RESUPPLY => 1 ], 0,   clienttranslate('Resupply'),  ]),
      33 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_HARVEST => 1 ], 0,    clienttranslate('Great Harvest'),  ]),
      MARKET_TYPE_ESPIONAGE => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_ESPIONAGE => 1 ], 0,  clienttranslate('Espionage'),  ]),
      35 => $f([ 0, 1, MARKET_TYPE_ACTION_ONCE, [], [ACTION_VICEKING => 1 ], 0,   clienttranslate('Viceroy'),  ]),
      
      36 => $f([ 0, 1, MARKET_TYPE_ACTION_REUSEABLE, [], [ACTION_EXPLORATION => 1, ], 0,  clienttranslate('Pathfinder'),  ]),
      
      MARKET_TYPE_FOREMAN => $f([ 1, 0, MARKET_TYPE_SCORING, [], [], 0,  clienttranslate('Foreman'),  ]),
      MARKET_TYPE_DUTY    => $f([ 1, 1, MARKET_TYPE_SCORING, [], [ACTION_SPEND_SPICES => 6], 5,  clienttranslate('Duty'),  ]),
      39 => $f([ 0, 1, MARKET_TYPE_SCORING, [], [], 2,   clienttranslate('Small Mission'),  ]),
      40 => $f([ 0, 1, MARKET_TYPE_SCORING, [], [], 3,   clienttranslate('Large Mission'),  ]),

    ];
  }
}
