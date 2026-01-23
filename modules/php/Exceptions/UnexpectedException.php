<?php
namespace Bga\Games\Goa\Exceptions;
use BgaVisibleSystemException;

class UnexpectedException extends BgaVisibleSystemException
{
    protected $code;

    public function __construct(int $code,string $str)
    {
        parent::__construct($str);
        $this->code = $code;
    }
}
?>
