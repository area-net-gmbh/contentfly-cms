<?php
namespace Areanet\Contentfly\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\Contentfly\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_tag")
 * @PIM\Config(label="Tag", labelProperty="title", sortBy="title", sortOrder="ASC")
 */
class Tag extends Base
{

    /**
     * @ORM\Column(type="string", unique=true)
     * @PIM\Config(label="Name", showInList=20, unique=true)
     */
    protected $title;

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


}
