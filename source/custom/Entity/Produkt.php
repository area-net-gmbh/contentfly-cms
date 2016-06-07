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
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=20, label="Formnummer")
     */
    protected $formnummer;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=30, label="Kürzel")
     */
    protected $kuerzel;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $titel;

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
     * @ORM\ManyToMany(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinTable(name="produkt_alternativprodukte",
     *     joinColumns={@ORM\JoinColumn(name="produkt_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="produkt_alternativ_id", onDelete="CASCADE")})
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
     * @ORM\OneToMany(targetEntity="Custom\Entity\ProduktFilterOption", mappedBy="produkt")
     * @PIM\MatrixChooser(target1Entity="Custom\Entity\Filter", mapped1By="filter",
     *                    target2Entity="Custom\Entity\Filteroption", mapped2By="option")
     * @PIM\Config(label="Filter", tab="filter")
     */
    protected $filterOptionen;

    /**
     * @ORM\ManyToMany(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinTable(name="produkt_dateien_layoutvorlagen", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Layoutvorlagen", accept="*", tab="files")
     */
    protected $layoutvorlagen;

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
    public function getFormnummer()
    {
        return $this->formnummer;
    }

    /**
     * @param mixed $formnummer
     */
    public function setFormnummer($formnummer)
    {
        $this->formnummer = $formnummer;
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
    public function getFilterOptionen()
    {
        return $this->filterOptionen;
    }

    /**
     * @param mixed $filterOptionen
     */
    public function setFilterOptionen($filterOptionen)
    {
        $this->filterOptionen = $filterOptionen;
    }

    /**
     * @return mixed
     */
    public function getLayoutvorlagen()
    {
        return $this->layoutvorlagen;
    }

    /**
     * @param mixed $layoutvorlagen
     */
    public function setLayoutvorlagen($layoutvorlagen)
    {
        $this->layoutvorlagen = $layoutvorlagen;
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


       


}