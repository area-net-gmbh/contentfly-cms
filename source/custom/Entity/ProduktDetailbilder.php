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
 * @ORM\Table(name="produkt_bilder")
 * @PIM\Config(hide=true, label="Produkte - Detailbilder")
 */
class ProduktDetailbilder extends BaseSortable
{

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt", inversedBy="bilder")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produkt;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @PIM\Config(label="Detailbild", accept="image/*")
     */
    protected $bild;

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
    public function getBild()
    {
        return $this->bild;
    }

    /**
     * @param mixed $bild
     */
    public function setBild($bild)
    {
        $this->bild = $bild;
    }
    
    



}