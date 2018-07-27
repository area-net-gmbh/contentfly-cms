<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_option")
 * @PIM\Config(label="Option", labelProperty="value", sortBy="value", sortOrder="ASC")
 */
class Option extends Base
{

    /**
     * @ORM\Column(type="string", nullable=false)
     * @PIM\Config(label="Wert", showInList=20)
     */
    protected $value;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\OptionGroup")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @PIM\Config(label="Checkbox Gruppe", showInList=15, isFilterable=true, readonly=true)
     */
    protected $group;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

}



