<?php
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

    file_put_contents('build-log.txt', $time."\n".json_encode($payload), FILE_APPEND);
}else {
    file_put_contents('build-log.txt', $time."\n"."Webhook failed: payload error", FILE_APPEND);
}