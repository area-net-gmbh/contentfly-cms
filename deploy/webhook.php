<?php

if($payload = file_get_contents('php://input')) {
    try {
        $payload = json_decode($payload);
    } catch(Exception $ex) {
        echo $ex;
        exit(0);
    }
    // put the branch you want here
    if($payload->ref != "refs/heads/master") {
        echo "wrong head";
        exit(0);
    }
    //put the branch you want here, as well as the directory your site is in
    $result = `cd /html/pim && git pull origin master`;

    echo $result;
} else {
    echo "failed request";
}

?>