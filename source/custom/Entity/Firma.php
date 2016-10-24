<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="firma")
 * @PIM\Config(label="Firmen")
 */
class Firma extends Base
{
    /**
     * @ORM\Column(type="string", nullable=false)
     * @PIM\Config(showInList=40, label="Name")
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=60, label="Strasse")
     */
    protected $strasse;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=80, label="PLZ")
     */
    protected $plz;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=100, label="ORT")
     */
    protected $ort;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Logo")
     */
    protected $logo;

    /**
     * @ORM\ManyToMany(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinTable(name="firma_bilder", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Bilder")
     */
    protected $bilder;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getStrasse()
    {
        return $this->strasse;
    }

    /**
     * @param mixed $strasse
     */
    public function setStrasse($strasse)
    {
        $this->strasse = $strasse;
    }

    /**
     * @return mixed
     */
    public function getPlz()
    {
        return $this->plz;
    }

    /**
     * @param mixed $plz
     */
    public function setPlz($plz)
    {
        $this->plz = $plz;
    }

    /**
     * @return mixed
     */
    public function getOrt()
    {
        return $this->ort;
    }

    /**
     * @param mixed $ort
     */
    public function setOrt($ort)
    {
        $this->ort = $ort;
    }

    /**
     * @return mixed
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param mixed $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
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

    




}