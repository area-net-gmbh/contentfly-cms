<?php
/**
 * Created by PhpStorm.
 * User: ms
 * Date: 30.08.16
 * Time: 10:01
 */

namespace Areanet\PIM\Classes;


class Helper
{
    public function getFullEntityName($entityShortName)
    {
        if (substr($entityShortName, 0, 3) == 'PIM') {
            $entityNameToLoad = 'Areanet\PIM\Entity\\' . substr($entityShortName, 4);
        } else {
            $entityNameToLoad = 'Custom\Entity\\' . ucfirst($entityShortName);
        }

        return $entityNameToLoad;
    }
}