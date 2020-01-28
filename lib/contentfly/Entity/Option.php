<?php
namespace Areanet\Contentfly\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\Contentfly\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_option")
 * @PIM\Config(label="Option", labelProperty="value", sortBy="sorting", sortOrder="ASC", sortRestrictTo="group")
 */
class Option extends BaseSortable
{

    /**
     * @ORM\Column(type="string", nullable=false)
     * @PIM\Config(label="Wert", showInList=20)
     */
    protected $value;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\Contentfly\Entity\OptionGroup")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @PIM\Config(label="Optionen Gruppe", showInList=15, isFilterable=true, readonly=true)
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



