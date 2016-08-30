<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="textblock")
 * @PIM\Config(label = "Textblock")
 */
class Textblock extends Base
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=20, label="Alias")
     */
    protected $alias;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=40, listShorten=200, label="Textzeile")
     */
    protected $textzeile;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(showInList=60, listShorten=100, label="Textblock")
     * @PIM\Rte();
     */
    protected $textblock;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @PIM\Config(showInList=80, label="Datum")
     */
    protected $time;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @PIM\Config(label="Bild", accept="image/*")
     */
    protected $bild;

    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param mixed $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return mixed
     */
    public function getTextzeile()
    {
        return $this->textzeile;
    }

    /**
     * @param mixed $textzeile
     */
    public function setTextzeile($textzeile)
    {
        $this->textzeile = $textzeile;
    }

    /**
     * @return mixed
     */
    public function getTextblock()
    {
        return $this->textblock;
    }

    /**
     * @param mixed $textblock
     */
    public function setTextblock($textblock)
    {
        $this->textblock = $textblock;
    }

    /**
     * @return mixed
     */
    public function getBild()
    {
        return $this->bild;
    }

    /**
     * @param mixed $bild
     */
    public function setBild($bild)
    {
        $this->bild = $bild;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    

    


}