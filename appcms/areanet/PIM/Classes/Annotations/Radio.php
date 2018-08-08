<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Radio extends Annotation
{
    /**
     * @var string
     */
    public $group = null;

    /**
    * @var boolean
    */
    public $horizontalAlignment = false;

    /**
     * @var boolean
     */
    public $select = false;

    /**
     * @var integer
     */
    public $columns = 4;

}