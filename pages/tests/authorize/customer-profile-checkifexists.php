<?php


define('AUTHORIZE_LOGINID', '6Xk88Qu5h'); // SANDBOX
define('AUTHORIZE_KEY', '7979cw3QwjT5LLfm'); // SANDBOX
// define('AUTHORIZE_LOGINID', '8sY9FtB49W2Z'); // LIVE
// define('AUTHORIZE_KEY', '6x285d5q82De2ZPx'); // LIVE

$authorize = new Authorize(
	'sandbox', // Endpoint. Either `production` or `sandbox`.
	AUTHORIZE_LOGINID,
	AUTHORIZE_KEY
);

$urls = [
	'production' => 'https://api2.authorize.net/xml/v1/request.api',
	'sandbox' => 'https://apitest.authorize.net/xml/v1/request.api'
];

$amount = '12.34';
$card = [
	'number' => '4111111111111111',
	'expiration' => '12/17',
	'cvv' => '123'
];

$CUSTNO = 'ABC01';
$invoice_no = 'abc-124';

$payload = '<?xml version="1.0" encoding="utf-8"?>
<getCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
	<merchantAuthentication>
		<name>' . AUTHORIZE_LOGINID . '</name>
		<transactionKey>' . AUTHORIZE_KEY . '</transactionKey>
	</merchantAuthentication>
	<customerProfileId>' . $CUSTNO . '</customerProfileId>
</getCustomerProfileRequest>';

$request = curl_init();
curl_setopt($request, CURLOPT_URL, $urls['sandbox']);
curl_setopt($request, CURLOPT_RETURNTRANSFER, True);
curl_setopt($request, CURLOPT_POST, True);
curl_setopt($request, CURLOPT_POSTFIELDS, $payload);
curl_setopt($request, CURLOPT_SSL_VERIFYPEER, False);
$response_xml = curl_exec($request);
curl_close ($request);
$response = new DOMDocument;
$response->loadXML($response_xml);

print '<pre>';
print htmlentities($response);
print '</pre>';
