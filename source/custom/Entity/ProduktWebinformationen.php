<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt_webinformationen")
 * @PIM\Config(hide=true, label="Webinformationen")
 */
class ProduktWebinformationen extends Base
{
    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Teaser-Text")
     */
    protected $teaserText;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Marketing-Text")
     */
    protected $marketingText;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\USPText")
     * @ORM\JoinTable(name="produktwebinformaion_usptexte", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="USP-Texte")
     */
    protected $uspTexte;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\Ausverkaufsmeldung")
     * @ORM\JoinTable(name="produktwebinformaion_ausverkaufsmeldungen", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Ausverkaufsmeldungen")
     */
    protected $ausverkaufsmeldungen;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\Hinweistext")
     * @ORM\JoinTable(name="produktwebinformaion_hinweistexte", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Hinweistexte")
     */
    protected $hinweistexte;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="Neu")
     */
    protected $istNeu = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="FSC")
     */
    protected $istFsc = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="Express")
     */
    protected $istExpress = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="B-Ware")
     */
    protected $istBWare = false;

    /**
     * @ORM\Column(type="decimal", nullable=true)
     * @PIM\Config(label="B-Ware Rabatt")
     */
    protected $bWareRabatt;

    /**
     * @return mixed
     */
    public function getTeaserText()
    {
        return $this->teaserText;
    }

    /**
     * @param mixed $teaserText
     */
    public function setTeaserText($teaserText)
    {
        $this->teaserText = $teaserText;
    }

    /**
     * @return mixed
     */
    public function getMarketingText()
    {
        return $this->marketingText;
    }

    /**
     * @param mixed $marketingText
     */
    public function setMarketingText($marketingText)
    {
        $this->marketingText = $marketingText;
    }



    /**
     * @return mixed
     */
    public function getUspTexte()
    {
        return $this->uspTexte;
    }

    /**
     * @param mixed $uspTexte
     */
    public function setUspTexte($uspTexte)
    {
        $this->uspTexte = $uspTexte;
    }

    /**
     * @return mixed
     */
    public function getIstNeu()
    {
        return $this->istNeu;
    }

    /**
     * @param mixed $istNeu
     */
    public function setIstNeu($istNeu)
    {
        $this->istNeu = $istNeu;
    }

    /**
     * @return mixed
     */
    public function getIstFsc()
    {
        return $this->istFsc;
    }

    /**
     * @param mixed $istFsc
     */
    public function setIstFsc($istFsc)
    {
        $this->istFsc = $istFsc;
    }

    /**
     * @return mixed
     */
    public function getIstBWare()
    {
        return $this->istBWare;
    }

    /**
     * @param mixed $istBWare
     */
    public function setIstBWare($istBWare)
    {
        $this->istBWare = $istBWare;
    }

    /**
     * @return mixed
     */
    public function getBWareRabatt()
    {
        return $this->bWareRabatt;
    }

    /**
     * @param mixed $bWareRabatt
     */
    public function setBWareRabatt($bWareRabatt)
    {
        $this->bWareRabatt = $bWareRabatt;
    }

    /**
     * @return mixed
     */
    public function getIstExpress()
    {
        return $this->istExpress;
    }

    /**
     * @param mixed $istExpress
     */
    public function setIstExpress($istExpress)
    {
        $this->istExpress = $istExpress;
    }

    /**
     * @return mixed
     */
    public function getAusverkaufsmeldungen()
    {
        return $this->ausverkaufsmeldungen;
    }

    /**
     * @param mixed $ausverkaufsmeldungen
     */
    public function setAusverkaufsmeldungen($ausverkaufsmeldungen)
    {
        $this->ausverkaufsmeldungen = $ausverkaufsmeldungen;
    }

    /**
     * @return mixed
     */
    public function getHinweistexte()
    {
        return $this->hinweistexte;
    }

    /**
     * @param mixed $hinweistexte
     */
    public function setHinweistexte($hinweistexte)
    {
        $this->hinweistexte = $hinweistexte;
    }

    

}