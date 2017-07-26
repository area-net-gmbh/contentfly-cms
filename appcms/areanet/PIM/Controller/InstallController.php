<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 17.07.17
 * Time: 11:44
 */

namespace Areanet\PIM\Controller;


use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Controller\BaseController;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class InstallController extends BaseController
{

    public function indexAction(){
        if(Adapter::getConfig()->DB_HOST != '$SET_DB_HOST'){
            return $this->app->redirect(Adapter::getConfig()->FRONTEND_URL);
        }

        return $this->app['twig']->render('install.twig', array(
            'errors' => null
        ));
    }

    public function submitAction(Request $request){
        if(Adapter::getConfig()->DB_HOST != '$SET_DB_HOST'){
            return $this->app->redirect(Adapter::getConfig()->FRONTEND_URL);
        }


        $checkErrors = $this->checkInstallation($request);
        if(count($checkErrors)){
            return $this->app['twig']->render('install.twig', array(
                'errors' => $checkErrors
            ));
        }

        $installationErrors = $this->executeInstallation($request);

        if(count($installationErrors)){
            return $this->app['twig']->render('install.twig', array(
                'errors' => $installationErrors
            ));
        }

        return $this->app['twig']->render('install.twig', array(
            'errors' => array(),
            'installed' => time()
        ));
    }

    protected function checkInstallation(Request $request){
        $errors = array();

        $constraints = new Assert\Collection(array(
            'fields' => array(
                'db_host' => new Assert\NotBlank(),
                'db_name' => new Assert\NotBlank(),
                'db_user' => new Assert\NotBlank(),
                'db_pass' => new Assert\NotBlank(),
                'db_strategy' => new Assert\NotBlank()
            ),
            'allowExtraFields' => true
        ));
        $all = $request->request->all();
        $constraintErrors = $this->app['validator']->validate($all, $constraints);


        if(count($constraintErrors)){
            foreach($constraintErrors as $constraintError){
                $errors[$constraintError->getPropertyPath()] =  $constraintError->getMessage();
            }
        }


        if(!self::isEnabled('chmod')){
            $errors['chmod'] =  'PHP-Funktion chmod() ist deaktiviert.';
        }

        try{
            new \PDO('mysql:host='.$request->get('db_host').';dbname='.$request->get('db_name'), $request->get('db_user'), $request->get('db_pass'));
        }catch(\Exception $e){
            $errors['db'] =  $e->getMessage();
        }

        @chmod(ROOT_DIR.'/../custom/config.php', 0775);
        @chmod(ROOT_DIR.'/../data/files', 0775);
        @chmod(ROOT_DIR.'/../data/cache', 0775);

        if(!is_writable(ROOT_DIR.'/../custom/config.php')){
            $errors['custom/config.php'] =  'Konfigurationsdatei custom/config.php kann nicht geschrieben werden.';
        }

        $errorsSym1 = $this->app['helper']->createSymlink(ROOT_DIR.'/public/custom/', 'Frontend', '../../../custom/Frontend');
        $errorsSym2 = $this->app['helper']->createSymlink(ROOT_DIR.'/public/ui/', 'default', '../../areanet/PIM-UI/default/assets');

        $errors  = array_merge($errors, $errorsSym1, $errorsSym2);

        return $errors;
    }

    protected function executeInstallation(Request $request){

        $db_strategy_bool = $request->get('db_strategy') == 'guid' ? 'true' : 'false';

        $configFileData = file_get_contents(ROOT_DIR."/../custom/config.php");

        $configFileData = str_replace('$SET_DB_HOST', $request->get('db_host'), $configFileData);
        $configFileData = str_replace('$SET_DB_NAME', $request->get('db_name'), $configFileData);
        $configFileData = str_replace('$SET_DB_USER', $request->get('db_user'), $configFileData);
        $configFileData = str_replace('$SET_DB_PASS', $request->get('db_pass'), $configFileData);
        $configFileData = str_replace('\'$SET_DB_GUID_STRATEGY\'', $db_strategy_bool, $configFileData);

        try {
            file_put_contents(ROOT_DIR . "/../custom/config.php", $configFileData);
        }catch(\Exception $e){
            return array('config.php' => ROOT_DIR . "/../custom/config.php konnte nicht geschrieben werden.");
        }

        if ($request->get('db_strategy') == 'guid') {
            define('APPCMS_ID_TYPE', 'string');
            define('APPCMS_ID_STRATEGY', 'UUID');
        } else {
            define('APPCMS_ID_TYPE', 'integer');
            define('APPCMS_ID_STRATEGY', 'AUTO');
        }

        $this->app->register(new DoctrineServiceProvider(), array(
            'dbs.options' => array (
                'pim' => array(
                    'driver'    => 'pdo_mysql',
                    'host'      => $request->get('db_host'),
                    'dbname'    => $request->get('db_name'),
                    'user'      => $request->get('db_user'),
                    'password'  => $request->get('db_pass'),
                    'charset'   => Adapter::getConfig()->DB_CHARSET,
                    'collate'   => Adapter::getConfig()->DB_COLLATE,
                )
            )
        ));

        $this->app->register(new \Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), array(
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
                    ),
                )
            ),
            'orm.custom.functions.numeric' => array(
                'Find_In_Set' => '\Areanet\PIM\Classes\ORM\Query\Mysql\FindInSet'
            )
        ));

        $this->app['typeManager'] = $this->app->share(function ($app) {
            return new \Areanet\PIM\Classes\Manager\TypeManager($app);
        });


        foreach (Adapter::getConfig()->APP_SYSTEM_TYPES as $systemType) {
            $typeClass = new $systemType($this->app);
            $this->app['typeManager']->registerType($typeClass);
        }

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->app['orm.em']);
        $classes = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();

        try {
            $schemaTool->updateSchema($classes);
            $this->app['helper']->install($this->app['orm.em']);

        }catch(\Exception $e){
            return array('database' => "Die Datenbank konnte nicht initialisiert werden.");
        }

        return array();

    }


    protected static function isEnabled($func) {
        return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
    }

}