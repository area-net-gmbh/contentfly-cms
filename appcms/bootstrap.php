<?php
define('ROOT_DIR', __DIR__);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);




require_once ROOT_DIR.'/version.php';
require_once ROOT_DIR.'/vendor/autoload.php';
if(file_exists(ROOT_DIR.'/../custom/vendor/autoload.php')){
    require_once ROOT_DIR.'/../custom/vendor/autoload.php';
}

require_once ROOT_DIR.'/../custom/config.php';
require_once ROOT_DIR.'/../custom/version.php';

define('HOST', isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : 'default');

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Silex\Application;
use \Areanet\PIM\Classes\Config;
use Knp\Provider\ConsoleServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/areanet/PIM/Classes/Annotations/Config.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/areanet/PIM/Classes/Annotations/ManyToMany.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/areanet/PIM/Classes/Annotations/MatrixChooser.php');

if(Config\Adapter::getConfig()->APP_DEBUG){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL ^E_NOTICE);
}

$app = new Application();
$app['session'] = function () {
    return new Symfony\Component\HttpFoundation\Session\Session();
};


$app['is_installed'] = (Config\Adapter::getConfig()->DB_HOST != '$SET_DB_HOST');
$app['auth.user'] = null;

Config\Adapter::setHostname(HOST);
date_default_timezone_set(Config\Adapter::getConfig()->APP_TIMEZONE);

$app->register(new Silex\Provider\ServiceControllerServiceProvider());

define('APP_CMS_SHOW_ID_IN_LIST', Config\Adapter::getConfig()->FRONTEND_SHOW_ID_IN_LIST);
define('APP_CMS_SHOW_OWNER_IN_LIST', Config\Adapter::getConfig()->FRONTEND_SHOW_OWNER_IN_LIST);

if(Config\Adapter::getConfig()->APP_LANGUAGES){
    define('APP_CMS_MAIN_LANG', Config\Adapter::getConfig()->APP_LANGUAGES[0]);
}else{
    define('APP_CMS_MAIN_LANG', null);
}

if($app['is_installed']) {
    if (Config\Adapter::getConfig()->DB_GUID_STRATEGY) {
        define('APPCMS_ID_TYPE', 'string');
        define('APPCMS_ID_STRATEGY', 'UUID');
    } else {
        define('APPCMS_ID_TYPE', Config\Adapter::getConfig()->DB_ID_INTEGER_TYPE);
        define('APPCMS_ID_STRATEGY', 'AUTO');
    }

    $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
        'dbs.options' => array(
            'pim' => array(
                'driver' => 'pdo_mysql',
                'host' => Config\Adapter::getConfig()->DB_HOST,
                'dbname' => Config\Adapter::getConfig()->DB_NAME,
                'user' => Config\Adapter::getConfig()->DB_USER,
                'password' => Config\Adapter::getConfig()->DB_PASS,
                'charset' => Config\Adapter::getConfig()->DB_CHARSET,
                'defaultTableOptions' => array(
                    'charset' => Config\Adapter::getConfig()->DB_CHARSET,
                    'collate' => Config\Adapter::getConfig()->DB_COLLATE
                )
            )
        ),
    ));
}

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'PIM',
    'console.version'           => APP_VERSION,
    'console.project_directory' => ROOT_DIR
));


$app['helper'] = function () {
    return new \Areanet\PIM\Classes\Helper();
};

$app['auth'] = function ($app) {
    return new \Areanet\PIM\Classes\Auth($app);
};

