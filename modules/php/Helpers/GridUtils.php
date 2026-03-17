<?php
namespace Bga\Games\Goa\Helpers;

use Bga\Games\Goa\Core\Notifications;
use Bga\Games\Goa\Managers\Tiles;
use Bga\Games\Goa\Managers\Tokens;
use Bga\Games\Goa\Models\AuctionMarker;
use Bga\Games\Goa\Models\Player;
use Bga\Games\Goa\Models\Token;

abstract class GridUtils 
{
    /**
     * @return array the list of spots to place the token : 
     *  if#1 spots on the edges near non empty spot+ empty spots near non empty spot in the grid, 
     *  else non empty spots near the previous auction (if any) 
     *  else all non empty spots 
     */
    public static function listPossibleSpotsForNextAuctionMarker(Token $token, Collection $boardTiles): array
    {
        $boardTokens = Tokens::getBoardTokens();
        $isNearTile = function ($row,$col) use ($boardTiles){
            $nearTiles = $boardTiles->filter(function ($tile) use($row,$col){
                return GridUtils::isAdjacentCoord($row,$col, $tile->getRow(), $tile->getCol());
            });
            return $nearTiles->count() > 0;
        };
        $isEmptySpot = function ($row,$col) use ($boardTiles){
            return $boardTiles->filter(function ($tile) use($row,$col){
                return $row == $tile->getRow() && $col == $tile->getCol() ;
            })->count() == 0;
        };
        $noTokenOn = function ($row,$col) use ($boardTokens){
            return $boardTokens->filter(function ($pToken) use($row,$col){
                return $row == $pToken->getRow() && $col == $pToken->getCol() ;
            })->count() == 0;
        };

        $spots = [];
        $type = $token->getType();
        if($type == TOKEN_TYPE_AUCTION_MARKER_1){
            //if#1 spots on the edges near non empty spot+ empty spots near non empty spot in the grid, 
            //Edges of the grid :
            foreach(self::listOrderedSpotsOnEdges_TopLeft_ClockWise() as $edge){
                $row = $edge['row'];
                $col = $edge['col'];
                if($isNearTile($row,$col) ) $spots[] = ['row'=>$row,'col'=>$col];
            }

            //+ empty spots near non empty spot in the grid, 
            for($row=1;$row<=MARKET_GRID_SIZE;$row++){
                for($col=1;$col<=MARKET_GRID_SIZE;$col++){
                    if($isEmptySpot($row,$col) && $isNearTile($row,$col)) $spots[] = ['row'=>$row,'col'=>$col];
                }
            }
        }
        else {
            //else non empty spots near the previous auction (if any) 
            $currentTypeIndex = array_search($type,TOKEN_TYPE_AUCTION_MARKERS);
            $previousMarkerType = TOKEN_TYPE_AUCTION_MARKERS[$currentTypeIndex-1];
            $previousMarker = $boardTokens->filter(function ($pToken) use($previousMarkerType){
                return ($pToken instanceof AuctionMarker) && $previousMarkerType == $pToken->getType() ;
            })->first();
            $nearPrevious = GridUtils::listAdjacentSpots($previousMarker->getRow(), $previousMarker->getCol());
            foreach($nearPrevious as $pSpot){
                $row = $pSpot['row'];
                $col = $pSpot['col'];
                if(!$isEmptySpot($row,$col) && $noTokenOn($row,$col)) $spots[] = ['row'=>$row,'col'=>$col];
            }
        }

        //else all non empty spots  :
        if(count($spots) == 0 ){
            for($row=1;$row<=MARKET_GRID_SIZE;$row++){
                for($col=1;$col<=MARKET_GRID_SIZE;$col++){
                    if(!$isEmptySpot($row,$col) && $noTokenOn($row,$col)) $spots[] = ['row'=>$row,'col'=>$col];
                }
            }
        }

        return $spots;
    }

