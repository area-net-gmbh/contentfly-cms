<?php
namespace Areanet\PIM\Classes\Manager;

use Areanet\PIM\Classes\Exceptions\ContentflyException;
use Areanet\PIM\Classes\Manager;
use Areanet\PIM\Classes\Messages;
use Areanet\PIM\Classes\Plugin;


/**
 * Class PluginManager
 * @package Areanet\PIM\Classes\Manager
 */
class PluginManager extends Manager
{
    protected $plugins = array();

    /**
     * @param string $pluginName
     * @throws ContentflyException
     */
    public function register($pluginName, $options = null){
        $splittedPluginNames    = explode('_', $pluginName);
        $className              = array_pop($splittedPluginNames);
        $fullClassName          = "Plugins\\$pluginName\\${className}Plugin";

        if(!class_exists($fullClassName)){
            throw new ContentflyException(Messages::contentfly_general_plugin_not_found, $fullClassName, Messages::contentfly_status_not_found);
        }

        $plugin = new $fullClassName($this->app, $options);
        if(!($plugin instanceof Plugin)){
            throw new ContentflyException(Messages::contentfly_general_invalid_plugin_base, $fullClassName, Messages::contentfly_status_not_found);
        }

        $this->plugins[$plugin->getKey()] = $plugin;

    }


    /**
     * @return array
     */
    public function getEntities(){
        $entities = array();
        foreach($this->plugins as $plugin){
            $entities = array_merge($entities, $plugin->getEntities());
        }

        return $entities;
    }


    /**
     * @param string $pluginName
     * @return Plugin
     * @throws ContentflyException
     */
    public function getPlugin($pluginName){
        if(!isset($this->plugins[$pluginName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_plugin, $key);
        }

        return $this->plugins[$pluginName];
    }
}