<?php

$session->ensureLogin();

$args = array(
	'title' => 'Large Orders',
	'breadcrumbs' => array(
		'Large Orders' => BASE_URI . '/dashboard/warehouse/large-orders'
	),
	'body-class' => 'padded'
);

function get_orders(){

	// Get large orders.

	$db = DB::get();
	$q = $db->query("
		DECLARE @today DATE;
		DECLARE @tomorrow DATE;

		SET @today = GETDATE()
		SET @tomorrow = DATEADD(DAY, 1, @today)

		SELECT DISTINCT
			LTRIM(RTRIM(m.sono)) AS sono,
			LTRIM(RTRIM(m.custno)) AS custno,
			t.qtyord - t.shipqty AS total_qty,
			CAST(m.ordate AS date) AS ordate,
			m.orderstat,
			
			CASE
				WHEN m.ordate = @today OR m.ordate = @tomorrow
				THEN 'green'
				WHEN m.ordate < @today
				THEN 'red'
				ELSE 'black'
			END AS color,
			
			'-' AS atps,
			'-' AS move_up,
			'-' AS priority,
			'-' AS estimated_time
		FROM ".DB_SCHEMA_ERP.".somast m
		INNER JOIN ".DB_SCHEMA_ERP.".sotran t
			ON t.sono = m.sono
		WHERE orderstat != 'SHIPPED'
			AND orderstat != ''
			AND orderstat IS NOT NULL
			AND t.qtyord-t.shipqty > 100
			AND m.sostat NOT IN ('C','V')
			AND m.defloc = 'DC'
			AND m.orderstat NOT IN ('SHIPPED', 'PICKUP')
		ORDER BY ordate ASC
	");

	return $q->fetchAll();

}

// Get the large orders.
$orders = get_orders();

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	.green {
		color: #19d819;
	}
	.red {
		color: red;
	}
</style>

<h2>Large Orders</h2>
<div class="row-fluid">
	<table id="orders-table" class="table table-striped table-hover columns-filterable columns-sortable">
		<thead>
			<th class="sortable filterable">Add to Schedule</th>
			<th class="sortable filterable">Move Up</th>
			<th class="sortable filterable">Priority</th>
			<th class="sortable filterable">SO Number</th>
			<th class="sortable filterable">Client</th>
			<th class="sortable filterable">Total Qty</th>
			<th class="sortable filterable">Estimated Time</th>
			<th class="sortable filterable">Required Date</th>
			<th class="sortable filterable">Order Status</th>
		</thead>
		<tbody>
			<?php
			foreach($orders as $order){
			?>
				<tr>

					<?php
						// Formate values for the table.
						$qty = number_format($order['total_qty']);
						$dsono = htmlentities(json_encode(array('so-number'=>$order['sono'])));
						$dclient = htmlentities(json_encode(array('custno'=>$order['custno'])));

						// Get date colors.
						$color = $order['color'];

					?>

					<td class="filterable sortable"><?php print htmlentities($order['atps']) ?></td>
					<td class="filterable sortable"><?php print htmlentities($order['move_up']) ?></td>
					<td class="filterable sortable"><?php print htmlentities($order['priority']) ?></td>
					<td class="filterable sortable overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print $dsono ?>"><?php print htmlentities($order['sono']) ?></td>
					<td class="filterable sortable overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print $dclient ?>"><?php print htmlentities($order['custno']) ?></td>
					<td class="filterable sortable"><?php print $qty ?></td>
					<td class="filterable sortable"><?php print htmlentities($order['estimated_time']) ?></td>
					<td class="filterable sortable <?php print htmlentities($color) ?>"><?php print htmlentities($order['ordate']) ?></td>
					<td class="filterable sortable"><?php print htmlentities($order['orderstat']) ?></td>
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div>