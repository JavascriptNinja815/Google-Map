<?php

/*
Temporarily disabled this.
I'm not sure why this page requires login, but its preventing the Hot Orders
from appearing on the corp display. What changed? - Jake
*/

//$session->ensureLogin();

$one_day = 86400;
$today = date('Y-m-d', time());
$today_ts = strtotime($today);
$tomorrow = date('Y-m-d', time() + $one_day);
$two_weeks_out = date('Y-m-d', time() + ($one_day * 14));

if(isset($_REQUEST['type'])) {
	$grab_orders = $db->query("
		SELECT
			somast.sono                    AS sales_order_number,    -- Sales Order Number
			somast.orderstat               AS status,                -- SO Status
			somast.terr                    AS territory,             -- Warehouse Territory
			somast.defloc                  AS location,              -- Warehouse Location
			somast.custno                  AS customer_code,         -- Customer Code
			somast.ponum                   AS customer_po,           -- Customer Purchase Order Number
			LTRIM(RTRIM(somast.salesmn))   AS sales_person,          -- Sales Person
			somast.adduser                 AS who_entered,           -- Who Entered
			CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
			CONVERT(varchar(10), somast.ordate, 120) AS due_date,    -- Due Date
			somast.sostat                  AS sales_order_status,    -- Sales Order Status
			somast.sotype                  AS sales_order_type       -- Sales Order Type
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.hot = 1
			AND
			somast.sostat NOT IN ('V', 'C')
			AND
			somast.sotype NOT IN ('B', 'R')
			AND
			RTRIM(LTRIM(somast.defloc)) = 'DC'
			AND
			" . (
				$_REQUEST['type'] == 'priority' ?
					// Today & Tomorrow
					"
					somast.ordate <= " . $db->quote($tomorrow) . " -- Tomorrow at the latest
					AND
					somast.ordate >= " . $db->quote($today) . " -- Today at the earliest
					"
				: $_REQUEST['type'] == 'outlook' ?
					// Next Two Weeks
					"
					(
						somast.ordate < " . $db->quote($today) . "
						OR
						somast.ordate > " . $db->quote($tomorrow) . "
					)
					"
				: $_REQUEST['type'] == 'today' ?
					// Today only.
					"
					(
						somast.ordate = " . $db->quote($today) . "
					)
					"
				:
					Null
			) . "
		ORDER BY
			somast.ordate ASC
	");

	/*
	$grab_sales_orders = $db->query("
		SELECT
			somast.sono                    AS sales_order_number,    -- Sales Order Number
			somast.orderstat               AS status,                -- SO Status
			somast.terr                    AS territory,             -- Warehouse Territory
			somast.defloc                  AS location,              -- Warehouse Location
			somast.custno                  AS customer_code,         -- Customer Code
			somast.ponum                   AS customer_po,           -- Customer Purchase Order Number
			LTRIM(RTRIM(somast.salesmn))   AS sales_person,          -- Sales Person
			somast.adduser                 AS who_entered,           -- Who Entered
			CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
			CONVERT(varchar(10), somast.ordate, 120) AS due_date,    -- Due Date
			somast.sostat                  AS sales_order_status,    -- Sales Order Status
			somast.sotype                  AS sales_order_type       -- Sales Order Type
		FROM
			" . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.hot = 1
			AND
			somast.sostat NOT IN ('V', 'C')
			AND
			somast.sotype NOT IN ('B', 'R')
			AND
			RTRIM(LTRIM(somast.defloc)) = 'DC'
	");
	*/

	$hot = [];
	foreach($grab_orders as $order) {
		$due_date = new DateTime($order['due_date']);
		$late = False;
		if($due_date < $today) {
			$late = True;
		}
		$hot[] = [
			'sales_order_number' => trim($order['sales_order_number']),
			'status' => trim($order['status']),
			'territory' => trim($order['territory']),
			'location' => trim($order['location']),
			'customer_code' => trim($order['customer_code']),
			'customer_po' => trim($order['customer_po']),
			'sales_person' => trim($order['sales_person']),
			'who_entered' => trim($order['who_entered']),
			'input_date' => trim($order['input_date']),
			'due_date' => trim($order['due_date']),
			'sales_order_status' => trim($order['sales_order_status']),
			'sales_order_type' => trim($order['sales_order_type']),
			'late' => $late
		];
	}

	print json_encode([
		'datetime' => date('n/j/Y \a\t g:ia', time()),
		'hot' => $hot
	]);
}
