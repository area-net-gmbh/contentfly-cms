<?php
namespace Areanet\Contentfly\Classes\Command;

use Knp\Command\Command;

class CustomCommand extends Command
{

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $name = 'custom:'.$name;
        parent::setName($name);

        return $this;
    }
}