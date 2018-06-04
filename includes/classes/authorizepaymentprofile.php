<?php

class AuthorizePaymentProfileException extends Exception {}

class AuthorizePaymentProfile {
	private $db;
	private $custno;
	private $profile;
	private $authorize;

	public $exists = False;

	public function __construct($custno, $payment_profile_id = Null) {
		$this->custno = trim($custno);
		$this->db = DB::get();

		$this->profile = $this->__load($payment_profile_id);
	}

	public function create($nickname, $nameoncard, $card_number, $expiration, $cardcode, $address = Null, $zip = Null) {
		global $session;

		if($this->exists) {
			return False;
		}

		$authorize = $this->__getAuthorizeObject();

		// Instantiate the Authorize.net Customer Profile.
		$customer_profile = new AuthorizeCustomerProfile($this->custno);

		$authorize_payment_profile_id = Null;
		$last4 = substr($card_number, -4);

		// Check if the customer profile exists.
		if($customer_profile->exists) {
			// Customer profile exists, retrieve the authorize_customer_profile_id.
			$authorize_customer_profile_id = $customer_profile->getAuthorizeCustomerProfileId();
		} else {
			/**
			 * Customer profile does not exist in local DB, but may exist on
			 * Authorize.net's servers.
			 * 
			 * If customer exists on the Authorize.net server, only
			 * `authorize_customer_profile_id` will be returned.
			 * 
			 * If customer does not exist on the Authorize.net server, then
			 * the payment profile has to be created when the customer profile
			 * is created so both `authorize_customer_profile_id` and
			 * `authorize_payment_profile_id` will be returned.
			 */
			$create_customer_result = $customer_profile->create($nickname, $card_number, $expiration, $cardcode, $address, $zip);
			if(!empty($create_customer_result['errors'])) {
				// Errors returned, raise an exception.
				$message = [];
				foreach($create_customer_result['errors'] as $error) {
					$message[] = '(Error Code ' . $error['code'] . ') ' . $error['message'];
				}
				$message = implode(', ', $message);
				throw New AuthorizePaymentProfileException($message);
			}
			// Now that the customer profile exists, retrieve the Authorize ID for it.
			$authorize_customer_profile_id = $customer_profile->getAuthorizeCustomerProfileId();
			if(isset($create_customer_result['authorize_payment_profile_id'])) {
				// Customer did not exist on Authorize.net server, customer and
				// payment profiles now created.
				$authorize_payment_profile_id = $create_customer_result['authorize_payment_profile_id'];
			}
		}

		if(!$authorize_payment_profile_id) {
			// Still need to create the Authorize Payment Profile.
			$create_payment_result = $authorize->createPaymentProfile($authorize_customer_profile_id, $card_number, $expiration, $cardcode, $address, $zip);
			
			/**
			 * TODO: Handle "(Error Code E00039) A duplicate customer payment profile already exists."
			 * - By checking error code and calling getCustomerPaymentProfileListRequest and finding the authorize.net payment profile id.
			 */

			if(!empty($create_payment_result['errors'])) {
				// Errors returned, raise an exception.
				$message = [];
				foreach($create_payment_result['errors'] as $error) {
					$message[] = '(Error Code ' . $error['code'] . ') ' . $error['message'];
				}
				$message = implode(', ', $message);
				throw New AuthorizePaymentProfileException($message);
			}

			$authorize_payment_profile_id = $create_payment_result['payment_profile_id'];
		}
		
		// Insert the newly created Payment Profile ID into the database.
		$grab_profile = $this->db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".authorize_payment_profiles
			(
				customer_profile_id,
				authorize_payprofile_id,
				last4,
				code,
				expiration,
				nameoncard,
				profile_name,
				live,
				added_on,
				salesmn,
				address,
				zip
			)
			OUTPUT
				INSERTED.payment_profile_id,
				INSERTED.customer_profile_id,
				INSERTED.authorize_payprofile_id,
				INSERTED.last4,
				INSERTED.code,
				INSERTED.expiration,
				INSERTED.nameoncard,
				INSERTED.profile_name,
				INSERTED.live,
				INSERTED.added_on,
				INSERTED.salesmn,
				INSERTED.address,
				INSERTED.zip
			VALUES (
				" . $this->db->quote($customer_profile->getLocalCustomerProfileId()) . ",
				" . $this->db->quote($authorize_payment_profile_id) . ",
				" . $this->db->quote($last4) . ",
				" . $this->db->quote($cardcode) . ",
				" . $this->db->quote($expiration) . ",
				" . $this->db->quote($nameoncard) . ",
				" . $this->db->quote($nickname) . ",
				" . (ISLIVE ? '1' : '0') . ",
				GETDATE(),
				" . $this->db->quote($session->login['initials']) . ",
				" . $this->db->quote($address) . ",
				" . $this->db->quote($zip) . "
			)
		");
		$this->profile = $grab_profile->fetch();

		// Now that we've created the DB entry and set exists to True.
		$this->exists = True;

		return True;
	}

