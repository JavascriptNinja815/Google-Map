<?php

class AuthorizeException extends Exception {}

class Authorize {
	private static $_endpoints = [
		'production' => 'https://api2.authorize.net/xml/v1/request.api',
		'sandbox' => 'https://apitest.authorize.net/xml/v1/request.api'
	];
	
	public $debug = [];

	private $url;
	private $credentials;

	private static function _loadResponse($response_string) {
		$response = new DOMDocument;
		$response->loadXML($response_string);
		return $response;
	}

	public function __construct($endpoint, $api_login, $api_key) {
		if(empty(self::$_endpoints[$endpoint])) {
			throw new AuthorizeException('$endpoint argument with value of `production` or ` sandbox` must be specified');
		}
		$this->url = self::$_endpoints[$endpoint];
		$this->credentials = [
			'login' => $api_login,
			'key' => $api_key
		];
	}

	public function getCustomerProfileId($merchant_customer_id) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<getCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<merchantCustomerId>' . htmlentities($merchant_customer_id, ENT_XML1) . '</merchantCustomerId>
			</getCustomerProfileRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response)
			];
		}

		$customer_profile_id = $response->getElementsByTagName('customerProfileId')->item(0)->nodeValue;
		return [
			'success' => True,
			'customer_profile_id' => $customer_profile_id
		];
	}

	public function createCustomerAndPaymentProfile($merchant_customer_id, $card_number, $expiration, $cardcode, $address = Null, $zip = Null) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<profile>
					<merchantCustomerId>' . htmlspecialchars($merchant_customer_id, ENT_XML1) . '</merchantCustomerId>
					<paymentProfiles>
						' . (
							!empty($address) || !empty($zip) ?
								'<billTo>' . (
									!empty($address) ? '<address>' . htmlspecialchars($address, ENT_XML1) . '</address>' : Null
								) . (
									!empty($zip) ? '<zip>' . htmlspecialchars($zip, ENT_XML1) . '</zip>' : Null
								) . '</billTo>'
							: Null
						) . '
						<payment>
							<creditCard>
								<cardNumber>' . htmlspecialchars($card_number, ENT_XML1) . '</cardNumber>
								<expirationDate>' . htmlspecialchars($expiration, ENT_XML1) . '</expirationDate>
								<cardCode>' . htmlspecialchars($cardcode, ENT_XML1) . '</cardCode>
							</creditCard>
						</payment>
					</paymentProfiles>
				 </profile>
			</createCustomerProfileRequest>
		';
		//print($payload);
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			$errors = [];
			foreach($this->__getResponseErrors($response) as $error) { // Iterate over each error.
				if($error['code'] === 'E00039') { // A customer profile already exists for the merchant_customer_id specified. Let's grab it.
					$customer_profile_result = $this->getCustomerProfileId($merchant_customer_id);
					if(!$customer_profile_result['success']) {
						return $customer_profile_result; // Return result and errors.
					}
					$customer_profile_id = $customer_profile_result['customer_profile_id'];

					$payment_profile_result = $this->createPaymentProfile($customer_profile_id, $card_number, $expiration, $cardcode);
					if(!$payment_profile_result['success']) {
						return $payment_profile_result; // Return result and errors.
					}
					$payment_profile_id = $payment_profile_result['payment_profile_id'];

					return [
						'success' => True,
						'customer_profile_id' => $customer_profile_id,
						'payment_profile_id' => $payment_profile_id
					];
				}
				$errors[] = $error;
			}
			if(!empty($errors)) {
				return [
					'success' => False,
					'errors' => $errors
				];
			}
		}

		$customer_profile_id = $response->getElementsByTagName('customerProfileId')->item(0)->nodeValue;
		$payment_profile_id = $response->getElementsByTagName('customerPaymentProfileIdList')->item(0)->getElementsByTagName('numericString')->item(0)->nodeValue;

		return [
			'success' => True,
			'customer_profile_id' => $customer_profile_id,
			'payment_profile_id' => $payment_profile_id
		];
	}

	public function createPaymentProfile($customer_profile_id, $card_number, $expiration, $cardcode, $address = Null, $zip = Null) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<customerProfileId>' . htmlspecialchars($customer_profile_id, ENT_XML1) . '</customerProfileId>
				<paymentProfile>
					' . (
						!empty($address) || !empty($zip) ?
							'<billTo>' . (
								!empty($address) ? '<address>' . htmlspecialchars($address, ENT_XML1) . '</address>' : Null
							) . (
								!empty($zip) ? '<zip>' . htmlspecialchars($zip, ENT_XML1) . '</zip>' : Null
							) . '</billTo>'
						: Null
					) . '
					<payment>
						<creditCard>
							<cardNumber>' . htmlspecialchars($card_number, ENT_XML1) . '</cardNumber>
							<expirationDate>' . htmlspecialchars($expiration, ENT_XML1) . '</expirationDate>
						</creditCard>
					</payment>
				</paymentProfile>
			</createCustomerPaymentProfileRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response
			];
		}
		
		$payment_profile_id = $response->getElementsByTagName('customerPaymentProfileId')->item(0)->nodeValue;
		return [
			'success' => True,
			'payment_profile_id' => $payment_profile_id,
			'request' => $payload,
			'response' => $response
		];
	}

	public function deletePaymentProfile($customer_profile_id, $payment_profile_id) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<deleteCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<customerProfileId>' . htmlspecialchars($customer_profile_id, ENT_XML1) . '</customerProfileId>
				<customerPaymentProfileId>' . htmlspecialchars($payment_profile_id, ENT_XML1) . '</customerPaymentProfileId>
			</deleteCustomerPaymentProfileRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response
			];
		}

		return [
			'success' => True,
			'request' => $payload,
			'response' => $response
		];
	}

	public function authWithCard($amount, $card_number, $expiration, $cardcode, $invoice_number = Null, $address = Null, $zip = Null) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<transactionRequest>
					<transactionType>authOnlyTransaction</transactionType>
					<amount>' . number_format($amount, 2, '.', '') . '</amount>
					<payment>
						<creditCard>
							<cardNumber>' . htmlspecialchars($card_number, ENT_XML1) . '</cardNumber>
							<expirationDate>' . htmlspecialchars($expiration, ENT_XML1) . '</expirationDate>
							<cardCode>' . htmlspecialchars($cardcode, ENT_XML1) . '</cardCode>
						</creditCard>
					</payment>
					' . (
						!empty($invoice_number) ?
							'<order>
								<invoiceNumber>' . htmlspecialchars($invoice_number, ENT_XML1) . '</invoiceNumber>
							</order>'
						: Null
					) . '
					' . (
						!empty($address) || !empty($zip) ?
							'<billTo>' . (
								!empty($address) ? '<address>' . htmlspecialchars($address, ENT_XML1) . '</address>' : Null
							) . (
								!empty($zip) ? '<zip>' . htmlspecialchars($zip, ENT_XML1) . '</zip>' : Null
							) . '</billTo>'
						: Null
					) . '
					<retail>
						<marketType>2</marketType>
						<deviceType>10</deviceType>
					</retail>
					<transactionSettings>
						<setting>
							<settingName>duplicateWindow</settingName>
							<settingValue>5</settingValue>
						</setting>
					</transactionSettings>
				</transactionRequest>
			</createTransactionRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response->saveHTML()
			];
		}

		$transaction_id = $response->getElementsByTagName('transId')->item(0)->nodeValue;
		$auth_code = $response->getElementsByTagName('authCode')->item(0)->nodeValue;
		return [
			'success' => True,
			'transaction_id' => $transaction_id,
			'auth_code' => $auth_code,
			'request' => $payload,
			'response' => $response->saveHTML()
		];
	}

	public function authWithPaymentProfile($amount, $customer_profile_id, $payment_profile_id, $invoice_number = Null, $address = Null, $zip = Null) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<transactionRequest>
					<transactionType>authOnlyTransaction</transactionType>
					<amount>' . number_format($amount, 2, '.', '') . '</amount>
					<profile>
						<customerProfileId>' . htmlentities($customer_profile_id, ENT_XML1) . '</customerProfileId>
						<paymentProfile>
							<paymentProfileId>' . htmlentities($payment_profile_id, ENT_XML1) . '</paymentProfileId>
						</paymentProfile>
					</profile>
					' . (
						!empty($invoice_number) ?
							'<order>
								<invoiceNumber>' . htmlspecialchars($invoice_number, ENT_XML1) . '</invoiceNumber>
							</order>'
						:
							Null
					) . '
					' . ( // Billing info cannot be present when charging payment profiles.
						//!empty($address) || !empty($zip) ?
						//	'<billTo>' . (
						//		!empty($address) ? '<address>' . htmlspecialchars($address, ENT_XML1) . '</address>' : Null
						//	) . (
						//		!empty($zip) ? '<zip>' . htmlspecialchars($zip, ENT_XML1) . '</zip>' : Null
						//	) . '</billTo>'
						//: Null
					'') . '
					<retail>
						<marketType>2</marketType>
						<deviceType>10</deviceType>
					</retail>
					<transactionSettings>
						<setting>
							<settingName>duplicateWindow</settingName>
							<settingValue>5</settingValue>
						</setting>
					</transactionSettings>
				</transactionRequest>
			</createTransactionRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response->saveHTML()
			];
		}

		$transaction_id = $response->getElementsByTagName('transId')->item(0)->nodeValue;
		$auth_code = $response->getElementsByTagName('authCode')->item(0)->nodeValue;
		return [
			'success' => True,
			'transaction_id' => $transaction_id,
			'auth_code' => $auth_code,
			'request' => $payload,
			'response' => $response->saveHTML()
		];
	}

	public function authCaptureWithCard($amount, $card_number, $expiration, $cardcode, $invoice_number = Null, $address = Null, $zip = Null) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<transactionRequest>
					<transactionType>authCaptureTransaction</transactionType>
					<amount>' . number_format($amount, 2, '.', '') . '</amount>
					<payment>
						<creditCard>
							<cardNumber>' . htmlspecialchars($card_number, ENT_XML1) . '</cardNumber>
							<expirationDate>' . htmlspecialchars($expiration, ENT_XML1) . '</expirationDate>
							<cardCode>' . htmlspecialchars($cardcode, ENT_XML1) . '</cardCode>
						</creditCard>
					</payment>
					' . (
						!empty($invoice_number) ?
							'<order>
								<invoiceNumber>' . htmlspecialchars($invoice_number, ENT_XML1) . '</invoiceNumber>
							</order>'
						:
							Null
					) . '
					' . (
						!empty($address) || !empty($zip) ?
							'<billTo>' . (
								!empty($address) ? '<address>' . htmlspecialchars($address, ENT_XML1) . '</address>' : Null
							) . (
								!empty($zip) ? '<zip>' . htmlspecialchars($zip, ENT_XML1) . '</zip>' : Null
							) . '</billTo>'
						: Null
					) . '
					<retail>
						<marketType>2</marketType>
						<deviceType>10</deviceType>
					</retail>
					<transactionSettings>
						<setting>
							<settingName>duplicateWindow</settingName>
							<settingValue>5</settingValue>
						</setting>
					</transactionSettings>
				</transactionRequest>
			</createTransactionRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response->saveHTML()
			];
		}

		$transaction_id = $response->getElementsByTagName('transId')->item(0)->nodeValue;
		$auth_code = $response->getElementsByTagName('authCode')->item(0)->nodeValue;
		return [
			'success' => True,
			'transaction_id' => $transaction_id,
			'auth_code' => $auth_code,
			'request' => $payload,
			'response' => $response->saveHTML()
		];
	}

	public function authCaptureWithPaymentProfile($amount, $customer_profile_id, $payment_profile_id, $invoice_number = Null, $address = Null, $zip = Null) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<transactionRequest>
					<transactionType>authCaptureTransaction</transactionType>
					<amount>' . number_format($amount, 2, '.', '') . '</amount>
					<profile>
						<customerProfileId>' . htmlentities($customer_profile_id, ENT_XML1) . '</customerProfileId>
						<paymentProfile>
							<paymentProfileId>' . htmlentities($payment_profile_id, ENT_XML1) . '</paymentProfileId>
						</paymentProfile>
					</profile>
					' . (
						!empty($invoice_number) ?
							'<order>
								<invoiceNumber>' . htmlspecialchars($invoice_number, ENT_XML1) . '</invoiceNumber>
							</order>'
						:
							Null
					) . '
					' . ( // Billing info cannot be present when charging payment profiles.
						//!empty($address) || !empty($zip) ?
						//	'<billTo>' . (
						//		!empty($address) ? '<address>' . htmlspecialchars($address, ENT_XML1) . '</address>' : Null
						//	) . (
						//		!empty($zip) ? '<zip>' . htmlspecialchars($zip, ENT_XML1) . '</zip>' : Null
						//	) . '</billTo>'
						//: Null
					'') . '
					<retail>
						<marketType>2</marketType>
						<deviceType>10</deviceType>
					</retail>
					<transactionSettings>
						<setting>
							<settingName>duplicateWindow</settingName>
							<settingValue>5</settingValue>
						</setting>
					</transactionSettings>
				</transactionRequest>
			</createTransactionRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response->saveHTML()
			];
		}

		$transaction_id = $response->getElementsByTagName('transId')->item(0)->nodeValue;
		$transaction_hash = $response->getElementsByTagName('transHash')->item(0)->nodeValue;
		$auth_code = $response->getElementsByTagName('authCode')->item(0)->nodeValue;
		return [
			'success' => True,
			'transaction_id' => $transaction_id,
			'transaction_hash' => $transaction_hash,
			'auth_code' => $auth_code,
			'request' => $payload,
			'response' => $response->saveHTML()
		];
	}

	public function refund($transaction_id, $last4, $amount) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<transactionRequest>
					<transactionType>refundTransaction</transactionType>
					<amount>' . number_format($amount, 2, '.', '') . '</amount>
					<payment>
						<creditCard>
							<cardNumber>' . htmlentities($last4, ENT_XML1) . '</cardNumber>
							<expirationDate>XXXX</expirationDate>
						</creditCard>
					</payment>
					<refTransId>' . htmlentities($transaction_id, ENT_XML1) . '</refTransId>
				</transactionRequest>
			</createTransactionRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response->saveHTML()
			];
		}

		$transaction_id = $response->getElementsByTagName('transId')->item(0)->nodeValue;
		$transaction_hash = $response->getElementsByTagName('transHash')->item(0)->nodeValue;
		return [
			'success' => True,
			'transaction_id' => $transaction_id,
			'transaction_hash' => $transaction_hash,
			'request' => $payload,
			'response' => $response->saveHTML()
		];
	}

	public function void($transaction_id) {
		$payload = '<?xml version="1.0" encoding="utf-8"?>
			<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
				' . $this->__getAuthXML() . '
				<transactionRequest>
				  <transactionType>voidTransaction</transactionType>
				  <refTransId>' . htmlentities($transaction_id, ENT_XML1) . '</refTransId>
				 </transactionRequest>
			</createTransactionRequest>
		';
		$response = $this->__sendRequest($payload);

		if($this->__responseHasErrors($response)) { // Check if response has errors.
			return [
				'success' => False,
				'errors' => $this->__getResponseErrors($response),
				'request' => $payload,
				'response' => $response->saveHTML()
			];
		}

		$transaction_id = $response->getElementsByTagName('transId')->item(0)->nodeValue;
		$auth_code = $response->getElementsByTagName('transId')->item(0)->nodeValue;
		return [
			'success' => True,
			'transaction_id' => $transaction_id,
			'auth_code' => $auth_code,
			'request' => $payload,
			'response' => $response->saveHTML()
		];
	}
	
	private function __getAuthXML() {
		return '
			<merchantAuthentication>
				<name>' . htmlspecialchars($this->credentials['login'], ENT_XML1) . '</name>
				<transactionKey>' . htmlspecialchars($this->credentials['key'], ENT_XML1) . '</transactionKey>
			</merchantAuthentication>
		';
	}

	private function __sendRequest($payload) {
		$url = $this->url;

		$request = curl_init();
		curl_setopt($request, CURLOPT_URL, $url);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, True);
		curl_setopt($request, CURLOPT_POST, True);
		curl_setopt($request, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, False);
		$response_xml = curl_exec($request);
		curl_close($request);

		// Hack to fix Authorize.net's invalid namespaces.
		$response_xml = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $response_xml);

		$response = new DOMDocument;
		$response->loadXML($response_xml);

		$this->__debug([
			'url' => $url,
			'request' => $payload,
			'response' => $response_xml
		]);

		return $response;
	}

	private function __responseHasErrors($response) {
		$message_container = $response->getElementsByTagName('messages')->item(0);
		$message = $message_container->getElementsByTagName('message')->item(0);
		$text = $message->getElementsByTagName('text')->item(0)->nodeValue;
		if($text !== 'Successful.') {
			return True;
		}

		// Simply having a message text of "Successful." is not enough, errors can still be present...
		$errors = $this->__getResponseErrors($response);
		if(!empty($errors)) {
			return True;
		}
		return False;
	}

	private function __getResponseErrors($response) {
		$errors = [];

		// Grab top-level response.
		$message_container = $response->getElementsByTagName('messages')->item(0);
		$message = $message_container->getElementsByTagName('message')->item(0);
		$code = $message->getElementsByTagName('code')->item(0)->nodeValue;
		$text = $message->getElementsByTagName('text')->item(0)->nodeValue;
		if($text !== 'Successful.') {
			$errors[] = [
				'code' => $code,
				'message' => $text
			];
		}

		// Grab transaction-level errors (when present).
		$errors_container = $response->getElementsByTagName('errors');
		if($errors_container->length) {
			foreach($errors_container->item(0)->getElementsByTagName('error') as $error) {
				$code = $error->getElementsByTagName('errorCode')->item(0)->nodeValue;
				$text = $error->getElementsByTagName('errorText')->item(0)->nodeValue;
				$errors[] = [
					'code' => $code,
					'message' => $text
				];
			}
		}
		return $errors;
	}

	private function __debug($data) {
		/**
		 * SECURITY CLEANUP:
		 * Strip pieces of the request which we don't want to store such as API
		 * authentication info or credit card numbers.
		 */

		$checks = [
			'/\<cardNumber\>[\s\S]+?\<\/cardNumber\>/', // Credit Card Number
			'/\<merchantAuthentication\>[\s\S]+?\<\/merchantAuthentication\>/' // API Credentials
		];

		foreach($checks as $check) {
			$data['request'] = preg_replace($check, '[ REDACTED (FOR SECURITY) ]', $data['request']);
		}

		// Append debug data to the object.
		$this->debug[] = $data;
	}

	public function getDebugData() {
		return $this->debug;
	}
}
