<?php
//phpinfo();

//print_r(apache_get_modules());

$file = "vagrant/source/data/files/1/po13.jpg";
header('Content-type: ' . mime_content_type($file));
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header("X-Sendfile: " . $file);
exit;