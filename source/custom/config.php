<?php
$settings = array();

switch(HOST){
    default: //Lokale Vagrant-Box
        $settings['DB_HOST'] = '127.0.0.1';
        $settings['DB_NAME'] = 'db';
        $settings['DB_USER'] = 'user';
        $settings['DB_PASS'] = 'pass';

        $settings['APP_DEVMODE'] = true;
        $settings['APP_DEBUG'] = true;

        $settings['ITEMS_PER_PAGE'] = 20;
        break;
}

define('APP_MAILTO', 'schmid@area-net.de');

define('DB_HOST', $settings['DB_HOST']);
define('DB_NAME', $settings['DB_NAME']);
define('DB_USER', $settings['DB_USER']);
define('DB_PASS', $settings['DB_PASS']);
define('DB_CHARSET', isset($settings['DB_CHARSET']) ? $settings['DB_CHARSET'] : 'utf8');

define('APP_DEVMODE', isset($settings['APP_DEVMODE']) ? $settings['APP_DEVMODE'] : false);
define('APP_MAILFROM', isset($settings['APP_MAILFROM']) ? $settings['APP_MAILFROM'] : '');
define('APP_UI', 'default');
define('APP_DEBUG', isset($settings['APP_DEBUG']) ? $settings['APP_DEBUG'] : false);
define('APP_TIMEZONE', isset($settings['APP_TIMEZONE']) ? $settings['APP_TIMEZONE'] : 'Europe/Berlin');
define('APP_DEFAULT_CONTROLLER', isset($settings['APP_DEFAULT_CONTROLLER']) ? $settings['APP_DEFAULT_CONTROLLER'] : 'ui.controller:showAction');
define('APP_BACKEND_URL', isset($settings['APP_BACKEND_URL']) ? $settings['APP_BACKEND_URL'] : 'admin');

define('PUSH_GOOGLE_KEY', isset($settings['PUSH_GOOGLE_KEY']) ? $settings['PUSH_GOOGLE_KEY'] : '');
define('PUSH_APPLE_HOST', isset($settings['PUSH_APPLE_HOST']) ? $settings['PUSH_APPLE_HOST'] : 'gateway.push.apple.com:2195');
define('PUSH_APPLE_CERT', isset($settings['PUSH_APPLE_CERT']) ? $settings['PUSH_APPLE_CERT'] : '');
define('PUSH_APPLE_PASS', isset($settings['PUSH_APPLE_PASS']) ? $settings['PUSH_APPLE_PASS'] : '');

define('ITEMS_PER_PAGE', $settings['ITEMS_PER_PAGE']);