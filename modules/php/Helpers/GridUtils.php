<?php
namespace Bga\Games\Goa\Helpers;

use Bga\Games\Goa\Models\Token;

abstract class GridUtils 
{
    /**
     * @return array the list of spots to place the token : 
     *  if#1 empty spots on the edges near non empty spot+ empty spots near non empty spot in the grid, 
     *  else non empty spots near the previous auction (if any) 
     *  else all non empty spots 
     */
    public static function listPossibleSpotsForNextAuctionMarker(Token $token): array
    {
        //TODO JSA COMPUTE 
        $spots = [];
        $type = $token->getType();
        if($type == TOKEN_TYPE_AUCTION_MARKER_1){
            //Edges of the grid :
            for($col=1;$col<=MARKET_GRID_SIZE;$col++){
                $spots[] = ['row'=>0,'col'=>$col];
            }
            for($row=1;$row<=MARKET_GRID_SIZE;$row++){
                $spots[] = ['row'=>$row,'col'=>0];
                $spots[] = ['row'=>$row,'col'=>MARKET_GRID_SIZE+1];
            }
            for($col=1;$col<=MARKET_GRID_SIZE;$col++){
                $spots[] = ['row'=>MARKET_GRID_SIZE+1,'col'=>$col];
            }
        }
        else {
            $spots = [ ['row'=>1,'col'=>1], ['row'=>4,'col'=>3]];
        }

        return $spots;
    }
}