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

$config->APP_ENABLE_XSENDFILE = true;

$config->FRONTEND_ITEMS_PER_PAGE = 30;
$config->FRONTEND_CUSTOM_LOGO = true;

$configFactory = Factory::getInstance();
$configFactory->setConfig($config);



/*
 * Config for Test-System
 */
$config = new \Areanet\PIM\Classes\Config('dev.pim.areanet-buehner.de', $config);

$config->DB_HOST = 'db1242.mydbserver.com';
$config->DB_NAME = 'usr_p212925_3';
$config->DB_USER = 'p212925d1';
$config->DB_PASS = 'Ufisisog.693';

$config->APP_ENABLE_XSENDFILE = false;

$configFactory->setConfig($config);



