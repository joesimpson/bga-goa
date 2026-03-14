<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\CardLocation;
use Bga\Games\Goa\Models\MoneyCard;
use Bga\Games\Goa\Models\MoneyCardType;

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

        $toLocation = CardLocation::AUTOMA_MONEY_AUCTION_1->value;
        //TODO JSA 2, 3

        $card->setLocation($toLocation);
        Notifications::automaDrawMoney($card);
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
