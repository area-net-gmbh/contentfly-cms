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
 * @ORM\Table(name="kategorie_produkte")
 * @PIM\Config(hide=true, label="Kategorien zu Produkte Zuordnung")
 */
class KategorieProdukte extends BaseSortable
{
    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Kategorie", inversedBy="produktVerknuepfungen")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $kategorie;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt", inversedBy="kategorien")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produkt;

    /**
     * @return mixed
     */
    public function getKategorie()
    {
        return $this->kategorie;
    }

    /**
     * @param mixed $kategorie
     */
    public function setKategorie($kategorie)
    {
        $this->kategorie = $kategorie;
    }

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

    

}