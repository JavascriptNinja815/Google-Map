<?php

/**
 * DEPRECATED. USE MIA_Email instead.
 */

class EmailException extends Exception {}

class EMail {
	public $host = False;
	public $port = False;

	public $username = False;
	public $password = False;

	public function __construct($host, $port, $username, $password) {
		$this->host = $host;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
	}

	public function Send($from, $to, $subject, $plaintext, $html, $attachments = False, $cc = False, $bcc = False) {
		$errors = [];
		if(empty($this->host)) {
			$errors[] = 'Host not specified';
		}
		if(empty($this->port)) {
			$errors[] = 'Port not specified';
		}
		if(empty($this->username)) {
			$errors[] = 'User Name not specified';
		}
		if(empty($this->password)) {
			$errors[] = 'Password not specified';
		}
		if(empty($from)) {
			$errors[] = 'From not specified';
		}
		if(empty($to)) {
			$errors[] = 'To not specified';
		}
		if(empty($subject)) {
			$errors[] = 'Subject not specified';
		}
		if(empty($plaintext)) {
			$errors[] = 'Plain Text Body not specified';
		}
		if(empty($html)) {
			$errors[] = 'HTML Body not specified';
		}

		if(!empty($errors)) {
			$error_msg = implode('. ', $errors);
			throw new EmailException('Not all information provided to send e-mail: ' . $error_msg);
		}

		require_once('Mail.php');
		require_once('Mail/mime.php');

		// Specify Text and HTML bodies
		$mime = New Mail_Mime("\n");
		$mime->SetTxtBody($plaintext);
		$mime->SetHtmlBody($html);

		if($attachments) {
			foreach($attachments as $attachment) {
				$mime->addAttachment($attachment, 'application/octet-stream');
			}
		}

		$body = $mime->Get(); // Get generated e-mail friendly body

		if(is_array($to)) {
			$to = implode(', ', $to);
		}
		if(is_array($cc)) {
			$cc = implode(', ', $cc);
		}
		if(is_array($bcc)) {
			$bcc = implode(', ', $bcc);
		}

		// Recipients is who the email is actually sent to.
		$recipients = [ $to ];
		if(!empty($cc)) {
			$recipients[] = $cc;
		}
		if(!empty($bcc)) {
			$recipients[] = $bcc;
		}
		$recipients = implode(', ', $recipients);

		// Headers are simply to tell mail clients who an email was sent to.
		$headers = [
			'From' => $from,
			'To' => $to,
			'Subject' => $subject
		];
		if(!empty($cc)) {
			$headers['CC'] = $cc;
		}

		$auth = [
			'host' => $this->host,
			'port' => $this->port,
			'auth' => True,
			'username' => $this->username,
			'password' => $this->password
		];

		// Specify e-mail headers
		$headers = $mime->Headers($headers);

		// Create our SMTP object
		$smtp = Mail::Factory('smtp', $auth);

		// Attempt to send the e-mail.
		$email_response = $smtp->Send($recipients, $headers, $body);

		if(PEAR::IsError($email_response)) {
			throw new EmailException('Erorr encountered: ' . json_encode($email_response));
		}

		return $email_response;
	}
}
