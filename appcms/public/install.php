<?php
//BOILERPLATE

$db_host = null;
$db_name = null;
$db_user = null;
$db_pass = null;
$system_php = null;
$db_strategy_bool = null;

$dirsToCreate = array(
    'custom',
    'custom/Classes',
    'custom/Classes/Annotations',
    'custom/Classes/Types',
    'custom/Command',
    'custom/Entity',
    'custom/Frontend',
    'custom/Frontend/ui',
    'custom/Frontend/ui/default',
    'custom/Frontend/ui/default/img',
    'custom/Frontend/ui/default/scripts',
    'custom/Frontend/ui/default/scripts/controllers',
    'custom/Frontend/ui/default/styles',
    'custom/Frontend/ui/default/types',
    'custom/Frontend/ui/default/views',
    'custom/Frontend/ui/default/views/blocks',
    'custom/Traits',
    'custom/Views'
);


$filesToCreate = array(
    'custom/app.php' => "<?php
",

    'custom/version.php' => "<?php
define('CUSTOM_VERSION', '0.0.0');",

    'custom/Traits/File.php' => "<?php
namespace Custom\\Traits;

trait File{

}",

    'custom/Traits/Folder.php' => "<?php
namespace Custom\\Traits;

trait Folder{

}",


    'custom/Traits/Group.php' => "<?php
namespace Custom\\Traits;

trait Group{

}",


    'custom/Traits/User.php' => "<?php
namespace Custom\\Traits;

trait User{

}"
);

$configFileData = "<?php
use \\Areanet\\PIM\\Classes\\Config\\Factory;

\$configFactory = Factory::getInstance();

/*
 * Default Config
 */

\$configDefault = new \\Areanet\\PIM\\Classes\\Config();

\$configDefault->DB_HOST =  '\$db_host';
\$configDefault->DB_NAME = '\$db_name';
\$configDefault->DB_USER = '\$db_user';
\$configDefault->DB_PASS = '\$db_pass';
\$configDefault->DB_GUID_STRATEGY = \$db_strategy_bool;

\$configDefault->APP_DEBUG               = true;
\$configDefault->APP_ENABLE_SCHEMA_CACHE = false;

\$configDefault->SYSTEM_PHP_CLI_COMMAND = '\$system_php';

\$configFactory->setConfig(\$configDefault);

\$configDefault->DO_INSTALL = true;";

//START
if(file_exists(__DIR__.'/../../custom/config.php')){
    header('Location: /');
}

require_once('../version.php');

$error = null;

