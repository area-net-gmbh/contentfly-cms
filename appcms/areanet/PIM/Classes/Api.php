<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 21.04.17
 * Time: 10:26
 */

namespace Areanet\PIM\Classes;


use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Exceptions\File\FileExistsException;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\Log;
use Areanet\PIM\Entity\User;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Table;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    /** @var Connection $database */
    protected $database;

    /** @var  @var Request $request */
    protected $request;

    public function __construct($app, $request = null, User $user = null)
    {
        $this->app              = $app;
        $this->em               = $app['orm.em'];
        $this->database         = $app['database'];

        if($user){
            $this->app['auth.user'] = $user;
        }else{
            $this->app['auth.user'] = isset($this->app['auth.user']) ? $this->app['auth.user'] : null;
        }

        $this->request          = $request;
    }

    public function doDelete($entityName, $id){
        $schema = $this->app['schema'];

        $entityPath = 'Custom\Entity\\'.$entityName;
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        if(!($permission = Permission::isDeletable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
        }

        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            throw new \Exception("Das Objekt wurde nicht gefunden.", 404);
        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId())) ){
            throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
                }
            }
        }

        if($entityPath == 'Areanet\PIM\Entity\User'){

            if($object->getAlias() == 'admin'){
                throw new \Exception("Der Hauptadministrator kann nicht gelöscht werden.", 400);
            }

        }

        $parent = null;
        if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
            $subObjects = $this->em->getRepository($entityPath)->findBy(array('treeParent' => $object->getId()));
            if($subObjects){
                foreach($subObjects as $subObject){
                    $this->delete($entityName, $subObject->getId(), $this->app);
                }
            }
            $parent = $object->getTreeParent();
        }

        if($entityName == 'PIM\\File') {
            $backend    = Backend::getInstance();

            $path   = $backend->getPath($object);
            foreach (new \DirectoryIterator($path) as $fileInfo) {
                if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;
                unlink($fileInfo->getPathname());
            }
            @rmdir($path);
        }

        /**
         * Log delete actions
         */
        $schema = $this->app['schema'];

        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName(ucfirst($entityName));
        $log->setUserCreated($this->app['auth.user']);
        $log->setMode(Log::DELETED);

        if($schema[ucfirst($entityName)]['settings']['labelProperty']){
            $labelGetter = 'get'.ucfirst($schema[ucfirst($entityName)]['settings']['labelProperty']);
            $label = $object->$labelGetter();
            $log->setModelLabel($label);
        }

        $this->em->persist($log);
        $this->em->flush();

        foreach($schema[ucfirst($entityName)]['properties'] as $property => $propertyConfig){
            if($propertyConfig['type'] == 'onejoin'){
                $getterJoinedEntity = 'get'.ucfirst($property);
                $joinedEntity       = $object->$getterJoinedEntity();
                if($joinedEntity) $this->em->remove($joinedEntity);
            }
        }

        $this->em->remove($object);
        $this->em->flush();

        if($schema[ucfirst($entityName)]['settings']['isSortable']){
            $oldPos = $object->getSorting();
            if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
                if(!$parent){
                    $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.sorting > $oldPos AND e.sorting > 0 AND e.treeParent IS NULL");
                }else{
                    $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.sorting > $oldPos AND e.sorting > 0 AND e.treeParent = '".$parent->getId()."'");
                }

            }else{
                $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting - 1 WHERE e.sorting > $oldPos AND e.sorting > 0");
            }
            $query->execute();
        }

        $this->em->flush();

        return $object;
    }

    public function doInsert($entityName, $data)
    {
        $schema  = $this->app['schema'];

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        if(!Permission::isWritable($this->app['auth.user'], $entityName)){
            throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
        }

        $object  = new $entityPath();

        foreach($data as $property => $value){
            if(!isset($schema[ucfirst($entityName)]['properties'][$property])){
                throw new \Exception("Unkown property $property for entity $entityPath", 500);
            }

            $type = $schema[ucfirst($entityName)]['properties'][$property]['type'];
            $typeObject = $this->app['typeManager']->getType($type);
            if(!$typeObject){
                throw new \Exception("Unkown Type $typeObject for $property for entity $entityPath", 500);
            }

            if($schema[ucfirst($entityName)]['properties'][$property]['unique']){
                $objectDuplicated = $this->em->getRepository($entityPath)->findOneBy(array($property => $value));
                if($objectDuplicated) throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Dieser Eintrag ist bereits vorhanden.");
            }

            if($property == 'id'){
                $metadata = $this->em->getClassMetaData(get_class($object));
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                if(Config\Adapter::getConfig()->DB_GUID_STRATEGY) $metadata->setIdGenerator(new AssignedGenerator());
            }

            $typeObject->toDatabase($this, $object, $property, $value, $entityName, $schema, $this->app['auth.user'], $data);

        }

        if($object instanceof Base){
            $object->setUserCreated($this->app['auth.user']);
            $object->setUser($this->app['auth.user']);
        }

        try {

            $this->em->persist($object);


            if($schema[ucfirst($entityName)]['settings']['isSortable']){
                if($schema[ucfirst($entityName)]['settings']['type'] == 'tree') {
                    $parent = $object->getTreeParent();
                    if(!$parent){
                        $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.treeParent IS NULL");
                    }else {
                        $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.treeParent = '".$parent->getId()."'");
                    }
                }elseif($schema[ucfirst($entityName)]['settings']['sortRestrictTo']) {
                    $restrictToProperty = $schema[ucfirst($entityName)]['settings']['sortRestrictTo'];
                    $getter             = 'get'.ucfirst($restrictToProperty);
                    $restrictToObject   = $object->$getter();
                    if(!$restrictToObject){
                        $query  = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.$restrictToProperty IS NULL");
                    }else{
                        $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1 WHERE e.$restrictToProperty = '".$restrictToObject->getId()."'");
                    }
                }else{
                    $query = $this->em->createQuery("UPDATE $entityPath e SET e.sorting = e.sorting + 1");
                }

                $query->execute();
            }

            $this->em->flush();

            $isPush = $schema[$entityName]['settings']['isPush'];
            if($isPush){
                $pushTitle  = $schema[$entityName]['settings']['pushTitle'];
                $pushText   = $schema[$entityName]['settings']['pushText'];


                $event = new \Areanet\PIM\Classes\Event();
                $event->setParam('entity',          $entityName);
                $event->setParam('data',            $data);
                $event->setParam('user',            $this->app['auth.user']);
                $event->setParam('additionalData',  array());
                $event->setParam('pushTitle',       $pushTitle);
                $event->setParam('pushText',        $pushText);
                $event->setParam('app',             $this->app);
                $this->app['dispatcher']->dispatch('pim.push.before.send', $event);

                $additionalData = $event->getParam('additionalData');
                $pushTitle      = $event->getParam('pushTitle');
                $pushText       = $event->getParam('pushText');

                $push = new Push($this->em, $object);
                $push->send($pushTitle, $pushText, $additionalData);
            }

            /**
             * Log insert actions
             */
            $log = new Log();

            $log->setModelId($object->getId());
            $log->setModelName($entityName);
            $log->setUserCreated($this->app['auth.user']);
            $log->setMode(Log::INSERTED);

            if($schema[ucfirst($entityName)]['settings']['labelProperty']){
                $labelGetter = 'get'.ucfirst($schema[ucfirst($entityName)]['settings']['labelProperty']);
                $label = $object->$labelGetter();
                $log->setModelLabel($label);
            }

            $this->em->persist($log);
            $this->em->flush();
        }catch(UniqueConstraintViolationException $e){
            if($entityPath == 'Areanet\PIM\Entity\User'){
                throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Ein Benutzer mit diesem Benutzername ist bereits vorhanden.");
            }
            $uniqueObjectLoaded = false;

            foreach($schema[$entityName]['properties'] as $property => $propertySettings){

                if($propertySettings['unique']){
                    $object = $this->em->getRepository($entityPath)->findOneBy(array($property => $data[$property]));
                    if(!$object){
                        throw new \Exception("Unbekannter Fehler", 501);
                    }
                    $uniqueObjectLoaded = true;
                    break;
                }
            }

            if(!$uniqueObjectLoaded) throw new \Exception("Unbekannter Fehler", 500);
        }

        return $object;
    }

    /**
     * @param string $entityName
     * @param integer $id
     * @param array $data
     * @param boolean $disableModifiedTime
     * @param User $user
     * @return JsonResponse
     */
    public function doUpdate($entityName, $id, $data, $disableModifiedTime, $currentUserPass = null)
    {
        $schema  = $this->app['schema'];

        $entityPath = 'Custom\Entity\\'.ucfirst($entityName);
        if(substr($entityName, 0, 3) == "PIM"){
            $entityPath = 'Areanet\PIM\Entity\\'.substr($entityName, 4);
        }

        if(!($permission = Permission::isWritable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
        }

        $object = $this->em->getRepository($entityPath)->find($id);
        if(!$object){
            throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityNotFoundException();

        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId()) && $object != $this->app['auth.user'])){
            throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new AccessDeniedHttpException("Zugriff auf $entityName::$id verweigert.");
                }
            }
        }

        if($object instanceof User && isset($data['pass']) && !$this->app['auth.user']->getIsAdmin()){
            if(!$this->app['auth.user']->isPass($currentUserPass)){
                throw new \Exception('Passwort des aktuellen Benutzers wurde nicht korrekt übergeben.');
            }
        }


        foreach($data as $property => $value){
            if($property == 'modified' || $property == 'created') continue;


            if(!isset($schema[ucfirst($entityName)]['properties'][$property])){
                throw new \Exception("Unkown property $property for entity $entityPath", 500);
            }

            $type = $schema[ucfirst($entityName)]['properties'][$property]['type'];
            $typeObject =  $this->app['typeManager']->getType($type);
            if(!$typeObject){
                throw new \Exception("Unkown Type $typeObject for $property for entity $entityPath", 500);
            }



            $typeObject->toDatabase($this, $object, $property, $value, $entityName, $schema, $this->app['auth.user']);

        }

        foreach($schema[ucfirst($entityName)]['properties'] as $property => $propertyConfig){
            if($propertyConfig['type'] == 'onejoin'){
                $getterJoinedEntity = 'get'.ucfirst($property);
                $joinedEntity       = $object->$getterJoinedEntity();
                $joinedEntity->setUsers($object->getUsers(true));
                $joinedEntity->setGroups($object->getGroups(true));
                $joinedEntity->setUserCreated($object->getUserCreated());
            }
        }

        $object->setModified(new \DateTime());
        $object->setUser($this->app['auth.user']);

        try{
            if($disableModifiedTime){
                $object->doDisableModifiedTime(true);
            }

            $this->em->persist($object);
            $this->em->flush();

        }catch(UniqueConstraintViolationException $e){
            if($entityPath == 'Areanet\PIM\Entity\User'){
                throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Ein Benutzer mit diesem Benutzername ist bereits vorhanden.");
            }elseif($entityPath == 'Areanet\PIM\Entity\File') {
                $existingFile = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $object->getName(), 'folder' => $object->getFolder()->getId()));

                throw new FileExistsException("Die Datei ist in diesem Ordner bereits vorhanden. Wollen Sie die bestehende Datei überschreiben?", $existingFile->getId());
            }else{
                throw new \Areanet\PIM\Classes\Exceptions\Entity\EntityDuplicateException("Ein gleicher Eintrag ist bereits vorhanden.");
            }
        }
        /**
         * Log update actions
         */
        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName(ucfirst($entityName));
        $log->setUserCreated($this->app['auth.user']);
        $log->setMode(Log::UPDATED);

        if($schema[ucfirst($entityName)]['settings']['labelProperty']){
            $labelGetter = 'get'.ucfirst($schema[ucfirst($entityName)]['settings']['labelProperty']);
            $label = $object->$labelGetter();
            $log->setModelLabel($label);
        }

        $this->em->persist($log);
        $this->em->flush();
    }


    public function getAll($lastModified = null, $flatten = false, $filedata = null){
        $entities   = array('Areanet\PIM\Entity\File', 'Areanet\PIM\Entity\User', 'Areanet\PIM\Entity\Group');

        $entityFolder = __DIR__.'/../../../../custom/Entity/';
        foreach (new \DirectoryIterator($entityFolder) as $fileInfo) {
            if($fileInfo->isDot()) continue;
            if(substr($fileInfo->getBasename('.php'), 0, 1) == '.') continue;
            $entities[] = 'Custom\Entity\\'.ucfirst($fileInfo->getBasename('.php'));
        }

        $all = array();

        foreach($entities as $entityName){
            $entityShortcut = substr($entityName, strrpos($entityName, '\\') + 1);
            if(substr($entityName, 0, 11) == 'Areanet\\PIM'){
                $entityShortcut = 'PIM\\'.$entityShortcut;
            }

            $entityNameAlias = 'a'.md5($entityShortcut);

            if(!($permission = Permission::isReadable($this->app['auth.user'], $entityShortcut))){
                continue;
            }

            $qb = $this->em->createQueryBuilder();

            $qb->select($entityNameAlias)
                ->from($entityName, $entityNameAlias);

            $qb->where("1 = 1");

            if($permission == \Areanet\PIM\Entity\Permission::OWN){
                $qb->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userCreated, $entityNameAlias.users) = 1");
                $qb->setParameter('userCreated', $this->app['auth.user']);
            }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                $group = $this->app['auth.user']->getGroup();
                if(!$group){
                    $qb->andWhere("$entityNameAlias.userCreated = :userCreated");
                    $qb->setParameter('userCreated', $this->app['auth.user']);
                }else{
                    $qb->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userGroup, $entityNameAlias.groups) = 1");
                    $qb->setParameter('userGroup', $group);
                    $qb->setParameter('userCreated', $this->app['auth.user']);
                }
            }

            if($lastModified) {
                $qb->andWhere($entityNameAlias . '.modified >= :lastModified');
                $qb->setParameter('lastModified', $lastModified);
            }

            $query      = $qb->getQuery();
            $objects    = $query->getResult();


            $array = array();
            foreach($objects as $object){

                $objectData = $object->toValueObject($this->app, $entityShortcut, $flatten);

                if($object instanceof File && $filedata !== null){

                    $backendFS = new Backend\FileSystem();
                    foreach($filedata as $size){
                        $sizePrefix = $size == 'org' ? '' : $size.'-';
                        $path       = $backendFS->getPath($object);
                        $filePath   = $path.'/'.$sizePrefix.$object->getName();

                        if(file_exists($filePath)){
                            if(!isset($objectData->filedata)) $objectData->filedata = new \stdClass();

                            $data   = file_get_contents($filePath);
                            $base64 = base64_encode($data);
                            $objectData->filedata->$size = $base64;
                        }
                    }
                }

                $array[] = $objectData;
            }

            //GET DELETED
            $qb = $this->em->createQueryBuilder();

            $qb->select('log')
                ->from('Areanet\PIM\Entity\Log', 'log')
                ->where('log.modelName = :modelName')
                ->andWhere("log.mode = 'DEL' OR log.mode = 'Gelöscht'")
                ->setParameter('modelName', $entityShortcut);

            if($lastModified) {
                $qb->andWhere('log.created >= :lastModified');
                $qb->setParameter('lastModified', $lastModified);
            }

            $query = $qb->getQuery();
            $objects = $query->getResult();

            foreach($objects as $object){
                $array[] = array(
                    'id' => $object->getModelId(),
                    'isDeleted' => true
                );
            }

            if(!count($array)){
                continue;
            }

            $all[$entityShortcut] = $array;
        }

        return $all;
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

        $entityNameAlias = 'a'.md5($entityName);

        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new AccessDeniedHttpException("Zugriff auf $entityNameToLoad verweigert.");
        }

        $schema     = $this->app['schema'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select("count(".$entityNameAlias.")")
            ->from($entityNameToLoad, $entityNameAlias)
            ->andWhere("$entityNameAlias.isIntern = false");


        if($permission == \Areanet\PIM\Entity\Permission::OWN){
            $queryBuilder->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userCreated, $entityNameAlias.users) = 1");
            $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
        }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
            $group = $this->app['auth.user']->getGroup();
            if(!$group){
                $queryBuilder->andWhere("$entityNameAlias.userCreated = :userCreated");
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }else{
                $queryBuilder->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userGroup, $entityNameAlias.groups) = 1");
                $queryBuilder->setParameter('userGroup', $group);
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }
        }

        if($lastModified){
            $queryBuilder->andWhere($entityNameAlias.'.modified >= :lastModified')->setParameter('lastModified', $lastModified);
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
                            $queryBuilder->leftJoin("$entityNameAlias.$field", "joined$joinedCounter");
                            $queryBuilder->andWhere("joined$joinedCounter.$mappedBy IS NULL");
                        }else{
                            $searchJoinedEntity = $schema[$entityName]['properties'][$field]['accept'];
                            $searchJoinedObject = $this->em->getRepository($searchJoinedEntity)->find($value);
                            $mappedBy           = $schema[$entityName]['properties'][$field]['mappedBy'];

                            $queryBuilder->leftJoin("$entityNameAlias.$field", "joined$joinedCounter");
                            $queryBuilder->andWhere("joined$joinedCounter.$mappedBy = :$field");
                            $queryBuilder->setParameter($field, $searchJoinedObject);
                            $placeholdCounter++;
                        }

                    }else{

                        $queryBuilder->leftJoin("$entityNameAlias.$field", 'k');
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
                            if($value == -1){
                                $queryBuilder->andWhere("$entityNameAlias.$field IS NULL");
                            }else{
                                $queryBuilder->andWhere("$entityNameAlias.$field = :$field");
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

                            $queryBuilder->andWhere("$entityNameAlias.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                        case 'integer':
                            $value = intval($value);

                            $queryBuilder->andWhere("$entityNameAlias.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                        default:

                            $queryBuilder->andWhere("$entityNameAlias.$field = :$field");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;

                            break;
                    }


                }

            }

            if(isset($where['fulltext'])){
                $orX = $queryBuilder->expr()->orX();
                $fulltextTypes = array('string', 'text', 'textarea', 'rte');

                $orX->add("$entityNameAlias.id = :FT_id");
                $queryBuilder->setParameter("FT_id", $where['fulltext']);

                foreach($schema[$entityName]['properties'] as $field => $fieldOptions){

                    if(in_array($fieldOptions['type'], $fulltextTypes)){
                        $orX->add("$entityNameAlias.$field LIKE :FT_$field");
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
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn("$entityNameAlias.type", $types));
                }elseif(isset($this->_MIMETYPES[$where['mimetypes']])){

                    $queryBuilder->andWhere($queryBuilder->expr()->in("$entityNameAlias.type", $this->_MIMETYPES[$where['mimetypes']]));

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
                $queryBuilder->addOrderBy($entityNameAlias.'.'.$orderBy, $orderSort);
            }
        }else{
            $queryBuilder->orderBy($entityNameAlias.'.id', 'DESC');
        }

        if($groupBy){
            $queryBuilder->groupBy($entityNameAlias.".".$groupBy);
        }

        if(count($properties) > 0){
            $partialProperties = implode(',', $properties);
            $queryBuilder->select('partial '.$entityNameAlias.'.{id,'.$partialProperties.'}');
            $query  = $queryBuilder->getQuery();
        }else{
            $queryBuilder->select($entityNameAlias);
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
            if($fileInfo->isDot()) continue;
            if(substr($fileInfo->getBasename('.php'), 0, 1) == '.') continue;
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
        $entities[] = "PIM\\Option";
        $entities[] = "PIM\\OptionGroup";

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
            $entityName = $entity;

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
                    'default'   => array('title' => Adapter::getConfig()->FRONTEND_TAB_GENERAL_NAME, 'onejoin' => false)
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

            $skipEntity = false;

            foreach($classAnnotations as $classAnnotation) {

                if($classAnnotation instanceof MappedSuperclass){
                    $skipEntity = true;
                    break;
                }

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

                $event = new \Areanet\PIM\Classes\Event();
                $event->setParam('classAnnotation', $classAnnotation);
                $event->setParam('settings',        $settings);
                $this->app['dispatcher']->dispatch('pim.schema.after.classAnnotation', $event);
                $settings = $event->getParam('settings');
            }

            if($skipEntity) continue;

            $list               = array();
            $properties         = array();
            $customProperties   = array();

            foreach ($props as $prop) {


                $reflectionProperty = new \ReflectionProperty($className, $prop->getName());


                $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);


                $allPropertyAnnotations = array();
                foreach($propertyAnnotations as $propertyAnnotation){
                    $allPropertyAnnotations[get_class($propertyAnnotation)] = $propertyAnnotation;

                    $event = new \Areanet\PIM\Classes\Event();
                    $event->setParam('propertyAnnotation', $propertyAnnotation);
                    $event->setParam('properties', isset($customProperties[$prop->getName()]) ? $customProperties[$prop->getName()] : array());
                    $this->app['dispatcher']->dispatch('pim.schema.after.propertyAnnotation', $event);

                    if(($customProperties = $event->getParam('properties'))){
                        $customProperties[$prop->getName()] =  $customProperties;
                    }

                }
                krsort($allPropertyAnnotations);

                $lastMatchedPriority = -1;

                foreach($this->app['typeManager']->getTypes() as $type){
                    if($type->doMatch($allPropertyAnnotations) && $type->getPriority() >= $lastMatchedPriority){

                        $propertySchema                 = $type->processSchema($prop->getName(), $defaultValues[$prop->getName()], $allPropertyAnnotations, $entityName);
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

                if(!empty($properties[$prop->getName()]) && !empty($customProperties[$prop->getName()])){
                    $properties[$prop->getName()] = array_merge($properties[$prop->getName()], $customProperties[$prop->getName()]);
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

        $data['_hash'] = md5(serialize($data));

        if(Adapter::getConfig()->APP_ENABLE_SCHEMA_CACHE){

            file_put_contents($cacheFile, serialize($data));
        }

        return $data;
    }

    public function getSingle($entityName, $id = null, $where = null){

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

    protected function getTableName($entityName){

        if(empty($this->app['schema'][$entityName])){
            return $entityName;
        }

        return isset($this->app['schema'][$entityName]['settings']['dbname']) ? $this->app['schema'][$entityName]['settings']['dbname'] : $entityName;
    }

    public function getTree($entityName, $parent, $properties = array()){

        if (substr($entityName, 0, 3) == 'PIM') {
            $entity = 'Areanet\PIM\Entity\\' . substr($entityName, 4);
        }elseif(substr($entityName, 0, 7) == 'Areanet'){
            $splitter = explode('\\', $entityName);
            $entity = $entityName;
            $entityName       = 'PIM\\'.$splitter[count($splitter) - 1];
        }else{
            $entityName = ucfirst($entityName);
            $entity = 'Custom\Entity\\' . ucfirst($entityName);
        }

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder->from($entity, $entityName)
            ->where("$entityName.isIntern = false")
            ->orderBy($entityName.'.sorting', 'ASC');

        if($parent){
            $queryBuilder->andWhere("$entityName.treeParent = :treeParent");
            $queryBuilder->setParameter('treeParent', $parent);
        }else{
            $queryBuilder->andWhere("$entityName.treeParent IS NULL");
        }

        if(count($properties) > 0){
            $partialProperties = implode(',', $properties);
            $queryBuilder->select('partial '.$entityName.'.{id,'.$partialProperties.'}');
        }else{
            $queryBuilder->select($entityName);
        }

        $query   = $queryBuilder->getQuery();
        $objects = $query->getResult();

        $array   = array();

        foreach($objects as $object){
            $data = $object->toValueObject($this->app, $entityName, true, $properties);
            $data->treeChilds = $this->getTree($entityName, $object, $properties);
            $array[] = $data;
        }

        return $array;
    }

    public function getQuery(Array $params){
        $queryBuilder = $this->database->createQueryBuilder();

        $paramCount = 0;

        if(!isset($params['select']) || !isset($params['from'])){
            throw new \Exception("Mandatory keys 'from' or 'select' missing.");
        }

        foreach($params as $method => $params){

            if($method == 'delete' || $method == 'insert'|| $method == 'update'){
                throw new \Exception("'delete', 'insert' oder 'update not allowed in query.");
            }

            if(method_exists($queryBuilder, $method)){
                if(is_array($params)){
                    if($this->isIndexedArray($params)){
                        //array('select' => array('field1', 'field2'))

                        if(in_array($method, array('join', 'innerJoin', 'leftJoin', 'rightJoin'))){

                            if(count($params) != 4){
                                throw new \Exception("Wrong Parameter-Count for '$method'");
                            }

                            $entityName     = $params[1];
                            $entityAlias    = $params[2];

                            if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
                                throw new AccessDeniedHttpException("Zugriff auf $entityName verweigert.");
                            }

                            if($permission == \Areanet\PIM\Entity\Permission::OWN){
                                $queryBuilder->andWhere("$entityAlias.userCreated = ? OR FIND_IN_SET(?, $entityAlias.users) = 1");
                                $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                $paramCount++;
                            }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                                $group = $this->app['auth.user']->getGroup();
                                if(!$group){
                                    $queryBuilder->andWhere("$entityAlias.userCreated = ?");
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                    $paramCount++;
                                }else{
                                    $queryBuilder->andWhere("$entityAlias.userCreated = ? OR FIND_IN_SET(?, $entityAlias.groups) = 1");
                                    $queryBuilder->setParameter($paramCount, $group);
                                    $paramCount++;
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                    $paramCount++;
                                }
                            }

                            $params[1] = $this->getTableName($entityName);
                        }

                        call_user_func_array(array($queryBuilder, $method), $params);

                    }else{
                        reset($params);
                        $queryKey       = key($params);
                        $queryParams    = $params[$queryKey];

                        if($method == 'from'){
                            //$queryKey     = entityName
                            //$queryParams  = entityAlias

                            if(!($permission = Permission::isReadable($this->app['auth.user'], $queryKey))){
                                throw new AccessDeniedHttpException("Zugriff auf $queryKey verweigert.");
                            }

                            if($permission == \Areanet\PIM\Entity\Permission::OWN){
                                $queryBuilder->andWhere("$queryParams.userCreated = ? OR FIND_IN_SET(?, $queryParams.users) = 1");
                                $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                $paramCount++;
                            }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                                $group = $this->app['auth.user']->getGroup();
                                if(!$group){
                                    $queryBuilder->andWhere("$queryParams.userCreated = ?");
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                    $paramCount++;
                                }else{
                                    $queryBuilder->andWhere("$queryParams.userCreated = ? OR FIND_IN_SET(?, $queryParams.groups) = 1");
                                    $queryBuilder->setParameter($paramCount, $group);
                                    $paramCount++;
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                    $paramCount++;
                                }
                            }

                            $queryKey = $this->getTableName($queryKey);
                        }

                        if(is_array($queryParams)){
                            //array('where' => array('field1 = ? OR field2 = ?' => array('field1', 'field2'))
                            $queryBuilder->$method($queryKey);
                            foreach($queryParams as $queryParam){
                                $queryBuilder->setParameter($paramCount, $queryParam);
                                $paramCount++;
                            }
                        }elseif(strpos($queryKey, '?') !== false){
                            //array('where' => array('field1 = ?' => 'field2')
                            $queryBuilder->$method($queryKey);
                            $queryBuilder->setParameter($paramCount, $queryParams);
                            $paramCount++;
                        }else{
                            //array('where' => array('tableName' => 'tableAlias')
                            $queryBuilder->$method($queryKey, $queryParams);
                        }

                    }
                }else{
                    //array('from' => 'entity')
                    if($method == 'from'){

                        if(!($permission = Permission::isReadable($this->app['auth.user'], $params))){
                            throw new AccessDeniedHttpException("Zugriff auf $params verweigert.");
                        }

                        if($permission == \Areanet\PIM\Entity\Permission::OWN){
                            $queryBuilder->andWhere("userCreated = ? OR FIND_IN_SET(?, users) = 1");
                            $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                            $paramCount++;
                        }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                            $group = $this->app['auth.user']->getGroup();
                            if(!$group){
                                $queryBuilder->andWhere("userCreated = ?");
                                $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                $paramCount++;
                            }else{
                                $queryBuilder->andWhere("userCreated = ? OR FIND_IN_SET(?, groups) = 1");
                                $queryBuilder->setParameter($paramCount, $group);
                                $paramCount++;
                                $queryBuilder->setParameter($paramCount, $this->app['auth.user']);
                                $paramCount++;
                            }
                        }

                        $params = $this->getTableName($params);
                    }



                    $queryBuilder->$method($params);
                }

            }
        }

        return $queryBuilder->execute()->fetchAll();

    }

    protected function isIndexedArray(&$arr) {
        for (reset($arr); is_int(key($arr)); next($arr));
        return is_null(key($arr));
    }



}