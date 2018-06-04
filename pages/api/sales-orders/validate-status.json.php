<?php

// TODO: In the future, query these from the DB.

$valid_statuses = array(
	'PICKUP',
	'SHIPPING',
	'STAGED',
	'PRODUCTION',
	'PICKING',
	'PRINTED',
	'PURCHASING',
	'OTHER',
	'SHIPPED',
	'QCUSTOM',
	'BACKORDER',
	'TRANSFER',
	'VENDOR',
	'QUEUED'
);

if(!isset($_REQUEST['status']) || !in_array($_REQUEST['status'], $valid_statuses)) {
	print json_encode(array(
		'success' => False,
		'message' => 'invalid status'
	));
	exit();
}

print json_encode(array(
	'success' => True
));
