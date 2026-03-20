<?php

declare(strict_types=1);

namespace Bga\Games\Goa\States;

use Bga\GameFramework\StateType;
use Bga\Games\Goa\Core\Globals;
use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Game;
use Bga\Games\Goa\Helpers\Log;
use Bga\Games\Goa\Helpers\Utils;
use Bga\Games\Goa\Managers\Cards;
use Bga\Games\Goa\Managers\Players;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;

class EndSession extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: Utils::getStateIdFromClass(self::class),
            type: StateType::GAME,
        );
    }

    /**
     * Game state action
     *
     */
    function onEnteringState(int $activePlayerId) {
        $this->game->trace("EndSession.onEnteringState($activePlayerId)");

        $session = Globals::getSession();
        self::computeScoring($session);
        
        $maxSessions = 1;
        if(Globals::getOptionCampaign() == OPTION_CAMPAIGN_ON){
            $maxSessions = NB_SESSIONS_CAMPAIGN;
        }

        $gameEnd = false;
        if($session >= $maxSessions){
            $gameEnd = true;
        }

        if ($gameEnd) {
            Log::addStateCheckpoint(EndScore::class);
            return EndScore::class;
        } else {
            Log::addStateCheckpoint(NewSession::class);
            return NewSession::class;
        }
    }

    // -------------------------------------------------------
    /**
     * 
     * @param int $session
     * @return void
     * 
     *   Victory points (VP) for the position of the Progress markers.
     * 
     *   Victory points for the number of colonies founded during the game.
     *   
     *   Victory points for Expedition cards.
     * 
     *   Victory points for the player with the most ducats.
     * 
     *   Victory points for plantations.
     * 
     *   Victory points for a Duty tile.
     * 
     *   Victory points for a Mission tile.
     * 
     */
    public static function computeScoring(int $session) : array{
       
        Notifications::computeScoring($session);
        $players = Players::getAll();

        //INIT Datas to save
        $sessionScoringDatas = [];
        foreach($players as $pid => $player){
            $sessionScoringDatas[$pid] = [
                SCORING_PROGRESS     => 0, 
                SCORING_OUTPOSTS     => 0, 
                SCORING_EXPLORATION  => [], 
                SCORING_COINS        => 0, 
                SCORING_TILES        => 0, 
            ];
        }

        //Players LOOP 1 : SCORING_PROGRESS
        foreach($players as $pId => &$player){
            $points = 0;
            $markers = Tokens::getProgressMarkers($pId);
            foreach($markers as $marker){
                if(!array_key_exists($marker->getRow(),END_SCORE_PROGRESS)) continue;
                $points += END_SCORE_PROGRESS[$marker->getRow()];
            }
            $sessionScoringDatas[$pId][SCORING_PROGRESS] = $points;
            $player->addPoints($points, false);
            Notifications::addPointsWithProgress($player,$points,);
        }

        //Players LOOP 2 : SCORING_OUTPOSTS
        foreach($players as $pId => &$player){
            $nbTiles = Tiles::getPlayerOutposts($player)->count();
            if(!array_key_exists($nbTiles,END_SCORE_OUTPOSTS)) continue;
            $points = END_SCORE_OUTPOSTS[$nbTiles];
            $sessionScoringDatas[$pId][SCORING_OUTPOSTS] = $points;
            $player->addPoints($points, false);
            Notifications::addPointsWithOutposts($player,$points, $nbTiles);
        }
        
        //Players LOOP 3 : SCORING_EXPLORATION
        foreach($players as $pId => &$player){
            $cards = Cards::getExplorationHand($pId);
            $sessionScoringDatas[$pId][SCORING_EXPLORATION] = [];
            foreach(CARD_SYMBOLS as $symbol => $name){
                $nbCards = $cards->filter(function($card) use ($symbol) {return $card->getSymbol() === $symbol; })->count();
                if(!array_key_exists($nbCards,END_SCORE_EXPLORATION)) continue;
                $points = END_SCORE_EXPLORATION[$nbCards];
                $sessionScoringDatas[$pId][SCORING_EXPLORATION][$symbol] = $points;
                $player->addPoints($points, false);
                Notifications::addPointsWithSymbols($player,$points, $nbCards, $symbol); 
            }
        }
        
        //Players LOOP 4 : SCORING_COINS
        $automaMoney = Players::automaPlayerMoney();
        $mostCoins = 0;
        $mostCoinsPlayers = [];//may be tied
        if($automaMoney > $mostCoins) {
            $automa = Players::automaPlayer();
            $mostCoins = $automaMoney;
            $mostCoinsPlayers = [$automa->getId()];
            Notifications::revealMoney($automa, $automaMoney);
        }
        foreach($players as $pId => &$player){
            $coins = $player->getMoney();
            if($coins > $mostCoins){
                $mostCoins = $coins;
                $mostCoinsPlayers = [$pId];//reset
            }
            else if($coins === $mostCoins) {
                $mostCoinsPlayers[] = $pId;//add
            }
            $player->setTieBreakerPoints($coins);
            Notifications::revealMoney($player, $coins);
        }
        foreach($players as $pId => &$player){
            if(!in_array($pId, $mostCoinsPlayers)) continue;
            $points = END_SCORE_MOST_COINS;
            $sessionScoringDatas[$pId][SCORING_COINS] = $points;
            $player->addPoints($points, false);
            Notifications::addPointsWithMoney($player,$points, $mostCoins); 
        }
        
        //Players LOOP 5 : SCORING_TILES
        foreach($players as $pId => &$player){
            $tiles = Tiles::getPlayerMarketTiles($player);
            $hasForeman = ($tiles->filter(function($tile) {return $tile->getType() === MARKET_TYPE_FOREMAN; })->count() > 0);

            $pointsWithTiles = 0;
            foreach($tiles as $tile){
                $points = 0;
                switch($tile->getPlay_type()){
                    case MARKET_TYPE_PLANTATION: 
                        $points = $tile->getEnd_score();
                        if($hasForeman && $points === 1) $points = END_SCORE_PLANTATIONS_WITH_FOREMAN;
                        break;
                    case MARKET_TYPE_SCORING: 
                        $points = $tile->getEnd_score();
                        if($tile->getType() === MARKET_TYPE_DUTY && !$tile->alreadyPlayed()){
                            $points = 0;
                        }
                        break;
                    default: 
                        $points = $tile->getEnd_score();
                        break;
                }
                if($points > 0){
                    $player->addPointsWithTile($points, $tile);
                }
                $pointsWithTiles += $points;
            }
            $sessionScoringDatas[$pId][SCORING_TILES] = $pointsWithTiles;
        }

        
        foreach($players as $pId => &$player){
           Notifications::scoreRank($player);
        }
        
        $endScoringDatas = Globals::getEndScoring();
        $endScoringDatas[$session] = $sessionScoringDatas;
        Globals::setEndScoring($endScoringDatas);

        return $endScoringDatas;
    }
}