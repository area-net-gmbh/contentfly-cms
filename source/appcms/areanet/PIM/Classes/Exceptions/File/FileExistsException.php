<?php
namespace Areanet\PIM\Classes\Exceptions\File;
use Areanet\PIM\Entity\File;


/**
 * Class NotFoundException
 *
 * @package Areanet\PIM\Classes\Exceptions\File
 */

class FileExistsException extends \Exception
{
    /**
     * @var Integer
     */
    public $fileId = null;

    public function __construct($message, $fileId)
    {
        parent::__construct($message);
        $this->fileId = $fileId;
    }
}