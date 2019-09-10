<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class ConfigIf extends Annotation
{
    /**
     * @var string
     */
    public $property = null;

    /**
     * @var string
     */
    public $equals = null;

    /**
     * @var string
     */
    public $label = null;

    /**
     * @var boolean
     */
    public $readonly = false;

    /**
     * @var boolean
     */
    public $hide = false;
}
