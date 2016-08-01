<?php
namespace Areanet\PIM\Classes;

use Silex\Application;

class Manager
{
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