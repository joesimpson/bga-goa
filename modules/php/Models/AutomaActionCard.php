<?php

namespace Bga\Games\Goa\Models;

use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Managers\MoneyCards;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;

class AutomaActionCard extends Card
{  
    protected $staticAttributes = [
        ['not_first', 'int'],
        ['when_first', 'int'],
    ];

    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = CARD_SUBTTYPE_AUTOMA_ACTION;
        //keep json as short as possible by removing static datas (we can get it with material sent at page load)
        unset($data['not_first']);
        unset($data['when_first']);
        return $data;
    }

    
    public function play(AutomaPlayer $player)
    {
        $action = $this->getNot_first();
        $firstPlayerToken = Tokens::getFirstPlayerToken();
        if($firstPlayerToken->getPId() == $player->getId()){
            $action = $this->getWhen_first();
        }

        switch($action){
            case AutomaActionType::GAIN_MONEY_1: 
                MoneyCards::automaReceivesFromBank(MoneyCardType::MONEY_1, $player);
                break;
            case AutomaActionType::GAIN_MONEY_2: 
                MoneyCards::automaReceivesFromBank(MoneyCardType::MONEY_2, $player);
                break;
            case AutomaActionType::GAIN_MONEY_5: 
                MoneyCards::automaReceivesFromBank(MoneyCardType::MONEY_5, $player);
                break;
            case AutomaActionType::GAIN_MONEY_10: 
                MoneyCards::automaReceivesFromBank(MoneyCardType::MONEY_10, $player);
                break;
                
            case AutomaActionType::NOGAIN_MONEY_2: 
                Notifications::automaDontGetBankMoney($player,MoneyCardType::MONEY_2);
                break;
            case AutomaActionType::NOGAIN_MONEY_5: 
                Notifications::automaDontGetBankMoney($player,MoneyCardType::MONEY_5);
                break;
            case AutomaActionType::NOGAIN_MONEY_10: 
                Notifications::automaDontGetBankMoney($player,MoneyCardType::MONEY_10);
                break;
                
            case AutomaActionType::DISCARD_2_KOLLAM: 
                Tiles::discardTilesFromOutpostStack($player,COLONY_KOLLAM,2);
                break;
            case AutomaActionType::DISCARD_2_MADRAS: 
                Tiles::discardTilesFromOutpostStack($player,COLONY_MADRAS,2);
                break;
        }

    }
 
}
