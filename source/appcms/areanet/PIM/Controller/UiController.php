<?php
namespace Areanet\PIM\Controller;

use Areanet\PIM\Classes\Controller\BaseController;
use Areanet\PIM\Entity\User;

class UiController extends BaseController
{

    public function showAction()
    {
        return $this->app->redirect('ui/default', 303);
    }
}