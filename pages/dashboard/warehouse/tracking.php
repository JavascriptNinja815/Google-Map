<?php

set_time_limit (120);

$session->ensureLogin();

$args = array(
	'title' => 'Tracking',
	'breadcrumbs' => array(
		'Warehouse' => BASE_URI . '/dashboard/warehouse/shipping',
		'Tracking' => BASE_URI . '/dashboard/warehouse/tracking',
	)
);

$month_ago = date('Y-m-d', strtotime('1 month ago'));

// Determine whether we're in live or dev.
if(substr($_SERVER['HTTP_HOST'], 0, strlen('dev.')) == 'dev.') {
	// DEV
	$live = 0;
} else {
	// LIVE
	$live = 1;
}

$grab_shipments = $db->query("
	DECLARE @days_ago INTEGER
	DECLARE @now DATE
	DECLARE @then DATE

	SET @days_ago = -30
	SET @now = GETDATE()
	SET @then = DATEADD(DAY, @days_ago, @now)

	SELECT DISTINCT
		p.tracking,
		shipments.shipment_id,
		LTRIM(RTRIM(shipments.sono)) AS sono,
		shipments.carrier,
		shipments.method,
		shipments.added_on,
		LTRIM(RTRIM(somast.custno)) AS custno,
		shipments.status,
		
		CASE WHEN EXISTS(
			SELECT 1
			FROM PRO01.dbo.sotran t
			WHERE LTRIM(RTRIM(t.sono)) = LTRIM(RTRIM(shipments.sono))
				AND t.descrip COLLATE Latin1_General_BIN = p.tracking
		) THEN 1 ELSE 0
		END AS in_pro
	FROM
		" . DB_SCHEMA_ERP . ".shipments
	INNER JOIN
		" . DB_SCHEMA_ERP . ".somast
		ON
		LTRIM(RTRIM(somast.sono)) = LTRIM(RTRIM(shipments.sono))
	LEFT JOIN PRO01.dbo.shipment_packages p
		ON p.shipment_id = shipments.shipment_id
	WHERE shipments.added_on >= @then
		AND shipments.live = " . $db->quote($live) . "
	ORDER BY shipments.added_on DESC
");

Template::Render('header', $args, 'account');

?>

<style type="text/css">
	#shipping-body .shipment.shipment-voided td {
		background-color:#fee;
	}
	#shipping-body .shipment.shipment-apifailure td {
		background-color:#aaa;
	}
	.fa-exclamation {
		color: red;
		margin-right: 30%;
	}
</style>

<div class="padded" id="shipping-body">
	<h2>Shipment Tracking</h2>
	<table class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable" id="shipping-table">
		<thead>
			<tr>
				<th class="sortable">Status</th>
				<th class="filterable sortable">Shipment ID</th>
				<th class="filterable sortable">SO #</th>
				<th class="filterable sortable">Client Code</th>
				<th class="filterable sortable">Carrier</th>
				<th class="filterable sortable">Tracking</th>
				<th class="filterable sortable">Method</th>
				<th class="filterable sortable">Shipped On</th>
			</tr>
		</thead>
		<tbody id="shipping-container">
			<?php
			foreach($grab_shipments as $shipment) {
				$shipment_data = json_encode([
					'shipment_id' => $shipment['shipment_id']
				]);
				$sono_data = json_encode([
					'so-number' => trim($shipment['sono'])
				]);
				$cust_data = json_encode([
					'custno' => trim($shipment['custno'])
				]);

				if($shipment['status'] == -2) {
					$status_class = 'shipment-apifailure';
				} else if($shipment['status'] == -1) {
					$status_class = 'shipment-voided';
				} else if($shipment['status'] == 1) {
					$status_class = 'shipment-ok';
				}
				?>
				<tr class="shipment <?php print $status_class;?>" shipment_id="<?php print htmlentities($shipment['shipment_id'], ENT_QUOTES);?>">
					<td class="content content-status"><?php
						if($shipment['status'] == -1) {
							print 'VOIDED';
						} else if($shipment['status'] == 1) {
							print 'OK';
						} else {
							print 'API Failure';
						}
					?></td>
					<td class="content content-id overlayz-link" overlayz-url="/dashboard/warehouse/tracking/details" overlayz-data="<?php print htmlentities($shipment_data, ENT_QUOTES);?>"><?php print htmlentities($shipment['shipment_id']);?></td>
					<td class="content content-sono overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities($sono_data, ENT_QUOTES);?>"><?php print htmlentities($shipment['sono']);?></td>
					<td class="content content-custno overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities($cust_data, ENT_QUOTES);?>"><?php print htmlentities($shipment['custno']);?></td>
					<td class="content content-carrier"><?php print htmlentities($shipment['carrier']);?></td>

					<?php
						// Flag tracking numbers not in PRO.
						$tracking = htmlentities($shipment['tracking']);
						if($tracking & $shipment['in_pro']!=1){
							$tracking .= '<i class="fa fa-fw fa-exclamation pull-right"></i>';
						}

					//

					?>
					<td class="content content-tracking"><?php print $tracking;?></td>
					<td class="content content-method"><?php print htmlentities($shipment['method']);?></td>
					<td class="content content-addedon"><?php print date('Y-m-d', strtotime($shipment['added_on']));?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
<?php

Template::Render('footer', 'account');
