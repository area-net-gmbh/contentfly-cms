<?php
define('ROOT_DIR', __DIR__);

require_once ROOT_DIR.'/version.php';
require_once ROOT_DIR.'/vendor/autoload.php';
if(file_exists(ROOT_DIR.'/../custom/vendor/autoload.php')){
    require_once ROOT_DIR.'/../custom/vendor/autoload.php';
}

require_once ROOT_DIR.'/../custom/config.php';
require_once ROOT_DIR.'/../custom/version.php';

if(Config\Adapter::getConfig()->DB_HOST == '$SET_DB_HOST'){
    header('Location: /install.php');
    exit;
}


define('HOST', isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : 'default');

use Silex\Application;
use \Areanet\PIM\Classes\Config;
use Knp\Provider\ConsoleServiceProvider;

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/areanet/PIM/Classes/Annotations/Config.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/areanet/PIM/Classes/Annotations/ManyToMany.php');
\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(ROOT_DIR.'/areanet/PIM/Classes/Annotations/MatrixChooser.php');

if(Config\Adapter::getConfig()->DB_GUID_STRATEGY){
    define('APPCMS_ID_TYPE', 'string');
    define('APPCMS_ID_STRATEGY', 'UUID');
}else{
    define('APPCMS_ID_TYPE', 'integer');
    define('APPCMS_ID_STRATEGY', 'AUTO');
}


$app = new Application();
$app->register(new Silex\Provider\SessionServiceProvider());


Config\Adapter::setHostname(HOST);
date_default_timezone_set(Config\Adapter::getConfig()->APP_TIMEZONE);

$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array (
        'pim' => array(
            'driver'    => 'pdo_mysql',
            'host'      => Config\Adapter::getConfig()->DB_HOST,
            'dbname'    => Config\Adapter::getConfig()->DB_NAME,
            'user'      => Config\Adapter::getConfig()->DB_USER,
            'password'  => Config\Adapter::getConfig()->DB_PASS,
            'charset'   => Config\Adapter::getConfig()->DB_CHARSET,
        )
    ),
));

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'PIM',
    'console.version'           => APP_VERSION,
    'console.project_directory' => ROOT_DIR
));


$app['helper'] = $app->share(function ($app) {
    return new \Areanet\PIM\Classes\Helper();
});

$app['auth'] = $app->share(function ($app) {
    return new \Areanet\PIM\Classes\Auth($app);
});

$app->register(new \Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), array(
    'orm.proxies_dir' => ROOT_DIR.'/../data/cache/doctrine',
    'orm.em.options' => array(
        'connection' => 'pim',
        'mappings' => array(
            array(
                'type' => 'annotation',
                'namespace' => 'Areanet\PIM\Entity',
                'path' => ROOT_DIR.'/areanet/PIM/Entity',
                'use_simple_annotation_reader' => false
            ),
            array(
                'type' => 'annotation',
                'namespace' => 'Custom\Entity',
                'path' => ROOT_DIR.'/../custom/Entity',
                'use_simple_annotation_reader' => false
            ),
        )
    ),
    'orm.custom.functions.numeric' => array(
        'Find_In_Set' => '\Areanet\PIM\Classes\ORM\Query\Mysql\FindInSet'
    )
));




$app['typeManager'] = $app->share(function ($app) {
    return new \Areanet\PIM\Classes\Manager\TypeManager($app);
});


foreach(Config\Adapter::getConfig()->APP_SYSTEM_TYPES as $systemType){
    $typeClass = new $systemType($app);
    $app['typeManager']->registerType($typeClass);
}


if(!is_dir(ROOT_DIR.'/../custom/Views/')){
    mkdir(ROOT_DIR.'/../custom/Views/');
}

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' =>   array(ROOT_DIR.'/../custom/Views/', ROOT_DIR.'/areanet/PIM-UI/default/')
));

//Config Image-Processing

$app['thumbnailSettings'] = function ($app) {
    try {
        $queryBuilder = $app['orm.em']->createQueryBuilder();
        $queryBuilder
            ->select('thumbnailSetting')
            ->from('Areanet\PIM\Entity\ThumbnailSetting', 'thumbnailSetting');
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }catch (Exception $e){
        return array();
    }
};

foreach(Config\Adapter::getConfig()->FILE_PROCESSORS as $fileProcessorSetting){
    $fileProcessor = new $fileProcessorSetting();

    foreach($app['thumbnailSettings'] as $thumbnailSetting){
        $fileProcessor->registerImageSize($thumbnailSetting);
    }

    Areanet\PIM\Classes\File\Processing::registerProcessor($fileProcessor);
}

$app['debug'] = Config\Adapter::getConfig()->APP_DEBUG;

$app['consoleManager'] = $app->share(function ($app) {
    return new \Areanet\PIM\Classes\Manager\ConsoleManager($app);
});

$app['uiManager'] = $app->share(function ($app) {
    return new \Areanet\PIM\Classes\Manager\UIManager($app);
});

$app['routeManager'] = $app->share(function ($app) {
    return new \Areanet\PIM\Classes\Manager\RouteManager($app);
});

$app['dispatcher']->addListener(\Knp\Console\ConsoleEvents::INIT, function(\Knp\Console\ConsoleEvent $event) {
    $app = $event->getApplication();
    $app->add(new \Areanet\PIM\Command\SetupCommand());
});

$app['schema'] = $app->share(function ($app){
    $api = new \Areanet\PIM\Classes\Api($app);
    return $api->getSchema();
});

//Config Spatial ORM-Types
/*
Doctrine\DBAL\Types\Type::addType('point', '\Areanet\PIM\Classes\ORM\Spatial\PointType');
$em= $app['orm.em']->getConnection()->getDatabasePlatform();
$em->registerDoctrineTypeMapping('point', 'point');

//Doctrine Extensions
$config = new Doctrine\ORM\Configuration();
$config->addCustomNumericFunction('DISTANCE', '\Areanet\PIM\Classes\ORM\Spatial\PointType\Distance');
$config->addCustomNumericFunction('POINT_STR', '\Areanet\PIM\Classes\ORM\Spatial\PointType\PointStr');
$config->addCustomStringFunction('FIND_IN_SET', '\Areanet\PIM\Classes\ORM\Query\Mysql\FindInSet');
//TODO: KLASSE WIRD NICHT GEFUNDEN
*/

require_once ROOT_DIR.'/../custom/app.php';

$app['routeManager']->bindRoutes();