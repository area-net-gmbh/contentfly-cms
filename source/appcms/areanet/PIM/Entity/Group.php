<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_group")
 * @PIM\Config(label="Gruppe")
 */
class Group extends Base
{
    use \Custom\Traits\Group;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     * @PIM\Config(showInList=30, label="Name")
     */
    protected $name;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    



}