<?php

ini_set('display_errors','on');
error_reporting(E_ALL);

$host = 'ssl://smtp.gmail.com';
$port = '465';
$username = 'jdburnz@gmail.com';
$password = 'ncoctiyslqlzktza';

$from = "Joshua Burns <jdburnz@gmail.com>";
$to = "Santa Clause <joshuadburns@hotmail.com>";
$subject = 'EXAMPLE SUBJECT LINE.';

$plaintext = 'This is a plaintext body.';
$html = '<html><body>This is an <b>HTML</b> body.</body></html>';

$email = new Email(
	$host,
	$port,
	$username,
	$password
);

try {
	$response = $email->send(
		$from,
		$to,
		$subject,
		$plaintext,
		$html,
		$attachments = [
			'pages\\tests\\example-attachment.pdf'
		]
	);
	print '<pre>';
	print_r($response);
	print '</pre>';
} catch(EmailException $e) {
	print 'Exception thrown:';
	print '<br><br>';
	print '<pre>';
	print $e->getMessage();
	print '</pre>';
}
