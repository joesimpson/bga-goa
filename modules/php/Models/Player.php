<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Game;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tokens;

/*
 * Player: all utility functions concerning a player
 */

class Player extends \Bga\Games\Goa\Helpers\DB_Model
{
  protected $table = 'player';
  protected $primary = 'player_id';
  protected $attributes = [
    'id' => ['player_id', 'int'],
    'no' => ['player_no', 'int'],
    'name' => 'player_name',
    'color' => 'player_color',
    'eliminated' => 'player_eliminated',
    'score' => ['player_score', 'int'],
    'scoreAux' => ['player_score_aux', 'int'],
    'zombie' => 'player_zombie',

    //GAME SPECIFIC : 
    'money' => ['money', 'int'],
    'ships' => ['ships', 'int'],
    'contracts' => ['contracts', 'int'],
    'compasses' => ['compasses', 'int'],
    'nbActions' => ['nbActions', 'int'],
    'passed' => ['passed', 'bool'],
    'actionPlayed' => ['actionPlayed', 'bool'],
    'cardsPlayed' => ['cardsPlayed', 'obj'],

  ];

  public function getUiData($currentPlayerId = null)
  {
    $data = parent::getUiData();

    $data['exploration_hand_size'] = Cards::countInExplorationHand($this->getId());

    $data['spices'] = $this->getSpicesCount();
    return $data;
  }

  public function getPref(int $prefId)
  {
    //BGA framework :
    return Game::get()->userPreferences->get($this->getId(),$prefId);
  }

  public function getStat($name)
  {
    $name = 'get' . \ucfirst($name);
    return Stats::$name($this->getId());
  }
  
  /**
   * @param int $points
   * @param bool $sendNotif (Default true)
   */
  public function addPoints(int $points, bool $sendNotif = true)
  {
    if($points == 0) return;
    //REAL INC in DB in case of not up to date score in object
    Players::incPlayerScore($this->getId(), $points);
    Stats::inc( "score", $this->getId(), $points );
    if($sendNotif) Notifications::addPoints($this,$points);
  }
  
  
  public function addPointsWithTile(int $points, Tile $tile)
  {
    $this->addPoints($points,false);
    Notifications::addPointsWithTile($this,$points, $tile);
  }

  public function setTieBreakerPoints(int $points)
  {
    $this->setScoreAux($points);
  }
  public function addTieBreakerPoints(int $points)
  {
    if($points == 0) return;
    $this->incScoreAux($points);
  }

  /**
   * Sets player datas related to turn number $turnIndex
   * @param int $turnIndex
   */
  public function startTurn(int $turnIndex)
  { 
  }
  
  public function giveExtraTime(){
    Game::get()->giveExtraTime($this->getId());
  }

  public function getSpicesCount(){
    return [

      SPICE_WHITE => Tokens::countSpices($this->getId(), TOKEN_TYPE_SPICE_WHITE ),
      SPICE_BLACK  => Tokens::countSpices($this->getId(), TOKEN_TYPE_SPICE_BLACK ),
      SPICE_YELLOW => Tokens::countSpices($this->getId(), TOKEN_TYPE_SPICE_YELLOW),
      SPICE_PINK   => Tokens::countSpices($this->getId(), TOKEN_TYPE_SPICE_PINK  ),
      SPICE_GREEN  => Tokens::countSpices($this->getId(), TOKEN_TYPE_SPICE_GREEN ),
    ];

  }
  
  /**
   * @return int minimum number of actions to be played by this player during actions phase
   */
  public function getMinRoundActions(): int {
    return NB_MIN_ACTIONS_PER_ROUND;
  }
}
