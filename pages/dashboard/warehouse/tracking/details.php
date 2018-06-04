<?php

$session->ensureLogin();

ob_start(); // Start loading output into buffer.

$grab_shipment = $db->query("
	SELECT
		shipments.shipment_id,
		shipments.sono,
		shipments.carrier,
		shipments.method,
		CONVERT(varchar(10), shipments.added_on, 120) AS added_on,
		shipments.address_name,
		shipments.address_line1,
		shipments.address_line2,
		shipments.address_city,
		shipments.address_state,
		shipments.address_zip,
		shipments.address_country,
		shipments.contact_name,
		shipments.contact_phone,
		shipments.contact_email,
		shipments.debug_request,
		shipments.debug_response,
		shipments.status
	FROM
		" . DB_SCHEMA_ERP . ".shipments
	WHERE
		shipments.shipment_id = " . $db->quote($_POST['shipment_id']) . "
");
$shipment = $grab_shipment->fetch();

$grab_tracking = $db->query("
	SELECT
		shipment_packages.package_id,
		shipment_packages.tracking,
		shipment_packages.weight,
		shipment_packages.length,
		shipment_packages.width,
		shipment_packages.height,
		shipment_packages.label_filename,
		shipment_packages.intelliship_label_url,
		shipment_packages.intelliship_cost as cost,
		shipments.carrier,
		shipments.method
	FROM
		" . DB_SCHEMA_ERP . ".shipment_packages
	INNER JOIN
		" . DB_SCHEMA_ERP . ".shipments
		ON
		shipments.shipment_id = shipment_packages.shipment_id
	WHERE
		shipment_packages.shipment_id = " . $db->quote($shipment['shipment_id']) . "
");

?>

<style type="text/css">
	#tracking-container .inline {
		width:45%;
		display:inline-block;
		vertical-align:top;
	}
	#tracking-container td {
		vertical-align:top;
	}
	#tracking-container .void-shipment {
		background-color: rgb(204, 0, 0);
		border: 1px solid #a00000;
		border-radius: 3px;
		color: rgb(255, 255, 255);
		cursor: pointer;
		font-size: 19px;
		font-weight: bold;
		height: 29px;
		line-height: 29px;
		position: fixed;
		right: 126px;
		text-align: center;
		top: 140px;
		width: 58px;
	}
	#tracking-container .void-shipment:hover {
		cursor:pointer;
	}
	#tracking-container .tracking .fa {
		font-size:22px;
		color:#00f;
		cursor:pointer;
		padding:6px;
	}
</style>

