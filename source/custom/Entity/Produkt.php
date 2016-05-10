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
 * @PIM\Config(tabs="{'img': 'Bilder'}")
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
     * @PIM\Config(showInList=30, label="Formnummer")
     */
    protected $formnummer;

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
     * @ORM\ManyToMany(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinTable(name="produkt_detailbilder", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Detailbilder", accept="image/*", tab="img")
     */
    protected $detailbilder;

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
    public function getDetailbilder()
    {
        return $this->detailbilder;
    }

    /**
     * @param mixed $detailbilder
     */
    public function setDetailbilder($detailbilder)
    {
        $this->detailbilder = $detailbilder;
    }




}