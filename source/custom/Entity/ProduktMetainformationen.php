<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt_metainformationen")
 * @PIM\Config(hide=true, label="Metainformationen")
 */
class ProduktMetainformationen extends Base
{

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Browsertitel")
     */
    protected $browsertitel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="textarea", label="Beschreibung")
     */
    protected $beschreibung;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="textarea", label="Keywords")
     */
    protected $keywords;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="SEO-URL")
     */
    protected $seoUrl;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Kurz-URL")
     */
    protected $kurzUrl;

    /**
     * @return mixed
     */
    public function getBrowsertitel()
    {
        return $this->browsertitel;
    }

    /**
     * @param mixed $browsertitel
     */
    public function setBrowsertitel($browsertitel)
    {
        $this->browsertitel = $browsertitel;
    }

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
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param mixed $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return mixed
     */
    public function getSeoUrl()
    {
        return $this->seoUrl;
    }

    /**
     * @param mixed $seoUrl
     */
    public function setSeoUrl($seoUrl)
    {
        $this->seoUrl = $seoUrl;
    }

    /**
     * @return mixed
     */
    public function getKurzUrl()
    {
        return $this->kurzUrl;
    }

    /**
     * @param mixed $kurzUrl
     */
    public function setKurzUrl($kurzUrl)
    {
        $this->kurzUrl = $kurzUrl;
    }





}