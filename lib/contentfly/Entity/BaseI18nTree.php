<?php
namespace Areanet\Contentfly\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\Contentfly\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\Table(name="pim_i18n_tree")
*/

class BaseI18nTree extends BaseI18nSortable
{
    /**
     * @ORM\ManyToOne(targetEntity="Areanet\Contentfly\Entity\BaseI18nTree", inversedBy="treeChilds")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     * @PIM\Config(showInList=60, label="Eltern-Objekt", isFilterable=true, isSidebar=true, i18n_universal=true)
     */
    protected $treeParent;

    /**
     * @ORM\OneToMany(targetEntity="Areanet\Contentfly\Entity\BaseTree", mappedBy="treeParent")
     */
    protected $treeChilds;

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