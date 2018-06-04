<?php

class AuthorizeCustomerProfileException extends Exception {}

class AuthorizeCustomerProfile {
	private $db;
	private $custno;
	private $profile;
	private $authorize;

	public $exists = False;

	public function __construct($custno) {
		$this->custno = trim($custno);
		$this->db = DB::get();

		$this->profile = $this->__load();
	}

	public function create($nickname, $card_number, $expiration, $cardcode, $address = Null, $zip = Null) {
		global $session;

		/**
		 * NOTE:
		 * 
		 * If the customer already exists within Authorize.net, only the
		 * authorize_customer_profile_id is returned.
		 * 
		 * If the customer does not exist within Authorize.net, both the customer
		 * and payment profiles will be created at the same time, and both
		 * authorize_customer_profile_id and authorize_payment_profile_id
		 * will be returned.
		 * 
		 * In either scenario, the customer profile is inserted into the database.
		 */

		if($this->exists) {
			return False; // Already exists, no reason to attempt to create.
		}

		$authorize = $this->__getAuthorizeObject();

		// It's possible that Authorize.net already has a Customer Profile in
		// their system. Before requesting they create a new one, see if they
		// have one first.
		$get_profile_result = $authorize->getCustomerProfileId($this->custno);
		if($get_profile_result['success']) {
			// Authorize.net already has a Customer Profile for this client.
			$this->profile['authorize_customer_profile_id'] = $get_profile_result['customer_profile_id'];

			$return = [
				'authorize_customer_profile_id' => $this->profile['authorize_customer_profile_id']
			];
		} else {
			// Authorize.net does not have a Customer Profile for this client, create it.
			$create_profile_result = $authorize->createCustomerAndPaymentProfile($this->custno, $card_number, $expiration, $cardcode, $address, $zip);
			if(!empty($create_profile_result['errors'])) {
				// Errors returned, raise an exception.
				$message = [];
				foreach($create_profile_result['errors'] as $error) {
					$message[] = '(Error Code ' . $error['code'] . ') ' . $error['message'];
				}
				$message = implode(', ', $message);
				throw New AuthorizeCustomerProfileException($message);
			}
			$this->profile['authorize_customer_profile_id'] = $create_profile_result['customer_profile_id'];

			$return = [
				'authorize_customer_profile_id' => $this->profile['authorize_customer_profile_id'],
				'authorize_payment_profile_id' => $create_profile_result['payment_profile_id']
			];
		}

		// Insert an entry into the database for this Customer Profile.
		$grab_profile = $this->db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".authorize_customer_profiles
			(
				custno,
				authorize_custprofile_id,
				live,
				added_on,
				salesmn
			)
			OUTPUT
				INSERTED.customer_profile_id,
				INSERTED.custno,
				INSERTED.authorize_custprofile_id AS authorize_customer_profile_id,
				INSERTED.added_on,
				INSERTED.salesmn
			VALUES (
				" . $this->db->quote($this->custno) . ",
				" . $this->db->quote($this->profile['authorize_customer_profile_id']) . ",
				" . (ISLIVE ? '1' : '0') . ",
				GETDATE(),
				" . $this->db->quote($session->login['initials']) . "
			)
		");
		$this->profile = $grab_profile->fetch();

		// Now that we've created the DB entry and set exists to True.
		$this->exists = True;

		return $return;
	}

	public function getAuthorizeCustomerProfileId() {
		return $this->profile['authorize_customer_profile_id'];
	}

	public function getLocalCustomerProfileId() {
		return $this->profile['customer_profile_id'];
	}

	private function __load() {
		$grab_profile = $this->db->query("
			SELECT
				authorize_customer_profiles.customer_profile_id,
				authorize_customer_profiles.custno,
				authorize_customer_profiles.authorize_custprofile_id AS authorize_customer_profile_id,
				authorize_customer_profiles.added_on,
				authorize_customer_profiles.salesmn
			FROM
				" . DB_SCHEMA_ERP . ".authorize_customer_profiles
			WHERE
				authorize_customer_profiles.custno = LTRIM(RTRIM(" . $this->db->quote($this->custno) . "))
				AND
				authorize_customer_profiles.live = " . (ISLIVE ? '1' : '0') . "
		");
		$profile = $grab_profile->fetch();

		if(empty($profile)) {
			// Profile does not exist, return a prototype.
			return [
				'customer_profile_id' => Null,
				'custno' => $this->custno,
				'authorize_custprofile_id' => Null,
				'added_on' => Null,
				'salesmn' => Null,
			];
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
