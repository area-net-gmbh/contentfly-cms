<?php
//BOILERPLATE

$db_host = null;
$db_name = null;
$db_user = null;
$db_pass = null;
$system_php = null;
$db_strategy_bool = null;


//START
$SET_DB_GUID_STRATEGY = null;

require_once('../areanet/PIM/Classes/Config/Factory.php');
require_once('../areanet/PIM/Classes/Config.php');
require_once('../version.php');
require_once('../../custom/config.php');

if($configDefault->DB_HOST != '$SET_DB_HOST'){
    header('Location: /');
}

$error = null;

function isEnabled($func) {
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

function install($system_php, $db_host, $db_name, $db_user, $db_pass, $db_strategy){

    $db_strategy_bool = $db_strategy ? 'true' : 'false';

    $configFileData = file_get_contents(__DIR__."/../../custom/config.php");
    
    $configFileData = str_replace('$SET_DB_HOST', $db_host, $configFileData);
    $configFileData = str_replace('$SET_DB_NAME', $db_name, $configFileData);
    $configFileData = str_replace('$SET_DB_USER', $db_user, $configFileData);
    $configFileData = str_replace('$SET_DB_PASS', $db_pass, $configFileData);
    $configFileData = str_replace('$SET_DB_GUID_STRATEGY', $db_strategy_bool, $configFileData);
    $configFileData = str_replace('$SET_SYSTEM_PHP_CLI_COMMAND', $system_php, $configFileData);
    file_put_contents(__DIR__."/../../custom/config.php", $configFileData);

    shell_exec(('cd '.__DIR__.'/.. && SERVER_NAME="'.$_SERVER['SERVER_NAME'].'" '.$system_php.' vendor/bin/doctrine orm:schema:update --force'));
    shell_exec(('cd '.__DIR__.'/.. && SERVER_NAME="'.$_SERVER['SERVER_NAME'].'" '.$system_php.' console.php appcms:setup'));

    header('Location: /');
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
        return 'PHP-CLI konnte unter '.$_POST['system_php'].' nicht aufgerufen werden.';
    }

    //MySQL
    try{
        $dbh = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    }catch(Exception $e){
        return $e->getMessage();
    }

    @chmod(__DIR__.'/../../custom/config.php', 0775);
    @chmod(__DIR__.'/../../data/files', 0775);
    @chmod(__DIR__.'/../../data/cache', 0775);

    if(!is_writable(__DIR__.'/../../custom/config.php')){
        return 'Konfigurationsdatei custom/config.php kann nicht geschrieben werden.';
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
        $error = install($system_php, $db_host, $db_name, $db_user, $db_pass, $db_strategy);
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
