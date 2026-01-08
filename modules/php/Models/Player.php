<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Game;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Core\Stats;
use Bga\Games\Goa\Managers\Players;

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
  ];

  public function getUiData($currentPlayerId = null)
  {
    $data = parent::getUiData();
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
}
