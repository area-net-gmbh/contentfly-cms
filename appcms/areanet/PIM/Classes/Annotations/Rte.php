<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Rte extends Annotation
{
    /**
     * @var string
     */
    public $toolbar = "formatselect | bold italic strikethrough subscript superscript | alignleft aligncenter alignright | bullist numlist outdent indent | link unlink anchor | undo redo | code";


    /**
     * @var string
     */
    public $extend = "";
}
