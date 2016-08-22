<?php
$versions = file_get_contents('http://www.das-app-cms.de/download/versions.php');
$versions = json_decode($versions);

$pdo = new PDO('mysql:host=localhost;dbname=redmine', 'root', 'Heex9ahn');

$data = "h1. Downloads APP-CMS\n\n";
foreach($versions as $version){
    $data .= "* Version version:".$version->version." - \"Download\":".$version->url."\n";
    if($version->boilerplate_url){
        $data .= " - \"Boilerplate\":".$version->boilerplate_url;
    }
    if($version->apidoc_url){
        $data .= " - \"API-Doku\":".$version->apidoc_url;
    }
    $data .= "\n";


    $statement = $pdo->prepare("UPDATE versions SET status = 'closed' WHERE project_id = 93 AND name = ?");
    $statement->execute(array($version->version));
}


$statement = $pdo->prepare("UPDATE wiki_contents SET text = ? WHERE id = 81");
$statement->execute(array($data));

