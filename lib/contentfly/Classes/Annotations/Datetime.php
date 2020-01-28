<?php
namespace Areanet\Contentfly\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Datetime extends Annotation
{
    const DEFAULT_FORMAT = 'DD.MM.YYYY';
    /**
     * @var string
     */
    public $format = self::DEFAULT_FORMAT;
}
