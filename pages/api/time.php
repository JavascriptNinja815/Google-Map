<?php

$time = time();

print json_encode(array(
	'success' => True,
	'timestamp' => $time,
	'time' => date('g:i A', $time),
	'hour' => date('g'),
	'minute' => date('i'),
	'ampm' => date('A'),
	'milhour' => date('G')
));
