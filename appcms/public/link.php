<?php
//die("FILE: ".file_exists('/html/appcms/public/custom/Frontend/ui/default/scripts/FileSaver.js'));
echo shell_exec('cd /html/appcms/public/custom && rm -rf Frontend && ln -s ../../../custom/Frontend Frontend');