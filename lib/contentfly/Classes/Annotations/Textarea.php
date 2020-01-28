<?php
namespace Areanet\Contentfly\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Textarea extends Annotation
{
    /**
     * @var integer
     */
    public $lines = 4;
}