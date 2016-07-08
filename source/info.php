<?php

$fileName = '/html/pim/source/data/files/77/600-pk12sf.jpg';

header("Expires: ".gmdate("D, d M Y H:i:s", $fileMTime+1800)." GMT");
header("Cache-Control: max-age=1800");
header("Vary: Accept-Encoding");

header('Content-type: image/png');
header("Content-length: " . filesize($fileName));
readfile($fileName);
