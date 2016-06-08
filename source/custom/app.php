<?php
$customManager = new \Areanet\PIM\Classes\Manager\CustomManager($app);
$customManager->addCommand(new \Custom\Command\AccessImport());
$customManager->addCommand(new \Custom\Command\DatabaseImport());