    public static function listOrderedSpotsOnEdges_TopLeft_ClockWise(): array
    {
        $spots = [];
        //Edges of the grid :
        for($col=1;$col<=MARKET_GRID_SIZE;$col++){
            $row = 0;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($row=1;$row<=MARKET_GRID_SIZE;$row++){
            $col = MARKET_GRID_SIZE+1;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($col=MARKET_GRID_SIZE;$col>=1;$col--){
            $row = MARKET_GRID_SIZE+1;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($row=MARKET_GRID_SIZE;$row>=1;$row--){
            $col = 0;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        return $spots;
    }
    
    public static function listOrderedSpotsOnEdges_TopLeft_CounterClockWise(): array
    {
        $spots = GridUtils::listOrderedSpotsOnEdges_TopLeft_ClockWise();
        $spots = array_reverse($spots);
        return $spots;
    }
    public static function listOrderedSpotsOnEdges_TopRight_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsOnEdges_TopLeft_ClockWise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsOnEdges_BottomRight_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsOnEdges_TopRight_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsOnEdges_BottomLeft_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsOnEdges_BottomRight_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    
    public static function listOrderedSpotsOnEdges_TopRight_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsOnEdges_BottomRight_CounterClockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsOnEdges_BottomRight_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsOnEdges_BottomLeft_CounterClockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsOnEdges_BottomLeft_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsOnEdges_TopLeft_CounterClockWise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    /**
     * @return array same array as param but rotate order 90deg Clockwise
     */
    public static function rotateClockwise(array $baseSpots): array
    {
        return array_merge(
            array_slice($baseSpots,MARKET_GRID_SIZE,MARKET_GRID_SIZE*3 ),
            array_slice($baseSpots,0,MARKET_GRID_SIZE )
        );
    }
    
    public static function center(): array
    {
        return ['row' => 3, 'col'=>3];
    }
    
    
    /**
     * @param bool $withDiag (default false) : specify true to also check diagonally
     * @return bool true if points A (rowA, colA) and B (rowB, colB) are ORTHOGONALLY adjacent, else otherwise
     */
    public static function isAdjacentCoord(int $rowA,int $colA, int $rowB,int $colB, bool $withDiag = false): bool
    {
        if($rowA == $rowB){
            if($colA == $colB){
                //same cell
                return false;
            }
            else if($colA == ($colB +1) || $colA == ($colB -1) ){
                return true;
            }
        }
        else if($rowA == ($rowB +1) || $rowA == ($rowB -1) ){
            if($colA == $colB){
                return true;
            }
            else if($colA == ($colB +1) || $colA == ($colB -1) ){
                if($withDiag) return true;
            }
        }
        return false;
    }

    
    /**
     * @return array list of ORTHOGONALLY or DIAGONALLY adjacent spots around a given point A (rowA, colA) 
     */
    public static function listAdjacentSpots(int $rowA,int $colA,): array
    {
        $spots = [];
        for($row=1;$row<=MARKET_GRID_SIZE;$row++){
            for($col=1;$col<=MARKET_GRID_SIZE;$col++){
                if(GridUtils::isAdjacentCoord($rowA,$colA,$row,$col, true)){
                    $spots[] = ['row'=>$row,'col'=>$col];
                }
            }
        }

        return $spots;
    }

    
    /**
     * @return array the list of spots to take :
     *    all non empty spots as ['row'=>$row,'col'=>$col]
     */
    public static function listPossibleSpotsForSwap(): array
    {
        $boardTiles = Tiles::getBoardTiles();
        $isEmptySpot = function ($row,$col) use ($boardTiles){
            return $boardTiles->filter(function ($tile) use($row,$col){
                return $row == $tile->getRow() && $col == $tile->getCol() ;
            })->count() == 0;
        };
        $spots = [];

        for($row=1;$row<=MARKET_GRID_SIZE;$row++){
            for($col=1;$col<=MARKET_GRID_SIZE;$col++){
                if(!$isEmptySpot($row,$col)) $spots[] = ['row'=>$row,'col'=>$col];
            }
        }

        return $spots;
    }

    
    public static function placeAuction(Player $player, int  $row, int $col, int $auctionType)
    {
        if($auctionType == TOKEN_TYPE_AUCTION_MARKER_1){
            //add first auction token when needed
            $token1 = Tokens::placeFirstAuctionMarker($player,$row,$col);
            Notifications::auctionPlaced( $player, $row, $col, $token1 );
        }
        $token = Tokens::placeAuction($player,$row,$col, $auctionType);
        Notifications::auctionPlaced( $player, $row, $col, $token );
    }
}