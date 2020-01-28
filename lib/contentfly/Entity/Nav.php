<?php
namespace Areanet\Contentfly\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\Contentfly\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_nav")
 * @PIM\Config(label="Bereich", labelProperty="title")
 */
class Nav extends BaseSortable
{

    /**
     * @ORM\Column(type="string", nullable=false)
     * @PIM\Config(label="Name", showInList=20)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Icon", showInList=30)
     */
    protected $icon;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }




}
