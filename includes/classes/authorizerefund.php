<?php

class AuthorizeRefundException extends Exception {}

class AuthorizeRefund {
	private $original_transaction;
	private $transaction;
	private $authorize_transaction_id;
	private $authorize;

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
			throw new AuthorizeRefundException('Authorize Transaction ID specified does not exist in the local database');
		}

		$this->transaction = [
			'authorize_transaction_id' => Null,
			'last4' => Null,
			'amount' => Null
		];
	}

	public function refund($amount, $memo) {
		global $session;

		$this->transaction['amount'] = $amount;

		$authorize = $this->__getAuthorizeObject();

		// Insert the initial entry.
		$grab_refund_transaction = $this->db->query("
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
				" . $this->db->quote($this->transaction['amount']) . ",
				'refund',	
				GETDATE(),
				" . $this->db->quote($session->login['initials']) . ",
				" . (ISLIVE ? '1' : '0') . ",
				" . $this->db->quote($memo) . ",
				" . $this->db->quote($this->original_transaction['last4']) . ",
				" . $this->db->quote($this->original_transaction['nameoncard']) . ",
				" . $this->db->quote($this->original_transaction['transaction_id']) . "
			)
		");
		$refund_transaction = $grab_refund_transaction->fetch();

		$refund_transaction_result = $authorize->refund(
			$this->original_transaction['authorize_transaction_id'],
			$this->original_transaction['last4'],
			$this->transaction['amount']
		);

		$this->db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".authorize_transactions
			SET
				debug_request = " . $this->db->quote($refund_transaction_result['request']) . ",
				debug_response = " . $this->db->quote($refund_transaction_result['response']) . "
			WHERE
				authorize_transactions.transaction_id = " . $this->db->quote($refund_transaction['transaction_id']) . "
		");

		if(!empty($refund_transaction_result['errors'])) {
			$this->db->query("
				UPDATE
					" . DB_SCHEMA_ERP . ".authorize_transactions
				SET
					status = -1,
					messages = " . $this->db->quote(json_encode($refund_transaction_result['errors'])) . "
				WHERE
					authorize_transactions.transaction_id = " . $this->db->quote($refund_transaction['transaction_id']) . "
			");

			// Errors returned, raise an exception.
			$message = [];
			foreach($refund_transaction_result['errors'] as $error) {
				$message[] = '(Error Code ' . $error['code'] . ') ' . $error['message'];
			}
			$message = implode(', ', $message);
			throw New AuthorizeRefundException($message);
		}

		$this->db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".authorize_transactions
			SET
				status = 1,
				authorize_transaction_id = " . $this->db->quote($refund_transaction_result['transaction_id']) . "
			WHERE
				authorize_transactions.transaction_id = " . $this->db->quote($refund_transaction['transaction_id']) . "
		");

		$this->authorize_transaction_id = $refund_transaction_result['transaction_id'];

		return True;
	}
	
	public function getAuthorizeTransactionID() {
		return $this->authorize_transaction_id;
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
