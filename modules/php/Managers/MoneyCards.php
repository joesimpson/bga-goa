<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\CardLocation;
use Bga\Games\Goa\Models\MoneyCard;
use Bga\Games\Goa\Models\MoneyCardType;
use Bga\Games\Goa\Models\Player;

/* Class to manage all the Money cards */

class MoneyCards extends Cards
{
    
    public static function initAutoma(array $typesAmount): Collection
    {
        $cards = [];
        foreach($typesAmount as $cardType => $amount){
            $cards[] = [
                'type' => $cardType,
                'subtype' => CARD_SUBTTYPE_MONEY,
                'location' => CardLocation::AUTOMA_MONEY_DECK->value,
                'nbr' => $amount,
            ];
        }
        $ids = self::create($cards);
        $elements = self::get($ids);
        Notifications::initAutomaMoney($elements);
        self::shuffle(CardLocation::AUTOMA_MONEY_DECK->value);
        return $elements;
    }
    
    public static function countAutomaDeckSize() : int
    {
        return self::DB()
        ->where( 'subType', CARD_SUBTTYPE_MONEY)
        ->where(static::$prefix . 'location', CardLocation::AUTOMA_MONEY_DECK->value)
        ->count();
    }
    
    public static function getAutomaLocationForToken(int $token_type) : CardLocation
    {
        switch($token_type){
            case TOKEN_TYPE_AUCTION_MARKER_1: 
            default: return CardLocation::AUTOMA_MONEY_AUCTION_1; 
            case TOKEN_TYPE_AUCTION_MARKER_2: 
                return CardLocation::AUTOMA_MONEY_AUCTION_2; 
            case TOKEN_TYPE_AUCTION_MARKER_3: 
                return CardLocation::AUTOMA_MONEY_AUCTION_3; 
        }
    }
    public static function drawAutoma(int $token_type) : MoneyCard
    {
        $decksize = self::countAutomaDeckSize();
        if($decksize == 0){
            //TODO JSA Notif RESHUFFLE
        }
        $card = self::DB()
            ->where( 'subType', CARD_SUBTTYPE_MONEY)
            ->where(static::$prefix . 'location', CardLocation::AUTOMA_MONEY_DECK->value)
            ->get()->first();

        $toLocation = self::getAutomaLocationForToken($token_type)->value;

        $card->setLocation($toLocation);
        Notifications::automaDrawMoney($card);
        return $card;
    }
    
    public static function automaGiveToPlayer(int $token_type, Player $automata,Player $player,) : MoneyCard
    {

        $fromLocation = self::getAutomaLocationForToken($token_type)->value;
        $card = self::DB()
            ->where( 'subType', CARD_SUBTTYPE_MONEY)
            ->where(static::$prefix . 'location', $fromLocation)
            ->get()->first();
        //For now, we don't use money cards model for players money,     
        $cardMoney = $card->getValue();
        Players::giveMoney($player, $cardMoney , false );
        Notifications::winsAuctionWithCard($automata, $cardMoney,$player,$card);
        self::DB()->delete($card->getId());
        return $card;
    }

    /**
     * DISCARD AUTOMA Money card if any
     */
    public static function discardAutomaAuction(int $token_type, ) : MoneyCard|null
    { 
        $fromLocation = self::getAutomaLocationForToken($token_type)->value;
        $card = self::DB()
            ->where( 'subType', CARD_SUBTTYPE_MONEY)
            ->where(static::$prefix . 'location', $fromLocation)
            ->get()->first();
        if(!isset($card)) return null;
        $card->setLocation(CardLocation::AUTOMA_MONEY_DISCARD->value);
        Notifications::automaDiscardMoney($card);
        return $card;
    }
    ///////////////////////////////////////////////////////////////////////////////////////
    
    /** Creation of the cards
     * @param Collection $players
     */
    public static function setupNewGame($players, $options)
    {
    }
    
    /**
     * @return array of all the different types of Money Cards
     */
    public static function getMoneyCardsTypes(): array
    { 
        $f = function ($t) {
            return [
                'value'  => $t[0],
            ];
        };
        return [
            MoneyCardType::MONEY_1->value  =>  $f([ 1,  ]), 
            MoneyCardType::MONEY_2->value  =>  $f([ 2,  ]),
            MoneyCardType::MONEY_5->value  =>  $f([ 5,  ]),
            MoneyCardType::MONEY_10->value =>  $f([ 10,  ]),
        ];
    }

}
