<?php
namespace Areanet\PIM\Controller;
use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Classes\LoginProvider;
use Areanet\PIM\Classes\Manager\LoginManager;
use Areanet\PIM\Entity\Token;
use Areanet\PIM\Entity\User;
use Silex\Application;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class AuthController extends BaseController
{
    const MIN_LOGIN_INTERVAL = 60;
    const CHECK_LOGIN_INTERVAL = false;

    /**
     * @apiVersion 1.3.0
     * @api {post} /auth/login login
     * @apiName Login
     * @apiGroup User
     * @apiDescription API-Endpoint zur Authentifizierung eines Benutzers.
     *
     * Über einen benutzerdefinierten Login-Manager kann das standardmäßige Login-Verhalten des Contentfly CMS erweitert oder angepasst werden.
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     *
     * @apiParam {String} alias Benutzername
     * @apiParam {String} pass Passwort
     * @apiParam {String} loginManager Optionaler Login-Manager
     * @apiParam {Boolean} withSchema Schema zurückgeben
     * @apiParamExample {json} Request-Beispiel:
     *     {
     *      "alias": "admin",
     *      "pass": "xyz"
     *     }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "Login successful",
     *       "token": "sdnajn3sdfmkwrk23cskvavdfgq45sdfasgafg"
     *       "user": {
     *          "alias": "admin",
     *          "isAdmin": true
     *      }
     *   }
     * @apiError 401 Ungültiger Benutzername | Der Benutzer ist gesperrt | Benutzername und/oder Passwort fehlerhaft
     */
    public function loginAction(Request $request)
    {

        $loginProviderClass = $request->get('loginManager');
        if(($loginProvider = $this->getLoginProvider($request, $loginProviderClass))){
            try {
                $user = $loginProvider->auth();
                if(!($user instanceof User)){
                    return new JsonResponse(array('message' => 'Ungültiger Benutzer vom LoginManager'), 401);
                }
            }catch(\Exception $e){
                return new JsonResponse(array('message' => $e->getMessage()), 401);
            }
        }else{

            $user = $this->em->getRepository('Areanet\PIM\Entity\User')->findOneBy(array('alias' => $request->get('alias')));
            if(!$user){
                return new JsonResponse(array('message' => 'Ungültiger Benutzername.'), 401);
            }

            if(!$user->getIsActive()){
                return new JsonResponse(array('message' => 'Der Benutzer ist gesperrt.'), 401);
            }

            if($user->getLoginManager()){
                return new JsonResponse(array('message' => 'Der Benutzer ist nur über LoginManager authorisierbar.'), 401);
            }

            if(!$user->isPass($request->get('pass'))){
                return new JsonResponse(array('message' => 'Benutzername und/oder Passwort fehlerhaft.'), 401);
            }
        }

        if(self::CHECK_LOGIN_INTERVAL) {
            $lastToken = $this->em->getRepository('Areanet\PIM\Entity\Token')->findOneBy(array('user' => $user), array('created' => 'DESC'));
            if ($lastToken) {
                $created = $lastToken->getCreated()->getTimestamp();
                $now = (new \DateTime())->getTimestamp();
                $diff = $now - $created;
                if ($diff < self::MIN_LOGIN_INTERVAL) {
                    return new JsonResponse(array('message' => 'Login Intervall Error', 'remaining' => self::MIN_LOGIN_INTERVAL - $diff), 401);
                }
            }
        }

        $token = new Token();
        $token->setUser($user);

        $this->em->persist($token);
        $this->em->flush();

        $this->app['auth.user'] = $user;

        $response = array(
            'message' => 'Login successful',
            'token' => $token->getToken(),
            'user' => $user->toValueObject($this->app, 'PIM\User', false)
        );

        if(($tempData = $user->getTempData())){
            $response['data'] = $tempData;
        }

        if($request->get('withSchema')){
            $api = new Api($this->app);
            $response['schema'] = $api->getExtendedSchema();
            $response['hash']   = $this->app['schema']['_hash'];
        }

        return new JsonResponse($response);

    }

    /**
     * @apiVersion 1.3.0
     * @api {get} /auth/logout logout
     * @apiName Logout
     * @apiGroup User
     * @apiHeader {String} X-Token Acces-Token
     * @apiHeader {String} Content-Type=application/json
     */
    public function logoutAction()
    {
        $this->em->remove($this->app['auth.token']);
        $this->em->flush();

        unset($this->app['auth.token']);
        unset($this->app['auth.user']);

        return new JsonResponse(array('message' => 'Logout successful'));
    }

    protected function getLoginProvider(Request $request, $loginProviderClassName){
        if(empty($loginProviderClassName)){
            return null;
        }

        $loginProviderClass = "Custom\Classes\\$loginProviderClassName";

        if(!class_exists($loginProviderClass)){
            return null;
        }

        $loginProvider = new $loginProviderClass($this->app, $request);
        if(!($loginProvider instanceof LoginManager)){
            return null;
        }

        return $loginProvider;
    }
}