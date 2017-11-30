<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 21.04.17
 * Time: 10:26
 */

namespace Areanet\PIM\Classes;


use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Table;
use Silex\Application;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;

class Api
{
    protected $_MIMETYPES = array(
        'images' => array('image/jpeg', 'image/png', 'image/gif'),
        'pdf' => array('application/pdf')
    );

    /** @var Application $app */
    protected $app;

    /** @var EntityManager $em */
    protected $em;

    /** @var  @var Request $request */
    protected $request;

    public function __construct($app, $request = null)
    {
        $this->app      = $app;
        $this->em       = $app['orm.em'];
        $this->request  = $request;
    }
    
    public function getExtendedSchema(){
        $frontend = array(
            'customLogo' => Adapter::getConfig()->FRONTEND_CUSTOM_LOGO,
            'formImageSquarePreview' => Adapter::getConfig()->FRONTEND_FORM_IMAGE_SQUARE_PREVIEW,
            'title'  => Adapter::getConfig()->FRONTEND_TITLE,
            'welcome'  => Adapter::getConfig()->FRONTEND_WELCOME,
            'customNavigation' => array(
                'enabled' => Adapter::getConfig()->FRONTEND_CUSTOM_NAVIGATION
            ),
            'login_redirect' => Adapter::getConfig()->FRONTEND_LOGIN_REDIRECT
        );

        $uiblocks = $this->app['uiManager']->getBlocks();

        $schema         = $this->app['schema'];
        $permissions    = $this->getPermissions();

        if(Adapter::getConfig()->FRONTEND_CUSTOM_NAVIGATION){
            $frontend['customNavigation']['items'] = array();

            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                ->select("navItem")
                ->from("Areanet\PIM\Entity\NavItem", "navItem")
                ->join("navItem.nav", "nav")
                ->where('navItem.nav IS NOT NULL')
                ->orderBy('nav.sorting')
                ->orderBy('navItem.sorting');

            $items = $queryBuilder->getQuery()->getResult();
            foreach($items as $item){

                $entityUriName = str_replace('Areanet\PIM\Entity', 'PIM/', $item->getEntity());
                $entityUriName = str_replace('Custom\Entity', '', $entityUriName);

                if(empty($frontend['customNavigation']['items'][$item->getNav()->getId()])){
                    $frontend['customNavigation']['items'][$item->getNav()->getId()] = array(
                        'title' => $item->getNav()->getTitle(),
                        'icon' => $item->getNav()->getIcon() ? $item->getNav()->getIcon() : 'glyphicon glyphicon-th-large',
                        'items' => array()
                    );
                }

                $frontend['customNavigation']['items'][$item->getNav()->getId()]['items'][] = array(
                    'entity' => $item->getEntity(),
                    'title'  => $item->getTitle() ? $item->getTitle() : $schema[$item->getEntity()]['settings']['label'],
                    'uri'    => $item->getUri() ? $item->getUri() : '#/list/'.$entityUriName,
                );
            }
        }

        return array('frontend' => $frontend, 'uiblocks' => $uiblocks, 'devmode' => Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION.'/'.CUSTOM_VERSION, 'data' => $schema, 'permissions' => $permissions);
    }


    public function getList($entityName, $where = null, $order = null, $groupBy = null, $properties = array(), $lastModified = null, $flatten = false, $currentPage = 0, $itemsPerPage = 20){
        if(!empty($lastModified)) {
            try {
                $lastModified = new \Datetime($lastModified);
            } catch (\Exception $e) {

            }
        }

        if (substr($entityName, 0, 3) == 'PIM') {
            $entityNameToLoad = 'Areanet\PIM\Entity\\' . substr($entityName, 4);
        }elseif(substr($entityName, 0, 7) == 'Areanet'){
            $splitter = explode('\\', $entityName);
            $entityNameToLoad = $entityName;
            $entityName       = 'PIM\\'.$splitter[count($splitter) - 1];
        }else{
            $entityName = ucfirst($entityName);
            $entityNameToLoad = 'Custom\Entity\\' . ucfirst($entityName);
        }


        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad verweigert.");
        }

        $schema     = $this->app['schema'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select("count(".$entityName.")")
            ->from($entityNameToLoad, $entityName)
            ->andWhere("$entityName.isIntern = false");


        if($permission == \Areanet\PIM\Entity\Permission::OWN){
            $queryBuilder->andWhere("$entityName.userCreated = :userCreated OR FIND_IN_SET(:userCreated, $entityName.users) = 1");
            $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
        }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
            $group = $this->app['auth.user']->getGroup();
            if(!$group){
                $queryBuilder->andWhere("$entityName.userCreated = :userCreated");
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }else{
                $queryBuilder->andWhere("$entityName.userCreated = :userCreated OR FIND_IN_SET(:userGroup, $entityName.groups) = 1");
                $queryBuilder->setParameter('userGroup', $group);
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }
        }

