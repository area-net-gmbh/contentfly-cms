<?php
namespace Areanet\Contentfly\Classes\File;

use Areanet\Contentfly\Classes\File\Backend\FileSystem;

class Backend
{

    static public function getInstance()
    {
        return new FileSystem();
    }
}