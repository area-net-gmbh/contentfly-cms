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
 * @PIM\Config(label = "Allgemeine PopUp-Texte")
 */
class USPText extends Base
{
    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Thumbnail", accept="image/*")
     */
    protected $thumbnail;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Thumbnail-Text")
     */
    protected $titel;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Bildüberschrift groß")
     */
    protected $hinweistitel;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\File")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(label="Bild groß", accept="image/*")
     */
    protected $bild;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(label="Bildunterschrift groß")
     * @PIM\Rte();
     */
    protected $hinweistext;





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
    public function getHinweistitel()
    {
        return $this->hinweistitel;
    }

    /**
     * @param mixed $hinweistitel
     */
    public function setHinweistitel($hinweistitel)
    {
        $this->hinweistitel = $hinweistitel;
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
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param mixed $thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
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