<?php

//if($payload = file_get_contents('php://input')) {
if($payload = json_decode($_REQUEST['payload'])) {
    try {
        $payload = json_decode($payload);
    } catch(Exception $ex) {
        file_put_contents('log.txt', $ex);
        exit(0);
    }
    // put the branch you want here
    if($payload->ref != "refs/heads/master") {
        file_put_contents('log.txt', "failed request");
        exit(0);
    }
    //put the branch you want here, as well as the directory your site is in
    $result = `cd /html/pim && git pull origin master`;
    file_put_contents('log.txt', "OK");
    echo $result;
} else {
    file_put_contents('log.txt', "failed request");
}

?>