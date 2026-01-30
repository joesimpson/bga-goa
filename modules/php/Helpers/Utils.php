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
     * @return array Cartesian product of multiples arrays in arguments
     */
    public static function array_cartesian(array $possibleSets) : array {
        $allSets = [];

        $multiplyOthers = function($i, $array2) use($possibleSets) {
            
            //Game::get()->trace("multiplyOthers() : array BEFORE $i= ".json_encode($array2));

            $array = [];
            foreach($possibleSets as $j => $sets){
                if($j == $i) continue;
                foreach($sets as $set){
                    $array[] = array_values(array_merge($set,$array2));
                }
            }
            sort($array);
            //Game::get()->trace("multiplyOthers() : array AFTER $i= ".json_encode($array));
            return $array;
        };
        
        foreach($possibleSets as $key => $sets){
            foreach($sets as $set){
                $allSets[] = $multiplyOthers($key,$set);
            }
            
        }
        //flatten the array
        $allSets = array_merge(...$allSets);
        foreach($allSets as &$set){
            sort($set);
        }
        $allSets = Utils::array_of_uniquearrays($allSets);
        return $allSets;
    }


    
    public static function getNbContractsForColony(int $colony){
        switch(($colony)){
            case COLONY_KOLLAM: return 6;
            case COLONY_KOCHI:  return 8;
            case COLONY_MADRAS: return 10;
            case COLONY_KOZHIKODE: return 12;
            default: return 0;
        }
    }
    public static function getColonyName(int $colony){
        switch(($colony)){
            case COLONY_KOLLAM: return clienttranslate('Kollam');
            case COLONY_KOCHI:  return clienttranslate('Kochi');
            case COLONY_MADRAS: return clienttranslate('Madras');
            case COLONY_KOZHIKODE: return clienttranslate('Kozhikode');
            default: return '';
        }
    }
}