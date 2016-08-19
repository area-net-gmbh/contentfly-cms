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
     * @PIM\Config(showInList=60, label="Test")
     * @CUSTOM\Test()
     */
    protected $test;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @PIM\Config(showInList=60, label="Test 2")
     */
    protected $test2;

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

    /**
     * @return mixed
     */
    public function getTest2()
    {
        return $this->test2;
    }

    /**
     * @param mixed $test2
     */
    public function setTest2($test2)
    {
        $this->test2 = $test2;
    }





    
}