if($app['is_installed']) {

    if(!defined('APPCMS_CONSOLE')) $app['helper']->createSymlinks();

    $app->register(new \Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), array(
        'orm.proxies_dir' => ROOT_DIR . '/../data/cache/doctrine',
        'orm.em.options' => array(
            'connection' => 'pim',
            'mappings' => array(
                array(
                    'type' => 'annotation',
                    'namespace' => 'Areanet\PIM\Entity',
                    'path' => ROOT_DIR . '/areanet/PIM/Entity',
                    'use_simple_annotation_reader' => false
                ),
                array(
                    'type' => 'annotation',
                    'namespace' => 'Custom\Entity',
                    'path' => ROOT_DIR . '/../custom/Entity',
                    'use_simple_annotation_reader' => false
                )
            )
        ),
        'orm.auto_generate_proxies' => Config\Adapter::getConfig()->APP_AUTOGENERATE_PROXIES,
        'orm.custom.functions.numeric' => array(
            'Find_In_Set' => '\Areanet\PIM\Classes\ORM\Query\Mysql\FindInSet'
        )
    ));

    if (!Config\Adapter::getConfig()->APP_DEBUG && !defined('APPCMS_CONSOLE')) {
        $config = $app['orm.em']->getConfiguration();
        switch (Config\Adapter::getConfig()->APP_CACHE_DRIVER) {
            case 'apc':
                $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache('query'));
                $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache('metadata'));
                break;
            case 'apcu':
                $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcuCache('query'));
                $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcuCache('metadata'));
                break;
            case 'memcached':
                $cache = new \Doctrine\Common\Cache\MemcachedCache();
                $cache->setMemcached(new Memcached());

                $config->setQueryCacheImpl($cache);
                $config->setMetadataCacheImpl($cache);
                break;
            case 'filesystem':
            default:
                $config->setQueryCacheImpl(new \Doctrine\Common\Cache\FilesystemCache(ROOT_DIR . '/../data/cache/query'));
                $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\FilesystemCache(ROOT_DIR . '/../data/cache/metadata'));
                break;
        }
    }

    $app['typeManager'] = function ($app) {
        return new \Areanet\PIM\Classes\Manager\TypeManager($app);
    };

    /** @var \Areanet\PIM\Classes\Manager\PluginManager */
    $app['pluginManager'] = function ($app) {
        return new \Areanet\PIM\Classes\Manager\PluginManager($app);
    };

    foreach (Config\Adapter::getConfig()->APP_SYSTEM_TYPES as $systemType) {


        if(!class_exists($systemType)){
            die("contentfly_type_class_not_found: $systemType");
        }

        $typeClass = new $systemType($app);
        $app['typeManager']->registerType($typeClass);
    }
}else{
    $app['orm.em'] = null;
}

if(!is_dir(ROOT_DIR.'/../custom/Views/')){
    mkdir(ROOT_DIR.'/../custom/Views/');
}

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'     =>   array(ROOT_DIR.'/../custom/Views/', ROOT_DIR.'/areanet/PIM-UI/default/'),
    'twig.options'  => array('strict_variables' => false)
));

if($app['is_installed']) {
    $app['thumbnailSettings'] = function ($app) {
        try {
            $queryBuilder = $app['orm.em']->createQueryBuilder();
            $queryBuilder
                ->select('thumbnailSetting')
                ->from('Areanet\PIM\Entity\ThumbnailSetting', 'thumbnailSetting');
            $query = $queryBuilder->getQuery();
            return $query->getResult();
        } catch (Exception $e) {
            return array();
        }
    };

    foreach (Config\Adapter::getConfig()->FILE_PROCESSORS as $fileProcessorSetting) {
        $fileProcessor = new $fileProcessorSetting();

        foreach ($app['thumbnailSettings'] as $thumbnailSetting) {
            $fileProcessor->registerImageSize($thumbnailSetting);
        }

        Areanet\PIM\Classes\File\Processing::registerProcessor($fileProcessor);
    }
}

$app['debug'] = Config\Adapter::getConfig()->APP_DEBUG;

$app['consoleManager'] = function ($app) {
    return new \Areanet\PIM\Classes\Manager\ConsoleManager($app);
};

$app['uiManager'] = function ($app) {
    return new \Areanet\PIM\Classes\Manager\UIManager($app);
};

$app['routeManager'] = function ($app) {
    return new \Areanet\PIM\Classes\Manager\RouteManager($app);
};

$app->extend('dispatcher', function (EventDispatcherInterface $dispatcher, $app) {
    $dispatcher->addListener(\Knp\Console\ConsoleEvents::INIT, function (\Knp\Console\ConsoleEvent $event) {
        $app = $event->getApplication();
        $app->add(new \Areanet\PIM\Command\SetupCommand());
    });
    return $dispatcher;
});

$app['schema'] = function ($app){
    $api = new \Areanet\PIM\Classes\Api($app);
    return $api->getSchema();
};


$app['database'] = function ($app){
    $config = \Areanet\PIM\Classes\Config\Adapter::getConfig();

    $connectionParams = array(
        'dbname'    => $config->DB_NAME,
        'user'      => $config->DB_USER,
        'password'  => $config->DB_PASS,
        'port'      => $config->DB_PORT,
        'host'      => $config->DB_HOST,
        'driver'    => 'pdo_mysql',
        'charset'   => $config->DB_CHARSET
    );

    return  \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
};

if($app['is_installed']) {
    $evm = $app['orm.em']->getEventManager();
    $evm->addEventListener(Events::loadClassMetadata, new \Areanet\PIM\Classes\Events\LoadMetadata());
}

$app->register(new Silex\Provider\ValidatorServiceProvider());

$app['auth']->init();
require_once ROOT_DIR.'/../custom/app.php';

$app['routeManager']->bindRoutes();



