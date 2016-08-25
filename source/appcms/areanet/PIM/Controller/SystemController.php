<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Entity\Folder;
use Areanet\PIM\Entity\ThumbnailSetting;
use Areanet\PIM\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemController extends BaseController
{

    /**
     * @apiVersion 1.3.0
     * @api {post} /system/do do
     * @apiName Ausführen
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

        return new JsonResponse(array('method' => $method, 'datetime' => $date->format('Y-m-d H:i:s'),  'message' => $this->$method() ));
    }

    protected function flushSchemaCache()
    {
        if(file_exists(ROOT_DIR.'/../data/cache/schema.cache')){
            unlink(ROOT_DIR.'/../data/cache/schema.cache');
        }

        return 'Schema-Cache wurde geleert!';
    }

    protected function updateDatabase()
    {
        //die('php_cli '.ROOT_DIR.'/vendor/bin/doctrine orm:schema:update --force');
        return shell_exec('cd '.ROOT_DIR.' && SERVER_NAME="'.$_SERVER['SERVER_NAME'].'" '.Adapter::getConfig()->SYSTEM_PHP_CLI_COMMAND.' vendor/bin/doctrine orm:schema:update --force');
    }

    protected function validateORM()
    {
        //die('php_cli '.ROOT_DIR.'/vendor/bin/doctrine orm:schema:update --force');

        return shell_exec('cd '.ROOT_DIR.' && SERVER_NAME="'.$_SERVER['SERVER_NAME'].'" '.Adapter::getConfig()->SYSTEM_PHP_CLI_COMMAND.' vendor/bin/doctrine orm:validate-schema');
    }

}