        if($lastModified){
            $queryBuilder->andWhere($entityName.'.modified >= :lastModified')->setParameter('lastModified', $lastModified);
        }

        if($where){
            $placeholdCounter   = 0;
            $joinedCounter      = 0;

            foreach($where as $field => $value){

                if(!isset($schema[$entityName]['properties'][$field])){

                    continue;
                }

                if($schema[$entityName]['properties'][$field]['type'] == 'multijoin'){
                    if(isset($schema[$entityName]['properties'][$field]['mappedBy'])){
                        if($value == -1) {
                            $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];
                            $queryBuilder->leftJoin("$entityName.$field", "joined$joinedCounter");
                            $queryBuilder->andWhere("joined$joinedCounter.$mappedBy IS NULL");
                        }else{
                            $searchJoinedEntity = $schema[$entityName]['properties'][$field]['accept'];
                            $searchJoinedObject = $this->em->getRepository($searchJoinedEntity)->find($value);
                            $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];

                            $queryBuilder->leftJoin("$entityName.$field", "joined$joinedCounter");
                            $queryBuilder->andWhere("joined$joinedCounter.$mappedBy = :$field");
                            $queryBuilder->setParameter($field, $searchJoinedObject);
                            $placeholdCounter++;
                        }

                    }else{

                        $queryBuilder->leftJoin("$entityName.$field", 'k');
                        if($value == -1){
                            $queryBuilder->andWhere("k.id IS NULL");
                        }else{
                            $queryBuilder->andWhere("k.id = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;
                        }

                    }

                }else{
                    switch($schema[$entityName]['properties'][$field]['type']){
                        case 'join':
                            $value = $value;
                            if($value == -1){
                                $queryBuilder->andWhere("$entityName.$field IS NULL");
                            }else{
                                $queryBuilder->andWhere("$entityName.$field = :$field");
                                $queryBuilder->setParameter($field, $value);
                                $placeholdCounter++;
                            }
                            break;
                        case 'boolean':
                            if(strtolower($value) == 'false'){
                                $value = 0;
                            }elseif(strtolower($value) == 'true'){
                                $value = 1;
                            }else{
                                $value = boolval($value);
                            }

                            $queryBuilder->andWhere("$entityName.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                        case 'integer':
                            $value = intval($value);

                            $queryBuilder->andWhere("$entityName.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                        default:

                            $queryBuilder->andWhere("$entityName.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                    }


                }

            }

            if(isset($where['fulltext'])){
                $orX = $queryBuilder->expr()->orX();
                $fulltextTypes = array('string', 'text', 'textarea', 'rte');

                $orX->add("$entityName.id = :FT_id");
                $queryBuilder->setParameter("FT_id", $where['fulltext']);

                foreach($schema[$entityName]['properties'] as $field => $fieldOptions){

                    if(in_array($fieldOptions['type'], $fulltextTypes)){
                        $orX->add("$entityName.$field LIKE :FT_$field");
                        $queryBuilder->setParameter("FT_$field", '%' . $where['fulltext'] . '%');
                    }
                }

                $queryBuilder->andWhere($orX);
            }

            if(isset($where['mimetypes'])){

                if($where['mimetypes'] == 'other'){
                    $types = array();
                    foreach($this->_MIMETYPES as $mimetypes){
                        $types = array_merge($types, $mimetypes);
                    }
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn("$entityName.type", $types));
                }elseif(isset($this->_MIMETYPES[$where['mimetypes']])){

                    $queryBuilder->andWhere($queryBuilder->expr()->in("$entityName.type", $this->_MIMETYPES[$where['mimetypes']]));

                }


            }
        }

        $event = new \Areanet\PIM\Classes\Event();
        $event->setParam('request',        $this->request);
        $event->setParam('entity',         $entityName);
        $event->setParam('queryBuilder',   $queryBuilder);
        $event->setParam('app',            $this->app);
        $event->setParam('user',           $this->app['auth.user']);

        $this->app['dispatcher']->dispatch('pim.entity.before.list', $event);

        $query       = $queryBuilder->getQuery();
        $totalObjects = $query->getSingleScalarResult();

        if($currentPage*$itemsPerPage > $totalObjects){
            $currentPage = ceil($totalObjects/$itemsPerPage);
        }

        if($currentPage) {
            $queryBuilder
                ->setFirstResult($itemsPerPage * ($currentPage - 1))
                ->setMaxResults($itemsPerPage)
            ;
        }

        if($order !== null){
            foreach($order as $orderBy => $orderSort){
                $queryBuilder->addOrderBy($entityName.'.'.$orderBy, $orderSort);
            }
        }else{
            $queryBuilder->orderBy($entityName.'.id', 'DESC');
        }

        if($groupBy){
            $queryBuilder->groupBy($entityName.".".$groupBy);
        }

        if(count($properties) > 0){
            $partialProperties = implode(',', $properties);
            $queryBuilder->select('partial '.$entityName.'.{id,'.$partialProperties.'}');
            $query  = $queryBuilder->getQuery();
        }else{
            $queryBuilder->select($entityName);
            $query = $queryBuilder->getQuery();
        }


        $objects = $query->getResult();

        if(!$objects){
            return null;
        }

        $array = array();
        foreach($objects as $object){

            $objectData = $object->toValueObject($this->app, $entityName,  $flatten, $properties);

            $array[] = $objectData;

        }

        return array('totalObjects' => $totalObjects, 'objects' => $array);
    }

    public function getSchema(){
        $cacheFile = ROOT_DIR.'/../data/cache/schema.cache';

        if(Adapter::getConfig()->APP_ENABLE_SCHEMA_CACHE){

            if(file_exists($cacheFile)){

                $data = unserialize(file_get_contents($cacheFile));

                return $data;
            }
        }

        $entities = array();

        $entityFolder = ROOT_DIR.'/../custom/Entity/';

        foreach (new \DirectoryIterator($entityFolder) as $fileInfo) {
            if($fileInfo->isDot() || substr($fileInfo->getBasename('.php'), 0, 1) == '.') continue;
            $entities[] = $fileInfo->getBasename('.php');
        }
        $entities[] = "PIM\\File";
        $entities[] = "PIM\\Folder";
        $entities[] = "PIM\\Tag";
        $entities[] = "PIM\\User";
        $entities[] = "PIM\\Group";
        $entities[] = "PIM\\Log";
        $entities[] = "PIM\\PushToken";
        $entities[] = "PIM\\ThumbnailSetting";
        $entities[] = "PIM\\Permission";
        $entities[] = "PIM\\Nav";
        $entities[] = "PIM\\NavItem";

        $data     = array();

        foreach($entities as $entity){

            if(substr($entity,0,3) == "PIM"){
                $className = 'Areanet\PIM\Entity\\'.substr($entity, 4);
            }else{
                $className = "\Custom\Entity\\$entity";
            }

            $object    = new $className();
            $reflect   = new \ReflectionClass($object);
            $props     = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

            $defaultValues = $reflect->getDefaultProperties();

            $annotationReader = new AnnotationReader();

            $settings = array(
                'label' => $entity,
                'readonly' => false,
                'isPush' => false,
                'hide' => false,
                'pushTitle' => '',
                'pushText' => '',
                'pushObject' => '',
                'sortBy' => 'created',
                'sortRestrictTo' => null,
                'sortOrder' => 'DESC',
                'isSortable' => false,
                'labelProperty' => null,
                'type' => 'default',
                'tabs' => array(
                    'default'   => array('title' => 'Allgemein', 'onejoin' => false)
                ),
                'dbname' => null,
                'viewMode' => 0
            );


            if($object instanceof BaseSortable){
                $settings['sortBy']     = 'sorting';
                $settings['sortOrder']  = 'ASC';
                $settings['isSortable'] = true;
            }

            if($object instanceof BaseTree){
                $settings['type']  = 'tree';
            }

            $classAnnotations = $annotationReader->getClassAnnotations($reflect);


            foreach($classAnnotations as $classAnnotation) {

                if ($classAnnotation instanceof Table) {
                    $settings['dbname'] = $classAnnotation->name ? $classAnnotation->name : null;
                }

                if ($classAnnotation instanceof \Areanet\PIM\Classes\Annotations\Config) {
                    $settings['label']          = $classAnnotation->label ? $classAnnotation->label : $entity;
                    $settings['labelProperty']  = $classAnnotation->labelProperty ? $classAnnotation->labelProperty : $settings['labelProperty'];
                    $settings['readonly']       = $classAnnotation->readonly ? $classAnnotation->readonly : false;
                    $settings['isPush']         = ($classAnnotation->pushText && $classAnnotation->pushTitle);
                    $settings['pushTitle']      = $classAnnotation->pushTitle ? $classAnnotation->pushTitle : null;
                    $settings['pushText']       = $classAnnotation->pushText ? $classAnnotation->pushText : null;
                    $settings['pushObject']     = $classAnnotation->pushObject ? $classAnnotation->pushObject : null;
                    $settings['sortBy']         = $classAnnotation->sortBy ? $classAnnotation->sortBy : $settings['sortBy'];
                    $settings['sortOrder']      = $classAnnotation->sortOrder ? $classAnnotation->sortOrder : $settings['sortOrder'];
                    $settings['hide']           = $classAnnotation->hide ? $classAnnotation->hide : $settings['hide'];
                    $settings['sortRestrictTo'] = $classAnnotation->sortRestrictTo ? $classAnnotation->sortRestrictTo : $settings['sortRestrictTo'];
                    $settings['viewMode']       = $classAnnotation->viewMode ? intval($classAnnotation->viewMode) : $settings['viewMode'];

                    if($classAnnotation->tabs){
                        $tabs = json_decode(str_replace("'", '"', $classAnnotation->tabs));
                        foreach($tabs as $key=>$value){
                            $settings['tabs'][$key] = array('title' => $value, 'onejoin' => false);
                        }
                    }
                }
            }

            $list       = array();
            $properties = array();
            foreach ($props as $prop) {


                $reflectionProperty = new \ReflectionProperty($className, $prop->getName());


                $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);


                $allPropertyAnnotations = array();
                foreach($propertyAnnotations as $propertyAnnotation){
                    $allPropertyAnnotations[get_class($propertyAnnotation)] = $propertyAnnotation;

                }
                krsort($allPropertyAnnotations);

                $lastMatchedPriority = -1;

                foreach($this->app['typeManager']->getTypes() as $type){
                    if($type->doMatch($allPropertyAnnotations) && $type->getPriority() >= $lastMatchedPriority){

                        $propertySchema                 = $type->processSchema($prop->getName(), $defaultValues[$prop->getName()], $allPropertyAnnotations);
                        $properties[$prop->getName()]   = $propertySchema;

                        if(($tab = $type->getTab())){
                            $settings['tabs'][$tab->key] = $tab->config;
                        }

                        if($prop->getName() == 'treeParent'){
                            $properties[$prop->getName()]['accept'] = $className;
                        }

                        $lastMatchedPriority = $type->getPriority();

                    }
                }


                if(isset($properties[$prop->getName()]['showInList']) && $properties[$prop->getName()]['showInList'] !== false){
                    $list[$properties[$prop->getName()]['showInList']] = $prop->getName();
                }


            }

            if($entity != 'PIM\\Group') $settings['tabs']['settings']  = array('title' => 'Einstellungen', 'onejoin' => false);

            ksort($list);
            $data[$entity] = array(
                'list' => $list,
                'settings' => $settings,
                'properties' => $properties
            );
        }

        if(Adapter::getConfig()->APP_ENABLE_SCHEMA_CACHE){
            file_put_contents($cacheFile, serialize($data));
        }

        return $data;
    }

