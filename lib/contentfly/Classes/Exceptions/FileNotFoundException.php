<?php
namespace Areanet\Contentfly\Classes\Exceptions;


/**
 * Class NotFoundException
 *
 * @package Areanet\Contentfly\Classes\Exceptions\Config
 */

class FileNotFoundException extends \Exception
{
    public function __construct($message = 'File not found') {
        parent::__construct($message);
    }
}