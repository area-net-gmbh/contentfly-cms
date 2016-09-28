<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="referenzen")
 * @PIM\Config(label="Referenzen", sortBy="sorting")
 */
class Referenzen extends BaseSortable
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=20, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Kunde")
     */
    protected $kunde;


    /**
     * @ORM\Column(type="text", nullable = true)
     * @PIM\Config(label="Beschreibung")
     * @PIM\Rte()
     */
    protected $beschreibung;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Vorschaubild", accept="image/*")
     */
    protected $vorschaubild;

    /**
     * @ORM\ManyToMany(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinTable(name="referenzen_bilder", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Detailbilder", accept="image/*")
     */
    protected $bilder;

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
    public function getKunde()
    {
        return $this->kunde;
    }

    /**
     * @param mixed $kunde
     */
    public function setKunde($kunde)
    {
        $this->kunde = $kunde;
    }

    




}