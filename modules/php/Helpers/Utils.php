<?php
namespace Bga\Games\Goa\Helpers;

use Bga\GameFramework\UserException;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Managers\Players;
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

    
    public static function gameVersion() : int
    {
        $gameVersion = Game::get()->bga->tableOptions->get(BGA_GAMESTATE_GAMEVERSION);
        return intval($gameVersion);
    }
    /**
    * Check Server version to compare with client version : throw an error in case it 's not the same
    * From https://en.doc.boardgamearena.com/BGA_Studio_Cookbook#Force_players_to_refresh_after_new_deploy
    */
    public static function checkVersion(int $clientVersion)
    {
        $gameVersion = Utils::gameVersion();
        if ($clientVersion != $gameVersion) {
            throw new UserException('!!!checkVersion');
        }
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
        //Game::get()->trace(__FUNCTION__.'() : '.json_encode($set));
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

        //Game::get()->trace(__FUNCTION__.'() : RESULT = '.json_encode($perms));
        return $perms;
    }

    /**
     * @return array Cartesian product of multiples arrays in arguments
     */
    public static function array_cartesian(array $possibleSets) : array {
        $allSets = [];
        $allSets = Utils::array_cartesian_productV3($possibleSets);
        foreach($allSets as &$set){
            $set = array_merge(...$set);
            sort($set);
        }
        $allSets = Utils::array_of_uniquearrays($allSets);
        return $allSets;
    }

    /**
     * @source https://stackoverflow.com/questions/6311779/finding-cartesian-product-with-php-associative-arrays
     *  */
    static function array_cartesian_productV3($input) {
        $result = array();

        foreach ($input as $key => $values) {
            // If a sub-array is empty, it doesn't affect the cartesian product
            if (empty($values)) {
                continue;
            }

            // Seeding the product array with the values from the first sub-array
            if (empty($result)) {
                foreach($values as $value) {
                    $result[] = array($key => $value);
                }
            }
            else {
                // Second and subsequent input sub-arrays work like this:
                //   1. In each existing array inside $product, add an item with
                //      key == $key and value == first item in input sub-array
                //   2. Then, for each remaining item in current input sub-array,
                //      add a copy of each existing array inside $product with
                //      key == $key and value == first item of input sub-array

                // Store all items to be added to $product here; adding them
                // inside the foreach will result in an infinite loop
                $append = array();

                foreach($result as &$product) {
                    // Do step 1 above. array_shift is not the most efficient, but
                    // it allows us to iterate over the rest of the items with a
                    // simple foreach, making the code short and easy to read.
                    $product[$key] = array_shift($values);
                    
                    // $product is by reference (that's why the key we added above
                    // will appear in the end result), so make a copy of it here
                    $copy = $product;

                    // Do step 2 above.
                    foreach($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    // Undo the side effecst of array_shift
                    array_unshift($values, $product[$key]);
                }

                // Out of the foreach, we can add to $results now
                $result = array_merge($result, $append);
            }
        }

        return $result;
    }



    
    public static function getNbContractsForColony(int $colony){
        switch(($colony)){
            case COLONY_KOLLAM:
            case COLONY_KOCHI: 
            case COLONY_MADRAS:
            case COLONY_KOZHIKODE: 
                return COLONIES_CONTRACTS[$colony];
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

    public static function refreshFoundTileChoice(int $playerId){

        $player = Players::get($playerId);
        $choiceTiles = Globals::getChoiceTiles(); 
        //REFRESH TILES CHOICES DATAS according to played card/tile adding contracts
        foreach($choiceTiles as $tileId => &$tileChoices){
            foreach($tileChoices as &$tileChoiceDatas){
                if($tileChoiceDatas['selectType'] === CHOICE_FOUND_TILE 
                && !in_array(ACTION_FOUND_FORWARD,$tileChoiceDatas['choices']) ){
                    //
                    $missingContracts = $tileChoiceDatas['n'];
                    if($player->getContracts() >= $missingContracts){
                        $tileChoiceDatas['choices'][] = ACTION_FOUND_FORWARD;
                    }
                }
            }
        }
        Globals::setChoiceTiles($choiceTiles);
    }

}