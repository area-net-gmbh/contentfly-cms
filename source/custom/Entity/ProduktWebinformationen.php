<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt_webinformationen")
 * @PIM\Config(hide=true, label="Produkte - Webinformationen")
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
     * @PIM\Config(label="Marketing-Text")
     * @PIM\Rte();
     */
    protected $marketingText;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(label="USP-Liste DE")
     * @PIM\Textarea()
     */
    protected $uspDe;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(label="USP-Liste AT")
     * @PIM\Textarea()
     */
    protected $uspAt;

    /**
     * @ORM\OneToMany(targetEntity="Custom\Entity\ProduktWebinformationenAusverkaufsmeldungen", mappedBy="produktwebinformation")
     * @PIM\ManyToMany(targetEntity="Custom\Entity\Ausverkaufsmeldung", mappedBy="ausverkaufsmeldung")
     * @PIM\Config(label="Ausverkaufsmeldungen")
     */
    protected $ausverkaufsmeldungen;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\Hinweistext")
     * @ORM\JoinTable(name="produktwebinformation_hinweistexte", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Hinweistexte")
     */
    protected $hinweistexte;

    /**
     * @ORM\OneToMany(targetEntity="Custom\Entity\ProduktWebinformationenTextbloecke", mappedBy="produktwebinformation")
     * @PIM\ManyToMany(targetEntity="Custom\Entity\Textblock", mappedBy="textblock")
     * @PIM\Config(label="Textblöcke")
     */
    protected $textbloecke;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="Neu")
     */
    protected $istNeu = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="FSC zertifiziert")
     */
    protected $istFsc = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(label="FSC auf Wunsch")
     */
    protected $istFscWunsch = false;

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
     * ProduktWebinformationen constructor.
     * @param $teaserText
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->hinweistexte = new ArrayCollection();
    }


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
     * @return mixed
     */
    public function getUspDe()
    {
        return $this->uspDe;
    }

    /**
     * @param mixed $uspDe
     */
    public function setUspDe($uspDe)
    {
        $this->uspDe = $uspDe;
    }

    /**
     * @return mixed
     */
    public function getUspAt()
    {
        return $this->uspAt;
    }

    /**
     * @param mixed $uspAt
     */
    public function setUspAt($uspAt)
    {
        $this->uspAt = $uspAt;
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

    /**
     * @return mixed
     */
    public function getTextbloecke()
    {
        return $this->textbloecke;
    }

    /**
     * @param mixed $textbloecke
     */
    public function setTextbloecke($textbloecke)
    {
        $this->textbloecke = $textbloecke;
    }

    
    /**
     * @return mixed
     */
    public function getIstFscWunsch()
    {
        return $this->istFscWunsch;
    }

    /**
     * @param mixed $istFscWunsch
     */
    public function setIstFscWunsch($istFscWunsch)
    {
        $this->istFscWunsch = $istFscWunsch;
    }

    

}