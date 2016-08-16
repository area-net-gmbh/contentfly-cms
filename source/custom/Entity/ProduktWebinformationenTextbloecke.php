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
 * @ORM\Table(name="produktwebinformation_textbloecke")
 * @PIM\Config(hide=true, label="Produkte - Webinformationen - Textblöcke")
 */
class ProduktWebinformationenTextbloecke extends BaseSortable
{
    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\ProduktWebinformationen")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produktwebinformation;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Textblock")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $textblock;

    /**
     * @return mixed
     */
    public function getProduktwebinformation()
    {
        return $this->produktwebinformation;
    }

    /**
     * @param mixed $produktwebinformation
     */
    public function setProduktwebinformation($produktwebinformation)
    {
        $this->produktwebinformation = $produktwebinformation;
    }

    /**
     * @return mixed
     */
    public function getTextblock()
    {
        return $this->textblock;
    }

    /**
     * @param mixed $textblock
     */
    public function setTextblock($textblock)
    {
        $this->textblock = $textblock;
    }


}