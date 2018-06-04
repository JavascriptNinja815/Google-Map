<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2016, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'Orders: Late',
	'breadcrumbs' => array(
		'Orders' => BASE_URI . '/dashboard/sales-order-status',
		'Sales Orders' => BASE_URI . '/dashboard/pending-invoice'
	)
);

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

$grab_report = $db->prepare("
	SELECT
		COUNT(*) AS past_due_count,
		LTRIM(RTRIM(somast.defloc)) AS defloc -- Warehouse Location
	FROM
		" . DB_SCHEMA_ERP . ".somast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".sotran
		ON
		sotran.id_col = (
			-- This query ensures multiple rows aren't encountered matching in `sotran`.
			SELECT
				TOP (1) id_col
			FROM
				" . DB_SCHEMA_ERP . ".sotran
			WHERE
				sono = somast.sono
		)
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
		somast.orderstat IN ('SHIPPED', 'BACKORDER')
		AND
		somast.sostat NOT IN ('C', 'V') -- Closed, Voided
		AND
		somast.sotype NOT IN ('B', 'R') -- B = Bid, R = Return
		" . (!empty($where) ? ' AND ' . implode(' AND ', $where) : Null) . "
	GROUP BY
		LTRIM(RTRIM(somast.defloc))
	ORDER BY
		LTRIM(RTRIM(somast.defloc))
", [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]); // Cursor args passed equired for retrieving rowCount.
$grab_report->execute();

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#reports-container .location {
		width:46px;
	}
	#reports-container .past-due-count {
		cursor:pointer;
		color:#00f;
	}
</style>

<fieldset>
	<legend>
		<div class="padded-x">
			SOs Pending Invoice
		</div>
	</legend>
	<table id="reports-container" class="table table-small table-striped table-hover">
		<thead>
			<tr>
				<th>Location</th>
				<th>Order Count</th>
			</tr>
		</thead>
		<tbody>
			<?php
			while($order = $grab_report->fetch()) {
				?>
				<tr location="<?php print htmlentities($order['defloc'], ENT_QUOTES);?>">
					<td class="location"><?php print htmlentities($order['defloc']);?></td>
					<td class="past-due-count"><?php print number_format($order['past_due_count'], 0);?> past due</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</fieldset>

<script type="text/javascript">
	$(document).off('click', '#reports-container .past-due-count');
	$(document).on('click', '#reports-container .past-due-count', function(event) {
		var $row = $(this).closest('tr');
		var $next_row = $row.next();
	
		// Determine wheter we need to close the list of sales orders or show it.
		if($next_row.hasClass('sales-orders')) {
			$next_row.remove();
		} else {
			var $tr = $('<tr>').addClass('sales-orders').insertAfter($row);
			var $td = $('<td colspan="2">').appendTo($tr);
			$td.append(
				$ajax_loading_prototype.clone()
			);

			var data = {
				'location': $row.attr('location')
			};
			$.ajax({
				'url': BASE_URI + '/dashboard/pending-invoice/sales-orders',
				'method': 'POST',
				'data': data,
				'dataType': 'json',
				'success': function(response) {
					$td.html(response.html);
				}
			});
		}
	});
</script>

<?php Template::Render('footer', 'account');
