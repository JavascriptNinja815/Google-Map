<?php

$session->ensureLogin();

ob_start(); // Start loading output into buffer.

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
