<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Areanet\PIM\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="hinweistext")
 * @PIM\Config(label = "Hinweistext")
 */
class Hinweistext extends Base
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=20, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(showInList=40, listShorten=200, label="Text")
     */
    protected $text;

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
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }


}