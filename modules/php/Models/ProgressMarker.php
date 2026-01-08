<?php

namespace Bga\Games\Goa\Models;

/**
 * State of the token will be used to track progression
 */
class ProgressMarker extends Token
{  
    public function getUiData()
    {
        $data = parent::getUiData();
        $data['subtype'] = TOKEN_SUBTTYPE_PROGRESS_MARKER;
        $data['track'] = $this->getTrackIndex();
        return $data;
    }

    public function getTrackIndex() : int {
        switch($this->getType()){
            case TOKEN_TYPE_PROGRESS_MARKER_SHIPS   : return 1;
            case TOKEN_TYPE_PROGRESS_MARKER_HARVEST : return 2;
            case TOKEN_TYPE_PROGRESS_MARKER_TAXES   : return 3;
            case TOKEN_TYPE_PROGRESS_MARKER_EXP     : return 4;
            case TOKEN_TYPE_PROGRESS_MARKER_OUTPOST : return 5;
            default:
                return 1;
        }
    }
   
}
