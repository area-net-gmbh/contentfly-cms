<?php
namespace Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Config;
use Areanet\PIM\Classes\Exceptions\Config\FactoryNotFoundException;


/**
 * Class Adpater
 *
 * Adapter class to get config settings for a server host (local, development, production,...) with a "one-liner"
 *
 * @package Areanet\PIM\Classes\Config
 */
class Adapter{

    /**
     * @var string $host Server Hostname, init in bootstrap.php
     */
    protected static $host = 'default';


    /**
     * @param string $host
     */
    public static function setHostname($host){
        self::$host = $host;
    }

    /**
     * @return Config
     */
    public static function getConfig(){
        $configFactory = Factory::getInstance();

        return $configFactory->getConfig(self::$host);
    }
}