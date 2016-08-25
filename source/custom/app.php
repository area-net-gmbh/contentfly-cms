<?php

//Konsolen-Befehle registrieren
$app['consoleManager']->addCommand(new \Custom\Command\DatabaseImport());
$app['consoleManager']->addCommand(new \Custom\Command\FileImport());
$app['consoleManager']->addCommand(new \Custom\Command\SizeImport());

//Custom-Type registrieren (z.B. individuelles Eingabefeld in den Formularen, z.B. Google Maps für Location)
$app['typeManager']->registerType(new \Custom\Classes\Types\TestType($app));

//Angular-Oberfläche anpassen
$app['uiManager']->addBlock('INDEX_NAVIGATION_ADMIN_SUB_APPEND', 'blocks/deploy.html');
$app['uiManager']->addBlock('LIST_TABLE_BODY_BUTTONS_PREPEND', 'blocks/list-button.html');
$app['uiManager']->addBlock('LIST_TABLE_BODY_COL_APPEND', 'blocks/col-append.html');

//Eigenen Angular-Controller mit Route registrieren
$app['uiManager']->addRoute('/custom/deploy', 'deploy.html', 'DeployCtrl');
$app['uiManager']->addJSFile('controllers/list-button.controller.js');

//Eigene CSS-Klasse registrieren
$app['uiManager']->addCSSFile('custom.css');


//Event-Hook registrieren, der am Ende des Controllers "api/schema" greift
$app['dispatcher']->addListener('pim.controller.after.api.schemaaction', function (\Areanet\PIM\Classes\Event $event) {
    $response   = $event->getParam('response');
    $content    = json_decode($response->getContent());
    $content->message = "mySchemaAction";
    $response->setContent(json_encode($content));
});

//Event-Hook registrieren, der am vor der Auflistung einer Entity (API-Aufruf "api/list" greift)
$app['dispatcher']->addListener('pim.entity.before.list', function (\Areanet\PIM\Classes\Event $event) {
    $entityName     = $event->getParam('entity');
    $queryBuilder   = $event->getParam('queryBuilder');

    //$queryBuilder->andWhere("$entityName.isIntern = true");
});

//Event-Hook registrieren, der nach dem Hinzufügen eines Objektes greift (z.B. Versenden einer E-Mail)
$app['dispatcher']->addListener('pim.entity.after.insert', function (\Areanet\PIM\Classes\Event $event) {

});


//Eigenen PHP-Kontroller registrieren
//Achtung: Bei der Route "/" muss die Admin-Route/-URL unter "$configDefault->FRONTEND_URL" gesetzt werden
$app->get('/', function () use ($app) {
    /*
    if($user = $app['auth']->getUser()){
        die($user->getAlias());
    }else{
        $app['auth']->login('admin', 'admin');

    }
    */

    return $app['twig']->render('index.twig', array(
        'foo' => 'bar'
    ));
});