function isEnabled($func) {
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

function install($dirsToCreate, $filesToCreate, $configFileData, $system_php, $db_host, $db_name, $db_user, $db_pass, $db_strategy){

    $db_strategy_bool = $db_strategy ? 'true' : 'false';

    foreach($dirsToCreate as $dirToCreate){

        if(!mkdir(__DIR__."/../../$dirToCreate", 0755)){
            die("$dirToCreate konnte nicht erstellt werden!");
        }
    }

    foreach($filesToCreate as $fileToCreate => $fileContent){
        file_put_contents(__DIR__."/../../$fileToCreate", $fileContent);
    }

    $configFileData = str_replace('$db_host', $db_host, $configFileData);
    $configFileData = str_replace('$db_name', $db_name, $configFileData);
    $configFileData = str_replace('$db_user', $db_user, $configFileData);
    $configFileData = str_replace('$db_pass', $db_pass, $configFileData);
    $configFileData = str_replace('$db_strategy_bool', $db_strategy_bool, $configFileData);
    $configFileData = str_replace('$system_php', $system_php, $configFileData);
    file_put_contents(__DIR__."/../../custom/config.php", $configFileData);

    @chmod(__DIR__.'/../../custom/config.php', 0775);
    @chmod(__DIR__.'/../../data/files', 0775);
    @chmod(__DIR__.'/../../data/cache', 0775);

    shell_exec(('cd '.__DIR__.'/.. && SERVER_NAME="'.$_SERVER['SERVER_NAME'].'" '.$system_php.' vendor/bin/doctrine orm:schema:update --force'));

    header('Location: /setup');
}

function mask($input){
    return $input;
}

function check_install_errors($system_php, $db_host, $db_name, $db_user, $db_pass){

    //SHELL-EXEC
    if(!isEnabled('shell_exec')){
        return 'PHP-Funktion shell_exec() ist deaktiviert.';
    }

    //CHMOD
    if(!isEnabled('chmod')){
        return 'PHP-Funktion chmod() ist deaktiviert.';
    }

    //CLI
    $testCLI = shell_exec($system_php.' -v');
    if(strpos($testCLI, 'PHP') === false){
        return 'PHP-CLI konnte nicht unter '.$_POST['system_php'].' nicht aufgerufen werden.';
    }

    //MySQL
    try{
        $dbh = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    }catch(Exception $e){
        return $e->getMessage();
    }

    return;
}

if(!empty($_POST['start'])){
    $system_php             = mask($_POST['system_php']);
    $db_host                = mask($_POST['db_host']);
    $db_name                = mask($_POST['db_name']);
    $db_user                = mask($_POST['db_user']);
    $db_pass                = mask($_POST['db_pass']);
    $db_strategy            = mask($_POST['db_strategy']);

    if(!($error = check_install_errors($system_php, $db_host, $db_name, $db_user, $db_pass))){
        $error = install($dirsToCreate, $filesToCreate, $configFileData, $system_php, $db_host, $db_name, $db_user, $db_pass, $db_strategy);
    }


}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>:: APP-CMS Installation :::</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="page-header">
                <h1>APP-CMS <small>Installation</small></h1>
            </div>
        </div>
    </div>
    <form method="post">
    <input type="hidden" id="start" name="start" value="1">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <?php if($error):?>
                <div class="alert alert-danger" role="alert">
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                    <span class="sr-only">Fehler:</span>
                    <?php echo $error;?>
                </div>
            <?php endif;?>
            <h2>Datenbank</h2>
            <div class="form-group">
                <label for="db_host">Host</label>
                <input type="text" class="form-control" name="db_host" id="db_host" placeholder="DB-HOST" value="<?php echo isset($_POST['db_host']) ? $_POST['db_host'] : '';?>" required>
            </div>
            <div class="form-group">
                <label for="db_name">Name</label>
                <input type="text" class="form-control" name="db_name" id="db_name" placeholder="DB-NAME" value="<?php echo isset($_POST['db_name']) ? $_POST['db_name'] : '';?>" required >
            </div>
            <div class="form-group">
                <label for="db_user">Benutzer</label>
                <input type="text" class="form-control" name="db_user" id="db_user" placeholder="DB-USER" value="<?php echo isset($_POST['db_user']) ? $_POST['db_user'] : '';?>" required>
            </div>
            <div class="form-group">
                <label for="db_pass">Passwort</label>
                <input type="text" class="form-control" name="db_pass" id="db_pass" placeholder="DB-PASS" value="<?php echo isset($_POST['db_pass']) ? $_POST['db_pass'] : '';?>" required>
            </div>
            <h2>ID-Strategie</h2>
            <div class="form-group">
                <div class="radio">
                    <label>
                        <input type="radio" name="db_strategy" id="db_strategy_auto" value="guid" checked>
                        <b>GUID</b> (Synchronisations-Unterstützung)</label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="db_strategy" id="db_strategy_auto" value="auto">
                        <b>AUTO-INCREMENT</b> (Deprecated)</label>
                </div>
            </div>
            <h2>System</h2>
            <div class="form-group">
                <label for="system_php">PHP-CLI-EXECUTABLE</label>
                <input type="text" class="form-control" name="system_php" id="system_php" placeholder="PHP-CLI" value="<?php echo isset($_POST['system_php']) ? $_POST['system_php'] : 'php';?>"  required>
            </div>
            <button type="submit" name="btn_submit" class="btn btn-primary">APP-CMS jetzt installieren</button>
        </div>

    </div>
    </form>


</div>
</body>
</html>
