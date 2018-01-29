<?php
namespace Areanet\PIM\Classes\Controller\Provider;

use Areanet\PIM\Classes\Config\Adapter;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseControllerProvider implements ControllerProviderInterface
{

    const LOGIN_PATH           = '/login';
    const TOKEN_HEADER_KEY_ALT = 'X-XSRF-TOKEN';
    const TOKEN_HEADER_KEY     = 'appcms-token';
    const TOKEN_REQUEST_KEY    = '_token';
    protected $basePath = '';

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }


    protected function setUpMiddleware(Application $app)
    {
        $app->before(function (Request $request)use ($app) {

            $controller = $request->get('_controller');
            if (is_string($controller) && substr($controller, 0, 7) != 'install' && Adapter::getConfig()->DB_HOST == '$SET_DB_HOST'){
                return $app->redirect('install');
            }

            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = null;
                if($request->getContent()) {
                    $data = json_decode($request->getContent(), true);
                    if ($data === null) {
                        throw new \Exception("Inavlid JSON-Data", 500);
                    }
                }
                $request->request->replace(is_array($data) ? $data : array());
            }else{

            }

            if(!is_object($request->get('_controller'))) {
                $event = new \Areanet\PIM\Classes\Event();
                $event->setParam('request', $request);
                $event->setParam('app', $app);
                $controllerAction = str_replace(':', '.', strtolower($request->get('_controller')));
                if (empty($controllerAction)) {
                    return;
                }

                $controllerParts = explode('.', $controllerAction);
                $app['dispatcher']->dispatch('pim.controller.before.' . $controllerParts[0] . '.' . $controllerParts[2], $event);

                $controllerParts = explode('.', $controllerAction);
                $app['dispatcher']->dispatch('pim.controller.before.' . $controllerParts[0], $event);
                $app['dispatcher']->dispatch('pim.controller.before', $event);
            }
        });

        $app->after(function (Request $request, Response $response) use ($app) {

            if(!is_object($request->get('_controller'))) {
                $event = new \Areanet\PIM\Classes\Event();
                $event->setParam('request', $request);
                $event->setParam('response', $response);
                $event->setParam('app', $app);

                $controllerAction = str_replace(':', '.', strtolower($request->get('_controller')));
                if (empty($controllerAction)) {
                    return;
                }
                $controllerParts = explode('.', $controllerAction);
                $app['dispatcher']->dispatch('pim.controller.after.' . $controllerParts[0] . '.' . $controllerParts[2], $event);

                $controllerParts = explode('.', $controllerAction);
                $app['dispatcher']->dispatch('pim.controller.after.' . $controllerParts[0], $event);
                $app['dispatcher']->dispatch('pim.controller.after', $event);
            }
        });
    }

    protected function isAuthRequiredForPath($path)
    {
        return !in_array($path, [$this->basePath . self::LOGIN_PATH]);
    }

    protected function checkToken(Request $request, Application $app){
        $tokenString = $request->headers->get(self::TOKEN_HEADER_KEY, null);
        if(empty($tokenString)){
            $tokenString = $request->headers->get(self::TOKEN_HEADER_KEY_ALT, $request->get(self::TOKEN_REQUEST_KEY));
        }
        $headers = $request->headers->all();


        if(empty($tokenString)){
            return false;
        }

        $token = $app['orm.em']->getRepository('Areanet\PIM\Entity\Token')->findOneBy(array('token' => $tokenString));

        if(!$token){
            return false;
        }

        if(!$token->getUser() || !$token->getUser()->getIsActive()){
            return false;
        }

        $tokenTimeout = Adapter::getConfig()->APP_TOKEN_TIMEOUT;

        if(($group = $token->getUser()->getGroup())){
            $tokenTimeout =  $group->getTokenTimeout() * 60;
        }

        if(Adapter::getConfig()->APP_CHECK_TOKEN_TIMEOUT && !$token->getReferrer() && $tokenTimeout) {
            
            $modified   = $token->getModified()->getTimestamp();
            $now        = new \DateTime();
            $diff       = $now->getTimestamp() - $modified;

            if ($diff > $tokenTimeout) {
                $app['orm.em']->remove($token);
                $app['orm.em']->flush();
                return false;
            }else{
                $token->setModified($now);
                $app['orm.em']->flush();
            }
        }

        $app['auth.user']  = $token->getUser();
        $app['auth.token'] = $token;

        return true;
    }
}