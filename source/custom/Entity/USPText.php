<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="usptext")
 * @PIM\Config(label = "USP-Texte")
 */
class USPText extends Base
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="textarea", label="Hinweistext")
     */
    protected $hinweistext;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Bild", accept="image/*")
     */
    protected $bild;

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
    public function getHinweistext()
    {
        return $this->hinweistext;
    }

    /**
     * @param mixed $hinweistext
     */
    public function setHinweistext($hinweistext)
    {
        $this->hinweistext = $hinweistext;
    }

    /**
     * @return mixed
     */
    public function getBild()
    {
        return $this->bild;
    }

    /**
     * @param mixed $bild
     */
    public function setBild($bild)
    {
        $this->bild = $bild;
    }


}