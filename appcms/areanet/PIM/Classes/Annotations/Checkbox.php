<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Checkbox extends Annotation
{
    /**
     * @var string
     */
    public $group = null;
}