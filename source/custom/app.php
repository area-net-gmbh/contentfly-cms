<?php

$customManager = new \Areanet\PIM\Classes\Manager\CustomManager($app);

$customManager->addCommand(new \Custom\Command\DatabaseImport());
$customManager->addCommand(new \Custom\Command\FileImport());
$customManager->addCommand(new \Custom\Command\SizeImport());
