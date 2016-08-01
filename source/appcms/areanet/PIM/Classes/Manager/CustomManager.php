<?php
namespace Areanet\PIM\Classes\Manager;

use Areanet\PIM\Classes\Command\CustomCommand;
use Areanet\PIM\Classes\Manager;
use Custom\Command\AccessImport;
use Knp\Console\ConsoleEvent;
use Knp\Console\ConsoleEvents;
use Silex\Application;

class CustomManager extends Manager
{
    protected $app;


    /**
     * CustomManager constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }


    /**
     * @param CustomCommand $command
     */
    public function addCommand(CustomCommand $command)
    {

        $this->app['dispatcher']->addListener(ConsoleEvents::INIT, function(ConsoleEvent $event) use ($command) {
            $app = $event->getApplication();
            $app->add($command);
        });

    }
}
