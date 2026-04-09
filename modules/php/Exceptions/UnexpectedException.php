<?php
namespace Bga\Games\Goa\Exceptions;
use BgaVisibleSystemException;

class UnexpectedException extends BgaVisibleSystemException
{
    protected $code;

    public function __construct(int $code,string $str)
    {
        $message = $str ?? "";
        $message = " /!\ REPORT A BUG if you see this /!\ [$code] $message";
        parent::__construct($message);
        //Don't override BGA codes, I had problems with 114 for example
        //$this->code = $code;
    }
}
?>
