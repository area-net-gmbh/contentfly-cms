<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;


/**
 * @ORM\Entity
 * @ORM\Table(name="pim_thumbnail_setting")
 * @PIM\Config(label="Bildgrößen")
 */
class ThumbnailSetting extends Base
{

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     * @PIM\Config(showInList=20, label="Alias")
     */
    protected $alias;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=25, label="Zuschneiden")
     */
    protected $doCut=0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @PIM\Config(showInList=30, label="Breite")
     */
    protected $width;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @PIM\Config(showInList=40, label="Höhe")
     */
    protected $height;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @PIM\Config(showInList=50, label="Prozentual")
     */
    protected $percent;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=60, label="HG-Farbe")
     */
    protected $backgroundColor;

    /**
    * @ORM\Column(type="boolean", nullable=true)
    * @PIM\Config(showInList=70, label="JPEG-Ausgabe erzwingen")
    */
    protected $forceJpeg=0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=80, label="Responsive-Varianten")
     */
    protected $isResponsive=0;



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
    public function getDoCut()
    {
        return $this->doCut;
    }

    /**
     * @param mixed $doCut
     */
    public function setDoCut($doCut)
    {
        $this->doCut = $doCut;
    }
    

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param mixed $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param mixed $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return mixed
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * @param mixed $percent
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;
    }

    /**
     * @return mixed
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param mixed $backgroundColor
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * @return mixed
     */
    public function getForceJpeg()
    {
        return $this->forceJpeg;
    }

    /**
     * @param mixed $forceJpeg
     */
    public function setForceJpeg($forceJpeg)
    {
        $this->forceJpeg = $forceJpeg;
    }

    /**
     * @return mixed
     */
    public function getIsResponsive()
    {
        return $this->isResponsive;
    }

    /**
     * @param mixed $isResponsive
     */
    public function setIsResponsive($isResponsive)
    {
        $this->isResponsive = $isResponsive;
    }

}
