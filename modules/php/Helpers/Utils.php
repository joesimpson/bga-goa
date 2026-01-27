<?php
namespace Bga\Games\Goa\Helpers;

use Bga\Games\Goa\States\ConfirmTurn;
use Bga\Games\Goa\States\EndPlayerTurn;
use Bga\Games\Goa\States\EndRound;
use Bga\Games\Goa\States\EndScore;
use Bga\Games\Goa\States\EndSession;
use Bga\Games\Goa\States\EndTurn;
use Bga\Games\Goa\States\NewRound;
use Bga\Games\Goa\States\NewSession;
use Bga\Games\Goa\States\NewTurn;
use Bga\Games\Goa\States\NextPlayer;
use Bga\Games\Goa\States\PlayerChoice;
use Bga\Games\Goa\States\PlayerTurn;

abstract class Utils {

    /**
     * @return int state id to use with specified State class
     */
    public static function getStateIdFromClass($class) : int|null{
        switch($class){
            case NewSession::class: return ST_NEW_SESSION;
            case NewRound::class:   return ST_NEW_ROUND;
            case NewTurn::class:   return ST_NEW_TURN;
            case NextPlayer::class: return ST_NEXT_PLAYER;
            case PlayerTurn::class: return ST_PLAYER_TURN;
            case PlayerChoice::class: return ST_PLAYER_CHOICE;
            case EndTurn::class:    return ST_END_TURN;
            case EndRound::class:   return ST_END_ROUND;
            case EndSession::class: return ST_END_SESSION;
            case EndScore::class:   return ST_END_SCORING;
            
            case ConfirmTurn::class:   return ST_CONFIRM_TURN;
            case EndPlayerTurn::class:   return ST_END_PLAYERTURN;
        }
        return null;
    }
}