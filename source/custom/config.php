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
$configDefault->DB_GUID_STRATEGY = true;

$configDefault->APP_DEBUG               = true;
$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

$configDefault->FILE_PROCESSORS = array('\Areanet\PIM\Classes\File\Processing\ImageMagick');
$configDefault->FILE_HASH_MUST_UNIQUE = true;

$configDefault->FRONTEND_URL = 'admin';
$configDefault->FRONTEND_CUSTOM_LOGIN_BG = true;

$configFactory->setConfig($configDefault);


/*
 * Config for Test-System
 */


/*
 * Config for Test-System
 */
$configTest = new \Areanet\PIM\Classes\Config('dev.das-app-cms.de', $configDefault);

$configTest->DB_HOST = 'db1254.mydbserver.com';
$configTest->DB_NAME = 'usr_p356303_1';
$configTest->DB_USER = 'p356303';
$configTest->DB_PASS = 'utaYidul,191';

$configTest->APP_ENABLE_XSENDFILE       = false;
$configTest->APP_ENABLE_SCHEMA_CACHE    = true;
$configTest->SYSTEM_PHP_CLI_COMMAND     = 'php_cli';

$configFactory->setConfig($configTest);
