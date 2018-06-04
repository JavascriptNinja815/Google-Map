<?php

$session->ensureLogin();

$grab_package = $db->query("
	SELECT
		shipment_packages.label_filename
	FROM
		" . DB_SCHEMA_ERP . ".shipment_packages
	WHERE
		shipment_packages.package_id = " . $db->quote($_POST['package_id']) . "
");
$package = $grab_package->fetch();

// Determine whether dev or live path/
if(substr($_SERVER['HTTP_HOST'], 0, strlen('dev.')) == 'dev.') {
	// DEV
	$label_network_path = '\\\\glcad1\\shipping-labels-dev';
} else {
	// LIVE
	$label_network_path = '\\\\glcad1\\shipping-labels-live';
}

$image_paths = [
	$label_network_path . '\\' . $package['label_filename']
];

// Print to BarTender.
$labelPrinter = new LabelPrinter();
$labelPrinter->printIntellishipLabelsByFilename(
	'\\\\glc-fs1\\DC WHS LBL1 Zebra GX430t',
	//'\\\\glc-dc1\\GodexEZPi-1300_GZPL', // Printer.
	$image_paths // List of labels to print.
);

print json_encode([
	'success' => True,
]);
