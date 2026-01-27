<?php
namespace Bga\Games\Goa\Helpers;

use Bga\Games\Goa\States\PlayerChoice;
use Bga\Games\Goa\States\PlayerTurn;

abstract class Utils {

    public static function getStateIdFromClass($class) : int|null{
        switch($class){
            case PlayerChoice::class:
                return ST_PLAYER_CHOICE;
            case PlayerTurn::class:
                return ST_PLAYER_TURN;

        }
        return null;
    }
}