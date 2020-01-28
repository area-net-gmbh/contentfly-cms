<?php
namespace Areanet\Contentfly\Classes;

use Silex\Application;

class Manager
{
    /** @var Application */
    protected $app;

    /**
     * Manager constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        
        $this->app = $app;
    }
}