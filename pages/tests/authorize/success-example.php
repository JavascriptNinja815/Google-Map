<?php

$xml = '﻿<?xml version="1.0" encoding="utf-8"?>
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
</createTransactionResponse>';

