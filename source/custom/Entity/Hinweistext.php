<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="hinweistext")
 * @PIM\Config(label = "Hinweistext")
 */
class Hinweistext extends Base
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=20, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(showInList=40, listShorten=200, label="Text")
     */
    protected $text;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @PIM\Config(showInList=60, label="von")
     * @PIM\Select(options="1=Januar,2=Februar,3=März,4=April,5=Mai,6=Juni,7=Juli,8=August,9=September,10=Oktober,11=November,12=Dezember")
     */
    protected $vonMonat;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @PIM\Config(showInList=70, label="bis")
     * @PIM\Select(options="1=Januar,2=Februar,3=März,4=April,5=Mai,6=Juni,7=Juli,8=August,9=September,10=Oktober,11=November,12=Dezember")
     */
    protected $bisMonat;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="wichtig")
     */
    protected $wichtig;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="rot")
     */
    protected $rot;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @PIM\Config(label="SortKey")
     */
    protected $sortkey;

    /**
     * @return mixed
     */
    public function getTitel()
    {
        return $this->titel;
    }

    /**
     * @param mixed $titel
     */
    public function setTitel($titel)
    {
        $this->titel = $titel;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return mixed
     */
    public function getVonMonat()
    {
        return $this->vonMonat;
    }

    /**
     * @param mixed $vonMonat
     */
    public function setVonMonat($vonMonat)
    {
        $this->vonMonat = $vonMonat;
    }

    /**
     * @return mixed
     */
    public function getBisMonat()
    {
        return $this->bisMonat;
    }

    /**
     * @param mixed $bisMonat
     */
    public function setBisMonat($bisMonat)
    {
        $this->bisMonat = $bisMonat;
    }

    /**
     * @return mixed
     */
    public function getWichtig()
    {
        return $this->wichtig;
    }

    /**
     * @param mixed $wichtig
     */
    public function setWichtig($wichtig)
    {
        $this->wichtig = $wichtig;
    }

    /**
     * @return mixed
     */
    public function getRot()
    {
        return $this->rot;
    }

    /**
     * @param mixed $rot
     */
    public function setRot($rot)
    {
        $this->rot = $rot;
    }

    /**
     * @return mixed
     */
    public function getSortkey()
    {
        return $this->sortkey;
    }

    /**
     * @param mixed $sortkey
     */
    public function setSortkey($sortkey)
    {
        $this->sortkey = $sortkey;
    }



}