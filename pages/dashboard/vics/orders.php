<?php

$session->ensureLogin();

$one_day = 86400;
$today = date('Y-m-d', time());
$today_ts = strtotime($today);
$tomorrow = date('Y-m-d', time() + $one_day);
$two_weeks_out = date('Y-m-d', time() + ($one_day * 14));

if(isset($_REQUEST['type'])) {
	if(!$session->hasRole('Administration') && $session->hasRole('Sales')) {
		$client_permissions = $session->getPermissions('Sales', 'view-orders');

		if(!empty($client_permissions)) {
			// Sanitize values for DB querying.
			$client_permissions = array_map(function($value) {
				$db = \PM\DB\SQL::connection();
				return $db->quote($value);
			}, $client_permissions);
			$permission_constraint = "arcust.salesmn IN (" . implode(',', $client_permissions) . ")";
		} else {
			// If the user has not been granted any privs, then finish the query which will already return nothing.
			// TODO: There has to be a more elegant method of handling this...
			$permission_constraint = "1 != 1";
		}
	} else {
		$permission_constraint = "1 = 1";
	}
	if($_REQUEST['type'] == 'priority') {
		$grab_orders = $db->query("
			SELECT
				RTRIM(LTRIM(somast.ordate)) AS ordate,
				RTRIM(LTRIM(arcust.company)) AS company,
				RTRIM(LTRIM(somast.custno)) AS custno,
				RTRIM(LTRIM(somast.sono)) AS sono,
				RTRIM(LTRIM(somast.ponum)) AS ponum,
				LTRIM(RTRIM(somast.orderstat)) AS orderstat,
				LTRIM(RTRIM(somast.defloc)) AS defloc -- Warehouse Location
			FROM
				" . DB_SCHEMA_ERP . ".somast
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				arcust.id_col = (
					-- This query ensures multiple rows aren't encountered matching in `arcust`.
					SELECT
						TOP (1) id_col
					FROM
						" . DB_SCHEMA_ERP . ".arcust
					WHERE
						custno = somast.custno
				)
			WHERE
				arcust.vic = 1
				AND
				somast.orderstat IN ( -- TODO: Should this be exclusive rather than inclusive?
					'BACKORDER','HOLD','ISS','NSP','ON HOLD','OTHER','PICKING',
					'PRODUCTION','PURCHASING','Q''D - CUSTOM','QCUSTOM','QUEUED',
					'SHIPPING','SHIPPED','SSP','STAGED','STAGED FOR SHIP','TRANSFER','VENDOR',
					'WAIT ON PAYMENT','WAIT ON PICKUP','WAIT ON PRODUCT',
					'WAIT ON TRANSFR','WAITING: TRANS','WAITING: VENDOR',''
				)
				AND
				(
					(
						(somast.sotype IS NULL OR somast.sotype = '')
						AND
						(somast.sostat IS NULL OR somast.sostat = '')
					)
					OR
					(
						somast.sotype = 'O'
						AND
						(somast.sostat IS NULL
						OR
						somast.sostat != 'C')
					)
				)
				AND 
				somast.sostat NOT IN ('V', 'ISS', 'SSP', 'NSP') -- V = Void, ISS = In Stock; Ship, SSP = Stage Stock &amp; Purchase, NSP = Non-Stock; Purchase
				AND
				somast.sotype NOT IN ('B', 'R', 'V') -- B = Bid, R = Return
				AND
				somast.ordate <= " . $db->quote($tomorrow) . " -- Tomorrow at the latest
				AND
				somast.ordate >= " . $db->quote($today) . " -- Today at the earliest
				AND
				" . $permission_constraint . "
			ORDER BY
				somast.ordate
		");
	} else if($_REQUEST['type'] == 'outlook') {
		$grab_orders = $db->query("
			SELECT
				RTRIM(LTRIM(somast.ordate)) AS ordate,
				RTRIM(LTRIM(arcust.company)) AS company,
				RTRIM(LTRIM(somast.custno)) AS custno,
				RTRIM(LTRIM(somast.sono)) AS sono,
				RTRIM(LTRIM(somast.ponum)) AS ponum,
				LTRIM(RTRIM(somast.orderstat)) AS orderstat,
				LTRIM(RTRIM(somast.defloc)) AS defloc -- Warehouse Location
			FROM
				" . DB_SCHEMA_ERP . ".somast
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				arcust.id_col = (
					-- This query ensures multiple rows aren't encountered matching in `arcust`.
					SELECT
						TOP (1) id_col
					FROM
						" . DB_SCHEMA_ERP . ".arcust
					WHERE
						custno = somast.custno
				)
			WHERE
				arcust.vic = 1
				AND
				somast.orderstat IN ( -- TODO: Should this be exclusive rather than inclusive?
					'BACKORDER','HOLD','ISS','NSP','ON HOLD','OTHER','PICKING',
					'PRODUCTION','PURCHASING','Q''D - CUSTOM','QCUSTOM','QUEUED',
					'SHIPPING','SSP','STAGED','STAGED FOR SHIP','TRANSFER','VENDOR',
					'WAIT ON PAYMENT','WAIT ON PICKUP','WAIT ON PRODUCT',
					'WAIT ON TRANSFR','WAITING: TRANS','WAITING: VENDOR',''
				)
				AND
				(
					(
						(somast.sotype IS NULL OR somast.sotype = '')
						AND
						(somast.sostat IS NULL OR somast.sostat = '')
					)
					OR
					(
						somast.sotype = 'O'
						AND
						(somast.sostat IS NULL
						OR
						somast.sostat != 'C')
					)
				)
				AND
				somast.sotype NOT IN ('B', 'R', 'V') -- B = Bid, R = Return
				AND 
				somast.sostat NOT IN ('V', 'ISS', 'SSP', 'NSP') -- V = Void, ISS = In Stock; Ship, SSP = Stage Stock &amp; Purchase, NSP = Non-Stock; Purchase
				AND
				(
					somast.ordate < " . $db->quote($today) . "
					OR
					somast.ordate > " . $db->quote($tomorrow) . "
				)
				AND
				somast.ordate <= " . $db->quote($two_weeks_out) . "
				AND
				" . $permission_constraint . "
			ORDER BY
				somast.ordate
		");
	}
	$orders = [];
	foreach($grab_orders as $order) {
		$order_duedate_ts = strtotime($order['ordate']);
		$late = $order_duedate_ts < $today_ts ? 'late' : Null;

		$orders[] = [
			'ordate' => date('Y-m-d', strtotime($order['ordate'])),
			'company' => $order['company'],
			'custno' => $order['custno'],
			'sono' => $order['sono'],
			'ponum' => $order['ponum'],
			'orderstat' => $order['orderstat'],
			'defloc' => $order['defloc'],
			'late' => $order_duedate_ts < $today_ts ? true : false
		];
	}
	print json_encode([
		'success' => True,
		'datetime' => date('n/j/Y \a\t g:ia', time()),
		'orders' => $orders
	]);
}
