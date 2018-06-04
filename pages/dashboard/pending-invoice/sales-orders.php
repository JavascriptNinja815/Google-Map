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

$where = [];

// Ensure client list is constrained based on permissions.
if(!$session->hasRole('Administration') && $session->hasRole('Sales')) {
	$client_permissions = $session->getPermissions('Sales', 'view-orders');
	if(!empty($client_permissions)) {
		$where[] = "arcust.salesmn IN ('" . implode("','", $client_permissions) . "')";
	} else {
		// If the user has not been granted any privs, then finish the query which will already return nothing.
		// TODO: There has to be a more elegant method of handling this...
		$where[] = "1 != 1";
	}
}

// Live data for today.
$grab_late_sos = $db->prepare("
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
		somast.orderstat IN ('SHIPPED', 'BACKORDER')
		AND
		somast.sostat NOT IN ('C', 'V') -- Closed, Voided
		AND
		somast.sotype NOT IN ('B', 'R') -- B = Bid, R = Return
		AND
		LTRIM(RTRIM(somast.defloc)) = " . $db->quote($_REQUEST['location']) . "
		" . (!empty($where) ? ' AND ' . implode(' AND ', $where) : Null) . "
	ORDER BY
		days_past_due DESC
", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]); // Cursor args passed equired for retrieving rowCount.
$grab_late_sos->execute();

ob_start(); // Start loading output into buffer.
?>
<h4><?php print number_format($grab_late_sos->rowCount(), 0);?> Result(s) Returned</h4>
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
