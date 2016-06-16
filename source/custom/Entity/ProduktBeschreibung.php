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
     * @PIM\Config(label="Format Titel")
     */
    protected $formatTitel;

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
     * @PIM\Config(label="Werbefläche Titel")
     */
    protected $werbeflaecheTitel;

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
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Kalendarium Titel")
     */
    protected $kalendariumTitel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Kalendarium")
     */
    protected $kalendarium;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Kalenderfarben Titel")
     */
    protected $kalenderfarbenTitel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Kalenderfarben")
     */
    protected $kalenderfarben;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Ausstattung Titel")
     */
    protected $ausstattungTitel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Ausstattung")
     */
    protected $ausstattung;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Ausführung Titel")
     */
    protected $ausfuehrungTitel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Ausführung")
     */
    protected $ausfuehrung;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Ländervarianten Titel")
     */
    protected $laendervariantenTitel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Ländervarianten")
     */
    protected $laendervarianten;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Verarbeitung Titel")
     */
    protected $verarbeitungTitel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Verarbeitung")
     */
    protected $verarbeitung;

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
    public function getFormatTitel()
    {
        return $this->formatTitel;
    }

    /**
     * @param mixed $formatTitel
     */
    public function setFormatTitel($formatTitel)
    {
        $this->formatTitel = $formatTitel;
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
    public function getWerbeflaecheTitel()
    {
        return $this->werbeflaecheTitel;
    }

    /**
     * @param mixed $werbeflaecheTitel
     */
    public function setWerbeflaecheTitel($werbeflaecheTitel)
    {
        $this->werbeflaecheTitel = $werbeflaecheTitel;
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

    /**
     * @return mixed
     */
    public function getKalendariumTitel()
    {
        return $this->kalendariumTitel;
    }

    /**
     * @param mixed $kalendariumTitel
     */
    public function setKalendariumTitel($kalendariumTitel)
    {
        $this->kalendariumTitel = $kalendariumTitel;
    }

    /**
     * @return mixed
     */
    public function getKalendarium()
    {
        return $this->kalendarium;
    }

    /**
     * @param mixed $kalendarium
     */
    public function setKalendarium($kalendarium)
    {
        $this->kalendarium = $kalendarium;
    }

    /**
     * @return mixed
     */
    public function getAusstattungTitel()
    {
        return $this->ausstattungTitel;
    }

    /**
     * @param mixed $ausstattungTitel
     */
    public function setAusstattungTitel($ausstattungTitel)
    {
        $this->ausstattungTitel = $ausstattungTitel;
    }

    /**
     * @return mixed
     */
    public function getAusstattung()
    {
        return $this->ausstattung;
    }

    /**
     * @param mixed $ausstattung
     */
    public function setAusstattung($ausstattung)
    {
        $this->ausstattung = $ausstattung;
    }

    /**
     * @return mixed
     */
    public function getAusfuehrungTitel()
    {
        return $this->ausfuehrungTitel;
    }

    /**
     * @param mixed $ausfuehrungTitel
     */
    public function setAusfuehrungTitel($ausfuehrungTitel)
    {
        $this->ausfuehrungTitel = $ausfuehrungTitel;
    }

    /**
     * @return mixed
     */
    public function getAusfuehrung()
    {
        return $this->ausfuehrung;
    }

    /**
     * @param mixed $ausfuehrung
     */
    public function setAusfuehrung($ausfuehrung)
    {
        $this->ausfuehrung = $ausfuehrung;
    }

    /**
     * @return mixed
     */
    public function getKalenderfarbenTitel()
    {
        return $this->kalenderfarbenTitel;
    }

    /**
     * @param mixed $kalenderfarbenTitel
     */
    public function setKalenderfarbenTitel($kalenderfarbenTitel)
    {
        $this->kalenderfarbenTitel = $kalenderfarbenTitel;
    }

    /**
     * @return mixed
     */
    public function getKalenderfarben()
    {
        return $this->kalenderfarben;
    }

    /**
     * @param mixed $kalenderfarben
     */
    public function setKalenderfarben($kalenderfarben)
    {
        $this->kalenderfarben = $kalenderfarben;
    }

    /**
     * @return mixed
     */
    public function getVerarbeitungTitel()
    {
        return $this->verarbeitungTitel;
    }

    /**
     * @param mixed $verarbeitungTitel
     */
    public function setVerarbeitungTitel($verarbeitungTitel)
    {
        $this->verarbeitungTitel = $verarbeitungTitel;
    }

    /**
     * @return mixed
     */
    public function getVerarbeitung()
    {
        return $this->verarbeitung;
    }

    /**
     * @param mixed $verarbeitung
     */
    public function setVerarbeitung($verarbeitung)
    {
        $this->verarbeitung = $verarbeitung;
    }

    /**
     * @return mixed
     */
    public function getLaendervariantenTitel()
    {
        return $this->laendervariantenTitel;
    }

    /**
     * @param mixed $laendervariantenTitel
     */
    public function setLaendervariantenTitel($laendervariantenTitel)
    {
        $this->laendervariantenTitel = $laendervariantenTitel;
    }

    /**
     * @return mixed
     */
    public function getLaendervarianten()
    {
        return $this->laendervarianten;
    }

    /**
     * @param mixed $laendervarianten
     */
    public function setLaendervarianten($laendervarianten)
    {
        $this->laendervarianten = $laendervarianten;
    }
  
}