<div id="tracking-container" shipment_id="<?php print htmlentities($shipment['shipment_id'], ENT_QUOTES);?>">
	<h2>Shipping Details</h2>

	<div class="inline">
		<h3>Shipment Info</h3>
		<table>
			<tbody>
				<tr>
					<td>Shipment ID</td>
					<td><?php print htmlentities($shipment['shipment_id']);?></td>
				</tr>
				<tr>
					<td>Status</td>
					<td class="status-content"><?php
						if($shipment['status'] == -2) {
							print 'API Failure';
						} else if($shipment['status'] == -1) {
							print 'Voided';
						} else if($shipment['status'] == 1) {
							print 'Active';
						}
					?></td>
				<tr>
					<td>Shipped Date</td>
					<td><?php print htmlentities($shipment['added_on']);?></td>
				</tr>
				<tr>
					<td>SO #</td>
					<td><?php print htmlentities($shipment['sono']);?></td>
				</tr>
				<tr>
					<td>Contact Info</td>
					<td>
						<span><?php print htmlentities($shipment['contact_name']);?></span>
						<?php print !empty($shipment['contact_phone']) ? '<br /><span>' . htmlentities($shipment['contact_phone']) . '</span>' : Null;?>
						<?php print !empty($shipment['contact_email']) ? '<br /><span>' . htmlentities($shipment['contact_email']) . '</span>' : Null;?>
					</td>
				</tr>
				<tr>
					<td>Shipped To</td>
					<td>
						<span><?php print htmlentities($shipment['address_name']);?></span>
						<br />
						<span><?php print htmlentities($shipment['address_line1']);?></span>
						<?php print !empty($shipment['address_line2']) ? '<br /><span>' . htmlentities($shipment['address_line2']) . '</span>' : Null;?>
						<br />
						<span><?php print htmlentities($shipment['address_city']);?></span>, <?php print htmlentities($shipment['address_state']);?> <?php print htmlentities($shipment['address_zip']);?>
						<br />
						<span><?php print htmlentities($shipment['address_country']);?></span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="inline">
		<h3>Package Info</h3>
		<table>
			<thead>
				<tr>
					<th></th>
					<th>Carrier</th>
					<th>Method</th>
					<th>Tracking #</th>
					<th>Cost</th>
					<th>Weight</th>
					<th>Dimensions (L" x W" x H")</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_tracking as $tracking) {
					if($tracking['carrier'] == 'UPS') {
						$tracking_url = 'https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=';
					} else if($tracking['carrier'] == 'FedEx') {
						$tracking_url = 'https://www.fedex.com/apps/fedextrack/?action=track&cntry_code=us&trackingnumber=';
					} else if($tracking['carrier'] == 'USPS') {
						$tracking_url = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=';
					} else {
						$tracking_url = Null;
					}
					?>
					<tr class="tracking" package_id="<?php print htmlentities($tracking['package_id'], ENT_QUOTES);?>">
						<td class="actions">
							<i class="fa fa-print reprint" title="Reprint shipping label"></i>
							<a href="<?php print BASE_URI;?>/interface/images/shipping-labels/<?php print htmlentities($tracking['label_filename'], ENT_QUOTES);?>" target="label-<?php print htmlentities($tracking['label_filename'], ENT_QUOTES);?>" title="View shipping label"><i class="fa fa-file-image-o showlabel"></i></a>
						</td>
						<td class="carrier"><?php print htmlentities($tracking['carrier']);?></td>
						<td class="method"><?php print htmlentities($tracking['method']);?></td>
						<td class="tracking">
							<?php
							if(!empty($tracking_url)) {
								?><a href="<?php print $tracking_url;?><?php print htmlentities($tracking['tracking'], ENT_QUOTES);?>" target="tracking-<?php print htmlentities($tracking['tracking'], ENT_QUOTES);?>"><?php print htmlentities($tracking['tracking']);?></a><?php
							} else {
								print htmlentities($tracking['tracking']);
							}
							?>
						</td>
						<td class="weight">$<?php print number_format($tracking['cost'], 2);?></td>
						<td class="weight"><?php print number_format($tracking['weight'], 2);?> lbs</td>
						<td class="dimensions"><?php print number_format($tracking['length'], 0);?>" x <?php print number_format($tracking['width'], 0);?>" x <?php print number_format($tracking['height'], 0);?>"</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
	if($shipment['status'] == 1) {
		?><div class="void-shipment">VOID</div><?php
	}
	?>
</div>

<script type="text/javascript">
	$(document).off('click', '#tracking-container .void-shipment');
	$(document).on('click', '#tracking-container .void-shipment', function(event) {
		if(confirm('Are you sure you want to void this shipment and all labels therein?\n\nThis action cannot be undone.')) {
			var $void_icon = $(this);
			var $tracking_container = $('#tracking-container');
			var shipment_id = $tracking_container.attr('shipment_id');
			var data = {
				'shipment_id': shipment_id
			};

			var $void_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': {
					'body': {
						'width': '300px',
						'height': '300px',
						'border-radius': '150px',
						'border': '0px',
						'padding': '0px',
						'line-height': '300px'
					}
				}
			}).fadeIn();
			var $void_overlay_body = $void_overlay.find('.overlayz-body');
			$.ajax({
				'url': BASE_URI + '/dashboard/warehouse/tracking/void',
				'data': data,
				'method': 'POST',
				'dataType': 'json',
				'success': function(response) {
					if(!response.success) {
						if(response.message) {
							alert(response.message);
						} else {
							alert('Error: Something didn\'t go right');
						}
						$void_overlay.fadeOut('fast', function() {
							$void_overlay.remove();
						});
						return false;
					}

					$void_icon.fadeOut('fast');
					var $shipping_container = $('#shipping-container');
					console.log('Shipping Container:', $shipping_container);
					console.log('Find:', '.shipment[shipment_id=' + shipment_id + ']');
					var $shipping_row = $shipping_container.find('.shipment[shipment_id=' + shipment_id + ']');
					$shipping_row.removeClass('shipment-ok').addClass('shipment-voided');
					$shipping_row.find('.content-status').text('VOIDED');
					$tracking_container.find('.status-content').text('Voided');
				},
				'error': function() {
					alert('Error: Something didn\'t go right');
				},
				'complete': function() {
					$void_overlay.fadeOut('fast', function() {
						$(this).remove();
					});
				}
			});
		}
	});

	// Re-Print label
	$(document).off('click', '#tracking-container .tracking .reprint');
	$(document).on('click', '#tracking-container .tracking .reprint', function(event) {
		if(confirm('Are you sure you want to re-print this label?')) {
			var $icon = $(this);
			var $tracking = $icon.closest('.tracking');
			var package_id = $tracking.attr('package_id');

			var $reprint_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': {
					'body': {
						'width': '300px',
						'height': '300px',
						'border-radius': '150px',
						'border': '0px',
						'padding': '0px',
						'line-height': '300px'
					}
				}
			}).fadeIn();
			var $reprint_overlay_body = $reprint_overlay.find('.overlayz-body');

			$.ajax({
				'url': BASE_URI + '/dashboard/warehouse/tracking/label/reprint',
				'data': {
					'package_id': package_id
				},
				'dataType': 'json',
				'method': 'POST',
				'success': function(response) {
					if(!response.success) {
						if(response.message) {
							alert(response.message);
						} else {
							alert('Error: Something didn\'t go right');
						}
						return false;
					}
					// Success.
				},
				'complete': function() {
					$reprint_overlay.fadeOut('fast', function() {
						$reprint_overlay.remove();
					});
				}
			})
		}
	});

</script>

<?php
$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
