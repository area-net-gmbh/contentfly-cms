<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Areanet\PIM\Entity\File;
use Areanet\PIM\Entity\LinkSortable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt_digitalvorlagen")
 * @PIM\Config(hide=true, label="Produkte - Digitalvorlagen")
 */
class ProduktDigitalvorlagen extends BaseSortable
{

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produkt;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @PIM\Config(label="Digitalvorlage", accept="application/pdf")
     */
    protected $datei;

    /**
     * @return mixed
     */
    public function getProdukt()
    {
        return $this->produkt;
    }

    /**
     * @param mixed $produkt
     */
    public function setProdukt($produkt)
    {
        $this->produkt = $produkt;
    }

    /**
     * @return mixed
     */
    public function getDatei()
    {
        return $this->datei;
    }

    /**
     * @param mixed $datei
     */
    public function setDatei($datei)
    {
        $this->datei = $datei;
    }
}