<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$from_date = $_POST['date'];
$to_date = date('Y-m-t', strtotime($_POST['date'])); // "t" in "Y-m-d" grabs last day in month.

$where = [];

if($session->hasRole('Administration')) {
	// Administrator, nothing to constrain by.
} else if($session->hasRole('Sales')) {
	// Salesman, constrain by permissions.
	$salesman_permissions = [
		// Add salesman's own initials to the permissions.
		trim($session->login['initials'])
	];
	$retrieved_permissions = $session->getPermissions('Sales', 'view-orders');
	if($retrieved_permissions) {
		$salesman_permissions = array_merge($salesman_permissions, $retrieved_permissions);
	}
	$salesman_permissions_quoted = array_map(
		[$db, 'quote'],
		$salesman_permissions
	);
	$where[] = "LTRIM(RTRIM(arcust.salesmn)) IN (" . implode(',', $salesman_permissions_quoted) . ")";
} else {
	// Individual does not have permission to be viewing this page.
	print json_encode([
		'success' => False,
		'message' => 'Invalid permissions for sales'
	]);
	exit;
}

if($_REQUEST['type'] == 'individual') {
	$where[] = "LTRIM(RTRIM(arcust.salesmn)) = " . $db->quote(trim($_POST['value']));
} else if($_REQUEST['type'] == 'territory') {
	$where[] = "LTRIM(RTRIM(arcust.terr)) = " . $db->quote(trim($_POST['value']));
} else {
	// Individual does not have permission to be viewing this page.
	print json_encode([
		'success' => False,
		'message' => 'Invalid permissions for view'
	]);
	exit;
}

$grab_invoices_query = "
	SELECT
		arcust.salesmn AS salesman,
		artran.invno,
		artran.custno,
		arcust.company,
		artran.invdte,
		armast.ponum,
		armast.ornum,
		SUM(artran.qtyshp  * artran.cost) AS cost,
		SUM(artran.qtyshp * artran.price) AS invamt,
		(
			CASE WHEN SUM(artran.qtyshp * artran.price) > 0.00 THEN
				(
					SUM(artran.qtyshp * artran.price) - SUM(artran.qtyshp  * artran.cost)
				) / SUM(artran.qtyshp * artran.price)
			ELSE
				0.00
			END
		) AS margin
	FROM
		" . DB_SCHEMA_ERP . ".armast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".artran
		ON
		artran.invno = armast.invno
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.custno = artran.custno
	WHERE
		armast.arstat != 'V'
		AND
		armast.invdte >= " . $db->quote($from_date) . "
		AND
		armast.invdte <= " . $db->quote($to_date) . "
		AND
		" . implode($where, "\r\n\t\tAND\r\n\t\t") . "
	GROUP BY
		arcust.salesmn,
		artran.invno,
		artran.custno,
		arcust.company,
		artran.invdte,
		armast.ponum,
		armast.ornum
	ORDER BY
		margin ASC
";
$grab_invoices = $db->query($grab_invoices_query);

ob_start(); // Start loading output into buffer.
?>

<style type="text/css">
	#average-margin-container {
		position:absolute;
		top:15px;
		right:80px;
		font-size:60px;
		font-weight:bold;
		line-height:60px;
	}
</style>

<h2 class="padded">Invoice Margins for <?php print htmlentities($_POST['value']);?></h2>

<i class="bad">Notice: Due to freight calculations and potentially other small parts, these numbers may not perfectly reflect actual margins. This is a work in progress.</i>

<table id="invoice-margins-table" class="columns-sortable columns-filterable headers-sticky table-small">
	<thead>
		<tr>
			<th class="sortable filterable">Salesman</th>
			<th class="sortable filterable">Client Code</th>
			<th class="sortable filterable">Client</th>
			<th class="sortable filterable">Invoice No.</th>
			<th class="sortable filterable">Invoice Date</th>
			<th class="sortable filterable">PO Number</th>
			<th class="sortable filterable">SO Number</th>
			<th class="sortable filterable">Invoice $</th>
			<th class="sortable filterable">Margin %</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$total_price = 0.00;
		$total_cost = 0.00;
		foreach($grab_invoices as $invoice) {
			$margin = $invoice['margin'] * 100;
			$total_price += (float)$invoice['invamt'];
			$total_cost += (float)$invoice['cost'];
			//$margins[] = $margin;

			$customer_data = json_encode([
				'custno' => trim($invoice['custno'])
			]);
			$invoice_data = json_encode([
				'invno' => trim($invoice['invno'])
			]);
			$so_data = json_encode([
				'so-number' => trim($invoice['ornum'])
			])
			?>
			<tr>
				<td class="salesman"><?php print htmlentities($invoice['salesman']);?></td>
				<td class="customer-code overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities($customer_data, ENT_QUOTES);?>"><?php print htmlentities($invoice['custno']);?></td>
				<td class="customer-name"><?php print htmlentities($invoice['company']);?></td>
				<td class="invoice-no overlayz-link" overlayz-url="/dashboard/invoice/details" overlayz-data="<?php print htmlentities($invoice_data, ENT_QUOTES);?>"><?php print htmlentities($invoice['invno']);?></td>
				<td class="invoice-date"><?php print date('Y-m-d', strtotime($invoice['invdte']));?></td>
				<td class="po-number"><?php print htmlentities($invoice['ponum']);?></td>
				<td class="so-number overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities($so_data, ENT_QUOTES);?>"><?php print htmlentities($invoice['ornum']);?></td>
				<td class="invoice-amount">$<?php print number_format($invoice['invamt'], 2);?></td>
				<td class="margin <?php print $margin < 0 ? 'bad' : 'good';?>"><?php print number_format($margin, 1);?>%</td>
			</tr>
			<?php
		}
		$overall_margin = 0;
		if($total_price > 0) {
			// Prevents division by zero error
			$overall_margin = 100 - ((100 / $total_price) * $total_cost);
		}
		//$margin_average = array_sum($margins) / count($margins);
		?>
	</tbody>
</table>

<div id="average-margin-container"><?php print number_format($overall_margin, 0);?>%</div>

<script type="text/javascript">
	$(function() {
		/*
		 * Bind table features such as floating table header and solrtable/filterable column headers.
		 */
		var $table = $('#invoice-margins-table');
		var $table_container = $table.closest('.overlayz-body');

		applyTableFeatures(
			$table, // The table we're adding the features to.
			$table_container // The container we want the sticky header to attach to.
		);
	});
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
