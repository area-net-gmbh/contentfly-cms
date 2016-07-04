<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt_preise")
 * @PIM\Config(hide=true, label="Preise")
 */
class ProduktPreise extends Base
{
    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Kalk-Modus")
     * @PIM\Select(options="normal=Normal, formular=Formular, versteckt=Versteckt")
     */
    protected $kalkModus = 'normal';

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @PIM\Config(label="Ab-Preis")
     */
    protected $abPreis;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(label="Ab-Preis-Text")
     * @PIM\Textarea()
     */
    protected $abPreisText;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @PIM\Config(label="Herbstpreis")
     */
    protected $herbstpreis;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @PIM\Config(label="Beginn Herbstpreise")
     */
    protected $beginnHerbstpreise;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @PIM\Config(label="Ende Herbstpreise")
     */
    protected $endeHerbstpreise;

    /**
     * @return mixed
     */
    public function getKalkModus()
    {
        return $this->kalkModus;
    }

    /**
     * @param mixed $kalkModus
     */
    public function setKalkModus($kalkModus)
    {
        $this->kalkModus = $kalkModus;
    }


    /**
     * @return mixed
     */
    public function getAbPreis()
    {
        return $this->abPreis;
    }

    /**
     * @param mixed $abPreis
     */
    public function setAbPreis($abPreis)
    {
        $this->abPreis = $abPreis;
    }

    /**
     * @return mixed
     */
    public function getAbPreisText()
    {
        return $this->abPreisText;
    }

    /**
     * @param mixed $abPreisText
     */
    public function setAbPreisText($abPreisText)
    {
        $this->abPreisText = $abPreisText;
    }

    /**
     * @return mixed
     */
    public function getBeginnHerbstpreise()
    {
        return $this->beginnHerbstpreise;
    }

    /**
     * @return mixed
     */
    public function getHerbstpreis()
    {
        return $this->herbstpreis;
    }

    /**
     * @param mixed $herbstpreis
     */
    public function setHerbstpreis($herbstpreis)
    {
        $this->herbstpreis = $herbstpreis;
    }

    /**
     * @param mixed $beginnHerbstpreise
     */
    public function setBeginnHerbstpreise($beginnHerbstpreise)
    {
        if($beginnHerbstpreise) {
            if ($beginnHerbstpreise instanceof \Datetime) {
                $this->beginnHerbstpreise = $beginnHerbstpreise;
            } else {
                $this->beginnHerbstpreise = new \Datetime($beginnHerbstpreise);
            }
        }else{
            $this->beginnHerbstpreise = null;
        }
    }

    /**
     * @return mixed
     */
    public function getEndeHerbstpreise()
    {
        return $this->endeHerbstpreise;
    }

    /**
     * @param mixed $endeHerbstpreise
     */
    public function setEndeHerbstpreise($endeHerbstpreise)
    {
        if($endeHerbstpreise) {
            if ($endeHerbstpreise instanceof \Datetime) {
                $this->endeHerbstpreise = $endeHerbstpreise;
            } else {
                $this->endeHerbstpreise = new \Datetime($endeHerbstpreise);
            }
        }else{
            $this->endeHerbstpreise = null;
        }
    }




}