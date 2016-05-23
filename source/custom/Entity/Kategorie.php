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
 * @PIM\Config(type="tree")
 */
class Kategorie extends BaseSortable
{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Titel")
     */
    protected $titel;

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Kategorie", inversedBy="treeChilds")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     * @PIM\Config(showInList=60, label="Kategorie")
     */
    protected $treeParent;

    /**
     * @ORM\OneToMany(targetEntity="Custom\Entity\Kategorie", mappedBy="treeParent")
     */
    protected $treeChilds;

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
    public function getTreeParent()
    {
        return $this->treeParent;
    }

    /**
     * @param mixed $treeParent
     */
    public function setTreeParent($treeParent)
    {
        $this->treeParent = $treeParent;
    }

    /**
     * @return mixed
     */
    public function getTreeChilds()
    {
        return $this->treeChilds;
    }

    /**
     * @param mixed $treeChilds
     */
    public function setTreeChilds($treeChilds)
    {
        $this->treeChilds = $treeChilds;
    }






}