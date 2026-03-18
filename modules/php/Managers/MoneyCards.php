<?php

namespace Bga\Games\Goa\Managers;

use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Helpers\Collection;
use Bga\Games\Goa\Models\CardLocation;
use Bga\Games\Goa\Models\MoneyCard;
use Bga\Games\Goa\Models\MoneyCardType;
use Bga\Games\Goa\Models\MoneyCardValue;
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
        $elements = self::getMany($ids);
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
    /**
     * @return int decksize after reshuffle
     */
    public static function reshuffleAutomaDeck() : int
    {
        self::moveAllInLocation(CardLocation::AUTOMA_MONEY_DISCARD->value,CardLocation::AUTOMA_MONEY_DECK->value);
        self::shuffle(CardLocation::AUTOMA_MONEY_DECK->value);
        $decksize = self::countAutomaDeckSize();
        Notifications::reshuffleAutomaMoneyDeck($decksize );
        return $decksize;
    }
    public static function drawAutoma(int $token_type) : MoneyCard
    {
        $decksize = self::countAutomaDeckSize();
        if($decksize == 0){
            $decksize = self::reshuffleAutomaDeck();
            //IF still $decksize == 0, we cannot draw, but we should have enough cards at the beginning to avoid this problem
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
        Notifications::winsAuctionWithCard($automata, $cardMoney,$card,$player,);
        self::DB()->delete($card->getId());
        return $card;
    }
    
    public static function automaReceivesFromPlayer(int $money, int $token_type, Player $automata,Player $player,) : Collection
    {
        //RULE : Always give Automata the smallest number of Pardau Cards possible.
        $remaining = $money;
        $cardsTmp = [];
        while($remaining > 0){
            $cardvalue = MoneyCardValue::MONEY_1->value;
            $cardType = MoneyCardType::MONEY_1->value;
            if($remaining >= MoneyCardValue::MONEY_10->value ){
                $cardvalue = MoneyCardValue::MONEY_10->value;
                $cardType = MoneyCardType::MONEY_10->value;
            }
            else if($remaining >= MoneyCardValue::MONEY_5->value ){
                $cardvalue = MoneyCardValue::MONEY_5->value;
                $cardType = MoneyCardType::MONEY_5->value;
            }
            else if($remaining >= MoneyCardValue::MONEY_2->value ){
                $cardvalue = MoneyCardValue::MONEY_2->value;
                $cardType = MoneyCardType::MONEY_2->value;
            }
            $cardsTmp[] = [
                'type' => $cardType,
                'subtype' => CARD_SUBTTYPE_MONEY,
                'location' => CardLocation::AUTOMA_MONEY_DISCARD->value,
                'nbr' => 1,
            ];
            $remaining = $remaining - $cardvalue;
        }

        if(count($cardsTmp) == 0) return new Collection([]);

        $ids = self::create($cardsTmp);
        $cards = self::getMany($ids);
        foreach($cards as $card) {
            MoneyCards::insertOnTop($card->getId(), CardLocation::AUTOMA_MONEY_DISCARD->value);
        }
        Notifications::automaGetsPlayerMoney($cards,$automata, $money,$player,);
        return $cards;
    }
    
    public static function automaReceivesFromBank(MoneyCardType $moneyType, Player $automata,) : Collection
    {
        $cardsTmp = [];
        $cardType = $moneyType->value;
        $cardsTmp[] = [
            'type' => $cardType,
            'subtype' => CARD_SUBTTYPE_MONEY,
            'location' => CardLocation::AUTOMA_MONEY_DISCARD->value,
            'nbr' => 1,
        ];
        
        $ids = self::create($cardsTmp);
        $cards = self::getMany($ids);
        foreach($cards as $card) {
            MoneyCards::insertOnTop($card->getId(), CardLocation::AUTOMA_MONEY_DISCARD->value);
        }
        Notifications::automaGetsBankMoney($cards,$automata,);
        return $cards;
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
        MoneyCards::insertOnTop($card->getId(), CardLocation::AUTOMA_MONEY_DISCARD->value);
        $card->setLocation(CardLocation::AUTOMA_MONEY_DISCARD->value);
        Notifications::automaDiscardMoney($card);
        return $card;
    }
    /**
     * Move  AUTOMA Money card to the bank if any
     */
    public static function bankAutomaAuction(int $token_type, ) : MoneyCard|null
    { 
        $fromLocation = self::getAutomaLocationForToken($token_type)->value;
        $card = self::DB()
            ->where( 'subType', CARD_SUBTTYPE_MONEY)
            ->where(static::$prefix . 'location', $fromLocation)
            ->get()->first();
        if(!isset($card)) return null;
        Notifications::automaBankMoney($card);
        self::DB()->delete($card->getId());
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
            MoneyCardType::MONEY_1->value  =>  $f([ MoneyCardValue::MONEY_1->value,  ]), 
            MoneyCardType::MONEY_2->value  =>  $f([ MoneyCardValue::MONEY_2->value,  ]),
            MoneyCardType::MONEY_5->value  =>  $f([ MoneyCardValue::MONEY_5->value,  ]),
            MoneyCardType::MONEY_10->value =>  $f([ MoneyCardValue::MONEY_10->value,  ]),
        ];
    }

}
