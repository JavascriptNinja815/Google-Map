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
		'Sales Orders' => BASE_URI . '/dashboard/late-orders'
	)
);

$days_to_report = [
	7,
	30,
	90,
	180
];

$today = date('Y-m-d', time());

// Grab a list of distinct locations to report averages on.
$day_seconds = 60 * 60 * 24;
$days_180_seconds = $day_seconds * 180;
$grab_lateso_locations = $db->query("
	SELECT
		late_sos.defloc
	FROM
		" . DB_SCHEMA_INTERNAL . ".late_sos
	WHERE
		late_sos.reported_on >= " . $db->quote(date('Y-m-d', time() - $days_180_seconds)) . "
		AND
		late_sos.company_id = " . $db->quote(COMPANY) . "
	GROUP BY
		late_sos.defloc
");

// This is the date we first began reporting late orders. Don't allow dates
// older than this since it will not yield any useful reports.
$min_date = '2016-08-25';

$live_data = True;
if(!empty($_REQUEST['date'])) { // Date specified.
	$date = $_REQUEST['date'];
	if($date != $today) {
		$live_data = False;
	}
} else { // No date specified, query for current data.
	$date = $today;
}

if($live_data) {
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
			somast.sostat NOT IN ('V', 'ISS', 'SSP', 'NSP') -- V = Void, ISS = In Stock; Ship, SSP = Stage Stock &amp; Purchase, NSP = Non-Stock; Purchase
			AND
			somast.sotype NOT IN ('B', 'R') -- B = Bid, R = Return
			AND
			somast.ordate < " . $db->quote($today) . " -- Due Date is today (after work day, SO is late) or later
		GROUP BY
			LTRIM(RTRIM(somast.defloc))
		ORDER BY
			LTRIM(RTRIM(somast.defloc))
	", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed equired for retrieving rowCount.
} else {
	$grab_report = $db->prepare("
		SELECT
			COUNT(*) AS past_due_count,
			late_sos.defloc
		FROM
			" . DB_SCHEMA_INTERNAL . ".late_sos
		WHERE
			late_sos.reported_on = " . $db->quote($date) . "
			AND
			late_sos.company_id = " . $db->quote(COMPANY) . "
		GROUP BY
			late_sos.defloc
	", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed equired for retrieving rowCount.
}
$grab_report->execute();

Template::Render('header', $args, 'account');
?>

<fieldset>
	<legend>
		<div class="padded-x">Late Order Averages By Location & Date Range</div>
	</legend>
	<table class="table table-small table-striped table-hover">
		<thead>
			<tr>
				<th>Location</th>
				<?php
				foreach($days_to_report as $days) {
					?><th><?php print htmlentities($days);?> Days</th><?php
				}
				?>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_lateso_locations as $lateso_location) {
				?><tr><?php
					?><td><?php print htmlentities($lateso_location['defloc']);?></td><?php
					foreach($days_to_report as $days) {
						$grab_lateso_average = $db->query("
							SELECT
								late_sos.defloc,
								COUNT(*) / " . $days . " AS count
							FROM
								" . DB_SCHEMA_INTERNAL . ".late_sos
							WHERE
								late_sos.reported_on >= " . $db->quote(date('Y-m-d', time() - ($day_seconds * $days))) . "
								AND
								late_sos.defloc = " . $db->quote($lateso_location['defloc']) . "
								AND
								late_sos.company_id = " . $db->quote(COMPANY) . "
							GROUP BY
								late_sos.defloc
						");
						$lateso_average = $grab_lateso_average->fetch();
						?><td><?php print number_format($lateso_average['count'], 0);?></td><?php
					}
				?></tr><?php
			}
			?>
		</tbody>
	</table>
</fieldset>

<div class="padded">
	<fieldset>
		<legend></legend>
		<form method="get" class="form-horizontal">
			<input type="hidden" name="submit" value="date" />

			<div class="search-fields">
				<div class="search-field">
					<div class="control-group">
						<label class="control-label" for="date">Date</label>
						<div class="controls">
							<input type="date" name="date" id="date" min="<?php print $min_date;?>" value="<?php print $date;?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="control-group">
				<div class="controls">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-calendar fa-fw"></i>
						Retrieve
					</button>
				</div>
			</div>

		</form>
	</fieldset>
</div>

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
			<?php
			if(date('F j, Y', strtotime($date)) == date('F j, Y', time())) {
				// Live data.
				?>Current Late Orders<?php
			} else{
				// Historical data.
				?>Late Orders for <?php print date('F j, Y', strtotime($date));?><?php
			}
			?>
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
				'date': '<?php print $date;?>',
				'location': $row.attr('location')
			};
			$.ajax({
				'url': BASE_URI + '/dashboard/late-orders/sales-orders',
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
