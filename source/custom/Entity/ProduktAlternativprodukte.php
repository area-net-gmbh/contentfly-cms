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
 * @ORM\Table(name="produkt_alternativprodukte")
 * @PIM\Config(hide=true)
 */
class ProduktAlternativprodukte extends BaseSortable
{
    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt", inversedBy="alternativprodukte")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produkt;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $alternativprodukt;

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
    public function getAlternativprodukt()
    {
        return $this->alternativprodukt;
    }

    /**
     * @param mixed $alternativprodukt
     */
    public function setAlternativprodukt($alternativprodukt)
    {
        $this->alternativprodukt = $alternativprodukt;
    }

    


}