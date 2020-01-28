<?php
namespace Areanet\Contentfly\Classes\Type;

use Areanet\Contentfly\Classes\Type;

abstract class PluginType extends Type
{
    protected $pluginKey = null;

    final public function getPluginKey(){
        return $this->pluginKey;
    }

    final public function setPluginKey($key){
        $this->pluginKey = $key;
    }
}