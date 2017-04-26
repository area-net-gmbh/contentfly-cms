<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Virtualjoin extends Annotation
{
    /**
     * @var string
     */
    public $targetEntity = '';

}