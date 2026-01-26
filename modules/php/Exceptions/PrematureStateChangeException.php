<?php
namespace Bga\Games\Goa\Exceptions;

use Bga\Games\Goa\Models\Player;
use Exception;

class PrematureStateChangeException extends Exception
{
    public $code;
    public $nextStateClass;
    /**
     * Player to change if not the active player
     */
    public Player $changePlayer;

    public function __construct(int $code, $nextStateClass, Player $changePlayer)
    {
        parent::__construct("Game need to change state to $nextStateClass");
        $this->code = $code;
        $this->nextStateClass = $nextStateClass;
        $this->changePlayer = $changePlayer;
    }
}
?>
