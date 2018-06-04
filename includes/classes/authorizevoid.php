<?php

class AuthorizeVoidException extends Exception {}

class AuthorizeVoid {
	private $original_transaction;
	private $transaction;
	private $authorize;

	private $authorize_transaction_id;

	public function __construct($authorize_transaction_id) {
		$this->db = DB::get();

		$grab_transaction = $this->db->query("
			SELECT
				authorize_transactions.transaction_id,
				authorize_transactions.custno,
				authorize_transactions.last4,
				authorize_transactions.nameoncard,
				authorize_transactions.authorize_transaction_id,
				authorize_transactions.amount
			FROM
				" . DB_SCHEMA_ERP . ".authorize_transactions
			WHERE
				authorize_transactions.live = " . (ISLIVE ? '1' : '0') . "
				AND
				authorize_transactions.authorize_transaction_id = " . $this->db->quote($authorize_transaction_id) . "
		");
		$this->original_transaction = $grab_transaction->fetch();
		if(empty($this->original_transaction)) {
			throw new AuthorizeVoidException('Authorize Transaction ID specified does not exist in the local database');
		}

		$this->transaction = [
			'authorize_transaction_id' => Null,
			'auth_code' => Null
		];
	}

	public function void($memo) {
		global $session;

		$authorize = $this->__getAuthorizeObject();

		// Insert the initial entry.
		$grab_void_transaction = $this->db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".authorize_transactions
			(
				custno,
				status,
				amount,
				action,
				added_on,
				salesmn,
				live,
				memo,
				last4,
				nameoncard,
				ref_transaction_id
			)
			OUTPUT
				INSERTED.transaction_id
			VALUES (
				" . (!empty($this->original_transaction['custno']) ? $this->db->quote($this->original_transaction['custno']) : 'NULL') . ",
				0,
				" . $this->db->quote($this->original_transaction['amount']) . ",
				'void',	
				GETDATE(),
				" . $this->db->quote($session->login['initials']) . ",
				" . (ISLIVE ? '1' : '0') . ",
				" . $this->db->quote($memo) . ",
				" . $this->db->quote($this->original_transaction['last4']) . ",
				" . $this->db->quote($this->original_transaction['nameoncard']) . ",
				" . $this->db->quote($this->original_transaction['transaction_id']) . "
			)
		");
		$void_transaction = $grab_void_transaction->fetch();
		$this->transaction['transaction_id'] = $void_transaction['transaction_id'];

		$void_transaction_result = $authorize->void(
			$this->original_transaction['authorize_transaction_id']
		);

		$this->db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".authorize_transactions
			SET
				debug_request = " . $this->db->quote($void_transaction_result['request']) . ",
				debug_response = " . $this->db->quote($void_transaction_result['response']) . "
			WHERE
				authorize_transactions.transaction_id = " . $this->db->quote($this->transaction['transaction_id']) . "
		");

		if(!empty($void_transaction_result['errors'])) {
			$this->db->query("
				UPDATE
					" . DB_SCHEMA_ERP . ".authorize_transactions
				SET
					status = -1,
					messages = " . $this->db->quote(json_encode($void_transaction_result['errors'])) . "
				WHERE
					authorize_transactions.transaction_id = " . $this->db->quote($this->transaction['transaction_id']) . "
			");

			// Errors returned, raise an exception.
			$message = [];
			foreach($void_transaction_result['errors'] as $error) {
				$message[] = '(Error Code ' . $error['code'] . ') ' . $error['message'];
			}
			$message = implode(', ', $message);
			throw New AuthorizeVoidException($message);
		}

		$this->db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".authorize_transactions
			SET
				status = 1,
				authorize_transaction_id = " . $this->db->quote($void_transaction_result['transaction_id']) . ",
				auth_code = " . $this->db->quote($void_transaction_result['auth_code']) . "
			WHERE
				authorize_transactions.transaction_id = " . $this->db->quote($this->transaction['transaction_id']) . "
		");

		$this->transaction['authorize_transaction_id'] = $void_transaction_result['transaction_id'];
		$this->transaction['auth_code'] = $void_transaction_result['auth_code'];

		return True;
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

	public function getLocalTransactionID() {
		return $this->transaction['transaction_id'];
	}

	public function getAuthorizeTransactionID() {
		return $this->transaction['authorize_transaction_id'];
	}

	public function getAuthCode() {
		return $this->transaction['auth_code'];
	}
}
