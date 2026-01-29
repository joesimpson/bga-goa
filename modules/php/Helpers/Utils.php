<?php
namespace Bga\Games\Goa\Helpers;

use Bga\Games\Goa\Game;
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


    public static function array_of_uniquearrays(array $array): array {
        return array_intersect_key($array, array_unique(array_map('serialize', $array)));
    }
    
    /**
     * Return permutations of array
     * @source https://stackoverflow.com/questions/10222835/get-all-permutations-of-a-php-array & https://docstore.mik.ua/orelly/webprog/pcook/ch04_26.htm
     */
    private static function pc_next_permutation(array $p, int $size) {
        // slide down the array looking for where we're smaller than the next guy
        for ($i = $size - 1; isset($p[$i]) &&  isset($p[$i+1]) && $p[$i] >= $p[$i+1]; --$i) { }

        // if this doesn't occur, we've finished our permutations
        // the array is reversed: (1, 2, 3, 4) => (4, 3, 2, 1)
        if ($i == -1) { return false; }

        // slide down the array looking for a bigger number than what we found before
        for ($j = $size; isset($p[$j]) &&  isset($p[$i]) && $p[$j] <= $p[$i]; --$j) { }

        // swap them
        $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;

        // now reverse the elements in between by swapping the ends
        for (++$i, $j = $size; $i < $j; ++$i, --$j) {
            $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
        }

        return $p;
    }
    
    /**
     * Return permutations of array
     * @source https://stackoverflow.com/questions/10222835/get-all-permutations-of-a-php-array & https://docstore.mik.ua/orelly/webprog/pcook/ch04_26.htm
     */
    public static function computePermutations(array $set) : array{
        Game::get()->trace(__FUNCTION__.'() : '.json_encode($set));
        $size = count($set) - 1;
        //if($size == 0) return [];
        $perm = range(0, $size);
        $j = 0;

        $perms = [];
        do { 
            foreach ($perm as $i) { 
                if(!isset($perms[$j])) $perms[$j] = [];
                $perms[$j][] = $set[$i]; 
            }
        } while ($perm = self::pc_next_permutation($perm, $size) and ++$j);

        Game::get()->trace(__FUNCTION__.'() : RESULT = '.json_encode($perms));
        return $perms;
    }

    /**
     * @return array Permutations of multiples arrays in arguments
     * @source https://stackoverflow.com/questions/28188739/permutation-with-two-array-in-php
     */
    public static function array_cartesian() {
        $_ = func_get_args();
        if(count($_) == 0)
            return array(array());
        $a = array_shift($_);
        $c = call_user_func_array(__FUNCTION__, $_);
        $r = array();
        foreach($a as $v)
            foreach($c as $p)
                $r[] = array_merge(array($v), $p);
        return $r;
    }
}