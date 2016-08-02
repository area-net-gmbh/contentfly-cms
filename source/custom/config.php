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

$configDefault->APP_ENABLE_XSENDFILE    = false;
$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

$configDefault->FRONTEND_ITEMS_PER_PAGE             = 25;
$configDefault->FRONTEND_FORM_IMAGE_SQUARE_PREVIEW  = false;
$configDefault->FRONTEND_URL                        = "admin";

$configDefault->FILE_PROCESSORS         = array('\Areanet\PIM\Classes\File\Processing\ImageMagick');
$configDefault->FILE_HASH_MUST_UNIQUE   = false;

$configDefault->CUSTOM_IMPORT_FOLDER_BILDER             = 28;
$configDefault->CUSTOM_IMPORT_FOLDER_DATENBLAETTER      = 29;
$configDefault->CUSTOM_IMPORT_FOLDER_DIGITALVORLAGEN    = 30;
$configDefault->CUSTOM_IMPORT_FOLDER                    = ROOT_DIR.'/../data/';

$configFactory->setConfig($configDefault);



