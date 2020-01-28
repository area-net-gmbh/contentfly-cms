<?php
namespace Areanet\Contentfly\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class MatrixChooser extends Annotation
{
    /**
     * @var string
     */
    public $target1Entity = '';

    /**
     * @var string
     */
    public $mapped1By = '';

    /**
     * @var string
     */
    public $target2Entity = '';

    /**
     * @var string
     */
    public $mapped2By = '';

}