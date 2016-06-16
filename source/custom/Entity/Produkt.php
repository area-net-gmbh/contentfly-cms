<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt")
 * @PIM\Config(label = "Produkte", tabs="{'img': 'Bilder', 'cross': 'Cross-Selling', 'filter': 'Filter', 'files': 'Dateien'}")
 */
class Produkt extends Base
{

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=80, label="Aktiv")
     */
    protected $aktiv = 1;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=90, label="Versteckt")
     */
    protected $versteckt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @PIM\Config(showInList=95, label="Verfügbarkeit", type="select", options="1=grün: sofort lieferbar,2=gelb: nur wenige verfügbar,3=rot: leider ausverkauft")
     */
    protected $verfuegbarkeit = 1;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=20, label="Artikel")
     */
    protected $artikel;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=30, label="Formnummer")
     */
    protected $kuerzel;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="textarea", label="Keywords")
     */
    protected $keywords;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Vorschaubild", accept="image/*", tab="img")
     */
    protected $vorschaubild;

    /**
     * @ORM\OneToMany(targetEntity="Custom\Entity\ProduktDetailbilder", mappedBy="produkt")
     * @PIM\ManyToMany(targetEntity="Areanet\PIM\Entity\File", mappedBy="bild")
     * @PIM\Config(label="Detailbilder", tab="img")
     */
    protected $bilder;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\USPText")
     * @ORM\JoinTable(name="produkt_usptexte", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Allgemeine Bilder PopUp", tab="img")
     */
    protected $uspTexte;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Werbefläche-Skizze", accept="image/*", tab="img")
     */
    protected $skizze;

    /**
     * @ORM\OneToOne(targetEntity="Custom\Entity\ProduktBeschreibung")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Beschreibung")
     */
    protected $beschreibung;

    /**
     * @ORM\OneToOne(targetEntity="Custom\Entity\ProduktMetainformationen")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Metainformationen")
     */
    protected $metainformationen;

    /**
     * @ORM\OneToOne(targetEntity="Custom\Entity\ProduktWebinformationen")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Webinformationen")
     */
    protected $webinformationen;

    /**
     * @ORM\OneToOne(targetEntity="Custom\Entity\ProduktPreise")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Preise")
     */
    protected $preise;

    /**
     * @ORM\OneToMany(targetEntity="Custom\Entity\ProduktAlternativprodukte", mappedBy="produkt")
     * @PIM\ManyToMany(targetEntity="Custom\Entity\Produkt", mappedBy="alternativprodukt")
     * @PIM\Config(label="Alternativ-Produkte", tab="cross")
     */
    protected $alternativprodukte;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinTable(name="produkt_zubehoerprodukte",
     *     joinColumns={@ORM\JoinColumn(name="produkt_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="produkt_zubehor_id", onDelete="CASCADE")})
     * @PIM\Config(label="Zubehör-Produkte", tab="cross")
     */
    protected $zubehoerprodukte;
    
    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\Filteroption")
     * @ORM\JoinTable(name="produkt_filteroption", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Filter", tab="filter")
     */
    protected $filter;
    
    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Technisches Datenblatt", accept="*", tab="files")
     */
    protected $technischesDatenblatt;

    /**
     * @ORM\ManyToMany(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinTable(name="produkt_dateien_digitalvorlagen", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Digitalvorlagen", accept="application/pdf", tab="files")
     */
    protected $digitalvorlagen;

    /**
     * @ORM\OneToMany(targetEntity="Custom\Entity\KategorieProdukte", mappedBy="produkt")
     * @PIM\ManyToMany(targetEntity="Custom\Entity\Kategorie", mappedBy="kategorie")
     * @PIM\Config(label="Kategorien", readonly=true, isFilterable=true)
     */
    protected $kategorien;


    /**
     * @return mixed
     */
    public function getAktiv()
    {
        return $this->aktiv;
    }

    /**
     * @param mixed $aktiv
     */
    public function setAktiv($aktiv)
    {
        $this->aktiv = $aktiv;
    }

    /**
     * @return mixed
     */
    public function getVersteckt()
    {
        return $this->versteckt;
    }

    /**
     * @param mixed $versteckt
     */
    public function setVersteckt($versteckt)
    {
        $this->versteckt = $versteckt;
    }

    /**
     * @return mixed
     */
    public function getArtikel()
    {
        return $this->artikel;
    }

    /**
     * @param mixed $artikel
     */
    public function setArtikel($artikel)
    {
        $this->artikel = $artikel;
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
    public function getKuerzel()
    {
        return $this->kuerzel;
    }

    /**
     * @param mixed $kuerzel
     */
    public function setKuerzel($kuerzel)
    {
        $this->kuerzel = $kuerzel;
    }

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
    public function getVorschaubild()
    {
        return $this->vorschaubild;
    }

    /**
     * @param mixed $vorschaubild
     */
    public function setVorschaubild($vorschaubild)
    {
        $this->vorschaubild = $vorschaubild;
    }
    
    /**
     * @return mixed
     */
    public function getBilder()
    {
        return $this->bilder;
    }

    /**
     * @param mixed $bilder
     */
    public function setBilder($bilder)
    {
        $this->bilder = $bilder;
    }

    /**
     * @return mixed
     */
    public function getMetainformationen()
    {
        return $this->metainformationen;
    }

    /**
     * @param mixed $metainformationen
     */
    public function setMetainformationen($metainformationen)
    {
        $this->metainformationen = $metainformationen;
    }

    /**
     * @return mixed
     */
    public function getPreise()
    {
        return $this->preise;
    }

    /**
     * @param mixed $preise
     */
    public function setPreise($preise)
    {
        $this->preise = $preise;
    }

    /**
     * @return mixed
     */
    public function getWebinformationen()
    {
        return $this->webinformationen;
    }

    /**
     * @param mixed $webinformationen
     */
    public function setWebinformationen($webinformationen)
    {
        $this->webinformationen = $webinformationen;
    }

    /**
     * @return mixed
     */
    public function getAlternativprodukte()
    {
        return $this->alternativprodukte;
    }

    /**
     * @param mixed $alternativprodukte
     */
    public function setAlternativprodukte($alternativprodukte)
    {
        $this->alternativprodukte = $alternativprodukte;
    }

    /**
     * @return mixed
     */
    public function getZubehoerprodukte()
    {
        return $this->zubehoerprodukte;
    }

    /**
     * @param mixed $zubehoerprodukte
     */
    public function setZubehoerprodukte($zubehoerprodukte)
    {
        $this->zubehoerprodukte = $zubehoerprodukte;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function getDigitalvorlagen()
    {
        return $this->digitalvorlagen;
    }

    /**
     * @param mixed $digitalvorlagen
     */
    public function setDigitalvorlagen($digitalvorlagen)
    {
        $this->digitalvorlagen = $digitalvorlagen;
    }

    /**
     * @return mixed
     */
    public function getTechnischesDatenblatt()
    {
        return $this->technischesDatenblatt;
    }

    /**
     * @param mixed $technischesDatenblatt
     */
    public function setTechnischesDatenblatt($technischesDatenblatt)
    {
        $this->technischesDatenblatt = $technischesDatenblatt;
    }

    /**
     * @return mixed
     */
    public function getKategorien()
    {
        return $this->kategorien;
    }

    /**
     * @param mixed $kategorien
     */
    public function setKategorien($kategorien)
    {
        $this->kategorien = $kategorien;
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
    public function getVerfuegbarkeit()
    {
        return $this->verfuegbarkeit;
    }

    /**
     * @param mixed $verfuegbarkeit
     */
    public function setVerfuegbarkeit($verfuegbarkeit)
    {
        $this->verfuegbarkeit = $verfuegbarkeit;
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
    public function getSkizze()
    {
        return $this->skizze;
    }

    /**
     * @param mixed $skizze
     */
    public function setSkizze($skizze)
    {
        $this->skizze = $skizze;
    }
}