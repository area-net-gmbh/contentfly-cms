<?php
namespace Areanet\Contentfly\Classes\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
* @Annotation
*/
final class Config extends Annotation
{
    /**
     * @var integer
     */
    public $viewMode = 0;

    /**
     * @var integer
     */
    public $showInList = 0;

    /**
     * @var boolean
     */
    public $excludeFromSync = false;

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
    public $encoded = false;

    /**
     * @var boolean
     */
    public $unique = false;

    /**
     * @var boolean
     */
    public $isFilterable = false;

    /**
     * @var boolean
     */
    public $isDatalist = false;

    /**
     * @var boolean
     */
    public $isSidebar = false;

    /**
     * @var boolean
     */
    public $i18n_universal=false;

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
    public $sortBy = null;

    /**
     * @var string
     */
    public $sortOrder = null;

    /**
     * @var string
     */
    public $sortRestrictTo = null;


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
     * @var integer
     */
    public $sort = 1000;

    /**
     * @var string
     */
    public $tabs = null;

    /**
     * @var string
     */
    public $tab = 'default';

}