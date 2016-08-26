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
 * @ORM\Table(name="filteroption")
 * @PIM\Config(label="Filter-Option", sortBy="sorting")
 */
class Filteroption extends BaseSortable
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Filter")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @PIM\Config(label="Filter", showInList=50, isFilterable=true)
     */
    protected $filter;

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
    
    


}