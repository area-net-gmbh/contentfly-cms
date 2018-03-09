<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Entity\Folder;
use Areanet\PIM\Entity\Log;
use Areanet\PIM\Entity\ThumbnailSetting;
use Areanet\PIM\Entity\Token;
use Areanet\PIM\Entity\User;
use Custom\Entity\Ansprechpartner;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemController extends BaseController
{

    /**
     * @apiVersion 1.3.0
     * @api {post} /system/do do
     * @apiName Ausführen
     * @apiDescription Führt Systembefehle aus.
     * @apiGroup System
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} method Auszuführende Methode
     * @apiParamExample {json} Schema-Cache leeren:
     *     {
     *      "method": "flushSchemaCache",
     *     }
     * @apiParamExample {json} Datenbank synchronisieren:
     *     {
     *      "method": "updateDatabase",
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "method": "flushSchemaCache",
     *       "message:" "..."
     *   }
     */
    public function doAction(Request $request)
    {
        $method = $request->get('method');
        
        if(!method_exists($this, $method)){
            throw new \Exception("Methode $method nicht verfügbar.");
        }

        $date = new \DateTime();

        return new JsonResponse(array('method' => $method, 'datetime' => $date->format('Y-m-d H:i:s'),  'message' => $this->$method($request) ));
    }

    protected function flushSchemaCache(Request $request)
    {
        if(file_exists(ROOT_DIR.'/../data/cache/schema.cache')){
            unlink(ROOT_DIR.'/../data/cache/schema.cache');
        }

        if(!Adapter::getConfig()->APP_DEBUG){
            $this->app['orm.em']->getConfiguration()->getQueryCacheImpl()->deleteAll();
            $this->app['orm.em']->getConfiguration()->getMetadataCacheImpl()->deleteAll();
        }

        return 'Schema-Cache wurde geleert!';
    }

    protected function updateDatabase(Request $request)
    {

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->app['orm.em']);
        $classes = $this->app['orm.em']->getMetadataFactory()->getAllMetadata();
        try {
            $schemaTool->updateSchema($classes);
        }catch(\Exception $e){
            return $e->getMessage();
        }

        return "Die Datenbank wurde erfolgreich aktualisiert.";
    }
    

    protected function deleteToken(Request $request)
    {
        $id =  $request->get('id');

        $token = $this->em->getRepository('Areanet\\PIM\\Entity\\Token')->find($id);
        if(!$token){
            throw new \Exception('Token ungültig');
        }

        $log = new Log();
        $log->setModelId($id);
        $log->setModelName('PIM\\Token');
        $log->setUser($this->app['auth.user']);
        $log->setMode('Gelöscht');
        $log->setModelLabel($token->getToken());

        $this->em->remove($token);
        $this->em->persist($log);
        $this->em->flush();

        return true;
    }

    protected function generateToken(Request $request)
    {
        return bin2hex(openssl_random_pseudo_bytes(64));
    }

    protected function listTokens(Request $request)
    {
        $query  = $this->em->createQuery("SELECT token FROM Areanet\PIM\Entity\Token token WHERE token.referrer <> ''");
        $tokens = $query->getResult();

        $data = array();
        foreach($tokens as $token){
            $userData = array(
                'id'        => $token->getUser()->getId(),
                'alias'     => $token->getUser()->getAlias(),
                'active'    => $token->getUser()->getIsActive()
            );

            $data[] = array('id' => $token->getId(), 'token' => $token->getToken(), 'referrer' => $token->getReferrer(), 'user' => $userData);
        }

        return $data;
    }

    protected function addToken(Request $request)
    {
        $referrer    =  $request->get('referrer');
        $tokenString =  $request->get('token');
        $userId      =  $request->get('user');

        if(!$referrer || !$tokenString || !$userId){
            throw new \Exception('Token und/oder Referrer ungültig');
        }

        $user = $this->em->getRepository('Areanet\\PIM\\Entity\\User')->find($userId);
        if(!$user){
            throw new \Exception('Benutzer ungültig');
        }

        $token = new Token();
        $token->setUser($user);
        $token->setReferrer($referrer);
        $token->setToken($tokenString);



        try {
            $this->em->persist($token);
            $this->em->flush();
        }catch(\Exception $e){
            throw new \Exception('Der Token ist bereits vorhanden.');
        }

        $log = new Log();
        $log->setModelId($token->getId());
        $log->setModelName('PIM\\Token');
        $log->setUser($this->app['auth.user']);
        $log->setMode('Erstellt');
        $log->setModelLabel($token->getToken());
        $this->em->persist($log);
        $this->em->flush();

        $userData = array(
            'id' => $token->getUser()->getId(),
            'alias' => $token->getUser()->getAlias(),
            'active' => $token->getUser()->getIsActive()
        );

        return array('id' => $token->getId(), 'token' => $token->getToken(), 'referrer' => $token->getReferrer(), 'user' => $userData);
    }
}