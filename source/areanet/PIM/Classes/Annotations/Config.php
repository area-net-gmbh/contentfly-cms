<?php
namespace Areanet\PIM\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
* @Annotation
*/
final class Config extends Annotation
{
    /**
     * @var integer
     */
    public $showInList = 0;

    /**
     * @var integer
     */
    public $listShorten = 0;

    /**
     * @var boolean
     */
    public $hide = false;

    /**
     * @var boolean
     */
    public $isFilterable = false;

    /**
     * @var boolean
     */
    public $readonly = false;

    /**
    * @var string
    */
    public $label = '';

    /**
     * @var string
     */
    public $labelProperty = '';

    /**
     * @var string
     */
    public $type = '';
    
    /**
     * @var string
     */
    public $pushText = null;

    /**
     * @var string
     */
    public $pushTitle = null;

    /**
     * @var string
     */
    public $pushObject = null;

    /**
     * @var string
     */
    public $sortBy = null;

    /**
     * @var string
     */
    public $sortOrder = null;

    /**
     * @var integer
     */
    public $lines = 4;

    /**
     * @var string
     */
    public $accept = '*';

    /**
     * @var string
     */
    public $filter = null;

    /**
     * @var string
     */
    public $tabs = null;

    /**
     * @var string
     */
    public $tab = 'default';

}