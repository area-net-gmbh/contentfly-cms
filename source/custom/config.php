<?php
use \Areanet\PIM\Classes\Config\Factory;

$configFactory = Factory::getInstance();

/*
 * Default Config
 */

$configDefault = new \Areanet\PIM\Classes\Config();

$configDefault->DB_HOST = '127.0.0.1';
$configDefault->DB_NAME = 'db';
$configDefault->DB_USER = 'user';
$configDefault->DB_PASS = 'pass';

$configDefault->APP_DEBUG = true;

$configDefault->APP_ENABLE_XSENDFILE = true;
$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

$configDefault->FRONTEND_ITEMS_PER_PAGE = 25;
$configDefault->FRONTEND_CUSTOM_LOGO = true;

$configDefault->FILE_PROCESSORS  = array('\Areanet\PIM\Classes\File\Processing\ImageMagick');
$configDefault->FILE_HASH_MUST_UNIQUE = false;

$configDefault->CUSTOM_IMPORT_FOLDER_BILDER             = 28;
$configDefault->CUSTOM_IMPORT_FOLDER_DATENBLAETTER      = 29;
$configDefault->CUSTOM_IMPORT_FOLDER_DIGITALVORLAGEN    = 30;

$configFactory->setConfig($configDefault);


/*
 * Config for Test-System
 */
$configTest = new \Areanet\PIM\Classes\Config('dev.pim.areanet-buehner.de', $configDefault);

$configTest->DB_HOST = 'db1242.mydbserver.com';
$configTest->DB_NAME = 'usr_p212925_3';
$configTest->DB_USER = 'p212925d1';
$configTest->DB_PASS = 'Ufisisog.693';

$configTest->APP_ENABLE_XSENDFILE = false;
$configTest->APP_ENABLE_SCHEMA_CACHE = true;

$configDefault->CUSTOM_IMPORT_FOLDER_BILDER             = 11;
$configDefault->CUSTOM_IMPORT_FOLDER_DATENBLAETTER      = 12;
$configDefault->CUSTOM_IMPORT_FOLDER_DIGITALVORLAGEN    = 13;

$configFactory->setConfig($configTest);

/*
 * Config for Live-System
 */
$configLive = new \Areanet\PIM\Classes\Config('live.pim.areanet-buehner.de', $configDefault);

$configLive->DB_HOST = 'db1247.mydbserver.com';
$configLive->DB_NAME = 'usr_p212925_5';
$configLive->DB_USER = 'p212925d3';
$configLive->DB_PASS = 'BL9-cXC44e';

$configLive->APP_ENABLE_XSENDFILE = false;
$configTest->APP_ENABLE_SCHEMA_CACHE = true;

$configDefault->CUSTOM_IMPORT_FOLDER_BILDER             = 11;
$configDefault->CUSTOM_IMPORT_FOLDER_DATENBLAETTER      = 12;
$configDefault->CUSTOM_IMPORT_FOLDER_DIGITALVORLAGEN    = 13;

$configFactory->setConfig($configLive);



