<?php

$xml = '<?xml version="1.0" encoding="utf-8"?>
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
</createTransactionResponse>';

