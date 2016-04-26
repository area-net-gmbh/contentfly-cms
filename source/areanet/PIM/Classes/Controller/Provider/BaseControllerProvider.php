<?php
namespace Areanet\PIM\Classes\Controller\Provider;

use Areanet\PIM\Controller\ApiController;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class BaseControllerProvider implements ControllerProviderInterface
{
    const TOKEN_TIMEOUT        = 300; //5min
    const CHECK_TOKEN_TIMEOUT  = false;

    const LOGIN_PATH           = '/login';
    const TOKEN_HEADER_KEY     = 'X-Token';
    const TOKEN_REQUEST_KEY    = '_token';
    private $basePath = '';

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }


    protected function setUpMiddleware(Application $app)
    {
        $app->before(function (Request $request) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });
    }

    protected function isAuthRequiredForPath($path)
    {
        return !in_array($path, [$this->basePath . self::LOGIN_PATH]);
    }

    protected function checkToken(Request $request, Application $app){
        $tokenString = $request->headers->get(self::TOKEN_HEADER_KEY, $request->get(self::TOKEN_REQUEST_KEY));

        if(!$tokenString) return false;

        $token = $app['orm.em']->getRepository('Areanet\PIM\Entity\Token')->findOneBy(array('token' => $tokenString));
        if(!$token){
            return false;
        }

        if(self::CHECK_TOKEN_TIMEOUT) {
            $modified = $token->getModified()->getTimestamp();
            $now = (new \DateTime())->getTimestamp();
            $diff = $now - $modified;

            if ($diff > self::TOKEN_TIMEOUT) {
                $app['orm.em']->remove($token);
                $app['orm.em']->flush();
                return false;
            }
        }

        $app['auth.user']  = $token->getUser();
        $app['auth.token'] = $token;

        return true;
    }
}