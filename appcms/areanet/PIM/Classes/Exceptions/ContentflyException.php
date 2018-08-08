<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 06.08.18
 * Time: 15:52
 */

namespace Areanet\PIM\Classes\Exceptions;


class ContentflyException extends \Exception
{
    protected $value = null;

    public function __construct($message, $value = null, $code = 500) {
        parent::__construct($message, $code);

        $this->value = $value;
    }

    function getValue(){
        return $this->value;
    }
}