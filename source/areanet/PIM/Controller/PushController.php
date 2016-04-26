<?php
namespace Areanet\PIM\Controller;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\File\Backend\FileSystem;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\PushToken;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Silex\Application;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class PushController extends BaseController
{

    /**
     * @apiVersion 1.3.0
     * @api {post} /push/settoken settoken
     * @apiName Schema
     * @apiGroup Push
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiDescription Gibt das Schema aller Entities zurück
     *
     * @apiParam {String} token Device-Token.
     * @apiParam {String="ios","android"} platform Betriebssystem des Gerätes.
     * @apiSuccess {String} message  Token registriert
     * @apiError 500 Token bereits vorhanden
     * @apiError 501 Unbekannter Fehler beim Einfügen des Token
     */
    public function setTokenAction(Request $request)
    {
        $data = array();

        try {
            $pushToken = new PushToken();
            $pushToken->setToken($request->get("token"));
            $pushToken->setPlatform($request->get("platform"));
            $this->em->persist($pushToken);
            $this->em->flush();
        }catch(UniqueConstraintViolationException $e){
            return new JsonResponse(array('message' => "Token bereits vorhanden"), 500);
        }catch(\Exception $e){
            return new JsonResponse(array('message' => "Unbekannter Fehler beim Einfügen des Token"), 501);
        }

        return new JsonResponse(array('message' => "Token registriert"));
    }


}