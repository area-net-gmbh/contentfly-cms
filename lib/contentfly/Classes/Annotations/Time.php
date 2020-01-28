<?php
namespace Areanet\Contentfly\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Time extends Annotation
{
    const DEFAULT_FORMAT = 'H:i';
    /**
     * @var string
     */
    public $format = self::DEFAULT_FORMAT;
}
