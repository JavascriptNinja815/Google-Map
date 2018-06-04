<?php

$session->ensureLogin();

if(empty($_POST['shipment_id'])) {
	print json_encode([
		'success' => False,
		'message' => 'Shipment ID not specified'
	]);
	exit;
}

$grab_shipment_packages = $db->query("
	SELECT
		shipment_packages.intelliship_shipment_id
	FROM
		" . DB_SCHEMA_ERP . ".shipment_packages
	WHERE
		shipment_id = " . $db->quote($_POST['shipment_id']) . "
");
$shipment_packages = $grab_shipment_packages->fetchall();
if(!$shipment_packages) {
	print json_encode([
		'success' => False,
		'message' => 'Shipment does not seem to exist'
	]);
	exit;
}

$debug = [];

// Iterate over each shipment package, requesting it be voided through the Intelliship API.
foreach($shipment_packages as $shipment_package) {
	// Grab Intelliship Credentials from DB.
	if(substr($_SERVER['HTTP_HOST'], 0, strlen('dev.') == 'dev.') !== False) {
		// DEV
		$api_name = 'Intelliship Dev';
	} else {
		// LIVE
		$api_name = 'Intelliship Live';
	}
	//print("\r\nAPI Name: " . $api_name);
	$grab_credentials = $db->query("
		SELECT
			apis.credentials
		FROM
			" . DB_SCHEMA_INTERNAL . ".apis
		WHERE
			apis.name = " . $db->quote($api_name) . "
	");
	$credentials = $grab_credentials->fetch();
	$credentials = json_decode($credentials['credentials'], True);

	$request = [
		// Credentials
		'username' => $credentials['username'],
		'password' => $credentials['password'],
		'shipmentid' => $shipment_package['intelliship_shipment_id']
	];
	$api_url = $credentials['url'] . '/cancle_shipment';
	//print("\r\nAPI URL: " . $api_url);

	// Encode request into a JSON string.
	$request_json = json_encode($request);
	
	//print("\r\nREQUEST: " . $request_json);

	// Set up the request.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_url); // Set the URL.
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, False);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, False);
	curl_setopt($ch, CURLOPT_POST, 1); // Ensure we're set up to POST the data.
	curl_setopt($ch, CURLOPT_POSTFIELDS, [
		'api_params' => $request_json,
		'submit' => 'get response']
	); // This is our raw payload.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, True); // Return response as a variable instead of printing.

	$response_json = curl_exec($ch); // Do it.
	$debug[] = $response_json;
	curl_close($ch);
	$response = json_decode($response_json, True);
	if(!$response || !isset($response['SUCCESS']) || !count($response['SUCCESS'])) {
		print json_encode([
			'success' => False,
			'message' => 'API returned invalid response',
			'debug' => $debug
		]);
		exit;
	} else if($response['SUCCESS'][0] != 1) {
		print json_encode([
			'success' => False,
			'message' => 'API returned a status of failure',
			'debug' => $debug
		]);
		exit;
	}
}

$db->query("
	UPDATE
		" . DB_SCHEMA_ERP . ".shipments
	SET
		status = -1
	WHERE
		shipment_id = " . $db->quote($_POST['shipment_id']) . "
");

print json_encode([
	'success' => True,
	'debug' => $debug
]);
