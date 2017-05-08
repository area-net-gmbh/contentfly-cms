<?php
use \Areanet\PIM\Classes\Config\Factory;

$configFactory = Factory::getInstance();

/*
 * Default Config
 */

$configDefault = new \Areanet\PIM\Classes\Config();

$configDefault->DB_HOST                 = 'SET_DB_HOST';
$configDefault->DB_NAME                 = 'SET_DB_NAME';
$configDefault->DB_USER                 = 'SET_DB_USER';
$configDefault->DB_PASS                 = 'SET_pass';
$configDefault->DB_GUID_STRATEGY        = true;

$configDefault->APP_DEBUG               = true;
$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

$configDefault->SYSTEM_PHP_CLI_COMMAND = 'php';

$configFactory->setConfig($configDefault);