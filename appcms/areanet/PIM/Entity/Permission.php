<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_permission")
 * @PIM\Config(label="Berechtigungen", hide=true)
 */
class Permission extends Base
{
    const NONE    = 0;
    const OWN     = 1;
    const GROUP   = 3;
    const ALL     = 2;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\Group", inversedBy="permissions")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    protected $group;

    /**
     * @ORM\Column(type="string",)
     * @PIM\Config(showInList=30, label="Entity")
     */
    protected $entityName;

    /**
     * @ORM\Column(type="integer")
     * @PIM\Config(showInList=40, label="Lesen")
     */
    protected $readable;

    /**
     * @ORM\Column(type="integer")
     * @PIM\Config(showInList=50, label="Bearbeiten")
     */
    protected $writable;

    /**
     * @ORM\Column(type="integer")
     * @PIM\Config(showInList=60, label="Löschen")
     */
    protected $deletable;

    /**
     * @ORM\Column(type="text", nullable = true)
     * @PIM\Config(label="Erweitert")
     */
    protected $extended;

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



    /**
     * @return mixed
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param mixed $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @return mixed
     */
    public function getReadable()
    {
        return $this->readable;
    }

    /**
     * @param mixed $readable
     */
    public function setReadable($readable)
    {
        $this->readable = $readable;
    }

    /**
     * @return mixed
     */
    public function getWritable()
    {
        return $this->writable;
    }

    /**
     * @param mixed $writable
     */
    public function setWritable($writable)
    {
        $this->writable = $writable;
    }

    /**
     * @return mixed
     */
    public function getDeletable()
    {
        return $this->deletable;
    }

    /**
     * @param mixed $deletable
     */
    public function setDeletable($deletable)
    {
        $this->deletable = $deletable;
    }

    /**
     * @return mixed
     */
    public function getExtended()
    {
        return $this->extended;
    }

    /**
     * @param mixed $extended
     */
    public function setExtended($extended)
    {
        $this->extended = $extended;
    }
    





}