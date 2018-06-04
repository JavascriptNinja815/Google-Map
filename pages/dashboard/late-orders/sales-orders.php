<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_company = $db->query("
	SELECT
		companies.company_id,
		companies.company,
		companies.dbname
	FROM
		Neuron.dbo.companies
	WHERE
		companies.company_id = " . $db->quote(COMPANY) . "
");
$company = $grab_company->fetch();

$today = date('Y-m-d', time());
if($_REQUEST['date'] == date('Y-m-d', time())) {
	// Live data for today.
	$grab_late_sos = $db->query("
		SELECT
			LTRIM(RTRIM(somast.sono)) AS sono, -- Sales Order Number
			DATEDIFF(dd, somast.ordate, getdate()) AS days_past_due, -- Days past due date
			LTRIM(RTRIM(somast.orderstat)) AS orderstat,       -- Order Status
			LTRIM(RTRIM(arcust.custno)) AS custno
		FROM
			" . $company['dbname'] . ".somast
		INNER JOIN
			" . $company['dbname'] . ".sotran
			ON
			sotran.id_col = (
				-- This query ensures multiple rows aren't encountered matching in `sotran`.
				SELECT
					TOP (1) id_col
				FROM
					" . $company['dbname'] . ".sotran
				WHERE
					sono = somast.sono
			)
		INNER JOIN
			" . $company['dbname'] . ".arcust
			ON
			arcust.id_col = (
				-- This query ensures multiple rows aren't encountered matching in `arcust`.
				SELECT
					TOP (1) id_col
				FROM
					" . $company['dbname'] . ".arcust
				WHERE
					custno = somast.custno
			)
		WHERE
			(
				somast.orderstat IN ( -- TODO: Should this be exclusive rather than inclusive?
					'BACKORDER','HOLD','ISS','NSP','ON HOLD','OTHER','PICKING',
					'PRODUCTION','PURCHASING','Q''D - CUSTOM','QCUSTOM','QUEUED',
					'SHIPPING','SSP','STAGED','STAGED FOR SHIP','TRANSFER','VENDOR',
					'WAIT ON PAYMENT','WAIT ON PICKUP','WAIT ON PRODUCT',
					'WAIT ON TRANSFR','WAITING: TRANS','WAITING: VENDOR'
				)
				OR
				somast.orderstat IS NULL
				OR
				somast.orderstat = ''
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
			somast.sotype NOT IN ('B', 'R') -- B = Bid, R = Return
			AND
			somast.ordate < " . $db->quote($today) . " -- Due Date is before today
			AND
			LTRIM(RTRIM(somast.defloc)) = " . $db->quote($_REQUEST['location']) . "
		ORDER BY
			days_past_due DESC
	"); // Cursor args passed equired for retrieving rowCount.
} else {
	// Query for historical data.
	$grab_late_sos = $db->query("
		SELECT
			late_sos.sono,
			late_sos.days_past_due,
			late_sos.orderstat AS orderstat
		FROM
			" . DB_SCHEMA_INTERNAL . ".late_sos
		WHERE
			late_sos.company_id = " . $db->quote(COMPANY) . "
			AND
			late_sos.defloc = " . $db->quote($_REQUEST['location']) . "
			AND
			late_sos.reported_on = " . $db->quote($_REQUEST['date']) . "
		ORDER BY
			late_sos.days_past_due DESC
	");
}

ob_start(); // Start loading output into buffer.
?>
<table>
	<thead>
		<tr>
			<th>Sales Order Number</th>
			<th>Customer</th>
			<th>Days Late</th>
			<th>Current Status</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($grab_late_sos as $late_so) {
			?>
			<tr>
				<td class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities(json_encode(['so-number' => $late_so['sono']]), ENT_QUOTES);?>"><?php print htmlentities($late_so['sono']);?></td>
				<td class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/clients/details" overlayz-data="<?php print htmlentities(json_encode(['custno' => $late_so['custno']]), ENT_QUOTES);?>"><?php print htmlentities($late_so['custno']);?></td>
				<td><?php print number_format($late_so['days_past_due'], 0);?></td>
				<td><?php print htmlentities($late_so['orderstat']);?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
<?php
$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
