<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 14.07.16
 * Time: 16:55
 */

namespace Areanet\Contentfly\Classes;


class Event extends \Symfony\Component\EventDispatcher\Event implements \Iterator
{
    protected $params = array();

    public function setParam($key, $object){
        $this->params[$key] = $object;
    }

    public function getParam($key){
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    public function current()
    {
        return current($this->params);
    }

    public function next()
    {
        return next($this->params);
    }

    public function key()
    {
        return key($this->params);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function rewind()
    {
        reset($this->params);
    }


}