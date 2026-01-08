<?php
namespace Bga\Games\Goa\Exceptions;
use BgaVisibleSystemException;

class UnexpectedException extends BgaVisibleSystemException
{
    protected $code;

    public function __construct($code,$str)
    {
        parent::__construct($str);
        $this->code = $code;
    }
}
?>
