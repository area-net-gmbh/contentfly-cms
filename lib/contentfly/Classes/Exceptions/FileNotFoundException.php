<?php
namespace Areanet\PIM\Classes\Exceptions;


/**
 * Class NotFoundException
 *
 * @package Areanet\PIM\Classes\Exceptions\Config
 */

class FileNotFoundException extends \Exception
{
    public function __construct($message = 'File not found') {
        parent::__construct($message);
    }
}