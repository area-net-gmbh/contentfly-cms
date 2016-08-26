<?php
use \Areanet\PIM\Classes\Config\Factory;

$configFactory = Factory::getInstance();

/*
 * Default Config
 */

$configDefault = new \Areanet\PIM\Classes\Config();

$configDefault->DB_HOST = 'localhost';
$configDefault->DB_NAME = 'db';
$configDefault->DB_USER = 'user';
$configDefault->DB_PASS = 'pass';

$configDefault->APP_DEBUG               = true;
$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

$configDefault->FILE_PROCESSORS = array('\Areanet\PIM\Classes\File\Processing\ImageMagick');

$configFactory->setConfig($configDefault);


/*
 * Config for Test-System
 */

/*
$configTest = new \Areanet\PIM\Classes\Config('test.domain.de', $configDefault);

$configDefault->DB_HOST = 'localhost';
$configDefault->DB_NAME = 'db';
$configDefault->DB_USER = 'user';
$configDefault->DB_PASS = 'pass';

$configFactory->setConfig($configTest);
*/





$configDefault->DO_INSTALL = true;