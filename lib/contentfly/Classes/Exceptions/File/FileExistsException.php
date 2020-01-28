<?php
namespace Areanet\Contentfly\Classes\Exceptions\File;
use Areanet\Contentfly\Entity\File;


/**
 * Class NotFoundException
 *
 * @package Areanet\Contentfly\Classes\Exceptions\File
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