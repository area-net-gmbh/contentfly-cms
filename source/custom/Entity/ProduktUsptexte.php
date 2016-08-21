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
 * @ORM\Table(name="produkt_usptexte")
 * @PIM\Config(hide=true, label="Produkte - Allgemeine PopUp-Texte")
 */
class ProduktUsptexte extends BaseSortable
{
    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Produkt", inversedBy="uspTexte")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produkt;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\USPText")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $usptext;

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
    public function getUsptext()
    {
        return $this->usptext;
    }

    /**
     * @param mixed $usptext
     */
    public function setUsptext($usptext)
    {
        $this->usptext = $usptext;
    }

}