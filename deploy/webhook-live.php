<?php
$time = strftime("%Y-%m-%d %H:%M:%S", time());


$payload = file_get_contents("php://input");
file_put_contents('log-live.txt', $time."\n\n");

if($payload) {
    try {
        $payload = json_decode($payload);
    } catch(Exception $ex) {
        file_put_contents('log-live.txt', $time."\n".$ex."\n\n", FILE_APPEND);
        exit(0);
    }
    // put the branch you want here
    /*
    if($payload->ref != "refs/heads/master") {
        file_put_contents('log.txt', $time."\n"."Webhook failed: branche mismatch", FILE_APPEND);
        exit(0);
    }*/
    //put the branch you want here, as well as the directory your site is in
    $result = shell_exec('cd /html/pim-live && git pull origin master');
    file_put_contents('log.txt', $time."\n"."Webhook successful executed: ".$result, FILE_APPEND);
    $result = shell_exec('cd /html/pim-live/source && SERVER_NAME="live.pim.areanet-buehner.de" php_cli vendor/bin/doctrine orm:schema:update --force');
    file_put_contents('log.txt', $time."\n"."Webhook successful executed: ".$result, FILE_APPEND);
} else {
    file_put_contents('log.txt', $time."\n"."Webhook failed: payload error", FILE_APPEND);
}

?>