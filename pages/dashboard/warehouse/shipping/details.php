<?php

$session->ensureLogin();

function address_missing($sono){

	// Get master SONO and address SONO.
	$db = DB::get();
	$q = $db->query("
		SELECT
			m.sono AS master,
			a.sono AS addr
		FROM " . DB_SCHEMA_ERP . ".somast m
		LEFT JOIN " . DB_SCHEMA_ERP . ".soaddr a
			ON a.sono = m.sono
		WHERE RTRIM(LTRIM(m.sono)) = ".$db->quote($sono)."
	");

	// Avoid processing empty result sets.
	$r = $q->fetch();
	if($r === false){return false;}

	// Get the SONOs.
	$msono = $r['master'];
	$asono = $r['addr'];

	// If a master exists, but no address, the address is missing.
	if(!is_null($msono) and is_null($asono)){
		return true;
	}

	return false;

}

$freight_pay_methods = [
	'1' => 'Prepay & Add to Invoice',
	'2' => 'Prepay & No Charge to Customer',
	'3' => 'Freight Collect by Carrier',
	'4' => 'Collect Certified Funds Only',
	'5' => '3rd Party Bill'
];

$address_query = "
	SELECT
		LTRIM(RTRIM(soaddr.sono)) AS sono,
		LTRIM(RTRIM(soaddr.company)) AS company,
		LTRIM(RTRIM(soaddr.address1)) AS address1,
		LTRIM(RTRIM(soaddr.address2)) AS address2,
		LTRIM(RTRIM(soaddr.address3)) AS address3,
		LTRIM(RTRIM(soaddr.city)) AS city,
		LTRIM(RTRIM(soaddr.addrstate)) AS state,
		LTRIM(RTRIM(soaddr.zip)) AS zip,
		LTRIM(RTRIM(soaddr.country)) AS country,
		countries.alpha2 AS country_iso2,
		LTRIM(RTRIM(arcadr.contact)) AS contact_name,
		arcadr.email AS contact_email,
		LTRIM(RTRIM(arcadr.phone)) AS contact_phone,
		LTRIM(RTRIM(arcadr.upsshpact)) AS ups_account_number,
		LTRIM(RTRIM(arcadr.shpaccno)) AS shipping_account_number,
		LTRIM(RTRIM(somast.shipchg)) AS freight_pay_method,
		LTRIM(RTRIM(somast.shipvia)) AS shipvia
	FROM
		" . DB_SCHEMA_ERP . ".soaddr
	INNER JOIN
		" . DB_SCHEMA_ERP . ".somast
		ON
		LTRIM(RTRIM(somast.sono)) = LTRIM(RTRIM(soaddr.sono))
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcadr
		ON
		LTRIM(RTRIM(arcadr.cshipno)) = LTRIM(RTRIM(soaddr.cshipno))
		AND
		arcadr.custno = soaddr.custno
	LEFT JOIN
		" . DB_SCHEMA_INTERNAL . ".countries
		ON
		countries.alpha3 = LTRIM(RTRIM(soaddr.country))
	WHERE
		LTRIM(RTRIM(soaddr.sono)) = " . $db->quote(trim($_REQUEST['sono'])) . "
";

// Handle missing addresses before assuming the SO doesn't exist.
$missing = address_missing($_REQUEST['sono']);
if($missing){
	print json_encode([
		'success' => false,
		'message' => 'Sales Order Number found but missing shipping address.'
	]);
	exit;
}

$grab_address = $db->query($address_query);
$address = $grab_address->fetch();

if($address === False) {
	print json_encode([
		'success' => False,
		'message' => 'Sales Order Number does not exist'
	]);
	exit;
}

$grab_location = $db->query("
	SELECT
		LTRIM(RTRIM(sotran.loctid)) AS location
	FROM
		" . DB_SCHEMA_ERP . ".sotran
	WHERE
		LTRIM(RTRIM(sotran.sono)) = " . $db->quote(trim($_REQUEST['sono'])) . "
");
$location = $grab_location->fetch();

$freight_pay_method = '';
if(isset($freight_pay_methods[$address['freight_pay_method']])) {
	$freight_pay_method = $freight_pay_methods[$address['freight_pay_method']];
}

$country = 'US';
if($address['country_iso2']) {
	$country = $address['country_iso2'];
} else if($address['country']) {
	$country = $address['country'];
}

print json_encode([
	'success' => True,
	'address' => [
		'sono' => $address['sono'],
		'company' => $address['company'],
		'address1' => $address['address1'],
		'address2' => $address['address2'],
		'address3' => $address['address3'],
		'city' => $address['city'],
		'state' => $address['state'],
		'zip' => $address['zip'],
		'country' => $country,
	],
	'contact' => [
		'name' => $address['contact_name'],
		'phone' => $address['contact_phone'],
		'email' => trim($address['contact_email']),
	],
	'shipvia' => $address['shipvia'],
	'location' => $location['location'],
	'shipping_account_number' => $address['ups_account_number'],
	'freight_pay_method_id' => $address['freight_pay_method'],
	'freight_pay_method' => $freight_pay_method
]);
