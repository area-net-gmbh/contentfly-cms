<?php
use \Areanet\PIM\Classes\Config\Factory;

/*
 * Default Config
 */

$config = new \Areanet\PIM\Classes\Config();
$config->DB_HOST = '127.0.0.1';
$config->DB_NAME = 'db';
$config->DB_USER = 'user';
$config->DB_PASS = 'pass';

$config->APP_DEBUG = true;

$configFactory = Factory::getInstance();
$configFactory->setConfig($config);



/*
 * Config for Dev-System
 * 
 * $config = new \Areanet\PIM\Classes\Config('dev.domain.de', $config);
 * ...
 * $configFactory = Factory::getInstance();
 * $configFactory->setConfig($config);
 * */