<?php

$session->ensureLogin();

/**
 * Ensure customer code exists
 */
if(!isset($_POST['existing-customer']) || $_POST['existing-customer'] == '1') {
	$grab_client = $db->query("
		SELECT
			arcust.custno
		FROM
			" . DB_SCHEMA_ERP . ".arcust
		WHERE
			arcust.custno = " . $db->quote($_POST['custno']) . "
	");
	$client = $grab_client->fetch();
	if(!$client) {
		print json_encode([
			'success' => False,
			'message' => 'Client Code specified is invalid'
		]);
		exit;
	}
}

/**
 * Ensure all Payment fields present
 */
if(empty($_POST['amount']) || !(float)$_POST['amount']) {
	print json_encode([
		'success' => False,
		'message' => 'Amount must be specified'
	]);
	exit;
}
if(empty($_POST['payment_profile_id'])) {
	$_POST['payment_profile_id'] = 'different';
}

/**
 * Ensure all card fields present.
 */
if($_POST['payment_profile_id'] == 'different') {
	// Ensure the customer code is valid.
	//if(empty($_POST['nameoncard'])) {
	//	print json_encode([
	//		'success' => False,
	//		'message' => 'Name On Card must be specified'
	//	]);
	//	exit;
	//}
	if(empty($_POST['card_number'])) {
		print json_encode([
			'success' => False,
			'message' => 'Card Number must be specified'
		]);
		exit;
	}
	if(empty($_POST['expiration_month'])) {
		print json_encode([
			'success' => False,
			'message' => 'Expiration Month must be specified'
		]);
		exit;
	}
	if(empty($_POST['expiration_year'])) {
		print json_encode([
			'success' => False,
			'message' => 'Expiration Year must be specified'
		]);
		exit;
	}
	if(empty($_POST['code'])) {
		print json_encode([
			'success' => False,
			'message' => 'Security Code must be specified'
		]);
		exit;
	}
} else {
	// Ensure we can look up the card on file.
}

$authorize = new Authorize(
	AUTHORIZE_ENDPOINT, // Endpoint. Either `production` or `sandbox`.
	AUTHORIZE_LOGINID,
	AUTHORIZE_KEY
);

$custno = explode(' - ', $_POST['custno']);
$custno = $custno[0];

if($_POST['payment_profile_id'] == 'different') {
	$last4 = substr($_POST['card_number'], -4);
	$expiration = $_POST['expiration_year'] . '-' . $_POST['expiration_month'];
} else {
	$grab_payment_profile = $db->query("
		SELECT
			authorize_payment_profiles.payment_profile_id,
			authorize_payment_profiles.last4,
			authorize_payment_profiles.expiration,
			authorize_payment_profiles.address,
			authorize_payment_profiles.zip,
			authorize_payment_profiles.nameoncard
		FROM
			" . DB_SCHEMA_ERP . ".authorize_payment_profiles
		WHERE
			authorize_payment_profiles.payment_profile_id = " . $db->quote($_POST['payment_profile_id']) . "
	");
	$payment_profile = $grab_payment_profile->fetch();
	$last4 = $payment_profile['last4'];
	$expiration = $payment_profile['expiration'];
	$payment_profile_id = $payment_profile['payment_profile_id'];

	// Update Billing Address stored on Payment Profile, if it has changed.
	if($payment_profile['address'] != $_POST['billing_address']) {
		$db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".authorize_payment_profiles
			SET
				address = " . $db->quote($_POST['billing_address']) . "
			WHERE
				authorize_payment_profiles.payment_profile_id = " . $db->quote($payment_profile['payment_profile_id']) . "
		");
	}
	// Update Billing Zip Code stored on Payment Profile, if it has changed.
	if($payment_profile['zip'] != $_POST['billing_zip']) {
		$db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".authorize_payment_profiles
			SET
				zip = " . $db->quote($_POST['billing_zip']) . "
			WHERE
				authorize_payment_profiles.payment_profile_id = " . $db->quote($payment_profile['payment_profile_id']) . "
		");
	}
}

if(isset($_POST['payment_action'])) {
	$payment_action = $_POST['payment_action'];
} else {
	$payment_action = 'charge';
}

// Insert transaction attempt entry
$grab_transaction = $db->query("
	INSERT INTO
		" . DB_SCHEMA_ERP . ".authorize_transactions
	(
		custno,
		payment_profile_id,
		authorize_transaction_id,
		status,
		amount,
		action,
		last4,
		expiration,
		messages,
		live,
		salesmn,
		memo,
		nameoncard
	)
	OUTPUT
		INSERTED.transaction_id
	VALUES (
		" . $db->quote($custno) . ",
		" . ($_POST['payment_profile_id'] != 'different' ? $db->quote($_POST['payment_profile_id']) : 'NULL') . ",
		NULL, -- authorize_transaction_id set after API calls.
		0, -- status
		" . $db->quote($_POST['amount']) . ",
		" . $db->quote($payment_action) . ",
		" . $db->quote($last4) . ",
		" . $db->quote($expiration) . ",
		NULL, -- messages set after API calls.
		" . $db->quote(ISLIVE ? '1' : '0') . ",
		" . $db->quote($session->login['initials']) . ",
		" . $db->quote($_POST['memo']) . ",
		" . $db->quote(!empty($payment_profile) ? $payment_profile['nameoncard'] : $_POST['nameoncard']) . "
	)
");
$transaction = $grab_transaction->fetch();

if(isset($_POST['existing-customer']) && $_POST['existing-customer'] == '0') {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".authorize_transactions
		SET
			address1 = " . $db->quote($_POST['buyer']['street']) . ",
			address2 = " . $db->quote($_POST['buyer']['street2']) . ",
			city = " . $db->quote($_POST['buyer']['city']) . ",
			state = " . $db->quote($_POST['buyer']['state']) . ",
			zip = " . $db->quote($_POST['buyer']['zip']) . ",
			email = " . $db->quote($_POST['buyer']['email']) . ",
			phone = " . $db->quote($_POST['buyer']['phone']) . ",
			name = " . $db->quote($_POST['buyer']['name']) . ",
			company = " . $db->quote($_POST['buyer']['company']) . ",
			ap_email = " . $db->quote($_POST['ap']['email']) . ",
			ap_phone = " . $db->quote($_POST['ap']['phone']) . ",
			ap_name = " . $db->quote($_POST['ap']['name']) . "
		WHERE
			authorize_transactions.transaction_id = " . $db->quote($transaction['transaction_id']) . "
	");
}

if(!empty($_POST['buyer']) || !empty($_POST['ap']) || !empty($_POST['tax_exempt'])) {
	$customer = [];
	if(!empty($_POST['buyer'])) {
		if(!empty($_POST['buyer']['name'])) {
			$customer['name'] = $db->quote($_POST['buyer']['name']);
		}
		if(!empty($_POST['buyer']['company'])) {
			$customer['company'] = $db->quote($_POST['buyer']['company']);
		}
		if(!empty($_POST['buyer']['phone'])) {
			$customer['phone'] = $db->quote($_POST['buyer']['phone']);
		}
		if(!empty($_POST['buyer']['email'])) {
			$customer['email'] = $db->quote($_POST['buyer']['email']);
		}
		if(!empty($_POST['buyer']['street'])) {
			$customer['street'] = $db->quote($_POST['buyer']['street']);
		}
		if(!empty($_POST['buyer']['city'])) {
			$customer['city'] = $db->quote($_POST['buyer']['city']);
		}
		if(!empty($_POST['buyer']['state'])) {
			$customer['state'] = $db->quote($_POST['buyer']['state']);
		}
		if(!empty($_POST['buyer']['zip'])) {
			$customer['zip'] = $db->quote($_POST['buyer']['zip']);
		}
	}
	if(!empty($_POST['ap'])) {
		if(!empty($_POST['ap']['name'])) {
			$customer['ap_name'] = $db->quote($_POST['ap']['name']);
		}
		if(!empty($_POST['ap']['email'])) {
			$customer['ap_email'] = $db->quote($_POST['ap']['email']);
		}
		if(!empty($_POST['ap']['phone'])) {
			$customer['ap_phone'] = $db->quote($_POST['ap']['phone']);
		}
	}
	if(!empty($_POST['tax_exempt'])) {
		$customer['tax_exempt'] = $db->quote($_POST['tax_exempt']);
	}

	if(!empty($customer)) {
		$customer['transaction_id'] = $db->quote($transaction['transaction_id']);
		$fields = array_keys($customer);
		$fields = implode(', ', $fields);

		$values = array_values($customer);
		$values = implode(', ', $values);
		if(!empty($customer)) {
			$db->query("
				INSERT INTO
					" . DB_SCHEMA_ERP . ".authorize_transaction_customers
				(
					" . $fields . "
				) VALUES (
					" . $values . "
				)
			");
		}
	}
}

if(!empty($_POST['invnos']) && is_array($_POST['invnos'])) {
	foreach($_POST['invnos'] as $invno => $amount) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".authorize_transaction_relations
			(
				transaction_id,
				relation_type,
				relation_type_id,
				amount
			) VALUES (
				" . $db->quote($transaction['transaction_id']) . ",
				" . $db->quote('invno') . ",
				" . $db->quote($invno) . ",
				" . $db->quote(number_format($amount, 2, '.', '')) . "
			)
		");
	}
}
if(!empty($_POST['sonos']) && is_array($_POST['sonos'])) {
	foreach($_POST['sonos'] AS $sono => $amount) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".authorize_transaction_relations
			(
				transaction_id,
				relation_type,
				relation_type_id,
				amount
			) VALUES (
				" . $db->quote($transaction['transaction_id']) . ",
				" . $db->quote('sono') . ",
				" . $db->quote($sono) . ",
				" . $db->quote(number_format($amount, 2, '.', '')) . "
			)
		");
	}
}

// Determine the type of action to perform (Auth vs Auth & Capture)
if(empty($_POST['payment_action']) || $_POST['payment_action'] == 'charge') {
	$transaction_type = 'authCaptureTransaction';
} else if($_POST['payment_action'] == 'authorize') {
	$transaction_type = 'authOnlyTransaction';
}

// Deterine whether we're charging a card number, or a payment profile.
if($_POST['payment_profile_id'] == 'different') {
	// WE'RE USING A MANUALLY ENTERED CARD.

	if(!empty($_POST['save-card']) && $_POST['save-card'] == 1) {
		// WE'RE SAVING THIS CARD AS A PAYMENT PROFILE.

		// Ensure customer has an Authorize Customer Profile ID.
		$grab_customer_profile = $db->query("
			SELECT
				authorize_customer_profiles.customer_profile_id,
				authorize_customer_profiles.authorize_custprofile_id
			FROM
				" . DB_SCHEMA_ERP . ".authorize_customer_profiles
			WHERE
				authorize_customer_profiles.custno = " . $db->quote($custno) . "
				AND
				authorize_customer_profiles.live = " . $db->quote(ISLIVE ? '1' : '0') . "
		");
		$customer_profile = $grab_customer_profile->fetch();
		if(!$customer_profile) {
			// No customer profile yet, must be created.
			$authorize_profile_ids = $authorize->createCustomerAndPaymentProfile($custno, $_POST['card_number'], $expiration, $_POST['code'], $_POST['billing_address'], $_POST['billing_zip']);
			if(!$authorize_profile_ids['success']) {
				print json_encode([
					'success' => False,
					'errors' => $authorize_profile_ids['errors']
				]);
				exit;
			}
			$authorize_custprofile_id = $authorize_profile_ids['customer_profile_id'];
			$authorize_payprofile_id = $authorize_profile_ids['payment_profile_id'];

			// Insert profile IDs into the DB.
			$grab_customer_profile = $db->query("
				INSERT INTO
					" . DB_SCHEMA_ERP . ".authorize_customer_profiles
				(
					custno,
					authorize_custprofile_id,
					live,
					salesmn
				)
				OUTPUT
					INSERTED.customer_profile_id
				VALUES (
					" . $db->quote($custno) . ",
					" . $db->quote($authorize_profile_ids['customer_profile_id']) . ",
					" . $db->quote(ISLIVE ? '1' : '0') . ",
					" . $db->quote($session->login['initials']) . "
				)
			");
			$customer_profile = $grab_customer_profile->fetch();

		} else {
			// Customer profile exists, must create payment profile.
			$authorize_custprofile_id = $customer_profile['authorize_custprofile_id'];
			$authorize_paymentprofile_response = $authorize->createPaymentProfile($authorize_custprofile_id, $_POST['card_number'], $expiration, $_POST['code'], $_POST['billing_address'], $_POST['billing_zip']);
			if(!$authorize_paymentprofile_response['success']) {
				print json_encode([
					'success' => False,
					'errors' => $authorize_paymentprofile_response['errors']
				]);
				exit;
			}
			$authorize_payprofile_id = $authorize_paymentprofile_response['payment_profile_id'];
		}

		// Save the newly created Payment Profile to the database.
		$db->query("
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
				salesmn,
				address,
				zip
			) VALUES (
				" . $db->quote($customer_profile['customer_profile_id']) . ",
				" . $db->quote($authorize_payprofile_id) . ",
				" . $db->quote($last4) . ",
				" . $db->quote($_POST['code']) . ",
				" . $db->quote($expiration) . ",
				" . $db->quote($_POST['nameoncard']) . ",
				" . $db->quote($_POST['nickname']) . ",
				" . $db->quote(ISLIVE ? '1' : '0') . ",
				" . $db->quote($session->login['initials']) . ",
				" . $db->quote($_POST['billing_address']) . ",
				" . $db->quote($_POST['billing_zip']) . "
			)
		");

		// NOW THAT PAYMENT PROFILE HAS BEEN CREATED, CHARGE THE PAYMENT PROFILE.
		//if(empty($_POST['payment_action']) || $_POST['payment_action'] == 'charge') {
		$result = $authorize->authCaptureWithPaymentProfile($_POST['amount'], $authorize_custprofile_id, $authorize_payprofile_id, Null, $_POST['billing_address'], $_POST['billing_zip']);
		//} else if($_POST['payment_action'] == 'authorize') {
		//	$result = $authorize->authWithPaymentProfile($_POST['amount'], $authorize_custprofile_id, $authorize_payprofile_id, Null, $_POST['billing_address'], $_POST['billing_zip']);
		//}
	} else {
		// CHARGE THE MANUALLY ENTERED CARD

		//if(empty($_POST['payment_action']) || $_POST['payment_action'] == 'charge') {
		$result = $authorize->authCaptureWithCard($_POST['amount'], $_POST['card_number'], $expiration, $_POST['code'], Null, $_POST['billing_address'], $_POST['billing_zip']);
		//} else if($_POST['payment_action'] == 'authorize') {
		//	$result = $authorize->authWithCard($_POST['amount'], $_POST['card_number'], $expiration, $_POST['code'], Null, $_POST['billing_address'], $_POST['billing_zip']);
		//}
	}
} else {
	// CHARGE THE PAYMENT PROFILE SPECIFIED
	$grab_profile_ids = $db->query("
		SELECT
			authorize_customer_profiles.authorize_custprofile_id,
			authorize_payment_profiles.authorize_payprofile_id
		FROM
			" . DB_SCHEMA_ERP . ".authorize_payment_profiles
		INNER JOIN
			" . DB_SCHEMA_ERP . ".authorize_customer_profiles
			ON
			authorize_customer_profiles.customer_profile_id = authorize_payment_profiles.customer_profile_id
			AND
			authorize_customer_profiles.live = " . $db->quote(ISLIVE ? '1' : '0') . "
		WHERE
			authorize_payment_profiles.payment_profile_id = " . $db->quote($_POST['payment_profile_id']) . "
			AND
			authorize_payment_profiles.live = " . $db->quote(ISLIVE ? '1' : '0') . "
	");
	$profile_ids = $grab_profile_ids->fetch();
	$authorize_custprofile_id = $profile_ids['authorize_custprofile_id'];
	$authorize_payprofile_id = $profile_ids['authorize_payprofile_id'];
	//if(empty($_POST['payment_action']) || $_POST['payment_action'] == 'charge') {
	$result = $authorize->authCaptureWithPaymentProfile($_POST['amount'], $authorize_custprofile_id, $authorize_payprofile_id);
	//} else if($_POST['payment_action'] == 'authorize') {
	//	$result = $authorize->authWithPaymentProfile($_POST['amount'], $authorize_custprofile_id, $authorize_payprofile_id);
	//}
}

// Store requests and responses.
$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".authorize_transactions
	SET
		debug_request = " . $db->quote(
			str_replace(
				'<cardNumber>' . htmlspecialchars($_POST['card_number'], ENT_XML1) . '</cardNumber>',
				'<cardNumber>XXXXXXXXXXXXXXXX</cardNumber>',
				$result['request']
			)
		) . ",
		debug_response = " . $db->quote($result['response']) . "
	WHERE
		authorize_transactions.transaction_id = " . $db->quote($transaction['transaction_id']) . "
");

if(!$result['success']) {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".authorize_transactions
		SET
			status = -1,
			messages = " . $db->quote(json_encode($result['errors'])) . "
		WHERE
			authorize_transactions.transaction_id = " . $db->quote($transaction['transaction_id']) . "
	");
	print json_encode([
		'success' => False,
		'response' => $result['response'],
		'errors' => $result['errors'],
		'request' => $result['request']
	]);
	exit;
}

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".authorize_transactions
	SET
		status = 1,
		authorize_transaction_id = " . $db->quote($result['transaction_id']) . ",
		auth_code = " . $db->quote($result['auth_code']) . "
	WHERE
		authorize_transactions.transaction_id = " . $db->quote($transaction['transaction_id']) . "
");

print json_encode([
	'success' => True,
	'response' => $result['response'],
	'auth_code' => $result['auth_code'],
	'request' => $result['request'],
	'transaction_id' => $transaction['transaction_id']
]);
