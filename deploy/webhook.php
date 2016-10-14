<?php
$time = strftime("%Y-%m-%d %H:%M:%S", time());


$payload = file_get_contents("php://input");
file_put_contents('log.txt', $time."\n\n");

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
    unlink('/html/dev/source/data/cache/schema.cache');
    $result = shell_exec('cd /html/dev && git pull origin master');
    file_put_contents('log.txt', $time."\n"."Webhook successful executed: ".$result, FILE_APPEND);
    $result = shell_exec('cd /html/dev/source/appcms && SERVER_NAME="dev.das-app-cms.de" php_cli vendor/bin/doctrine orm:schema:update --force');
    file_put_contents('log.txt', $time."\n"."Webhook successful executed: ".$result, FILE_APPEND);

    
    //APP-CMS Dev-Version
    $version = 'dev';
    
    $output  = "\n" . shell_exec("mkdir appcms-$version");
    $output .= "\n" . shell_exec("cp -R --preserve=links ../source/appcms appcms-$version");
    $output .= "\n" . shell_exec("zip -ry appcms-$version.zip appcms-$version");
    $output .= "\n" . shell_exec("rm -rf ../../_releases/appcms-$version");
    $output .= "\n" . shell_exec("cp -R appcms-$version ../../_releases/");
    $output .= "\n" . shell_exec("rm -rf appcms-$version/");
    $output .= "\n" . shell_exec("mv appcms-$version.zip ../../www/download/");
    file_put_contents('log.txt', $time."\n"."Dev-Version builded: ".$output, FILE_APPEND);

    //BOILDERPLATE-DEV
    $output .= "\n" . shell_exec("mkdir boilerplate-$version");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Classes");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Classes/Annotations");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Classes/Types");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Command");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Entity");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default/img");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default/scripts");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default/scripts/controllers");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default/styles");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default/types");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default/views");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Frontend/ui/default/views/blocks");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Traits");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/custom/Views");

    $output .= "\n" . shell_exec("mkdir boilerplate-$version/data/");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/data/cache");
    $output .= "\n" . shell_exec("mkdir boilerplate-$version/data/files");

    $output .= "\n" . shell_exec("cp ../template/app.php boilerplate-$version/custom/app.php");
    $output .= "\n" . shell_exec("cp ../template/config.php boilerplate-$version/custom/config.php");
    $output .= "\n" . shell_exec("cp ../template/version.php boilerplate-$version/custom/version.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/File.php boilerplate-$version/custom/Traits/File.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/Folder.php boilerplate-$version/custom/Traits/Folder.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/Group.php boilerplate-$version/custom/Traits/Group.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/User.php boilerplate-$version/custom/Traits/User.php");
    $output .= "\n" . shell_exec("zip -r boilerplate-$version.zip boilerplate-$version");
    $output .= "\n" . shell_exec("rm -rf boilerplate-$version");
    $output .= "\n" . shell_exec("mv boilerplate-$version.zip ../../www/download/");
} else {
    file_put_contents('log.txt', $time."\n"."Webhook failed: payload error", FILE_APPEND);
}

?>