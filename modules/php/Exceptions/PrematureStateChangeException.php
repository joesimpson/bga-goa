<?php
namespace Bga\Games\Goa\Exceptions;

use Exception;

class PrematureStateChangeException extends Exception
{
    public $code;
    public $nextStateClass;

    public function __construct(int $code, $nextStateClass)
    {
        parent::__construct("Game need to change state to $nextStateClass");
        $this->code = $code;
        $this->nextStateClass = $nextStateClass;
    }
}
?>
