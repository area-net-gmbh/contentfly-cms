<?php

$app['consoleManager']->addCommand(new \Custom\Command\DatabaseImport());
$app['consoleManager']->addCommand(new \Custom\Command\FileImport());
$app['consoleManager']->addCommand(new \Custom\Command\SizeImport());

$app['uiManager']->addBlock('NAVIGATION_PREPEND', 'blocks/deploy.html');
$app['uiManager']->addBlock('LIST_BUTTON', 'blocks/list-button.html');

$app['uiManager']->addRoute('/custom/deploy', 'deploy.html', 'DeployCtrl');
$app['uiManager']->addJSFile('controllers/list-button.controller.js');

$app['dispatcher']->addListener('pim.after.api.controller.schemaaction', function (\Areanet\PIM\Classes\Event $event) {
    $response   = $event->getParam('response');
    $content    = json_decode($response->getContent());
    $content->message = "mySchemaAction";
    $response->setContent(json_encode($content));
});
