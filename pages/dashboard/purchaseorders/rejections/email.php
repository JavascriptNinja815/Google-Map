<?php

$host = 'ssl://smtp.gmail.com';
$port = '465';
$username = $session->login['login'];
$password = $session->login['email_password'];
$from = $session->login['first_name'] . ' ' . $session->login['last_name'] . ' <' . $session->login['login'] . '>';
$to = $_REQUEST['to'];
$message = str_replace("\r\n", "<br />\r\n", $_POST['body']);
$subject = $_POST['subject'];

if(empty($password)) {
	print 'ERROR: Maven gMail App password has not bee configured. Create an "Other (Custom)" password here (https://myaccount.google.com/apppasswords) and have it added to your Maven account.';
	exit;
}

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
		strip_tags($message), // plaintext,
		$message//, // html
		//$attachments,
		//$cc,
		//$bcc
	);

	header('Location: ' . BASE_URI . '/dashboard/purchaseorders/rejections?success');

} catch(EmailException $e) {
	print 'ERROR: ' . $e->getMessage();
	exit;
}
