<?php
define('ROOT_DIR', __DIR__);

$time = strftime("%Y-%m-%d %H:%M:%S", time());


$payload = file_get_contents("php://input");
file_put_contents('build-log.txt', $time."\n\n");

if($payload) {
    try {
        $payload = json_decode($payload);
    } catch(Exception $ex) {
        file_put_contents('build-log.txt', $time."\n".$ex."\n\n", FILE_APPEND);
        exit(0);
    }
    $version = str_replace('refs/tags/', '', $payload->ref);

    $output = '';
    $output = "\n" . shell_exec("mkdir appcms-$version");
    $output = "\n" . shell_exec("cp -R ../source/appcms appcms-$version");
    $output = "\n" . shell_exec("zip -r appcms-$version .");
    $output = "\n" . shell_exec("rm -rf appcms-$version");

    file_put_contents('build-log.txt', $time."\n".json_encode($payload), FILE_APPEND);
}else {
    file_put_contents('build-log.txt', $time."\n"."Webhook failed: payload error", FILE_APPEND);
}