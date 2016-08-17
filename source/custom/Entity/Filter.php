<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Custom\Classes\Annotations as CUSTOM;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\BaseTree;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="filter")
 * @PIM\Config(label="Filter", sortBy="sorting")
 */
class Filter extends BaseSortable
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=60, label="Titel")
     * @CUSTOM\Test()
     */
    protected $test;

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
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param mixed $test
     */
    public function setTest($test)
    {
        $this->test = $test;
    }



    
}