<?php
use \Areanet\Contentfly\Classes\Config\Factory;

$configFactory = Factory::getInstance();

/*
 * Default Config
 */

$configDefault = new \Areanet\Contentfly\Classes\Config();

$configDefault->DB_HOST                 = '$SET_DB_HOST';
$configDefault->DB_NAME                 = '$SET_DB_NAME';
$configDefault->DB_USER                 = '$SET_DB_USER';
$configDefault->DB_PASS                 = '$SET_DB_PASS';
$configDefault->DB_GUID_STRATEGY        = '$SET_DB_GUID_STRATEGY';

$configDefault->APP_DEBUG               = true;
$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

$configFactory->setConfig($configDefault);