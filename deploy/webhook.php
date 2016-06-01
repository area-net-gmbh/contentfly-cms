<?php
$time = strftime("%Y-%m-%d %H:%M:%S", time());


$payload = file_get_contents("php://input");
file_put_contents('log.txt', $time."\n".$input, FILE_APPEND);

if($payload) {
    try {
        $payload = json_decode($payload);
    } catch(Exception $ex) {
        file_put_contents('log.txt', $time."\n".$ex."\n\n", FILE_APPEND);
        exit(0);
    }
    // put the branch you want here
    /*
    if($payload->ref != "refs/heads/master") {
        file_put_contents('log.txt', $time."\n"."Webhook failed: branche mismatch", FILE_APPEND);
        exit(0);
    }*/
    //put the branch you want here, as well as the directory your site is in
    $result = `cd /html/pim && git pull origin master`;
    file_put_contents('log.txt', $time."\n"."Webhook successful executed", FILE_APPEND);
    echo $result;
} else {
    file_put_contents('log.txt', $time."\n"."Webhook failed: payload error", FILE_APPEND);
}

?>