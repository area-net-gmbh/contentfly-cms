<?php
namespace Areanet\PIM\Classes\Config;

use Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Exceptions\Config\NotFoundException;


/**
 * Class Factory
 *
 * Factory class to manage config setting for different server hosts (local, development, production,...)
 *
 * @package Areanet\PIM\Classes\Config
 */

class Factory{

    /**
     * @var Factory
     */
    protected static $_instance = null;

    /**
     * @var Config[]
     */
    protected $configSettings = array();


    /**
     * @param string $host Hostname
     * @return Config
     */
    public function getConfig($host = 'default')
    {

        if(!isset($this->configSettings[$host])){

            return $this->configSettings['default'];
        }

        return $this->configSettings[$host];
    }

    /**
     * @param Config $config Config-Settings
     */
    public function setConfig(Config $config)
    {
        $host = $config->getHost() ? $config->getHost() : 'default';
        $this->configSettings[$host] = $config;

    }


    /**
     * Get singleton instance
     * @return Factory;
     */
    public static function getInstance()
    {
        if(self::$_instance == null){
            self::$_instance = new Factory();
        }

        return self::$_instance;
    }


    /**
     * Disable cloning
     */
    protected function __clone(){}

    /**
     * Disable creating manual instances
     */
    protected function __construct(){}


}