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
    public $toolbar = "[['h1','h2','h3', 'h4', 'p','pre', 'quote', 'undo','redo','clear', 'html'],['bold','italics', 'ul', 'ol', 'indent', 'outdent', 'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull', 'insertLink']]";
}
