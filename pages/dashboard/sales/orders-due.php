<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$date_format = 'Y-m-d';
$now = time();
$yesterday = date($date_format, strtotime('yesterday'));
$today = date($date_format, $now);
$tomorrow = date($date_format, strtotime('tomorrow'));
$first_of_month = date($date_format, strtotime(date('Y-m-01')));
$first_of_year = date($date_format, strtotime(date('Y-01-01')));
$beginning_of_last_year = date($date_format, strtotime((date('Y') - 1) . '-01-01'));
$end_of_last_year = date($date_format, strtotime((date('Y') - 1) . '-12-31'));
$today_date = new DateTime($today);
$fourteen_date_in_future = date($date_format, strtotime('today + 14 days'));

/**
 * FUTURE & PAST DUE
 */
$grab_future = $db->query("
	SELECT
		somast.sono                    AS sales_order_number,    -- Sales Order Number
		somast.orderstat               AS status,                -- SO Status
		somast.terr                    AS territory,             -- Warehouse Territory
		somast.defloc                  AS location,              -- Warehouse Location
		somast.custno                  AS customer_code,         -- Customer Code
		somast.ponum                   AS customer_po,           -- Customer Purchase Order Number
		somast.adduser                 AS who_entered,           -- Who Entered
		CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
		CONVERT(varchar(10), somast.ordate, 120) AS due_date,    -- Due Date
		somast.sostat                  AS sales_order_status,    -- Sales Order Status
		somast.sotype                  AS sales_order_type      -- Sales Order Type
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		(
			somast.ordate > " . $db->quote($tomorrow) . "
			OR
			somast.ordate < " . $db->quote($today) . "
		)
		AND
		somast.ordate <= " . $db->quote($fourteen_date_in_future) . "
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		RTRIM(LTRIM(somast.salesmn)) = " . $db->quote($session->login['initials']) . "
		--RTRIM(LTRIM(somast.salesmn)) = 'JTS'
	ORDER BY
		somast.ordate ASC
");

/**
 * TODAY & TOMOTTOW
 */
$grab_due = $db->query("
	SELECT
		somast.sono                    AS sales_order_number,    -- Sales Order Number
		somast.orderstat               AS status,                -- SO Status
		somast.terr                    AS territory,             -- Warehouse Territory
		somast.defloc                  AS location,              -- Warehouse Location
		somast.custno                  AS customer_code,         -- Customer Code
		somast.ponum                   AS customer_po,           -- Customer Purchase Order Number
		somast.adduser                 AS who_entered,           -- Who Entered
		CONVERT(varchar(10), somast.adddate, 120) AS input_date, -- Add Date
		CONVERT(varchar(10), somast.ordate, 120) AS due_date,    -- Due Date
		somast.sostat                  AS sales_order_status,    -- Sales Order Status
		somast.sotype                  AS sales_order_type      -- Sales Order Type
	FROM
		" . DB_SCHEMA_ERP . ".somast
	WHERE
		somast.ordate >= " . $db->quote($today) . "
		AND
		somast.ordate <= " . $db->quote($tomorrow) . "
		AND
		RTRIM(LTRIM(somast.defloc)) = 'DC'
		AND
		RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
		AND
		RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
		AND
		RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		AND
		RTRIM(LTRIM(somast.salesmn)) = " . $db->quote($session->login['initials']) . "
		--RTRIM(LTRIM(somast.salesmn)) = 'JTS'
	ORDER BY
		somast.ordate ASC
");

?>
<div id="orders-due">
	<style type="text/css">
		#orders-due tr.late:nth-child(odd) > td,
		#orders-due tr.late:nth-child(even) > td {
			background-color:#fcc;
		}
		#orders-due img.late-icon {
			max-width: 24px;
		}
	</style>

	<h2>Upcoming Orders</h2>

	<fieldset>
		<legend>Due Today & Tomorrow</legend>
		<table class="table table-small table-striped table-hover" id="due-today-tomorrow">
			<thead>
				<tr>
					<th>Order Status</th>
					<th>Customer</th>
					<th>SO #</th>
					<th>Territory</th>
					<th>Location</th>
					<th>Customer PO</th>
					<th>Who Entered</th>
					<th>Add Date</th>
					<th>Due Date</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_due as $due) {
					?>
					<tr>
						<td><?php print htmlentities(trim($due['status']));?></td>
						<td><?php print htmlentities(trim($due['customer_code']));?></td>
						<td class="overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities(json_encode(['so-number' => trim($due['sales_order_number'])]), ENT_QUOTES);?>"><?php print htmlentities(trim($due['sales_order_number']));?></td>
						<td><?php print htmlentities(trim($due['territory']));?></td>
						<td><?php print htmlentities(trim($due['location']));?></td>
						<td><?php print htmlentities(trim($due['customer_po']));?></td>
						<td><?php print htmlentities(trim($due['who_entered']));?></td>
						<td><?php print htmlentities(trim($due['input_date']));?></td>
						<td><?php print htmlentities(trim($due['due_date']));?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</fieldset>

	<fieldset>
		<legend>Due In Next 14 Days</legend>
		<table class="table table-small table-striped table-hover" id="due-today-tomorrow">
			<thead>
				<tr>
					<th></th>
					<th>Order Status</th>
					<th>Customer</th>
					<th>SO #</th>
					<th>Territory</th>
					<th>Location</th>
					<th>Customer PO</th>
					<th>Who Entered</th>
					<th>Add Date</th>
					<th>Due Date</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_future as $due) {
					$due_date = new DateTime($due['due_date']);
					$late = False;
					if($due_date < $today_date) {
						$late = True;
					}
					?>
					<tr class="<?php print $late ? 'late' : Null;?>">
						<td><?php
							if($late) {
								?><img src="/interface/images/skullandbones.png" class="late-icon"><?php
							}
						?></td>
						<td><?php print htmlentities(trim($due['status']));?></td>
						<td><?php print htmlentities(trim($due['customer_code']));?></td>
						<td class="overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities(json_encode(['so-number' => trim($due['sales_order_number'])]), ENT_QUOTES);?>"><?php print htmlentities(trim($due['sales_order_number']));?></td>
						<td><?php print htmlentities(trim($due['territory']));?></td>
						<td><?php print htmlentities(trim($due['location']));?></td>
						<td><?php print htmlentities(trim($due['customer_po']));?></td>
						<td><?php print htmlentities(trim($due['who_entered']));?></td>
						<td><?php print htmlentities(trim($due['input_date']));?></td>
						<td><?php print htmlentities(trim($due['due_date']));?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</fieldset>
</div>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
