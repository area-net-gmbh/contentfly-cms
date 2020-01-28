<?php
namespace Areanet\Contentfly\Classes\Manager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Areanet\Contentfly\Classes\Command\CustomCommand;
use Areanet\Contentfly\Classes\Manager;
use Knp\Console\ConsoleEvent;
use Knp\Console\ConsoleEvents;
use Silex\Application;

class ConsoleManager extends Manager
{
    /**
     * @param ConsoleManager $command
     */
    public function addCommand(CustomCommand $command)
    {
        $this->app->extend('dispatcher', function (EventDispatcherInterface $dispatcher, $app) use ($command) {
            $dispatcher->addListener(ConsoleEvents::INIT, function (ConsoleEvent $event) use ($command) {
                $app = $event->getApplication();
                $app->add($command);
            });

            return $dispatcher;
        });
    }
}
