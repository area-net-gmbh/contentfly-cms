<?php
namespace Areanet\PIM\Classes;
use Areanet\PIM\Classes\Manager\LoginManager;
use Areanet\PIM\Entity\User;
use Silex\Application;


/**
 * Class Config
 * @package Areanet\PIM\Classes
 */
class Auth{

    /** @var Application $app */
    protected $app;

    protected $token;

    /**
     * Manager constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;


    }

    public function init(){
        if(($userId = $this->app['session']->get('auth.userid'))){
            $user = $this->app['orm.em']->getRepository('Areanet\PIM\Entity\User')->find($userId);
            if($user) $this->setUser($user);
        }
    }

    protected function getLoginProvider($loginProviderClassName){
        if(empty($loginProviderClassName)){
            return null;
        }

        $loginProviderClass = "Custom\Classes\\$loginProviderClassName";

        if(!class_exists($loginProviderClass)){
            return null;
        }

        $loginProvider = new $loginProviderClass($this->app, null);
        if(!($loginProvider instanceof LoginManager)){
            return null;
        }

        return $loginProvider;
    }

    public function login($alias, $pass){


        $user = $this->app['orm.em']->getRepository('Areanet\PIM\Entity\User')->findOneBy(array('alias' => $alias));
        if (!$user) {
            throw new \Exception('Ungültiger Benutzername.', 401);
        }

        if(!$user->getIsActive()){
            throw new \Exception(array('message' => 'Der Benutzer ist gesperrt.'), 401);
        }

        if($user->getLoginManager()){
            throw new \Exception(array('message' => 'Der Benutzer ist nur über LoginManager authorisierbar.'), 401);
        }

        if (!$user->isPass($pass)) {
            throw new \Exception('Benutzername und/oder Passwort fehlerhaft.', 401);
        }

        $this->app['session']->set('auth.userid', $user->getId());

        $this->setUser($user);

        return $user;
    }

    public function logout(){
        $this->app['session']->remove('auth.userid');
        $this->setUser(null);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return isset($this->app['auth.user']) ? $this->app['auth.user'] : null;
    }


    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->app['auth.user'] = $user;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }


}