<?php

$last_image = !empty($_REQUEST['last-image']) ? $_REQUEST['last-image'] : '';

$files = scandir(BASE_PATH . '/interface/images/sales-display/');
shuffle($files); // Randomly sorts array.

$image = '';
foreach($files as $file) {
	if($file == '.' || $file == '..' || $file == $last_image) {
		continue;
	}
	$image = $file;
	break;
}

print json_encode([
	'success' => True,
	'image' => $image
]);
