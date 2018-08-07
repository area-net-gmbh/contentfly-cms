<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 06.08.18
 * Time: 15:52
 */

namespace Areanet\PIM\Classes\Exceptions;


class ContentflyI18NException extends \Exception
{
    protected $entity   = null;
    protected $lang     = null;

    public function __construct($message, $value, $lang) {
        parent::__construct($message, 550);

        $this->entity = $value;
        $this->lang = $lang;
    }

    /**
     * @return null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return null
     */
    public function getLang()
    {
        return $this->lang;
    }


}