	public function delete($authorize_payment_profile_id) {
		if(!$this->exists) {
			return False;
		}

		// Grab the authorize object.
		$authorize = $this->__getAuthorizeObject();

		$customer_profile = new AuthorizeCustomerProfile($this->custno);
		$authorize_customer_profile_id = $customer_profile->getAuthorizeCustomerProfileId();

		$delete_result = $authorize->deletePaymentProfile($authorize_customer_profile_id, $authorize_payment_profile_id);
		if(!empty($delete_result['errors'])) {
			// Errors returned, raise an exception.
			$message = [];
			foreach($delete_result['errors'] as $error) {
				$message[] = '(Error Code ' . $error['code'] . ') ' . $error['message'];
			}
			$message = implode(', ', $message);
			throw New AuthorizePaymentProfileException($message);
		}

		$this->db->query("
			DELETE FROM
				" . DB_SCHEMA_ERP . ".authorize_payment_profiles
			WHERE
				authorize_payment_profiles.authorize_payprofile_id = " . $this->db->quote($authorize_payment_profile_id) . "
				AND
				authorize_payment_profiles.live = " . (ISLIVE ? '1' : '0') . "
		");

		return True;
	}

	public function getAuthorizePaymentProfileId() {
		return $this->profile['authorize_payment_profile_id'];
	}

	public function getLocalPaymentProfileId() {
		return $this->profile['payment_profile_id'];
	}

	private function __load($payment_profile_id) {
		$prototype = [
			'payment_profile_id' => Null,
			'customer_profile_id' => Null,
			'authorize_payment_profile_id' => Null,
			'last4' => Null,
			'code' => Null,
			'expiration' => Null,
			'nameoncard' => Null,
			'profile_name' => Null,
			'live' => Null,
			'added_on' => Null,
			'salesmn' => Null,
			'address' => Null,
			'zip' => Null
		];

		if(!$payment_profile_id) {
			return $prototype;
		}

		$grab_profile = $this->db->query("
			SELECT
				authorize_payment_profiles.payment_profile_id,
				authorize_payment_profiles.customer_profile_id,
				authorize_payment_profiles.authorize_payprofile_id AS authorize_payment_profile_id,
				authorize_payment_profiles.last4,
				authorize_payment_profiles.code,
				authorize_payment_profiles.expiration,
				authorize_payment_profiles.nameoncard,
				authorize_payment_profiles.profile_name,
				authorize_payment_profiles.live,
				authorize_payment_profiles.added_on,
				authorize_payment_profiles.salesmn,
				authorize_payment_profiles.address,
				authorize_payment_profiles.zip
			FROM
				" . DB_SCHEMA_ERP . ".authorize_payment_profiles
			WHERE
				authorize_payment_profiles.payment_profile_id = " . $this->db->quote($payment_profile_id) . "
				AND
				authorize_payment_profiles.live = " . (ISLIVE ? '1' : '0') . "
		");
		$profile = $grab_profile;

		if(empty($profile)) {
			// Profile does not exist, return a prototype.
			return $prototype;
		}

		$this->exists = True; // Does already exist in DB, set to True.

		return $profile;
	}

	private function __getAuthorizeObject() {
		if(!$this->authorize) { // Authorize object not yet instantiated, do so and set on object.
			$this->authorize = new Authorize(
				AUTHORIZE_ENDPOINT, // Endpoint. Either `production` or `sandbox`.
				AUTHORIZE_LOGINID,
				AUTHORIZE_KEY
			);
		}

		return $this->authorize;
	}
}
