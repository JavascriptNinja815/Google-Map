<?php

$filename = preg_replace('/\.{2,}/', '.', $_REQUEST['filename']);

header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');

$full_file_path = Quote::$base_dir . '\\' . $filename;
readfile($full_file_path);
