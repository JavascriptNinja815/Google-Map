<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_orders = $db->query("
	SELECT
		somast.sono,
		somast.orderstat AS status,
		somast.hot,
		somast.sono,
		somast.ponum,
		somast.ordamt AS open_amount,
		somast.shpamt AS invoiced_amount,
		somast.shipvia,
		somast.pterms AS payment_terms,
		somast.notes,
		CONVERT(varchar(10), somast.adddate, 120) AS adddate,
		CONVERT(varchar(10), somast.ordate, 120) AS ordate,
		somast.adduser
	FROM 
		" . DB_SCHEMA_ERP . ".arcust
	INNER JOIN
		" . DB_SCHEMA_ERP . ".somast
		ON
		somast.custno = arcust.custno
	WHERE
		RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
		AND
		somast.sostat NOT IN ('C', 'V')
		AND
		somast.sotype != 'B'
	ORDER BY
		somast.adddate
");

?>

<h2>Open Orders</h2>

<table class="orders">
	<thead>
		<tr>
			<th>Status</th>
			<th>Hot</th>
			<th>SO No.</th>
			<th>PO No.</th>
			<th>Open $</th>
			<th>Invoiced $</th>
			<th>Ship Via</th>
			<th>Terms</th>
			<th>Notes</th>
			<th>Add Date</th>
			<th>Due Date</th>
			<th>Add User</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($grab_orders as $order) {
			?><tr class="order" sono="<?php print htmlentities(trim($order['sono']), ENT_QUOTES);?>"><?php
				?><td class="client-order-status"><?php print htmlentities(trim($order['status']));?></td><?php
				?><td class="client-order-hot"><?php
					if(trim($order['hot']) == 1) {
						print '<i class="fa fa-fw fa-fire"></i>';
					}
				?></td><?php
				?><td class="client-order-sono overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities(
						json_encode([
							'so-number' => $order['sono']
						])
					);?>"><?php print htmlentities(trim($order['sono']));?></td><?php
				?><td class="client-order-pono"><?php print htmlentities(trim($order['ponum']));?></td><?php
				?><td class="client-order-openamount">$<?php print number_format($order['open_amount'], 2);?></td><?php
				?><td class="client-order-invoicedamount">$<?php print number_format($order['invoiced_amount'], 2);?></td><?php
				?><td class="client-order-shipvia"<?php print htmlentities(trim($order['shipvia']));?></td><?php
				?><td class="client-order-terms"><?php print htmlentities(trim($order['payment_terms']));?></td><?php
				?><td class="client-order-notes"><?php
					?><i class="fa fa-file-text notes-icon overlayz-link <?php print empty(trim($order['notes'])) ? 'faded' : Null;?>" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities(
						json_encode([
							'so-number' => $order['sono']
						])
					);?>"></i><?php
				?></td><?php
				?><td class="client-order-adddate"><?php print htmlentities(trim($order['adddate']));?></td><?php
				?><td class="client-order-duedate"><?php print htmlentities(trim($order['ordate']));?></td><?php
				?><td class="client-order-adduser"><?php print htmlentities(trim($order['adduser']));?></td><?php
			?></tr><?php
		}
		?>
	</tbody>
</table>

<script type="text/javascript">
	// Bind to clicks on "Edit Address" icon.
	$(document).off('click', '#client-details-page .orders .order .action-edit-notes');
	$(document).on('click', '#client-details-page .orders .order .action-edit-notes', function(event) {
		alert('TODO: Ability to edit notes.');
	});

	// Bind to search form submissions
	$(document).off('submit', '#client-details-page form.search-form');
	$(document).on('submit', '#client-details-page form.search-form', function(event) {
		alert('TODO: Implement search inputs and filtering.');
		return false;
	});
</script>
