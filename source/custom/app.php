<?php
$app->get('/', function () use ($app) {

    $products = $app['orm.em']->getRepository('Custom\\Entity\\Produkt')->findAll();
    var_dump($products);
    die("test");
});

//$app['uiManager']->addBlock('LIST_HEADER_FILTER_APPEND', 'blocks/liste-filteroption.html');
//$app['uiManager']->addBlock('LIST_TABLE_BODY_BUTTONS_PREPEND', 'blocks/liste-filter.html');
//$app['uiManager']->addBlock('FORM_APPEND', 'blocks/comments.html');
//$app['uiManager']->addJSFile('directives/comments.directive.js');

$app->get('/user', function () use ($app) {
    if(($user = $app['auth']->getUser())){
        var_dump($user);
        die();
    }else{
        die("GESPERRT");
    }
});

$app->get('/login', function () use ($app) {
    $app['auth']->login('admin', 'admin');
    die("LOGIN");
});

$app->get('/logout', function () use ($app) {
    $app['auth']->logout();
    die("LOGOUT");
});

