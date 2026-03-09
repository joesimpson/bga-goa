<?php

declare(strict_types=1);

namespace {

    function logForTests(string $message, string $level = 'TEST'){
        date_default_timezone_set('Europe/Brussels');
        $date = new DateTime();
        $date = $date->format("d/m/y H:i:s v");
        fwrite(STDOUT, print_r("$date - $level : $message\n", TRUE));
    }

    function clienttranslate(string $text): string
    {
        return $text; 
    }
    
    function mysql_escape_string(string $a): string
    {
        return $a;
    }
        
    /**
     * Base exception.
     * 
     * @deprecated use \Bga\GameFramework\UserException, \Bga\GameFramework\SystemException or \Bga\GameFramework\VisibleSystemException depending on your need
     */
    class feException extends Exception
    {
        public function __construct($message, $expected = false, $visibility = true, $code=100, $publicMsg='', public ?array $args = null) {
            parent::__construct($message);
        }
    }
    /**
     * @deprecated Use \Bga\GameFramework\SystemException instead
     */
    class BgaSystemException extends feException
    {
        public function __construct($message, $code=100, ?array $args = null) {
            parent::__construct($message);
        }
    }
    /**
     * @deprecated Use \Bga\GameFramework\VisibleSystemException instead
     */
    class BgaVisibleSystemException extends BgaSystemException
    {
        public function __construct($message, $code=100, ?array $args = null) {
            parent::__construct($message);
        }
    }
}
