<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\CardLocation;
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
        //TODO JSA NOTIF
        self::shuffle(CardLocation::AUTOMA_MONEY_DECK->value);
        return $elements;
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
