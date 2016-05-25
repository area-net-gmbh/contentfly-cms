<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class ManyToMany extends Annotation
{
    /**
     * @var string
     */
    public $targetEntity = '';

    /**
     * @var string
     */
    public $mappedBy = '';

}