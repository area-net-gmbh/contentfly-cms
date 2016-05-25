<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\MappedSuperclass
 */

class BaseSortable extends Base
{
    /**
     * @ORM\Column(type="integer", options={"default" = 0}, nullable=true)
     * @PIM\Config(hide=true, label="Position")
     */
    protected $sorting = 0;

    /**
     * @return mixed
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @param mixed $sorting
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }


}