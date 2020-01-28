<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 26.04.17
 * Time: 15:20
 */

namespace Areanet\PIM\Classes\Controller\Provider;


class Route
{
    const POST = 'post';
    const GET  = 'get';
    const MATCH  = 'match';

    public $method      = Route::POST;
    public $route       = null;
    public $isSecure    = false;
    public $action      = null;
}