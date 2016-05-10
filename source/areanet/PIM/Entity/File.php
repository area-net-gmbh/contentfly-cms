<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_file")
 */

class File extends Base
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(label="Name", readonly=true, showInList=30)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Titel", showInList=40)
     */
    protected $title;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(label="Dateityp", hide=true, showInList=50)
     */
    protected $type;

    /**
     * @ORM\Column(type="string", unique=true)
     * @PIM\Config(hide=true)
     */
    protected $hash;

    /**
     * @ORM\Column(type="integer")
     * @PIM\Config(label="Dateigröße", hide=true, showInList=60)
     */
    protected $size;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(hide=true, label="Versteckt")
     */
    protected $isHidden;

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return mixed
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * @param mixed $isHidden
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;
    }

    

    public function toValueObject()
    {
        $properties = parent::toValueObject();

        return $properties;
    }




}