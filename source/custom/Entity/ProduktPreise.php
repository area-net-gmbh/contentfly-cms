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
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @PIM\Config(label="Ab-Preis")
     */
    protected $abPreis;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="textarea", label="Ab-Preis-Text")
     */
    protected $abPreisText;

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
     * @param mixed $beginnHerbstpreise
     */
    public function setBeginnHerbstpreise($beginnHerbstpreise)
    {
        if($beginnHerbstpreise instanceof \Datetime){
            $this->beginnHerbstpreise = $beginnHerbstpreise;
        }else{
            $this->beginnHerbstpreise = new \Datetime($beginnHerbstpreise);
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
        if($endeHerbstpreise instanceof \Datetime){
            $this->endeHerbstpreise = $endeHerbstpreise;
        }else{
            $this->endeHerbstpreise = new \Datetime($endeHerbstpreise);
        }
    }




}