    public function getSinge($entityName, $id = null, $where = null){
       return $this->single($entityName, $id, $where);
    }

    public function single($entityName, $id = null, $where = null){

        if (substr($entityName, 0, 3) == 'PIM') {
            $entityNameToLoad = 'Areanet\PIM\Entity\\' . substr($entityName, 4);
        }elseif(substr($entityName, 0, 7) == 'Areanet'){
            $splitter = explode('\\', $entityName);
            $entityNameToLoad = $entityName;
            $entityName       = 'PIM\\'.$splitter[count($splitter) - 1];
        }else{
            $entityName = ucfirst($entityName);
            $entityNameToLoad = 'Custom\Entity\\' . ucfirst($entityName);
        }

        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedException("Zugriff auf $entityNameToLoad verweigert.");
        }

        $object = null;

        if($id){
            $object = $this->em->getRepository($entityNameToLoad)->find($id);
        }elseif($where){
            $object = $this->em->getRepository($entityNameToLoad)->findOneBy($where);

        }else{
            throw new \Exception("Keine ID oder WHERE-Abfrage übergeben.");
        }


        if (!$object) {
            return new JsonResponse(array('message' => "Object not found"), 404);
        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId()))){
            throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad::$id verweigert.");
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad::$id verweigert.");
                }
            }
        }

        return $object->toValueObject($this->app, $entityName, false);
    }

    protected function getPermissions()
    {
        $schema = $this->app['schema'];

        $permissions = array();
        foreach($schema as $entityName => $config){

            $permissions[$entityName] = array(
                'readable'  => Permission::isReadable($this->app['auth.user'], $entityName),
                'writable'  => Permission::isWritable($this->app['auth.user'], $entityName),
                'deletable' => Permission::isDeletable($this->app['auth.user'], $entityName),
                'extended'  => Permission::getExtended($this->app['auth.user'], $entityName)
            );
        }

        return $permissions;
    }

}