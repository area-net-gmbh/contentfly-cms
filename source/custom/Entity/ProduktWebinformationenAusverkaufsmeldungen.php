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
 * @ORM\Table(name="produktwebinformation_ausverkaufsmeldungen")
 * @PIM\Config(hide=true, label="Produkte - Webinformationen- Ausverkaufsmeldungen")
 */
class ProduktWebinformationenAusverkaufsmeldungen extends BaseSortable
{
    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\ProduktWebinformationen", inversedBy="ausverkaufsmeldungen")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $produktwebinformation;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Ausverkaufsmeldung")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $ausverkaufsmeldung;

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
    public function getAusverkaufsmeldung()
    {
        return $this->ausverkaufsmeldung;
    }

    /**
     * @param mixed $ausverkaufsmeldung
     */
    public function setAusverkaufsmeldung($ausverkaufsmeldung)
    {
        $this->ausverkaufsmeldung = $ausverkaufsmeldung;
    }



}