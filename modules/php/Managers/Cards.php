<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\Card;

/* Class to manage all the cards */

class Cards extends \Bga\Games\Goa\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = [ 'type',];
  protected static $autoreshuffle = true;
  protected static $autoreshuffleCustom = [CARD_LOCATION_DECK => CARD_LOCATION_DISCARD];

  protected static function cast($row)
  {
    $type = isset($row['type']) ? $row['type'] : null;
    $data = self::getExplorationCardsTypes()[$type];
    return new Card($row, $data);
  }

  /**
   * @param int $currentPlayerId Id of current player loading the game
   * @return array all cards visible by this player
   */
  public static function getUiData($currentPlayerId)
  {
    //$privateCards = self::getPlayerHand($currentPlayerId);

    return 
      self::getInLocation(CARD_LOCATION_BOARD)
      //->merge($privateCards)
      ->merge(self::getInLocation(CARD_LOCATION_HAND))
      ->map(function ($card) {
        return $card->getUiData();
      })
      ->toArray();
  } 
  
  public static function getByType(int $cardType)
  {
    return self::DB()
      ->where('type', $cardType)
      ->get();
  }
   
  ///////////////////////////////////////////////////////////////////////////////////////
   
  /** Creation of the cards
   * @param Collection $players
   */
  public static function setupNewGame($players, $options)
  {
    $cards = [];

    foreach (self::getExplorationCardsTypes() as $type => $card) {
      $cards[] = [
        'location' => CARD_LOCATION_DECK,
        'type' => $type,
        'nbr' => 1,
      ];
    } 

    self::create($cards);
    self::shuffle(CARD_LOCATION_DECK);

  }
  
  /**
   * @return array of all the different types of Exploration Cards
   */
  public static function getExplorationCardsTypes(): array
  { 
    $f = function ($t) {
      return [
        'todo1' => $t[0],
        'todo2' => $t[1],
        'todo3' => $t[2],
      ];
    };
    return [
      10 => $f([1,2,3, ]), 
      11 => $f([1,2,3, ]), 
      12 => $f([1,2,3, ]), 

    ];
  }
}
