<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_navItem")
 * @PIM\Config(label="Eintrag", sortRestrictTo="nav")
 */
class NavItem extends BaseSortable
{

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\Nav")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Bereich", showInList=15, isFilterable=true)
     */
    protected $nav;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Entity", showInList=20)
     * @PIM\EntitySelector()
     */
    protected $entity;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Titel", showInList=30)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="URL", showInList=40)
     */
    protected $uri;

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

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
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return mixed
     */
    public function getNav()
    {
        return $this->nav;
    }

    /**
     * @param mixed $nav
     */
    public function setNav($nav)
    {
        $this->nav = $nav;
    }









}
