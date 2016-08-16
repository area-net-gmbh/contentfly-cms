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
 * @ORM\Table(name="produkt_ausverkaufprodukte")
 * @PIM\Config(hide=true, label="Produkte - Ausverkaufprodukte")
 */
class ProduktAusverkaufprodukte extends BaseSortable
{
    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produkt;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $ausverkaufprodukt;

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
    public function getAusverkaufprodukt()
    {
        return $this->ausverkaufprodukt;
    }

    /**
     * @param mixed $ausverkaufprodukt
     */
    public function setAusverkaufprodukt($ausverkaufprodukt)
    {
        $this->ausverkaufprodukt = $ausverkaufprodukt;
    }


    

    


}