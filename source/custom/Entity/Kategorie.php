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
 * @ORM\Table(name="kategorie")
 */
class Kategorie extends BaseTree
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\OneToMany(targetEntity="Custom\Entity\Kategorie2Produkte", mappedBy="kategorie")
     * @PIM\Config(label="Produkte")
     */
    protected $produktVerknuepfungen;


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
    public function getProduktVerknuepfungen()
    {
        return $this->produktVerknuepfungen;
    }

    /**
     * @param mixed $produktVerknuepfungen
     */
    public function setProduktVerknuepfungen($produktVerknuepfungen)
    {
        $this->produktVerknuepfungen = $produktVerknuepfungen;
    }


    
    

}