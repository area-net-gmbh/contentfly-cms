<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="firma")
 * @PIM\Config(label="Firmen")
 */
class Firma extends Base
{
    /**
     * @ORM\Column(type="string", nullable=false)
     * @PIM\Config(showInList=40, label="Name")
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=60, label="Strasse")
     */
    protected $strasse;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=80, label="PLZ")
     */
    protected $plz;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=100, label="ORT")
     */
    protected $ort;

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
    public function getStrasse()
    {
        return $this->strasse;
    }

    /**
     * @param mixed $strasse
     */
    public function setStrasse($strasse)
    {
        $this->strasse = $strasse;
    }

    /**
     * @return mixed
     */
    public function getPlz()
    {
        return $this->plz;
    }

    /**
     * @param mixed $plz
     */
    public function setPlz($plz)
    {
        $this->plz = $plz;
    }

    /**
     * @return mixed
     */
    public function getOrt()
    {
        return $this->ort;
    }

    /**
     * @param mixed $ort
     */
    public function setOrt($ort)
    {
        $this->ort = $ort;
    }




}