<?php
namespace Areanet\PIM\Classes\Controller;

use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Silex\Application;

abstract class BaseController
{
    /** @var Application $app */
    protected $app;

    /** @var EntityManager $em */
    protected $em;

    /** @var \Twig_Environment $twig */
    protected $twig;

    public function __construct($app)
    {
        $this->app = $app;
        if($this->app['orm.em']) $this->setEM($this->app['orm.em']);
        $this->setTwig($this->app['twig']);
        
    }

    protected function setEM(EntityManager $em){
        $this->em = $em;
    }

    protected function setTwig(\Twig_Environment $twig){
        $this->twig = $twig;
    }
    
}
