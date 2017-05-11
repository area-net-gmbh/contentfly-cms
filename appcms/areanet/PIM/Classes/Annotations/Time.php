<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Time extends Annotation
{
    /**
     * @var string
     */
    public $format = 'H:i';
}
