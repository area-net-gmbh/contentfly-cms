<?php
include('appcms/bootstrap.php');

$statement = $app['database']->prepare("SELECT id,name FROM mandant LIMIT 1");
$statement->execute();

$object = $statement->fetch();
die("NAME=".$object['name']);