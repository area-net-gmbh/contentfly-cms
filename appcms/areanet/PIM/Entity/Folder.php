<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 13.07.16
 * Time: 09:34
 */

namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_folder")
 * @PIM\Config(label="Ordner", labelProperty="title")
 */

class Folder extends BaseTree
{
    use \Custom\Traits\Folder;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Titel", showInList=30)
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