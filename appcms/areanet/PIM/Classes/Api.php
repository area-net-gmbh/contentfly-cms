<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 21.04.17
 * Time: 10:26
 */

namespace Areanet\PIM\Classes;


use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Exceptions\ContentflyException;
use Areanet\PIM\Classes\Exceptions\ContentflyI18NException;
use Areanet\PIM\Classes\Exceptions\File\FileExistsException;
use Areanet\PIM\Classes\File\Backend;
use Areanet\PIM\Entity\Base;
use Areanet\PIM\Entity\BaseI18n;
use Areanet\PIM\Entity\BaseI18nSortable;
use Areanet\PIM\Entity\BaseI18nTree;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\Log;
use Areanet\PIM\Entity\User;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use PHPMailer\PHPMailer\Exception;
use Ramsey\Uuid\Uuid;
use Silex\Application;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


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

    public function doDelete($entityName, $id, $lang = null){
        $schema = $this->app['schema'];

        $helper             = new Helper();
        $entityFullName     = $helper->getFullEntityName($entityName);
        $entityShortName    = $helper->getShortEntityName($entityName);

        if(!isset($schema[$entityShortName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_entity, $entityShortName, Messages::contentfly_status_not_found);
        }

        $object = $this->getSingle($entityShortName, $id, null, $lang, true);

        $i18n   = $schema[$entityShortName]['settings']['i18n'];

        if(!$object){
            throw new ContentflyException(Messages::contentfly_general_not_found, $entityShortName, Messages::contentfly_status_not_found);
        }

        //Berechtigungen prüfen
        if(!($permission = Permission::isDeletable($this->app['auth.user'], $entityShortName))){
            throw new ContentflyException(Messages::contentfly_general_access_denied, $entityShortName, Messages::contentfly_status_access_denied);
        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId())) ){
            throw new ContentflyException(Messages::contentfly_general_access_denied, "$entityShortName::$id", Messages::contentfly_status_access_denied);
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new ContentflyException(Messages::contentfly_general_access_denied, "$entityShortName::$id", Messages::contentfly_status_access_denied);
                }
            }
        }

        if(!I18nPermission::isWritable($this->app, $entityShortName, $lang)){
            throw new ContentflyI18NException(Messages::contentfly_i18n_permission_denied, $entityShortName, $lang);
        }

        if($entityShortName == 'PIM\\User'){

            if($object->getAlias() == 'admin'){
                throw new ContentflyException(Messages::contentfly_general_admin_not_deletable);
            }

        }

        //Prüfen, ob für Subsprachen bereits Übersetzungen bestehen
        /*if($i18n){
            $mainLang = is_array(Adapter::getConfig()->APP_LANGUAGES) ? Adapter::getConfig()->APP_LANGUAGES[0] : null;

            if($object->getLang() != $mainLang){

                $this->em->getConnection()->exec('SET FOREIGN_KEY_CHECKS = 0;');

                //$query = $this->em->createQuery("SELECT COUNT(e) FROM $entityFullName e WHERE e.id = :id");
                //$query->setParameter('id', $object->getId());

                //if($query->getSingleScalarResult() > 1){
                //    throw new ContentflyI18NException(Messages::contentfly_i18n_translations_exists, $entityShortName, $mainLang);
                //}
            }
        }*/

        //Baumstruktur aktualisieren
        $parent = null;
        if($schema[$entityShortName]['settings']['type'] == 'tree') {
            $subObjects = $this->em->getRepository($entityFullName)->findBy(array('treeParent' => $object->getId()));
            if($subObjects){
                foreach($subObjects as $subObject){
                    $this->delete($entityShortName, $subObject->getId(), $this->app);
                }
            }
            $parent = $object->getTreeParent();
        }

        //Dateien löschen
        //todo: Löschen von Datein aus API auslagern
        if($entityShortName == 'PIM\\File') {
            if($object->getType() != 'link/youtube'){
                $backend    = Backend::getInstance();

                $path   = $backend->getPath($object);
                foreach (new \DirectoryIterator($path) as $fileInfo) {
                    if ($fileInfo->isDot() || !$fileInfo->isFile()) continue;
                    unlink($fileInfo->getPathname());
                }
                @rmdir($path);
            }

        }

        //Protokollierung
        $schema = $this->app['schema'];

        $log = new Log();

        $log->setModelId($object->getId());
        $log->setModelName($entityShortName);
        $log->setUserCreated($this->app['auth.user']);
        $log->setMode(Log::DELETED);

        if($schema[$entityShortName]['settings']['labelProperty']){
            try {
                $labelGetter = 'get' . ucfirst($schema[$entityShortName]['settings']['labelProperty']);
                $label = $object->$labelGetter();
                $log->setModelLabel($label);
            }catch(\Exception $e){

            }

        }

        $this->em->persist($log);
        $this->em->flush();


        //OneJoins löschen
        foreach($schema[$entityShortName]['properties'] as $property => $propertyConfig){
            if($propertyConfig['type'] == 'onejoin'){
                $getterJoinedEntity = 'get'.ucfirst($property);
                $joinedEntity       = $object->$getterJoinedEntity();
                if($joinedEntity) $this->em->remove($joinedEntity);
            }
        }

        if($i18n){
            $query = $this->em->createQuery("DELETE FROM $entityFullName e WHERE e.id = :id AND NOT e.lang = :lang");
            $query->setParameter('id', $object->getId());
            $query->setParameter('lang', $object->getLang());
            $query->execute();
        }

        //Objekt löschen
        $this->em->remove($object);
        $this->em->flush();

        return $object;
    }

    public function doInsert($entityName, $data, $lang = null)
    {
        $schema  = $this->app['schema'];

        $helper             = new Helper();
        $entityFullName     = $helper->getFullEntityName($entityName);
        $entityShortName    = $helper->getShortEntityName($entityName);

        if(!isset($schema[$entityShortName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_entity, $entityShortName, Messages::contentfly_status_not_found);
        }

        if(!Permission::isWritable($this->app['auth.user'], $entityShortName)){
            throw new ContentflyException(Messages::contentfly_general_permission_denied, $entityShortName, Messages::contentfly_status_access_denied);
        }

        if(I18nPermission::isOnlyReadable($this->app, $entityShortName, $lang)){
            throw new ContentflyI18NException(Messages::contentfly_i18n_permission_denied, $entityShortName, $lang);
        }

        $object  = new $entityFullName();

        $i18nObjects    = array();
        $i18nProperties = array();
        if($schema[$entityShortName]['settings']['i18n'] && isset($data['id'])){
            $tableName   = $schema[$entityShortName]['settings']['dbname'];
            $query       = $this->database->executeQuery("SELECT id, lang FROM $tableName WHERE id = ? AND NOT lang = ? ", array($data['id'], $lang));

            foreach($query->fetchAll() as $i18nObject){
                if(I18nPermission::isOnlyReadable($this->app, $entityShortName, $i18nObject['lang'])){
                    continue;
                }
                $i18nObjects[] = $i18nObject;
            }

        }

        foreach($data as $property => $value){
            if(!isset($schema[$entityShortName]['properties'][$property])){
                throw new ContentflyException(Messages::contentfly_general_unknown_property, "$entityShortName::$property");
            }

            $type = $schema[$entityShortName]['properties'][$property]['type'];
            $typeObject = $this->app['typeManager']->getType($type);
            if(!$typeObject){
                throw new ContentflyException(Messages::contentfly_general_unknown_type_object, "$entityShortName::$property::$typeObject");
            }

            if($schema[$entityShortName]['properties'][$property]['unique']){
                $objectDuplicated = $this->em->getRepository($entityFullName)->findOneBy(array($property => $value));
                if($objectDuplicated){
                    throw new ContentflyException(Messages::contentfly_general_record_already_exists, "$property::$value");
                }
            }

            if($property == 'id' && !empty($value)){
                $metadata = $this->em->getClassMetaData(get_class($object));
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                if(Config\Adapter::getConfig()->DB_GUID_STRATEGY) $metadata->setIdGenerator(new AssignedGenerator());

            }

            if($type == 'onejoin'){
                unset($value['id']);
            }

            if(!empty($schema[$entityShortName]['properties'][$property]['i18n_universal'])){
                if(count($i18nObjects)){
                    $i18nProperties[$property] = $value;
                }
            }

            $typeObject->toDatabase($this, $object, $property, $value, $entityShortName, $schema, $this->app['auth.user'], $data, $lang);

        }

        if($object instanceof Base){
            $object->setUserCreated($this->app['auth.user']);
            $object->setUser($this->app['auth.user']);
        }

        if($object instanceof BaseI18n){
            if(empty($data['id'])){
                $uuid = Uuid::uuid4();
                $object->setId($uuid);
            }

            if(empty($lang)){
                throw new ContentflyException(Messages::contentfly_i18n_missing_lang_param, $entityShortName);
            }

            $object->setLang($lang);

            $mainLang = is_array(Adapter::getConfig()->APP_LANGUAGES) ? Adapter::getConfig()->APP_LANGUAGES[0] : null;
            if($lang != $mainLang && !empty($data['id'])){
                $mainLangObject = $this->getSingle($entityShortName, $data['id'], null, $mainLang, true, null, null, true);
                if($mainLangObject){
                    foreach($schema[$entityShortName]['properties'] as $property => $propertyConfig){
                        if(!empty($propertyConfig['i18n_universal']) && $propertyConfig['type'] != 'multijoin' && $propertyConfig['type'] != 'multifile' && empty($data[$property])){
                            $getter = 'get'.ucfirst($property);
                            $setter = 'set'.ucfirst($property);
                            $object->$setter($mainLangObject->$getter());
                        }
                    }
                }
            }

        }

        try {
            $this->em->persist($object);
            $this->em->flush();

            /**
             * Log insert actions
             */
            $log = new Log();

            $log->setModelId($object->getId());
            $log->setModelName($entityShortName);
            $log->setUserCreated($this->app['auth.user']);
            $log->setMode(Log::INSERTED);

            if($schema[$entityShortName]['settings']['labelProperty']){
                try {
                    $labelGetter = 'get'.ucfirst($schema[$entityShortName]['settings']['labelProperty']);
                    $label = $object->$labelGetter();
                    $log->setModelLabel($label);
                }catch(\Exception $e){

                }

            }

            $this->em->persist($log);
            $this->em->flush();

        }catch(UniqueConstraintViolationException $e){
            if($entityShortName == 'PIM\User'){
                throw new ContentflyException(Messages::contentfly_general_user_already_exists, $data['alias']);
            }
            $uniqueObjectLoaded = false;

            foreach($schema[$entityShortName]['properties'] as $property => $propertySettings){

                if($propertySettings['unique']){
                    $object = $this->em->getRepository($entityFullName)->findOneBy(array($property => $data[$property]));
                    if(!$object){
                        throw new ContentflyException(Messages::contentfly_general_unknown_perror, "$entityShortName::$property (100)".$e->getMessage());
                    }
                    $uniqueObjectLoaded = true;
                    break;
                }
            }

            if(!$uniqueObjectLoaded){
                throw new ContentflyException(Messages::contentfly_general_unknown_perror, "$entityShortName::$property (200) ".$e->getMessage());
            }
        }catch(\Exception $e){
            throw new ContentflyException($e->getMessage());
        }

        if(count($i18nProperties) && count($i18nObjects)) {
            foreach ($i18nObjects as $i18nObject) {
                $this->doUpdate($entityShortName, $i18nObject['id'], $i18nProperties, true, null, $i18nObject['lang'], true);
            }
        }
        //if(count($i18nProperties) && count($i18nObjects)) {
        //    return array('updateI18n' => true, 'object' => $object, 'i18nObjects' => $i18nObjects, 'i18nProperties' => $i18nProperties);

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
    public function doUpdate($entityName, $id, $data, $disableModifiedTime, $currentUserPass = null, $lang = null, $isUnviversalUpdate = false)
    {
        $schema  = $this->app['schema'];

        $helper             = new Helper();
        $entityShortName    = $helper->getShortEntityName($entityName);

        if(!isset($schema[$entityShortName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_entity, $entityShortName, Messages::contentfly_status_not_found);
        }

        $object = $this->getSingle($entityShortName, $id, null, $lang, true);

        if(!$object){
            throw new ContentflyException(Messages::contentfly_general_not_found, $entityShortName, Messages::contentfly_status_not_found);
        }

        if(!($permission = Permission::isWritable($this->app['auth.user'], $entityShortName))){
            throw new ContentflyException(Messages::contentfly_general_permission_denied, $entityShortName, Messages::contentfly_status_access_denied);
        }

        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId()) && $object != $this->app['auth.user'])){
            throw new ContentflyException(Messages::contentfly_general_permission_denied, "$entityShortName::$id", Messages::contentfly_status_access_denied);
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new ContentflyException(Messages::contentfly_general_permission_denied, "$entityShortName::$id", Messages::contentfly_status_access_denied);
                }
            }
        }

        if(I18nPermission::isOnlyReadable($this->app, $entityShortName, $lang)){
            throw new ContentflyI18NException(Messages::contentfly_i18n_permission_denied, $entityShortName, $lang);
        }

        if($object instanceof User && isset($data['pass']) && !$this->app['auth.user']->getIsAdmin()){
            if(!$this->app['auth.user']->isPass($currentUserPass)){
                throw new ContentflyException(Messages::contentfly_general_invalid_password, $this->app['auth.user']->getAlias());
            }
        }

        $i18nObjects    = array();
        $i18nProperties = array();
        if($schema[$entityShortName]['settings']['i18n'] && !$isUnviversalUpdate){
            $tableName   = $schema[$entityShortName]['settings']['dbname'];
            $query       = $this->database->executeQuery("SELECT id, lang FROM $tableName WHERE id = ? AND NOT lang = ? ", array($object->getId(), $object->getLang()));
            foreach($query->fetchAll() as $i18nObject){
                if(I18nPermission::isOnlyReadable($this->app, $entityShortName, $i18nObject['lang'])){
                    continue;
                }
                $i18nObjects[] = $i18nObject;
            }
        }

        $usersRemoved   = $helper->getUsersRemoved($object, $data);

        foreach($data as $property => $value){
            if($property == 'modified' || $property == 'created') continue;


            if(!isset($schema[$entityShortName]['properties'][$property])){
                throw new ContentflyException(Messages::contentfly_general_unknown_property, "$entityShortName::$property");
            }

            $type = $schema[$entityShortName]['properties'][$property]['type'];
            $typeObject =  $this->app['typeManager']->getType($type);
            if(!$typeObject){
                throw new ContentflyException(Messages::contentfly_general_unknown_type_object, "$entityShortName::$property::$typeObject");
            }

            if(!empty($schema[$entityShortName]['properties'][$property]['i18n_universal'])){
                if(count($i18nObjects)){
                    $i18nProperties[$property] = $value;
                }
            }

            $typeObject->toDatabase($this, $object, $property, $value, $entityShortName, $schema, $this->app['auth.user'], null, $lang);

        }

        foreach($schema[$entityShortName]['properties'] as $property => $propertyConfig){
            if($propertyConfig['type'] == 'onejoin'){
                $getterJoinedEntity = 'get'.ucfirst($property);
                $joinedEntity       = $object->$getterJoinedEntity();
                if($joinedEntity){
                    $joinedEntity->setUsers($object->getUsers(true));
                    $joinedEntity->setGroups($object->getGroups(true));
                    $joinedEntity->setUserCreated($object->getUserCreated());
                };
            }
        }

        $object->setModified(new \DateTime());
        $object->setUser($this->app['auth.user']);

        try{
            if($disableModifiedTime){
                $object->doDisableModifiedTime(true);
            }

            $this->em->flush();

        }catch(UniqueConstraintViolationException $e){
            if($entityShortName == 'PIM\User'){
                throw new ContentflyException(Messages::contentfly_general_user_already_exists, $data['alias'],Messages::contentfly_status_ressource_already_exists);
            }elseif($entityShortName == 'PIM\File') {
                $existingFile = $this->em->getRepository('Areanet\PIM\Entity\File')->findOneBy(array('name' => $object->getName(), 'folder' => $object->getFolder()->getId()));
                throw new ContentflyException(Messages::contentfly_general_ressource_already_exists, $existingFile->getId(), Messages::contentfly_status_ressource_already_exists);
            }else{
                throw new ContentflyException(Messages::contentfly_general_ressource_already_exists, "$property::$value", Messages::contentfly_status_ressource_already_exists);
            }
        }catch(\Exception $e){
            throw new ContentflyException($e->getMessage());
        }

        /**
         * Log update actions
         */
        if(!$isUnviversalUpdate) {
            $log = new Log();

            $log->setModelId($object->getId());
            $log->setModelName(ucfirst($entityShortName));
            $log->setUserCreated($this->app['auth.user']);
            $log->setMode(Log::UPDATED);

            if ($schema[$entityShortName]['settings']['labelProperty']) {
                try {
                    $labelGetter = 'get' . ucfirst($schema[$entityShortName]['settings']['labelProperty']);
                    $label = $object->$labelGetter();
                    $log->setModelLabel($label);
                }catch(\Exception $e){

                }

            }

            $this->em->persist($log);

            foreach($usersRemoved as $userRemoved){
                $logUsrDel = new Log();

                $logUsrDel->setModelId($object->getId());
                $logUsrDel->setModelName(ucfirst($entityShortName));
                $logUsrDel->setUserCreated($this->app['auth.user']);
                $logUsrDel->setUsers($userRemoved);
                $logUsrDel->setMode(Log::USERDEL);

                if ($schema[$entityShortName]['settings']['labelProperty']) {
                    try {
                        $labelGetter = 'get' . ucfirst($schema[$entityShortName]['settings']['labelProperty']);
                        $label = $object->$labelGetter();
                        $logUsrDel->setModelLabel($label);
                    }catch(\Exception $e){

                    }

                }

                $this->em->persist($logUsrDel);
            }

            $this->em->flush();
        }

        if(count($i18nProperties) && count($i18nObjects)) {
            return array('i18nObjects' => $i18nObjects, 'i18nProperties' => $i18nProperties);
        }else{
            return null;
        }
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
                $qb->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userCreated, $entityNameAlias.users) > 0");
                $qb->setParameter('userCreated', $this->app['auth.user']);
            }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                $group = $this->app['auth.user']->getGroup();
                if(!$group){
                    $qb->andWhere("$entityNameAlias.userCreated = :userCreated");
                    $qb->setParameter('userCreated', $this->app['auth.user']);
                }else{
                    $qb->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userGroup, $entityNameAlias.groups) > 0");
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

    public function getCount($lastMofified, $entity = null){

        $data = array(
            'dataCount'     => 0,
            'filesCount'    => 0,
            'filesSize'     => 0,
        );

        $schema = $this->getSchema();

        $entitiesToExclude = array(
            'PIM\\File', 'PIM\\Folder', 'PIM\\Token', 'PIM\\Group', 'PIM\\PushToken', 'PIM\\ThumbnailSetting',
            'PIM\\Permission', 'PIM\\Nav', 'PIM\\NavItem', 'PIM\\Log', '_hash'
        );
        $details = array();
        foreach($schema as $entityName => $entityConfig){

            if($entity && $entity != $entityName) continue;

            if(in_array($entityName, $entitiesToExclude)){
                continue;
            }

            if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
                continue;
            }


            if($entityConfig['settings']['excludeFromSync']){
                continue;
            }

            $tableName = $entityConfig['settings']['dbname'];

            $query = "SELECT 1  FROM `$tableName`";

            if($entityConfig['settings']['type'] == 'tree'){
                $treeTableName = $entityConfig['i18n'] ? 'pim_i18n_tree' : 'pim_tree';
                $query .= " INNER JOIN `$treeTableName` ON `$tableName`.id = `$treeTableName`.id";
            }

            $params  = array();
            $tsQuery = " WHERE 1=1";
            if($lastMofified){
                if(is_array($lastMofified)){
                    if(isset($lastMofified[$entityName])){
                        $tsQuery .= " AND `modified` > ?";
                        $params = array($lastMofified[$entityName]);
                    }
                }else{
                    $tsQuery .= " AND `modified` > ?";
                    $params = array($lastMofified);
                }
            }

            if($permission == \Areanet\PIM\Entity\Permission::OWN){
                $tsQuery .= " AND (userCreated_id = ? OR FIND_IN_SET(?, users) > 0)";
                $params[] = $this->app['auth.user']->getId();
                $params[] = $this->app['auth.user']->getId();
            }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                $group = $this->app['auth.user']->getGroup();
                if(!$group){
                    $tsQuery .= " AND userCreated_id = ?";
                    $params[] = $this->app['auth.user']->getId();
                }else{
                    $tsQuery .= " AND (userCreated_id = ? OR FIND_IN_SET(?, groups) > 0)";
                    $params[] = $this->app['auth.user']->getId();
                    $params[] = $group->getId();
                }
            }

            $query .= $tsQuery;

            $entityCount        = $this->app['database']->executeQuery($query, $params)->rowCount();
            $data['dataCount'] += $entityCount;
            $details[$entityName] = $entityCount;
            foreach($entityConfig['properties'] as $field => $fieldOptions){
                if($fieldOptions['type'] == 'multifile'){
                    $joinTableName = $fieldOptions['foreign'] ? $fieldOptions['foreign'] :  $tableName + "_" + $field;
                    $joinQuery  = "
                        SELECT 1  
                        FROM `$joinTableName` 
                        INNER JOIN `pim_file`  
                        ON file_id = id";

                    $joinQuery .= $tsQuery;

                    $joinEntityCount    = $this->app['database']->executeQuery($query, $params)->rowCount();
                    $data['dataCount'] += $joinEntityCount;
                }

                if($fieldOptions['type'] == 'multijoin' && ! empty($fieldOptions['foreign'])){
                    $joinTableName = $fieldOptions['foreign'];
                    $joinField     = $fieldOptions['dbfield'];

                    if($entityConfig['settings']['type'] == 'tree'){
                        $treeTableName = $entityConfig['i18n'] ? 'pim_i18n_tree' : 'pim_tree';
                    }else{
                        $treeTableName = $tableName;
                    }

                    $joinQuery  = "
                        SELECT 1
                        FROM `$joinTableName` 
                        INNER JOIN `$treeTableName`  
                        ON $joinField = id";

                    $joinQuery .= $tsQuery;

                    $joinEntityCount    = $this->app['database']->executeQuery($query, $params)->rowCount();

                    $data['dataCount'] += $joinEntityCount;
                }
            }
        }

        if(!$entity || $entity && $entity == 'PIM\\File') {
            $query = "SELECT COUNT(*) AS `records`, SUM(size) AS `size` FROM `pim_file`";

            $params = array();
            if ($lastMofified) {
                if (is_array($lastMofified)) {
                    if (isset($lastMofified['PIM\\File'])) {
                        $query .= " WHERE `modified` > ?";
                        $params = array($lastMofified['PIM\\File']);
                    }
                } else {
                    $query .= " WHERE `modified` > ?";
                    $params = array($lastMofified);
                }
            }

            $files = $this->app['database']->fetchAssoc($query, $params);
            $data['filesCount'] = intval($files['records']);
            $data['filesSize'] = $files['size'] ? $files['size'] : 0;
        }

        $data['details']    = $details;
        return $data;
    }

    public function getDeleted($lastMofified){

        $data = array();

        $schema = $this->getSchema();

        $entitiesToExclude = array(
            'PIM\\Folder', 'PIM\\Token', 'PIM\\Group', 'PIM\\ThumbnailSetting',
            'PIM\\Permission', 'PIM\\Nav', 'PIM\\NavItem', 'PIM\\Log', '_hash'
        );

        foreach($schema as $entityName => $entityConfig){

            if(in_array($entityName, $entitiesToExclude)){
                continue;
            }

            $query = "SELECT model_name, model_id FROM `pim_log` WHERE model_name = ? AND (mode = 'DEL' OR (mode = 'USERDEL' AND users = ?))";

            $params  = array($entityName, $this->app['auth.user']->getId());
            $tsQuery = "";
            if($lastMofified){
                if(is_array($lastMofified)){
                    if(isset($lastMofified[$entityName])){
                        $tsQuery = " AND `created` > ?";
                        $params[] = $lastMofified[$entityName];
                    }
                }else{
                    $tsQuery = " AND `created` > ?";
                    $params[] = $lastMofified;
                }
            }

            $query .= $tsQuery;

            if(($deletedObjects = $this->app['database']->fetchAll($query, $params))){
                $data   = array_merge($data, $deletedObjects);
            }

        }

        return $data;
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
            'login_redirect' => Adapter::getConfig()->FRONTEND_LOGIN_REDIRECT,
            'exportMethods' => Adapter::getConfig()->APP_EXPORT_METHODS,
            'languages' => Adapter::getConfig()->APP_LANGUAGES
        );

        $uiblocks = $this->app['uiManager']->getBlocks();

        $schema         = $this->app['schema'];
        $permissions    = $this->getPermissions();

        $permission     = Permission::isReadable($this->app['auth.user'], 'PIM\\NavItem');

        if(Adapter::getConfig()->FRONTEND_CUSTOM_NAVIGATION && $permission){
            $frontend['customNavigation']['items'] = array();



            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                ->select("navItem")
                ->from("Areanet\PIM\Entity\NavItem", "navItem")
                ->join("navItem.nav", "nav")
                ->where('navItem.nav IS NOT NULL')
                ->orderBy('nav.sorting')
                ->orderBy('navItem.sorting');

            if($permission == \Areanet\PIM\Entity\Permission::OWN){
                $queryBuilder->andWhere("navItem.userCreated = :userCreated OR FIND_IN_SET(:userCreated, navItem.users) > 0");
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
                $group = $this->app['auth.user']->getGroup();
                if(!$group){
                    $queryBuilder->andWhere("navItem.userCreated = :userCreated");
                    $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
                }else{
                    $queryBuilder->andWhere("navItem.userCreated = :userCreated OR FIND_IN_SET(:userGroup, navItem.groups) > 0");
                    $queryBuilder->setParameter('userGroup', $group);
                    $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
                }
            }

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

        $i18nPermissions = null;
        if(($group = $this->app['auth.user']->getGroup())){
            $i18nPermissions = $group->getLanguages();
        }

        return array('frontend' => $frontend, 'uiblocks' => $uiblocks, 'devmode' => Adapter::getConfig()->APP_DEBUG, 'version' => APP_VERSION.'/'.CUSTOM_VERSION, 'data' => $schema, 'permissions' => $permissions, 'i18nPermissions' => $i18nPermissions);
    }


    public function getList($entityName, $where = null, $order = null, $groupBy = null, $properties = array(), $lastModified = null, $flatten = false, $currentPage = 0, $itemsPerPage = 20, $lang = null, $untranslatedLang = null){
        if(!empty($lastModified)) {
            try {
                $lastModified = new \Datetime($lastModified);
            } catch (\Exception $e) {

            }
        }

        $helper             = new Helper();
        $entityFullName     = $helper->getFullEntityName($entityName);
        $entityShortName    = $helper->getShortEntityName($entityName);

        $entityNameAlias = 'a'.md5($entityShortName);

        $schema  = $this->app['schema'];

        if(!isset($schema[$entityShortName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_entity, $entityShortName, Messages::contentfly_status_not_found);
        }

        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityShortName))){
            throw new ContentflyException(Messages::contentfly_general_permission_denied, $entityShortName, Messages::contentfly_status_access_denied);
        }

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select("count(".$entityNameAlias.")")
            ->from($entityFullName, $entityNameAlias)
            ->andWhere("$entityNameAlias.isIntern = false");


        if($permission == \Areanet\PIM\Entity\Permission::OWN){
            $queryBuilder->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userCreated, $entityNameAlias.users) > 0");
            $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
        }elseif($permission == \Areanet\PIM\Entity\Permission::GROUP){
            $group = $this->app['auth.user']->getGroup();
            if(!$group){
                $queryBuilder->andWhere("$entityNameAlias.userCreated = :userCreated");
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }else{
                $queryBuilder->andWhere("$entityNameAlias.userCreated = :userCreated OR FIND_IN_SET(:userGroup, $entityNameAlias.groups) > 0");
                $queryBuilder->setParameter('userGroup', $group);
                $queryBuilder->setParameter('userCreated', $this->app['auth.user']);
            }
        }

        if($lastModified && !$untranslatedLang){
            $queryBuilder->andWhere($entityNameAlias.'.modified >= :lastModified')->setParameter('lastModified', $lastModified);
        }

        if($schema[$entityShortName]['settings']['i18n']){
            if(empty($lang)){
                throw new ContentflyException(Messages::contentfly_i18n_missing_lang_param, $entityShortName);
            }

            if($untranslatedLang){
                $queryBuilder->andWhere($entityNameAlias.'.lang = :lang')->setParameter('lang', $untranslatedLang);
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->notIn(
                        $entityNameAlias.'.id',
                        $this->em->createQueryBuilder()
                            ->select($entityNameAlias.'sub.id')
                            ->from($entityFullName, $entityNameAlias.'sub')
                            ->where($entityNameAlias."sub.lang = :sublang")
                            ->getDQL()
                    )
                )->setParameter('sublang', $lang);
            }else{
                $queryBuilder->andWhere($entityNameAlias.'.lang = :lang')->setParameter('lang', $lang);
            }
        }

        if($where && !$untranslatedLang){
            $placeholdCounter   = 0;
            $joinedCounter      = 0;
            foreach($where as $field => $value){

                if(!isset($schema[$entityShortName]['properties'][$field])){

                    continue;
                }

                if($schema[$entityShortName]['properties'][$field]['type'] == 'multijoin' || $schema[$entityShortName]['properties'][$field]['type'] == 'checkbox'){
                    if(isset($schema[$entityShortName]['properties'][$field]['mappedBy'])){
                        if($value == -1) {
                            $mappedBy           = $schema[$entityShortName]['properties'][$field]['mappedBy'];
                            $queryBuilder->leftJoin("$entityNameAlias.$field", "joined$joinedCounter");
                            $queryBuilder->andWhere("joined$joinedCounter.$mappedBy IS NULL");
                        }else{
                            $searchJoinedEntity = $schema[$entityShortName]['properties'][$field]['accept'];
                            $searchJoinedObject = $this->em->getRepository($searchJoinedEntity)->find($value);
                            $mappedBy           = $schema[$entityShortName]['properties'][$field]['mappedBy'];

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
                    switch($schema[$entityShortName]['properties'][$field]['type']){
                        case 'join':
                            if($value == -1){
                                $queryBuilder->andWhere("$entityNameAlias.$field IS NULL");
                            }else{
                                $queryBuilder->andWhere("$entityNameAlias.$field = :$field");
                                $queryBuilder->setParameter($field, $value);
                                $placeholdCounter++;
                            }


                            break;
                        case 'virtualjoin':

                            $queryBuilder->andWhere("FIND_IN_SET(:$field, $entityNameAlias.$field) > 0");
                            $queryBuilder->setParameter($field, $value);
                            $placeholdCounter++;
                            break;
                        case 'boolean':
                            if(strtolower($value) == 'false'){
                                $value = 0;
                            }elseif(strtolower($value) == 'true'){
                                $value = 1;
                            }else{
                                $value = boolval($value);
                            }

                            $isNull = !$value ? "OR $entityNameAlias.$field IS NULL" : '';

                            $queryBuilder->andWhere("$entityNameAlias.$field = :$field $isNull");
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

                foreach($schema[$entityShortName]['properties'] as $field => $fieldOptions){

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
        $event->setParam('entity',         $entityShortName);
        $event->setParam('queryBuilder',   $queryBuilder);
        $event->setParam('app',            $this->app);
        $event->setParam('user',           $this->app['auth.user']);

        $this->app['dispatcher']->dispatch('pim.entity.before.list', $event);

        $query          = $queryBuilder->getQuery();
        $totalObjects   = $query->getSingleScalarResult();

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

        $validProperties            = array();
        $partialExcludedProperties  = array();
        if(count($properties)){

            foreach($properties as $name){

                if(!isset($schema[$entityShortName]['properties'][$name]) || in_array($name, array('id', 'lang'))){
                    continue;
                }

                $config = $schema[$entityShortName]['properties'][$name];

                if(in_array($config['type'], array('multijoin', 'multifile', 'checkbox'))){
                    continue;
                }

                if(in_array($config['type'], array('join', 'file'))){
                    $partialExcludedProperties[] = $name;
                    continue;
                }

                $validProperties[] = $name;
            }

            $validProperties[] = 'id';
            if($schema[$entityShortName]['settings']['i18n']){
                $validProperties[] = 'lang';
            }

            $queryBuilder->select('partial '.$entityNameAlias.'.{'.implode(',', $validProperties).'}');

            $validProperties = array_merge($validProperties, $partialExcludedProperties);
        }else{
            $queryBuilder->select($entityNameAlias);

        }

        $joinIsTree = false;

        foreach ($schema[$entityShortName]['properties'] as $field => $config) {
            if (count($properties) && !in_array($field, $properties)) continue;

            if($config['type'] == 'join' || $config['type'] == 'file'){
                $joinedShortEntity = $config['type'] == 'file' ? 'PIM\\File' : $helper->getShortEntityName($config['accept']);

                if ($schema[$joinedShortEntity]['settings']['i18n']) {
                    $queryBuilder->leftJoin("$entityNameAlias.$field", 'a_'.$field, Join::WITH, "a_$field.lang = :lang");
                    if(count($properties) && $schema[$joinedShortEntity]['settings']['type'] != 'tree') {
                        $labelProperty = $schema[$joinedShortEntity]['settings']['labelProperty'];
                        $labelPropertyField = $labelProperty && $schema[$joinedShortEntity]['properties'][$labelProperty]  ? ','.$labelProperty : '';
                        $queryBuilder->addSelect('partial '.'a_'.$field.'.{id, lang'.$labelPropertyField.'}');
                    }else{
                        $queryBuilder->addSelect( 'a_'.$field);
                    }
                }else{
                    $queryBuilder->leftJoin("$entityNameAlias.$field", 'a_'.$field);
                    if(count($properties) && $schema[$joinedShortEntity]['settings']['type'] != 'tree') {
                        $labelProperty = $schema[$joinedShortEntity]['settings']['labelProperty'];
                        $labelPropertyField = $labelProperty && $schema[$joinedShortEntity]['properties'][$labelProperty]  ? ','.$labelProperty : '';
                        $queryBuilder->addSelect('partial '.'a_'.$field.'.{id'.$labelPropertyField.'}');
                    }else{
                        $queryBuilder->addSelect('a_'.$field);
                    }
                }

                $joinIsTree = $joinIsTree || $schema[$joinedShortEntity]['settings']['type'] == 'tree';
            }
        }

        $query   = $queryBuilder->getQuery();


        if(count($properties) && !$joinIsTree){
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        }

        $objects = $query->getResult();

        if(!$objects){
            return null;
        }

        $array = array();
        foreach($objects as $object){

            $objectData = $object->toValueObject($this->app, $entityShortName,  $flatten, $validProperties);

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
                'export'    => Permission::canExport($this->app['auth.user'], $entityName),
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

            if($fileInfo->isDir()){
                foreach (new \DirectoryIterator($entityFolder.$fileInfo->getFilename()) as $subfileInfo) {
                    if($subfileInfo->isDot() || $subfileInfo->getExtension() != 'php') continue;
                    if(substr($subfileInfo->getBasename('.php'), 0, 1) == '.') continue;

                    $entities[] = $fileInfo->getFilename().'\\'.$subfileInfo->getBasename('.php');
                }
                continue;
            }

            if($fileInfo->getExtension() != 'php' || substr($fileInfo->getBasename('.php'), 0, 1) == '.') continue;

            $entities[] = $fileInfo->getBasename('.php');
        }

        $entities = array_merge($entities, $this->app['pluginManager']->getEntities());

        $entities[] = "PIM\\File";
        $entities[] = "PIM\\Folder";
        $entities[] = "PIM\\Tag";
        $entities[] = "PIM\\User";
        $entities[] = "PIM\\Group";
        $entities[] = "PIM\\Log";
        $entities[] = "PIM\\ThumbnailSetting";
        $entities[] = "PIM\\Permission";
        $entities[] = "PIM\\Nav";
        $entities[] = "PIM\\NavItem";
        $entities[] = "PIM\\Option";
        $entities[] = "PIM\\OptionGroup";

        $data           = array();
        $helper         = new Helper();
        $permissions    = array();

        foreach($entities as $entity){

            $className = $helper->getFullEntityName($entity);
            $object    = new $className();
            $reflect   = new \ReflectionClass($object);
            $props     = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
            $entityName = $entity;

            $defaultValues = $reflect->getDefaultProperties();

            $annotationReader = new AnnotationReader();

            $permissions[$entityName] = array(
                'readable'  => $this->app['auth.user'] ? Permission::isReadable($this->app['auth.user'], $entityName) : 0,
                'writable'  => $this->app['auth.user'] ? Permission::isWritable($this->app['auth.user'], $entityName) : 0,
                'deletable' => $this->app['auth.user'] ? Permission::isDeletable($this->app['auth.user'], $entityName) : 0,
                'export'    => $this->app['auth.user'] ? Permission::canExport($this->app['auth.user'], $entityName) : 0,
                'extended'  => $this->app['auth.user'] ? Permission::getExtended($this->app['auth.user'], $entityName) : 0
            );

            $i18n = false;
            if($object instanceof BaseI18n){

                if(!defined('APP_CMS_MAIN_LANG')){
                    throw new ContentflyException('contentfly_i18n_undefined_languages');
                }

                $i18n = true;
            }

            $settings = array(
                'label' => $entity,
                'readonly' => false,
                'hide' => false,
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
                'viewMode' => 0,
                'i18n' => $i18n,
                'sort' => 1000,
                'excludeFromSync' => false
            );



            if($object instanceof BaseSortable || $object instanceof BaseI18nSortable){
                $settings['sortBy']     = 'sorting';
                $settings['sortOrder']  = 'ASC';
                $settings['isSortable'] = true;
            }

            if($object instanceof BaseTree || $object instanceof BaseI18nTree){
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
                    $settings['sortBy']         = $classAnnotation->sortBy ? $classAnnotation->sortBy : $settings['sortBy'];
                    $settings['sortOrder']      = $classAnnotation->sortOrder ? $classAnnotation->sortOrder : $settings['sortOrder'];
                    $settings['hide']           = $classAnnotation->hide ? $classAnnotation->hide : $settings['hide'];
                    $settings['sortRestrictTo'] = $classAnnotation->sortRestrictTo ? $classAnnotation->sortRestrictTo : $settings['sortRestrictTo'];
                    $settings['viewMode']       = $classAnnotation->viewMode ? intval($classAnnotation->viewMode) : $settings['viewMode'];
                    $settings['sort']           = $classAnnotation->sort ? intval($classAnnotation->sort) : $settings['sort'];
                    $settings['excludeFromSync']= $classAnnotation->excludeFromSync ? $classAnnotation->excludeFromSync : false;

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
                        $type->setEntitySettings($settings);

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

        $data['_hash'] = md5(serialize($data).serialize($permissions));

        if(Adapter::getConfig()->APP_ENABLE_SCHEMA_CACHE){

            file_put_contents($cacheFile, serialize($data));
        }

        return $data;
    }

    public function getSingle($entityName, $id = null, $where = null, $lang = null, $returnObject = false, $compareToLang = null, $loadJoinedLang = null, $clearEM = false){

        $helper             = new Helper();
        $entityFullName     = $helper->getFullEntityName($entityName);
        $entityShortName    = $helper->getShortEntityName($entityName);

        $schema = $this->app['schema'];
        if(!isset($schema[$entityShortName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_entity, $entityShortName, Messages::contentfly_status_not_found);
        }

        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new ContentflyException(Messages::contentfly_general_access_denied, $entityShortName, Messages::contentfly_status_access_denied);
        }

        $entityNameAlias = 'a'.md5($entityShortName);

        $object = null;

        $queryBuilder = $this->em->createQueryBuilder();
        if($clearEM) $this->em->clear($entityFullName);
        $queryBuilder
            ->select($entityNameAlias)
            ->from($entityFullName, $entityNameAlias);


        $query = null;

        if($id){
            $queryBuilder
                ->where("$entityNameAlias.id = :id")
                ->setParameter('id', $id);

            if($schema[$entityShortName]['settings']['i18n']){
                if(empty($lang)){
                    throw new ContentflyException(Messages::contentfly_i18n_missing_lang_param, $entityShortName);
                }
                $queryBuilder
                    ->andWhere("$entityNameAlias.lang = :lang")
                    ->setParameter('lang', $lang);
            }
        }elseif($where){
            foreach($where as $field => $value){
                $queryBuilder
                    ->andWhere("$entityNameAlias.$field = :$field")
                    ->setParameter($field, $lang);
            }
        }else{
            throw new ContentflyException(Messages::contentfly_general_missing_params, $entityShortName);
        }

        foreach ($schema[$entityShortName]['properties'] as $field => $config) {


            switch ($config['type']) {
                case 'onejoin':
                    $joinedEntity = $helper->getShortEntityName($config['accept']);
                    if ($schema[$joinedEntity]['settings']['i18n']) {
                        $queryBuilder->leftJoin("$entityNameAlias.$field", $field, Join::WITH, "$field.lang = :lang");
                        $queryBuilder->addSelect($field);
                    }
                    break;
                case 'join':
                    $joinedEntity = $helper->getShortEntityName($config['accept']);
                    if ($schema[$joinedEntity]['settings']['i18n']) {
                        $queryBuilder->leftJoin("$entityNameAlias.$field", $entityNameAlias.$field, Join::WITH, $entityNameAlias.$field.".lang = :loadJoinedLang");
                        $queryBuilder->addSelect($entityNameAlias.$field);

                        if($loadJoinedLang){
                            $queryBuilder->setParameter('loadJoinedLang', $loadJoinedLang);
                        }else{
                            $queryBuilder->setParameter('loadJoinedLang', $lang);
                        }

                        foreach($schema[$joinedEntity]['properties'] as $subfield => $subconfig){
                            switch ($subconfig['type']) {
                                case 'join':
                                    $joinedSubEntity = $helper->getShortEntityName($subconfig['accept']);
                                    if ($schema[$joinedSubEntity]['settings']['i18n']) {

                                        $queryBuilder->leftJoin($entityNameAlias.$field.'.'.$subfield, $entityNameAlias.$field.$subfield, Join::WITH, $entityNameAlias.$field.$subfield.".lang = :loadJoinedLang");
                                        $queryBuilder->addSelect($entityNameAlias.$field.$subfield);

                                        if($loadJoinedLang){
                                            $queryBuilder->setParameter('loadJoinedLang', $loadJoinedLang);
                                        }else{
                                            $queryBuilder->setParameter('loadJoinedLang', $lang);
                                        }
                                    }
                                    break;
                            }
                        }
                    }
                    break;
                case 'multijoin':
                    $joinedEntity = $helper->getShortEntityName($config['accept']);
                    if ($schema[$joinedEntity]['settings']['i18n']) {
                        $queryBuilder->leftJoin("$entityNameAlias.$field", $field, Join::WITH, "$field.lang = :loadJoinedLang");
                        $queryBuilder->addSelect($field);

                        if($loadJoinedLang){
                            $queryBuilder->setParameter('loadJoinedLang', $loadJoinedLang);
                        }else{
                            $queryBuilder->setParameter('loadJoinedLang', $lang);
                        }
                    }
                    break;
            }
        }

        $object = $queryBuilder->getQuery()->getSingleResult();

        if (!$object) {
            return new JsonResponse(array('message' => "Object not found"), Messages::contentfly_status_not_found);

        }

        $compareObject = null;
        if($compareToLang && $compareToLang != $lang) {
            if(!$loadJoinedLang) {
                //Bestehenden übersetzten Datensatz bearbeiten
                try {
                    $compareObject = $this->getSingle($entityShortName, $id, $where, $compareToLang, true, null, null, true);
                } catch (\Exception $e) {
                    $compareObject = null;
                }

                if ($compareObject) {

                    foreach ($schema[$entityShortName]['properties'] as $field => $config) {
                        $getter = 'get' . ucfirst($field);
                        switch ($config['type']) {
                            case 'join':

                                if ($object->$getter() && !$compareObject->$getter()) {
                                    $helper = new Helper();
                                    throw new ContentflyI18NException(Messages::contentfly_i18n_missing_translations, $helper->getShortEntityName($config['accept']), $compareToLang);
                                }
                                break;
                            case 'multijoin':
                                $a1 = $compareObject->$getter() ? $compareObject->$getter() : array();
                                $a2 = $object->$getter() ? $object->$getter() : array();

                                if (count($a1) != count($a2)) {
                                    $helper = new Helper();
                                    throw new ContentflyI18NException(Messages::contentfly_i18n_missing_translations, $helper->getShortEntityName($config['accept']), $compareToLang);
                                }
                                break;
                        }
                    }
                }
            }else{
                //Datensatz neu übersetzen

                try {
                    $compareObject = $this->getSingle($entityShortName, $id, $where, $lang, true, null, null, true);
                } catch (\Exception $e) {
                    $compareObject = null;
                }

                if ($compareObject) {
                    foreach ($schema[$entityShortName]['properties'] as $field => $config) {
                        $getter = 'get' . ucfirst($field);
                        switch ($config['type']) {
                            case 'join':
                                if ($compareObject->$getter() && !$object->$getter()) {
                                    $helper = new Helper();
                                    throw new ContentflyI18NException(Messages::contentfly_i18n_missing_translations, $helper->getShortEntityName($config['accept']), $compareToLang);
                                }
                                break;
                            case 'multijoin':
                                $a1 = $compareObject->$getter() ? $compareObject->$getter() : array();
                                $a2 = $object->$getter() ? $object->$getter() : array();

                                if (count($a1) != count($a2)) {
                                    $helper = new Helper();
                                    throw new ContentflyI18NException(Messages::contentfly_i18n_missing_translations, $helper->getShortEntityName($config['accept']), $compareToLang);
                                }
                                break;
                        }
                    }
                }

            }
        }



        if($permission == \Areanet\PIM\Entity\Permission::OWN && ($object->getUserCreated() != $this->app['auth.user'] && !$object->hasUserId($this->app['auth.user']->getId()))){
            throw new ContentflyException(Messages::contentfly_general_access_denied, "$entityShortName::$id", Messages::contentfly_status_access_denied);
        }

        if($permission == \Areanet\PIM\Entity\Permission::GROUP){
            if($object->getUserCreated() != $this->app['auth.user']){
                $group = $this->app['auth.user']->getGroup();
                if(!($group && $object->hasGroupId($group->getId()))){
                    throw new ContentflyException(Messages::contentfly_general_access_denied, "$entityShortName::$id", Messages::contentfly_status_access_denied);
                }
            }
        }

        return $returnObject ? $object : $object->toValueObject($this->app, $entityShortName, false);
    }

    protected function getTableName($entityName, $tablename){

        if(empty($this->app['schema'][$entityName])){
            return $tablename;
        }

        return isset($this->app['schema'][$entityName]['settings']['dbname']) ? $this->app['schema'][$entityName]['settings']['dbname'] : $entityName;
    }

    public function getTranslations($entityName, $lang){

        $helper             = new Helper();
        $entityShortName    = $helper->getShortEntityName($entityName);

        if(!($permission = Permission::isReadable($this->app['auth.user'], $entityName))){
            throw new ContentflyException(Messages::contentfly_general_access_denied, $entityShortName, Messages::contentfly_status_access_denied);
        }

        $schema = $this->app['schema'];

        if(empty($schema[$entityName])){
            throw new ContentflyException(Messages::contentfly_general_invalid_entity, $entityShortName);
        }


        if(!$schema[$entityName]['settings']['i18n']){
            throw new ContentflyException(Messages::contentfly_general_invalid_base_entity, $entityShortName);
        }

        if(empty($lang)){
            throw new ContentflyException(Messages::contentfly_i18n_missing_lang_param, $entityShortName);
        }

        $dbName       = $schema[$entityName]['settings']['dbname'];
        /** @var $queryBuilder QueryBuilder */
        $queryBuilder = $this->database->createQueryBuilder();

        $queryBuilder
            ->select('lang', 'COUNT(*) AS records')
            ->from($dbName)
            ->where("id NOT IN (SELECT id FROM $dbName WHERE lang = :lang) ")
            ->groupBy('lang')
            ->setParameter('lang', $lang);

        return $queryBuilder->execute()->fetchAll();
    }

    public function getTree($entityName, $parent, $properties = array(), $lang = null){

        $helper             = new Helper();
        $schema             = $this->app['schema'];

        $entityFullName     = $helper->getFullEntityName($entityName);
        $entityShortName    = $helper->getShortEntityName($entityName);
        $entityNameAlias    = 'entity'.md5($entityName);
        $entityParentAlias  = 'entityparent'.md5($entityName);
        $properties         = is_array($properties) ? $properties : array();

        if(!isset($schema[$entityShortName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_entity, $entityShortName, Messages::contentfly_status_not_found);
        }

        $i18n               = $schema[$entityShortName]['settings']['i18n'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder->from($entityFullName, $entityNameAlias)
            ->where("$entityNameAlias.isIntern = false")
            ->orderBy($entityNameAlias.'.sorting', 'ASC');

        if($i18n){
            $queryBuilder->andWhere("$entityNameAlias.lang = :lang");
            $queryBuilder->setParameter('lang', $lang);
        }

        if($parent){
            if($i18n){
                $queryBuilder->join("$entityNameAlias.treeParent", $entityParentAlias);
                $queryBuilder->andWhere("$entityParentAlias.id = :treeParentId AND $entityParentAlias.lang=:lang");
                $queryBuilder->setParameter('treeParentId', $parent->getId());
            }else{
                $queryBuilder->andWhere("$entityNameAlias.treeParent = :treeParent");
                $queryBuilder->setParameter('treeParent', $parent);
            }

        }else{
            $queryBuilder->andWhere("$entityNameAlias.treeParent IS NULL");
        }

        if(count($properties)){
            $properties[] = 'id';
            if($i18n){
                $properties[] = 'lang';
            }

            $partialProperties = implode(',', $properties);
            $queryBuilder->select('partial '.$entityNameAlias.'.{'.$partialProperties.'}');
        }else{
            $queryBuilder->select($entityNameAlias);
        }


        $query   = $queryBuilder->getQuery();
        $objects = $query->getResult();

        $array   = array();

        foreach($objects as $object){
            $data = $object->toValueObject($this->app, $entityShortName, true, $properties);
            $data->treeChilds = $this->getTree($entityShortName, $object, $properties, $lang);
            $array[] = $data;
        }

        return $array;
    }

    public function getTree2($entityName, $lang = null){
        $helper             = new Helper();
        $schema             = $this->app['schema'];

        $entityFullName     = $helper->getFullEntityName($entityName);
        $entityShortName    = $helper->getShortEntityName($entityName);

        if(!isset($schema[$entityShortName])){
            throw new ContentflyException(Messages::contentfly_general_unknown_entity, $entityShortName, Messages::contentfly_status_not_found);
        }

        $i18n       = $schema[$entityShortName]['settings']['i18n'];
        $tblName    = $schema[$entityShortName]['settings']['dbname'];
        $dbFields   = array();

        foreach($schema[$entityShortName]['list'] as $propName){
            $propConfig = $schema[$entityShortName]['properties'][$propName];

            switch($propConfig['type']){
                case 'multijoin':
                case 'multifile':
                    continue;
                case 'file':
                case 'join':
                    $fieldName = $propConfig['dbfield'] ? $propConfig['dbfield'] : $propName.'_id';
                    $dbFields[$fieldName] = array('propName' => $propName, 'propType' => $propConfig['type']);
                    break;
                default:
                    $dbFields[$propName] = array('propName' => $propName, 'propType' => $propConfig['type']);
                    break;
            }
        }


        unset($dbFields['id']);
        unset($dbFields['sorting']);
        unset($dbFields['parent_id']);

        $tblTreeName  = 'pim_tree';
        $joinI18NCond = '';

        if($i18n){
            $tblTreeName  = 'pim_i18n_tree';
            $joinI18NCond = "AND t.lang = e.lang AND t.lang = '$lang'";
        }

        $statement = "
            SELECT t.id, ".implode(',', array_keys($dbFields)).", t.sorting, t.parent_id 
            FROM $tblName e 
            INNER JOIN $tblTreeName t 
              on e.id = t.id $joinI18NCond
            ORDER BY t.parent_id, t.sorting ";

        $records = $this->app['database']->fetchAll($statement);

        $tree = $this->treeSort($records, $dbFields, null);

        return $tree;
    }

    private function treeSort($records, $dbFields, $parent_id){

        $items = array_filter($records, function($record) use ($parent_id) { return $parent_id ? $record['parent_id'] == $parent_id : empty($record['parent_id']); });

        $tree = array();

        foreach($items as $item){

            foreach($item as $dbField => $value){
                $dbConfig = $dbFields[$dbField];
                switch($dbConfig['propType']){
                    case 'join':
                    case 'file':
                        $item[$dbConfig['propName']] = array('id' => $item[$dbField]);
                        unset($item[$dbField]);
                        break;
                    case 'integer':
                        $item[$dbField] = intval($item[$dbField]);
                        break;
                }
            }

            $item['parent'] = array('id' => $item['parent_id']);
            unset($item['parent_id']);
            $item['sorting'] = intval($item['sorting']);
            $item['childs'] = $this->treeSort($records, $dbFields, $item['id']);

            $tree[]         = $item;
        }

        return $tree;
    }

    public function getQuery(Array $params){

        $helper         = new Helper();
        $queryBuilder   = $this->database->createQueryBuilder();
        $paramCount     = 0;
        $schema         = $this->getSchema();

        if(!$this->app['auth.user']->getIsAdmin()){
            $group = $this->app['auth.user']->getGroup();
            if(!$group || $group->getApiQueryEnabled() != 'enabled'){
                throw new ContentflyException(Messages::contentfly_general_access_denied, 'api::query');
            }
        }

        if(!isset($params['select']) || !isset($params['from'])){
            throw new ContentflyException(Messages::contentfly_general_missing_params);
        }



        foreach($params as $method => $params){

            if($method == 'delete' || $method == 'insert'|| $method == 'update'){
                throw new ContentflyException(Messages::contentfly_general_invalid_params, $method);
            }

            $method = $method == 'where' ? 'andWhere' : $method;

            if(method_exists($queryBuilder, $method)){
                if(is_array($params)){
                    if($this->isIndexedArray($params)){

                        if(in_array($method, array('join', 'innerJoin', 'leftJoin', 'rightJoin'))){

                            $joins = array($params);

                            if(is_array($params[0])){
                                $joins = $params;
                            }

                            foreach($joins as $join){
                                if(count($join) != 4){
                                    throw new ContentflyException(Messages::contentfly_general_invalid_params, $method);
                                }

                                $entityName         = $join[1];
                                $entityAlias        = $join[2];
                                $entityShortName    = $helper->getShortEntityName($entityName);

                                if(!isset($schema[$entityShortName])) {
                                    foreach($schema as $entityNameFromSchema => $entityConfig){
                                        if($entityNameFromSchema == '_hash') continue;

                                        if(strtolower($entityConfig['settings']['dbname']) == strtolower($entityName)){
                                            $entityShortName = $entityNameFromSchema;
                                            break;
                                        }
                                    }
                                }

                                if(isset($schema[$entityShortName])) {

                                    if (!($permission = Permission::isReadable($this->app['auth.user'], $entityShortName))) {
                                        throw new ContentflyException(Messages::contentfly_general_access_denied, $entityShortName, Messages::contentfly_status_access_denied);
                                    }

                                    if ($permission == \Areanet\PIM\Entity\Permission::OWN) {
                                        $queryBuilder->andWhere("$entityAlias.usercreated_id = ? OR FIND_IN_SET(?, $entityAlias.users) > 0");
                                        $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                        $paramCount++;
                                        $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                        $paramCount++;
                                    } elseif ($permission == \Areanet\PIM\Entity\Permission::GROUP) {
                                        $group = $this->app['auth.user']->getGroup();
                                        if (!$group) {
                                            $queryBuilder->andWhere("$entityAlias.usercreated_id = ?");
                                            $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                            $paramCount++;
                                        } else {
                                            $queryBuilder->andWhere("$entityAlias.usercreated_id = ? OR FIND_IN_SET(?, $entityAlias.groups) > 0");
                                            $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                            $paramCount++;
                                            $queryBuilder->setParameter($paramCount, $group->getId());
                                            $paramCount++;
                                        }
                                    }
                                }

                                $join[1] = $this->getTableName($entityName, $join[1]);

                                call_user_func_array(array($queryBuilder, $method), $join);
                            }


                        }else{
                            call_user_func_array(array($queryBuilder, $method), $params);
                        }



                    }else{
                        reset($params);
                        $queryKey       = key($params);
                        $queryParams    = $params[$queryKey];

                        if($method == 'from'){
                            //$queryKey     = entityName
                            //$queryParams  = entityAlias
                            $entityShortName = $helper->getShortEntityName($queryKey);

                            if(!isset($schema[$entityShortName])) {
                                foreach($schema as $entityNameFromSchema => $entityConfig){
                                    if($entityNameFromSchema == '_hash') continue;

                                    if(strtolower($entityConfig['settings']['dbname']) == strtolower($queryKey)){
                                        $entityShortName = $entityNameFromSchema;
                                        break;
                                    }
                                }
                            }

                            if(isset($schema[$entityShortName])) {
                                if (!($permission = Permission::isReadable($this->app['auth.user'], $entityShortName))) {
                                    throw new ContentflyException(Messages::contentfly_general_access_denied, $entityShortName, Messages::contentfly_status_access_denied);
                                }

                                if ($permission == \Areanet\PIM\Entity\Permission::OWN) {
                                    $queryBuilder->andWhere("$queryParams.userCreated_id = ? OR FIND_IN_SET(?, $queryParams.users) > 0");
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                    $paramCount++;
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                    $paramCount++;
                                } elseif ($permission == \Areanet\PIM\Entity\Permission::GROUP) {
                                    $group = $this->app['auth.user']->getGroup();
                                    if (!$group) {
                                        $queryBuilder->andWhere("$queryParams.usercreated_id = ?");
                                        $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                        $paramCount++;
                                    } else {
                                        $queryBuilder->andWhere("$queryParams.usercreated_id = ? OR FIND_IN_SET(?, $queryParams.groups) > 0");
                                        $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                        $paramCount++;
                                        $queryBuilder->setParameter($paramCount, $group->getId());
                                        $paramCount++;
                                    }
                                }
                            }

                            $queryKey = $this->getTableName($entityShortName, $queryKey);

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
                        $entityShortName = $helper->getShortEntityName($params);

                        if(!isset($schema[$entityShortName])) {
                            foreach($schema as $entityNameFromSchema => $entityConfig){
                                if($entityNameFromSchema == '_hash') continue;
                                if(strtolower($entityConfig['settings']['dbname']) == strtolower($params)){
                                    $entityShortName = $entityNameFromSchema;
                                    break;
                                }
                            }
                        }

                        if(isset($schema[$entityShortName])) {

                            if (!($permission = Permission::isReadable($this->app['auth.user'], $entityShortName))) {
                                throw new ContentflyException(Messages::contentfly_general_access_denied, $entityShortName, Messages::contentfly_status_access_denied);
                            }

                            if ($permission == \Areanet\PIM\Entity\Permission::OWN) {
                                $queryBuilder->andWhere("userCreated_id = ? OR FIND_IN_SET(?, users) > 0");
                                $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                $paramCount++;
                                $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                $paramCount++;
                            } elseif ($permission == \Areanet\PIM\Entity\Permission::GROUP) {
                                $group = $this->app['auth.user']->getGroup();
                                if (!$group) {
                                    $queryBuilder->andWhere("userCreated_id = ?");
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                    $paramCount++;
                                } else {
                                    $queryBuilder->andWhere("usercreated_id = ? OR FIND_IN_SET(?, groups) > 0");
                                    $queryBuilder->setParameter($paramCount, $this->app['auth.user']->getId());
                                    $paramCount++;
                                    $queryBuilder->setParameter($paramCount, $group->getId());
                                    $paramCount++;
                                }
                            }
                        }

                        $params = $this->getTableName($entityShortName, $params);
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