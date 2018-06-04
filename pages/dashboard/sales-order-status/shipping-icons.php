<?php

/**
 * This script is used in an AJAX request by /dashboard/sales-order-status,
 * which returns a list of sales orders containing tracking information.
 */

if(!empty($_POST['sales-order-ids'])) {
	$command = '\python27\python.exe "\xampp\htdocs\DEV\scripts\ups.py" "' . PROUPS_FILENAME . '"';
	print $command;
	passthru($command);
	exit();

	// Grab a connection to the Fox Pro shipping tracking database.
	$proups = new PDO(DB_FOXPRO_SOTRACKING);
	$proups->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// Iterate over Sales Order IDs passed and query FoxPro for each one. We
	// can't aggregate the query into a single WHERE sonol IN (...) syntax,
	// because FoxPro throws a "SQL expression is too complex" error because
	// it runs out of memory...
	$sales_order_ids = array();
	foreach($_POST['sales-order-ids'] as $sales_order_id) {
		// Query for sales orders which contain tracking numbers.
		$grab_tracking = $proups->query("
			SELECT
				sofups.sonol AS sono
			FROM
				sofups
			WHERE
				sofups.sonol = " . $db->quote($sales_order_id) . "
			GROUP BY
				sofups.sonol
		");
		$tracking = $grab_tracking->fetch();
		if(!empty($tracking)) {
			$sales_order_ids[] = intval($tracking['sono']);
		}
	}

	$result = array(
		'success' => True,
		'sales-order-ids' => $sales_order_ids
	);
} else {
	$result = array(
		'success' => False,
		'message' => 'No Sales Order IDs passed'
	);
}

print json_encode($result);
