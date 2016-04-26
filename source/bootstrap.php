<?php
define('HOST', isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : 'cli');

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/custom/config.php';
require_once __DIR__.'/version.php';

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__.'/areanet/PIM/Classes/Annotations/Config.php');

date_default_timezone_set(APP_TIMEZONE);

use Silex\Application;

$app = new Application();
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array (
        'driver'    => 'pdo_mysql',
        'host'      => DB_HOST,
        'dbname'    => DB_NAME,
        'user'      => DB_USER,
        'password'  => DB_PASS,
        'charset'   => DB_CHARSET,
    ),
));


$app->register(new \Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), array(
    'orm.proxies_dir' => __DIR__.'/data/cache/doctrine',
    'orm.em.options' => array(
        'mappings' => array(
            array(
                'type' => 'annotation',
                'namespace' => 'Areanet\PIM\Entity',
                'path' => __DIR__.'/areanet/PIM/Entity',
                'use_simple_annotation_reader' => false
            ),
            array(
                'type' => 'annotation',
                'namespace' => 'Custom\Entity',
                'path' => __DIR__.'/custom/Entity',
                'use_simple_annotation_reader' => false
            ),
        ),
    ),
));

$user = new \Areanet\PIM\Entity\User();
$user->getAlias();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/custom/Views/',
));


$app['debug'] = APP_DEBUG;


//Config Image-Processing
Areanet\PIM\Classes\File\Processing::registerProcessor('image/jpeg', '\Areanet\Pim\Classes\File\Processing\Image');
Areanet\PIM\Classes\File\Processing::registerProcessor('image/gif', '\Areanet\Pim\Classes\File\Processing\Image');
Areanet\PIM\Classes\File\Processing::registerProcessor('image/png', '\Areanet\Pim\Classes\File\Processing\Image');

Areanet\PIM\Classes\File\Processing\Image::registerImageSize(320);
Areanet\PIM\Classes\File\Processing\Image::registerImageSize(640);
Areanet\PIM\Classes\File\Processing\Image::registerImageSize(960);

//Config Spatial ORM-Types
Doctrine\DBAL\Types\Type::addType('point', '\Areanet\PIM\Classes\ORM\Spatial\PointType');
$em= $app['orm.em']->getConnection()->getDatabasePlatform();
$em->registerDoctrineTypeMapping('point', 'point');

$config = new Doctrine\ORM\Configuration();
$config->addCustomNumericFunction('DISTANCE', '\Areanet\PIM\Classes\ORM\Spatial\PointType\Distance');
$config->addCustomNumericFunction('POINT_STR', '\Areanet\PIM\Classes\ORM\Spatial\PointType\PointStr');