<?php

define('AUTHORIZE_LOGINID', '6Xk88Qu5h');
define('AUTHORIZE_KEY', '7979cw3QwjT5LLfm');

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

$invoice_no = 'abc-124';

$payload = '<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
	<merchantAuthentication>
		<name>' . AUTHORIZE_LOGINID . '</name>
		<transactionKey>' . AUTHORIZE_KEY . '</transactionKey>
	</merchantAuthentication>
	<transactionRequest>
		<transactionType>authCaptureTransaction</transactionType>
		<amount>' . $amount . '</amount>
		<payment>
			<creditCard>
				<cardNumber>' . $card['number'] . '</cardNumber>
				<expirationDate>' . $card['expiration'] . '</expirationDate>
				<cardCode>' . $card['cvv'] . '</cardCode>
			</creditCard>
		</payment>
		<order>
			<invoiceNumber>' . $invoice_no . '</invoiceNumber>
		</order>
		<billTo>
			<firstName>Ellen</firstName>
			<lastName>Johnson</lastName>
			<company>Souveniropolis</company>
			<address>14 Main Street</address>
			<city>Pecan Springs</city>
			<state>TX</state>
			<zip>44628</zip>
			<country>USA</country>
		</billTo>
	</transactionRequest>
</createTransactionRequest>';

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

/**
 * SUCCESS RESPONSE
 * 
﻿<?xml version="1.0" encoding="utf-8"?>
<createTransactionResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
	<messages>
		<resultCode>Ok</resultCode>
		<message>
			<code>I00001</code>
			<text>Successful.</text>
		</message>
	</messages>
	<transactionResponse>
		<responseCode>1</responseCode>
		<authCode>0SZ68D</authCode>
		<avsResultCode>Y</avsResultCode>
		<cvvResultCode>P</cvvResultCode>
		<cavvResultCode>2</cavvResultCode>
		<transId>40005162441</transId>
		<refTransID />
		<transHash>9AF1B68E6D44A5A0506DC9133B7F5783</transHash>
		<testRequest>0</testRequest>
		<accountNumber>XXXX1111</accountNumber>
		<accountType>Visa</accountType>
		<messages>
			<message>
				<code>1</code>
				<description>This transaction has been approved.</description>
			</message>
		</messages>
		<transHashSha2 />
	</transactionResponse>
</createTransactionResponse>
 */

/**
 * FAILURE RESPONSE
 * 
﻿<?xml version="1.0" encoding="utf-8"?>
<createTransactionResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
	<messages>
		<resultCode>Error</resultCode>
		<message>
			<code>E00027</code>
			<text>The transaction was unsuccessful.</text>
		</message>
	</messages>
	<transactionResponse>
		<responseCode>3</responseCode>
		<authCode />
		<avsResultCode>P</avsResultCode>
		<cvvResultCode />
		<cavvResultCode />
		<transId>0</transId>
		<refTransID />
		<transHash>342FC45F3616C07F3A8BFFB9238186A2</transHash>
		<testRequest>0</testRequest>
		<accountNumber>XXXX1111</accountNumber>
		<accountType>Visa</accountType>
		<errors>
			<error>
				<errorCode>11</errorCode>
				<errorText>A duplicate transaction has been submitted.</errorText>
			</error>
		</errors>
		<transHashSha2 />
	</transactionResponse>
</createTransactionResponse>
 */
