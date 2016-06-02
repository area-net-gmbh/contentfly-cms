<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\BaseSortable;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="test")
 */
class Test extends BaseSortable
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $title;


    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $title2;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Hauptprodukt")
     */
    protected $hauptprodukt;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\Produkt")
     * @ORM\JoinTable(name="test_alternativprodukte", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Alternativprodukte")
     */
    protected $alternativprodukte;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle2()
    {
        return $this->title2;
    }

    /**
     * @param mixed $title2
     */
    public function setTitle2($title2)
    {
        $this->title2 = $title2;
    }
    

    /**
     * @return mixed
     */
    public function getHauptprodukt()
    {
        return $this->hauptprodukt;
    }

    /**
     * @param mixed $hauptprodukt
     */
    public function setHauptprodukt($hauptprodukt)
    {
        $this->hauptprodukt = $hauptprodukt;
    }

    /**
     * @return mixed
     */
    public function getAlternativprodukte()
    {
        return $this->alternativprodukte;
    }

    /**
     * @param mixed $alternativprodukte
     */
    public function setAlternativprodukte($alternativprodukte)
    {
        $this->alternativprodukte = $alternativprodukte;
    }

    


}