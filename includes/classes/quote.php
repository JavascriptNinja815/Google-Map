<?php

class Quote {
	public static $base_dir = '\\\\Glc-sql1\\sagepro\\CR\\CR Messages\\Quotes';

	public function __construct($opportunity_id) {
		$this->opportunity_id = $opportunity_id;
	}

	public function generatePDF($quotetemplate_id, $to_email, $to_name, $salesman_initials) {
		$db = \PM\DB\SQL::connection();

		$grab_quote_template = $db->query("
			SELECT
				opportunity_quotetemplates.filename,
				opportunity_quotetemplates.filename_prefix
			FROM
				" . DB_SCHEMA_ERP . ".opportunity_quotetemplates
			WHERE
				quotetemplate_id = " . $db->quote($quotetemplate_id) . "
		");
		$quote_template = $grab_quote_template->fetch();
		if(empty($quote_template)) {
			throw new QuoteException('Invalid Quote Template specified');
		}

		$saveas_filename = $this->__generateFilename($quote_template['filename_prefix']);

		/**
		 * These arguments must be defined in the order they should be passed
		 * to VisualCUT.
		 * 
		 * All argument values MUST be escaped for security reasons, using
		 * escapeshellarg().
		 * 
		 * Keys are for reference / self-documentation only.
		 */
		$args = [
			'template' => escapeshellarg($quote_template['filename']),
			'opportunity-id' => escapeshellarg('Parm1:' . $this->opportunity_id),
			'to-email' => escapeshellarg('Parm2:' . $to_email),
			'to-name' => escapeshellarg('Parm3:' . $to_name),
			'email-body' => escapeshellarg('Parm4:' . 'EMAIL BODY NOT USED'), // DEPRECATED.
			'salesmn' => escapeshellarg('Parm5:' . $salesman_initials),
			'saveas-filename' => escapeshellarg('Parm6:' . $saveas_filename)
		];
		$args_joined = implode(' ', $args);

		$output = '';
		$command = '"C:\\Program Files (x86)\\Visual CUT 11\\Visual CUT.exe" -E ' . $args_joined;
		system($command, $output);

		return $saveas_filename;
	}

	public static function send_gmail($host, $port, $username, $password, $from, $to, $subject, $message, $attachments, $cc, $bcc){

		// A function to send the "traditional" way - using the gmail API.

		$email = new Email(
			$host,
			$port,
			$username,
			$password
		);

		try {
			$email->send(
				$from,
				$to,
				$subject,
				strip_tags($message), // plaintext,
				$message, // html
				$attachments,
				$cc,
				$bcc
			);
			return [
				'success' => True
			];
		} catch(EmailException $e) {
			return [
				'success' => False,
				'message' => $e->getMessage()
			];
		}

	}

	public static function send_nylas($login_id, $to, $subject, $message, $attachments, $cc, $bcc){

		// A function to send using the Nylas API.

		// The URL to POST to.
		$url = 'http://10.1.247.195/nylas/send-quote';

		try {
			// Load the attchment into a string and base64 encode it.
			$data = file_get_contents($attachments[0]);
			$pdf = base64_encode($data);

			// Prepare the POST parameters.
			$post = array(
				'login_id' => $login_id,
				'to' => $to,
				'subject' => $subject,
				'body' => $message,
				'cc' => $cc,
				'bcc' => $bcc,
				'pdf' => $pdf
			);

			// Prepare POST with cURL.
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

			// POST.
			$response = curl_exec($ch);
			curl_close($ch);

			if($response){
				return json_decode($response, true);
			}
			else{
				return array(
					'success' => false,
					'message' => 'Quote not sent - Nylas Failure'
				);
			}

		} catch (Exception $e){
			return array(
				'success' => false,
				'message' => $e->getMessage()
			);
		}

	}

	public function send($subject, $message, $from, $to, $cc, $bcc, $attachments, $api=null) {
		global $db, $session;

		// Default the `api` value to 'gmail'.
		if(is_null($api)){
			$api = 'gmail';
		}

		if(is_array($to)) {
			$to = implode(', ', $to);
		}
		if(is_array($cc)) {
			$cc = implode(', ', $cc);
		}
		if(is_array($bcc)) {
			$bcc = implode(', ', $bcc);
		}

		/**
		 * Log the quote to the DB.
		 */
		$db['internal']->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".opportunity_logs
			(
				opportunity_id,
				login_id,
				field,
				from_value,
				to_value
			) VALUES (
				" . $db['internal']->quote($_POST['opportunity_id']) . ",
				" . $db['internal']->quote($session->login['login_id']) . ",
				'quote',
				" . $db['internal']->quote($to) . ",
				" . $db['internal']->quote($attachments[0]) . "
			)
		");

		/**
		 * E-Mail the quote.
		 */

		// Gmail:
		if($api=='gmail'){
			$host = 'ssl://smtp.gmail.com';
			$port = '465';
			$username = $session->login['login'];
			$password = $session->login['email_password'];

			$response = $this->send_gmail($host, $port, $username, $password, $from, $to, $subject, $message, $attachments, $cc, $bcc);
		}else{
			$login_id = $session->login['login_id'];
			$response = $this->send_nylas($login_id, $to, $subject, $message, $attachments, $cc, $bcc);
		}
		// Nylas:

		return $response;

	}

	private function __generateAlphabeticalList($lower, $upper) {
		// Create the generator which will be used for determining the quote's filename.
		++$upper;
		for($i = $lower; $i !== $upper; ++$i) {
			yield $i;
		}
	}

	private function __generateFilename($filename_prefix) {
		$good = False;
		foreach($this->__generateAlphabeticalList('A', 'ZZ') as $value) {
			$filename = $filename_prefix . '_' . $this->opportunity_id . '-' . $value . '.pdf';
			if(!file_exists(self::$base_dir . '\\' . $filename)) {
				$good = True;
				break;
			}
		}
		if(!$good) {
			foreach($this->__generateAlphabeticalList('A', 'ZZZ') as $value) {
				$filename = $filename_prefix . '_' . $this->opportunity_id . '-' . $value . '.pdf';
				if(!file_exists(self::$base_dir . '\\' . $filename)) {
					$good = True;
					break;
				}
			}
		}
		return $filename;
	}
}
