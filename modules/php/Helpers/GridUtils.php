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
        $size = count($baseSpots) / 4;
        return array_merge(
            array_slice($baseSpots, $size, $size*3 ),
            array_slice($baseSpots,0, $size )
        );
    }
    
    public static function center(): array
    {
        return ['row' => 3, 'col'=>3];
    }
    
    /**
     * First row/col we encounter on the grid
     */
    public static function listOrderedSpotsExterior_TopLeft_ClockWise(): array
    {
        $spots = [];
        //Edges of the grid :
        for($col=1;$col<=MARKET_GRID_SIZE;$col++){
            $row = 1;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($row=1;$row<=MARKET_GRID_SIZE;$row++){
            $col = MARKET_GRID_SIZE;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($col=MARKET_GRID_SIZE;$col>=1;$col--){
            $row = MARKET_GRID_SIZE;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($row=MARKET_GRID_SIZE;$row>=1;$row--){
            $col = 1;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        return $spots;
    }
    public static function listOrderedSpotsExterior_TopRight_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsExterior_TopLeft_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsExterior_BottomRight_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsExterior_TopRight_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsExterior_BottomLeft_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsExterior_BottomRight_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }

    public static function listOrderedSpotsExterior_TopLeft_CounterClockWise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsExterior_TopLeft_ClockWise();
        $size = count($baseSpots);
        return array_merge(
            array_slice($baseSpots,0, 1 ),
            array_reverse(array_slice($baseSpots, 1, $size )),
        );
    }
    public static function listOrderedSpotsExterior_TopRight_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsExterior_BottomRight_CounterClockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsExterior_BottomRight_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsExterior_BottomLeft_CounterClockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsExterior_BottomLeft_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsExterior_TopLeft_CounterClockWise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    
    public static function listOrderedSpotsMiddle_TopLeft_Clockwise(): array
    {
        $spots = [];
        for($col=1+1;$col<=MARKET_GRID_SIZE-1;$col++){
            $row = 2;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($row=1+2;$row<=MARKET_GRID_SIZE-1;$row++){
            $col = MARKET_GRID_SIZE-1;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($col=MARKET_GRID_SIZE-2;$col>=1+1;$col--){
            $row = MARKET_GRID_SIZE-1;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        for($row=MARKET_GRID_SIZE-2;$row>=1+2;$row--){
            $col = 2;
            $spots[] = ['row'=>$row,'col'=>$col];
        }
        return $spots;
    }
    
    public static function listOrderedSpotsMiddle_TopRight_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsMiddle_TopLeft_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsMiddle_BottomRight_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsMiddle_TopRight_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsMiddle_BottomLeft_Clockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsMiddle_BottomRight_Clockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsMiddle_TopLeft_CounterClockWise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsMiddle_TopLeft_ClockWise();
        $size = count($baseSpots);
        return array_merge(
            array_slice($baseSpots,0, 1 ),
            array_reverse(array_slice($baseSpots, 1, $size )),
        );
    }
    public static function listOrderedSpotsMiddle_TopRight_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsMiddle_BottomRight_CounterClockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsMiddle_BottomRight_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsMiddle_BottomLeft_CounterClockwise();
        return GridUtils::rotateClockwise($baseSpots);
    }
    public static function listOrderedSpotsMiddle_BottomLeft_CounterClockwise(): array
    {
        $baseSpots = GridUtils::listOrderedSpotsMiddle_TopLeft_CounterClockWise();
        return GridUtils::rotateClockwise($baseSpots);
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