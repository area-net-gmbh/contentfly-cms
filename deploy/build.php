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
    if(!$payload->checkout_sha){
        exit(0);
    }

    $version = str_replace('refs/tags/', '', $payload->ref);

    $output = '';
    
    //APP-CMS
    $output .= "\n" . shell_exec("mkdir appcms-$version");
    $output .= "\n" . shell_exec("cp -R ../source/appcms appcms-$version");
    $output .= "\n" . shell_exec("zip -r appcms-$version.zip appcms-$version");
    $output .= "\n" . shell_exec("rm -rf ../../_releases/appcms-$version");
    $output .= "\n" . shell_exec("cp -R appcms-$version ../../_releases/");
    $output .= "\n" . shell_exec("rm -rf ../../_releases/_current");
    $output .= "\n" . shell_exec("mkdir ../../_releases/_current");
    $output .= "\n" . shell_exec("cp appcms-$version/appcms ../../_releases/_current");
    $output .= "\n" . shell_exec("rm -rf appcms-$version/");
    $output .= "\n" . shell_exec("mv appcms-$version.zip ../../www/download/");
    
    //BOILDERPLATE
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

    $output .= "\n" . shell_exec("cp ../template/app.php boilerplate-$version/custom/app.php");
    $output .= "\n" . shell_exec("cp ../template/config.php boilerplate-$version/custom/config.php");
    $output .= "\n" . shell_exec("cp ../template/version.php boilerplate-$version/custom/version.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/File.php boilerplate-$version/custom/Traits/File.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/File.php boilerplate-$version/custom/Traits/Folder.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/File.php boilerplate-$version/custom/Traits/Group.php");
    $output .= "\n" . shell_exec("cp ../template/Traits/File.php boilerplate-$version/custom/Traits/User.php");
    $output .= "\n" . shell_exec("zip -r boilerplate-$version.zip boilerplate-$version");
    $output .= "\n" . shell_exec("rm -rf boilerplate-$version");
    $output .= "\n" . shell_exec("mv boilerplate-$version.zip ../../www/download/");

    file_put_contents('build-log.txt', $time."\n".$output."\n\n".json_encode($payload), FILE_APPEND);
}else {
    file_put_contents('build-log.txt', $time."\n"."Webhook failed: payload error", FILE_APPEND);
}