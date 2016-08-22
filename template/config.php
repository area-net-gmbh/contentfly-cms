<?php
use \Areanet\PIM\Classes\Config\Factory;

$configFactory = Factory::getInstance();

/*
 * Default Config
 */

$configDefault = new \Areanet\PIM\Classes\Config();

$configDefault->DB_HOST = 'DB_HOST';
$configDefault->DB_NAME = 'DB_NAME';
$configDefault->DB_USER = 'DB_USER';
$configDefault->DB_PASS = 'DB_PASS';

$configDefault->APP_DEBUG               = true;
$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

$configDefault->FILE_PROCESSORS = array('\Areanet\PIM\Classes\File\Processing\ImageMagick');

$configFactory->setConfig($configDefault);


/*
 * Config for Test-System
 */

/*
$configTest = new \Areanet\PIM\Classes\Config('test.domain.de', $configDefault);

$configDefault->DB_HOST = 'DB_HOST';
$configDefault->DB_NAME = 'DB_NAME';
$configDefault->DB_USER = 'DB_USER';
$configDefault->DB_PASS = 'DB_PASS';

$configFactory->setConfig($configTest);
*/



