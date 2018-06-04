<?php

$session->ensureLogin();
$session->ensureRole('Administration');

$printers = [];

if(!empty($_POST['locations'])) {
	
}

print json_encode([
	'success' => True,
	'printers' => $printers
]);
