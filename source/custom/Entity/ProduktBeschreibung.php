<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt_beschreibung")
 * @PIM\Config(hide=true, label="Beschreibung")
 */
class ProduktBeschreibung extends Base
{

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(label="Beschreibung")
     */
    protected $beschreibung;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Format 1")
     */
    protected $format1;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Format 2")
     */
    protected $format2;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Format 3")
     */
    protected $format3;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Werbefläche 1")
     */
    protected $werbeflaeche1;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Werbefläche 2")
     */
    protected $werbeflaeche2;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Werbefläche 3")
     */
    protected $werbeflaeche3;

    /**
     * @return mixed
     */
    public function getBeschreibung()
    {
        return $this->beschreibung;
    }

    /**
     * @param mixed $beschreibung
     */
    public function setBeschreibung($beschreibung)
    {
        $this->beschreibung = $beschreibung;
    }

    /**
     * @return mixed
     */
    public function getFormat1()
    {
        return $this->format1;
    }

    /**
     * @param mixed $format1
     */
    public function setFormat1($format1)
    {
        $this->format1 = $format1;
    }

    /**
     * @return mixed
     */
    public function getFormat2()
    {
        return $this->format2;
    }

    /**
     * @param mixed $format2
     */
    public function setFormat2($format2)
    {
        $this->format2 = $format2;
    }

    /**
     * @return mixed
     */
    public function getFormat3()
    {
        return $this->format3;
    }

    /**
     * @param mixed $format3
     */
    public function setFormat3($format3)
    {
        $this->format3 = $format3;
    }

    /**
     * @return mixed
     */
    public function getWerbeflaeche1()
    {
        return $this->werbeflaeche1;
    }

    /**
     * @param mixed $werbeflaeche1
     */
    public function setWerbeflaeche1($werbeflaeche1)
    {
        $this->werbeflaeche1 = $werbeflaeche1;
    }

    /**
     * @return mixed
     */
    public function getWerbeflaeche2()
    {
        return $this->werbeflaeche2;
    }

    /**
     * @param mixed $werbeflaeche2
     */
    public function setWerbeflaeche2($werbeflaeche2)
    {
        $this->werbeflaeche2 = $werbeflaeche2;
    }

    /**
     * @return mixed
     */
    public function getWerbeflaeche3()
    {
        return $this->werbeflaeche3;
    }

    /**
     * @param mixed $werbeflaeche3
     */
    public function setWerbeflaeche3($werbeflaeche3)
    {
        $this->werbeflaeche3 = $werbeflaeche3;
    }


    



}