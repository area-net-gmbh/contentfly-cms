<?php
if(file_exists(__DIR__.'/../../custom/config.php')){
    header('Location: /');
}

require_once('../version.php');

$error = null;

function isEnabled($func) {
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

function install($system_php, $db_host, $db_name, $db_user, $db_pass){

    if(!file_exists(__DIR__.'/../../boilerplate-'.APP_VERSION.'.zip')){
        shell_exec(('cd '.__DIR__.'/../../ && wget http://www.das-app-cms.de/download/boilerplate-'.APP_VERSION.'.zip'));
    }

    $zip = new ZipArchive();
    if ($zip->open(__DIR__.'/../../boilerplate-'.APP_VERSION.'.zip') !== TRUE) {
        return 'Boilerplate-ZIP "'.__DIR__.'/../../boilerplate-'.APP_VERSION.'.zip'.'" konnte nicht geschrieben/geladen werden.';
    }

    $zip->extractTo(__DIR__.'/../../');
    $zip->close();
    shell_exec(('mv '.__DIR__.'/../../boilerplate-'.APP_VERSION.'/* '.__DIR__.'/../../'));
    shell_exec(('rm -rf '.__DIR__.'/../../boilerplate-'.APP_VERSION));
    shell_exec(('rm -rf '.__DIR__.'/../../boilerplate-'.APP_VERSION.'.zip'));
    if(!file_exists(__DIR__.'/../../custom/config.php')){
        return 'Konfiguration "'.__DIR__.'/../../custom/config.php" konnte nicht erstellt werden.';
    }
    $configContent = file_get_contents(__DIR__.'/../../custom/config.php');


    $configContent = str_replace("'DB_HOST'", "'".$db_host."'", $configContent);
    $configContent = str_replace("'DB_NAME'", "'".$db_name."'", $configContent);
    $configContent = str_replace("'DB_USER'", "'".$db_user."'", $configContent);
    $configContent = str_replace("'DB_PASS'", "'".$db_pass."'", $configContent);

    if($system_php != 'php'){
        $configContent = str_replace("Config();", "Config();\n\n".'$configDefault->SYSTEM_PHP_CLI_COMMAND = "'.escapeshellcmd($system_php).'"');
    }
    if(strpos($configContent, 'DO_INSTALL') === false){
        $configContent .= "\n\n".'$configDefault->DO_INSTALL = true;';
    }
    file_put_contents(__DIR__.'/../../custom/config.php', $configContent);

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
    $system_php = mask($_POST['system_php']);
    $db_host    = mask($_POST['db_host']);
    $db_name    = mask($_POST['db_name']);
    $db_user    = mask($_POST['db_user']);
    $db_pass    = mask($_POST['db_pass']);

    if(!($error = check_install_errors($system_php, $db_host, $db_name, $db_user, $db_pass))){
        $error = install($system_php, $db_host, $db_name, $db_user, $db_pass);
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
