<?php

namespace Areanet\Contentfly\Classes\Manager;
use Areanet\Contentfly\Classes\Manager;
use Areanet\Contentfly\Entity\Group;
use Areanet\Contentfly\Entity\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

abstract class LoginManager extends Manager
{
    /* @var $request Request */
    var $request = null;

    public function __construct(Application $app, Request $request = null)
    {
        $this->request = $request;

        parent::__construct($app);
    }

    public function createManagedUser($alias, Group $group = null, $isAdmin = false){
        $class = get_class($this);

        $alias = md5($class).'-'.$alias;
        $user = $this->app['orm.em']->getRepository('Areanet\Contentfly\Entity\User')->findOneBy(array('alias' => $alias));
        if(!$user){
            $user = new User();
            $user->setAlias($alias);
            $user->setPass($alias);
            $user->setIsAdmin($isAdmin);
            if($group) $user->setGroup($group);
            $user->setLoginManager($class);
            $this->app['orm.em']->persist($user);
            $this->app['orm.em']->flush();
        }else{
            $user->setIsAdmin($isAdmin);
            if($group) $user->setGroup($group);
            $this->app['orm.em']->flush();
        }

        return $user;
    }

    abstract public function auth();
}