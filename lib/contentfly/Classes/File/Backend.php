<?php
namespace Areanet\PIM\Classes\File;

use Areanet\PIM\Classes\File\Backend\FileSystem;

class Backend
{

    static public function getInstance()
    {
        return new FileSystem();
    }
}