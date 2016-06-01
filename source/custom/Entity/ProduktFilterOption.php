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
 * @ORM\Table(name="produkt_filter_option")
 * @PIM\Config(hide=true)
 */
class ProduktFilterOption extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produkt;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Filter")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @PIM\Config(showInList=30, label="Filter")
     */
    protected $filter;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Filteroption")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @PIM\Config(showInList=40, label="Option")
     */
    protected $option;

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
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param mixed $option
     */
    public function setOption($option)
    {
        $this->option = $option;
    }


}