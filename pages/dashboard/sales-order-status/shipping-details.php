<?php

$html = '<h2>Sales Order #: ' . $_POST['so-number'] . ' Shipping Details</h2>';

// Grab a connection to the Fox Pro shipping tracking database.
$proups = new PDO(DB_FOXPRO_SOTRACKING);

// Tells PDO to throw an exception if something goes wrong, rather than just returning False.
$proups->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$grab_shipping_details = $proups->query("
	SELECT
		sofups.sonol AS sono,
		sofups.trkno AS tracking_number,
		sofups.shpamt AS cost,
		sofups.voidship AS voided,
		sofups.pkgwght AS weight,
		sofups.pickupdate AS shipped_date,
		sotups.address1,
		sotups.address2,
		sotups.city,
		sotups.addrstate,
		sotups.zip,
		sotups.country,
		sotups.phone AS phone_number,
		sotups.faxno AS fax_number,
		sotups.shptoemail AS email_address,
		sotups.upsservice AS shipping_method
	FROM
		sofups
	LEFT JOIN
		sotups
		ON
		sofups.sonol = sotups.sonol
	WHERE
		sofups.sonol = " . $db->quote($_POST['so-number']) . "
	ORDER BY
		sofups.pickupdate
");
$html .= '<table id="so-details-container">';
$html .=	'<thead>';
$html .=		'<tr>';
$html .=			'<th></th>';
$html .=			'<th>SO #</th>';
$html .=			'<th>Carrier</th>';
$html .=			'<th>Method</th>';
$html .=			'<th>Tracking Number</th>';
$html .=			'<th>Shipped On</th>';
$html .=			'<th>Weight</th>';
$html .=			'<th>Cost</th>';
$html .=			'<th>Shipping Address</th>';
$html .=			'<th>Contact Info</th>';
$html .=		'</tr>';
$html .=	'</thead>';
$html .=	'<tbody>';
$shipping_details_count = 0;
while($shipping_details = $grab_shipping_details->fetch()) {
	$shipping_details_count++;
	$carrier = substr($shipping_details['tracking_number'], 0, 2) == '1Z' ? 'UPS' : 'FedEx';

	$shipping_address = array();
	$shipping_address[] = htmlentities($shipping_details['address1']);
	trim($shipping_details['address2']) ? $shipping_address[] = htmlentities($shipping_details['address2']) : Null;
	$shipping_address[] = htmlentities($shipping_details['city']) . ', ' . htmlentities($shipping_details['addrstate']) . ' ' . htmlentities($shipping_details['zip']);
	$shipping_address[] = htmlentities($shipping_details['country']);
	$shipping_address = implode('<br />', $shipping_address);

	$shipping_contact = array();
	trim($shipping_details['phone_number']) ? $shipping_contact[] = 'Phone: <a href="tel:' . preg_replace('/[^0-9]/', '', $shipping_details['phone_number']) . '">' . htmlentities($shipping_details['phone_number']) . '</a>' : Null;
	trim($shipping_details['fax_number']) ? $shipping_contact[] = 'Fax: ' . htmlentities($shipping_details['fax_number']) : Null;
	trim($shipping_details['email_address']) ? $shipping_contact[] = '<a href="mailto:' . htmlentities($shipping_details['email_address'], ENT_QUOTES) . '">' . htmlentities($shipping_details['email_address']) . '</a>' : Null;
	$shipping_contact = implode('<br />', $shipping_contact);

	$tracking_number_url = $carrier == 'UPS' ?
		// UPS
		'http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=' . trim($shipping_details['tracking_number']) . '&loc=en_us'
		:
		// FedEx
		'https://www.fedex.com/fedextrack/WTRK/index.html?action=track&tracknumbers=' . trim($shipping_details['tracking_number'], ENT_QUOTES) . '&language=en&cntry_code=us&r=g&fdx=1490'
	;
	$tracking_number = '<a href="' . htmlentities($tracking_number_url, ENT_QUOTES) . '" target="tracking-' . htmlentities($tracking_number_url, ENT_QUOTES) . '">' . htmlentities($shipping_details['tracking_number']) . '</a>';

	$shipped_date = strtotime($shipping_details['shipped_date']);

	$html .=	'<tr>';
	$html .=		'<td class="content content-voided">' . ($shipping_details['voided'] == 'Y' ? '<b>VOIDED</b>' : Null) . '</td>';
	$html .=		'<td class="content content-so-number">' . htmlentities(trim($shipping_details['sono'])) . '</td>';
	$html .=		'<td class="content content-carrier">' . $carrier . '</td>';
	$html .=		'<td class="content content-method">' . htmlentities($shipping_details['shipping_method']) . '</td>';
	$html .=		'<td class="content content-tracking-number">' . $tracking_number . '</td>';
	$html .=		'<td class="content content-shipped-date">' . htmlentities($shipping_details['shipped_date']) . '</td>';
	$html .=		'<td class="content content-weight">' . number_format($shipping_details['weight'], 4) . '</td>';
	$html .=		'<td class="content content-cost">$' . number_format($shipping_details['cost'], 2) . '</td>';
	$html .=		'<td class="content content-shipping-address">' . $shipping_address . '</td>';
	$html .=		'<td class="content content-shipping-contact">' . $shipping_contact . '</td>';
	$html .=	'</tr>';
}

$html .=	'</tbody>';
$html .= '</table>';

if(!$shipping_details_count) {
	$html .= '<p><br /><b>No shipping details were found.</b></p>';
}

$html .= '<p><br /><i>Please note: Currently, only UPS and FedEx shipping details are available. USPS, DHL, Freight, etc. are not available for display.</i></p>';

print json_encode(array(
	'success' => True,
	'html' => $html
));