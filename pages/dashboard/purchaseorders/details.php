<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2018, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

// Grab PO Details.
$grab_po = $db->query("
	SELECT
		LTRIM(RTRIM(pomast.purno)) AS purno,
		LTRIM(RTRIM(pomast.vendno)) AS vendno,
		CONVERT(varchar(10), pomast.reqdate, 120) AS reqdate,
		pomast.cnf_price AS price_confirmation,
		pomast.cnf_del AS delivery_confirmation,
		pomast.postat,
		pomast.potype,
		pomast.loctid,
		pomast.notes,
		CONVERT(varchar(10), pomast.adddate, 120) AS adddate,
		pomast.adduser
	FROM
		" . DB_SCHEMA_ERP . ".pomast
	WHERE
		LTRIM(RTRIM(pomast.purno)) = " . $db->quote($_POST['purno']) . "
");
$po = $grab_po->fetch();

// Grab vendor line items matching this PO, that have mismatching line items.
$grab_unmapped_vendoritems = $db->query("
	SELECT
		po_statuses.adddate,
		po_statuses.vendor_so,
		po_statuses.brand,
		po_statuses.part_number,
		po_statuses.part_description,
		po_statuses.quantity,
		po_statuses.price,
		po_statuses.fob,
		po_statuses.shipvia,
		po_statuses.notes,
		po_statuses.releaseno
	FROM
		" . DB_SCHEMA_ERP . ".po_statuses
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".potran -- Exclude line items with proper vendor part numbers.
		ON
		LTRIM(RTRIM(potran.purno)) = LTRIM(RTRIM(po_statuses.po))
		AND
		LTRIM(RTRIM(potran.vpartno)) = LTRIM(RTRIM(po_statuses.part_number))
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".vendor_mappings
		ON
		LTRIM(RTRIM(vendor_mappings.vendor_mpn)) = LTRIM(RTRIM(po_statuses.part_number))
	WHERE
		po_statuses.po = " . $db->quote($po['purno']) . "
		AND
		potran.purno IS NULL
		AND
		vendor_mappings.vendor_mapping_id IS NULL
	ORDER BY
		po_statuses.part_number,
		po_statuses.releaseno
");
$grab_unmapped_vendoritems = $grab_unmapped_vendoritems->fetchall();

// Grab Address Information.
$grab_address = $db->query("
	SELECT
		poaddr.tocompany,
		poaddr.toaddr1,
		poaddr.toaddr2,
		poaddr.toaddr3,
		poaddr.tocity,
		poaddr.tostate,
		poaddr.tozip,
		poaddr.tocountry,
		poaddr.shcompany,
		poaddr.shaddr1,
		poaddr.shaddr2,
		poaddr.shaddr3,
		LTRIM(RTRIM(poaddr.cshipno)) AS cshipno
	FROM
		" . DB_SCHEMA_ERP . ".poaddr
	WHERE
		LTRIM(RTRIM(poaddr.purno)) = " . $db->quote(trim($po['purno'])) . "
");
$address = $grab_address->fetch();

$grab_lineitems = $db->query("
		WITH items AS (
			SELECT
				potran.item,
				potran.vpartno,
				potran.descrip,
				potran.cost,
				potran.qtyord,
				potran.qtyrec,
				potran.reqdate,
				po_statuses.part_number AS automapped_partnumber,
				vendor_mappings.vendor_mpn AS manualmapped_partnumber,
				vendor_mappings.vendor_mapping_id,
				vendor_mappings.vendor_brand
			FROM ".DB_SCHEMA_ERP.".potran
			LEFT JOIN ".DB_SCHEMA_ERP.".po_statuses
				ON LTRIM(RTRIM(po_statuses.po)) = LTRIM(RTRIM(potran.purno))
				AND LTRIM(RTRIM(po_statuses.part_number)) = LTRIM(RTRIM(potran.vpartno))
			LEFT JOIN ".DB_SCHEMA_ERP.".vendor_mappings
				ON LTRIM(RTRIM(vendor_mappings.vpartno)) = LTRIM(RTRIM(potran.vpartno))
			WHERE LTRIM(RTRIM(potran.purno)) = " . $db->quote($po['purno']) . "
			--ORDER BY item
		), shipped AS (
			SELECT
				po_shipping.part_number,
				SUM(po_shipping.ship_quantity) AS ship_quantity
			FROM ".DB_SCHEMA_ERP.".po_shipping
			WHERE po_shipping.po = " . $db->quote($po['purno']) . "
			GROUP BY part_number
		)
		SELECT
			items.*,
			shipped.ship_quantity AS incoming
		FROM items
		LEFT JOIN shipped
			ON (LTRIM(RTRIM(shipped.part_number)) = LTRIM(RTRIM(items.item)) COLLATE Latin1_General_BIN
			OR LTRIM(RTRIM(shipped.part_number)) = LTRIM(RTRIM(items.vpartno)) COLLATE Latin1_General_BIN
			OR LTRIM(RTRIM(shipped.part_number)) = LTRIM(RTRIM(items.descrip))  COLLATE Latin1_General_BIN
		)
		ORDER BY item
");

$status_ok = True;
if(!trim($po['postat'])) {
	$status_text = 'Open';
} else if($po['postat'] == 'O') {
	$status_text = 'Partially Received';
} else if($po['postat'] == 'C') {
	$status_text = 'Closed';
} else if($po['postat'] == 'V') {
	$status_text = 'Void';
} else {
	$status_ok = False;
	$status_text = '"' . $po['postat'] . '" (???)';
}
?>
<style type="text/css">
	.po-details .lineitems .lineitem .toggle-details {
		font-size:1.5em;
		color:#00f;
		cursor:pointer;
		padding:2px;
		display:inline-block;
	}
	.po-details .lineitems .lineitem-details {
		display:none;
	}
	.po-details .lineitems .lineitem-details > td {
		margin:0;
		padding:0;
	}
	.po-details .lineitems .lineitem-details > td > .details {
		display:none;
		padding:32px;
		background-color:#fff;
	}
	.po-details .po-details-itemized th,
	.po-details .lineitem-vendor-details th {
		vertical-align:top;
	}
	.po-details .po-details-actionitems .foredit {
		display:none;
	}
	.po-details .lineitems .lineitem.unmapped .toggle-details {
		display:none;
	}




	#file-actions-container {
		//border: 1px solid blue;
	}
	#related-file-select {
		margin-top: 5px;
	}
	#download-button {
		margin-left: 5px;
		margin-bottom: 6px;
	}
	#upload-overlay-button {
		margin-left: 5px;
		margin-bottom: 6px;
	}
	#file-download-container {
		margin-left: -44px;
	}



</style>

<div class="po-details" purno="<?php print htmlentities(trim($po['purno']))?>">
	<h2>PO #: <span class="purno"><?php print htmlentities($po['purno']);?></span> (<?php print htmlentities(trim($po['vendno']));?>)</h2>




	<div id="file-actions-container" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<div id="init-file-upload-container" class="container span2">
			<button id="upload-overlay-button" class="btn btn-small">Upload File</button>
		</div>
		<div id="file-download-container" class="container span4"></div>
	</div>





	<?php
	if(in_array($po['postat'], ['C', 'V', 'O']) || !$status_ok) {
		if(!$status_ok) { // Invalid status
			$notification_class = 'notification-error';
		} else if($po['postat'] == 'C') { // Closed/fulfilled.
			$notification_class = 'notification-info';
		} else if($po['postat'] == 'V') { // voided.
			$notification_class = 'notification-error';
		} else if($po['postat'] == 'O') { // partially fulfilled.
			$notification_class = 'notification-warning';
		} else {
			$notification_class = 'notification-info';
		}
		?>
		<div class="notification-container <?php print htmlentities($notification_class, ENT_QUOTES);?>">
			<div class="notification">
				<?php
				if($status_ok) {
					print 'This order is ' . $status_text;
				} else {
					print 'WARNING - Unknown status encountered: ' . $status_text;
				}
				?>
			</div>
		</div>
		<?php
	}
	?>

	<?php
	if(!empty($grab_unmapped_vendoritems)) {
		?>
		<h2>Action Items</h2>
		<table class="po-details-actionitems table table-small table-striped">
			<thead>
				<tr>
					<th>Part Number</th>
					<th>Brand</th>
					<th>Description</th>
					<th>Quantity</th>
					<th>Price</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_unmapped_vendoritems as $unmapped_vendoritem) {
					?>
					<tr class="unmapped-vendoritem">
						<td>
							<div class="forview">
								<i class="fa action fa-pencil action-edit"></i>
								<span class="part_number"><?php print htmlentities($unmapped_vendoritem['part_number']);?></span>
							</div>
							<div class="foredit">
								<i class="fa action fa-check action-save" title="Apply"></i>
								<i class="fa action fa-times action-cancel" title="Cancel"></i>
								<select name="mapped-patno"></select>
							</div>
						</td>
						<td class="brand"><?php print htmlentities($unmapped_vendoritem['brand']);?></td>
						<td><?php print htmlentities($unmapped_vendoritem['part_description']);?></td>
						<td><?php print number_format($unmapped_vendoritem['quantity'], 0);?></td>
						<td>$<?php print number_format($unmapped_vendoritem['price'], 4);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
	?>

	<table class="po-details-itemized table table-small table-striped">
		<tbody>
			<tr>
				<th style="width:84px;">Added</th>
				<td><i><?php print htmlentities($po['adddate']);?></i> by <b><?php print htmlentities($po['adduser']);?></b></td>
			</tr>

			<tr>
				<th>Required Date</th>
				<td><?php print htmlentities($po['reqdate']);?></td>
			</tr>

			<tr>
				<th>Status</th>
				<td>
					<?php
					print htmlentities($status_text);
					?>
				</td>
			</tr>

			<tr>
				<th>Vendor</th>
				<td>
					<?php print htmlentities($po['vendno']);?>
				</td>
			</tr>

			<?php
			if(!empty($address['shcompany'])) {
				?>
				<tr>
					<th>Ship From</th>
					<td>
						<div>
							<?php print htmlentities($address['shcompany']);?>
							<?php
							if(!empty($address['cshipno'])) {
								?>(<?php print $address['cshipno'];?>)<?php
							}
							?>
						</div>
						<div><?php print htmlentities($address['shaddr1']);?></div>
						<div><?php print htmlentities($address['shaddr2']);?></div>
						<div><?php print htmlentities($address['shaddr3']);?></div>
					</td>
				</tr>
				<?php
			}
			?>

			<?php
			if(!empty($address['tocompany'])) {
				?>
				<tr>
					<th>Ship To</th>
					<td>
						<div><?php print htmlentities($address['tocompany']);?></div>
						<div><?php print htmlentities($address['toaddr1']);?></div>
						<div><?php print htmlentities($address['toaddr2']);?></div>
						<div><?php print htmlentities($address['toaddr3']);?></div>
						<div><?php print htmlentities($address['tocity'] . ', ' . $address['tostate'] . ' ' . $address['tozip']);?></div>
						<div><?php print htmlentities($address['tocountry']);?></div>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<h2>Line Items</h2>

	<table class="table table-small table-striped lineitems">
		<thead>
			<tr>
				<th></th>
				<th>Line No</th>
				<th>Item</th>
				<th>Vendor Part #</th>
				<th>Description</th>
				<th style="text-align:left;">Price/Ea.</th>
				<th style="text-align:right;">Ordered</th>
				<th style="text-align:right;">Incoming</th>
				<th style="text-align:right;">Received</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$lineitem_ct = 0;
			foreach($grab_lineitems as $lineitem) {
				$lineitem_ct++;
				$price_ea = $lineitem['cost'];
				$received = (int)$lineitem['qtyrec'];
				$ordered = (int)$lineitem['qtyord'];
				if($received === 0) {
					$received_style = 'color:#f00;';
				} else if($received < $ordered) {
					$received_style = 'color:#990;';
				} else {
					$received_style = 'color:#090;';
				}

				// Query for vendor lineitem information.
				$grab_vendor_lineitem_details = $db->query("
					SELECT
						po_statuses.po_status_id,
						po_statuses.adddate,
						po_statuses.supplier_code,
						po_statuses.po,
						po_statuses.vendor_so,
						po_statuses.part_number,
						po_statuses.part_description,
						po_statuses.quantity,
						po_statuses.brand,
						po_statuses.price,
						po_statuses.fob,
						po_statuses.shipvia,
						po_statuses.[lineno],
						po_statuses.shipto_po,
						po_statuses.releaseno,
						po_statuses.shipto_company,
						po_statuses.shipto_address1,
						po_statuses.shipto_address2,
						po_statuses.shipto_city,
						po_statuses.shipto_state,
						po_statuses.shipto_zip,
						po_statuses.shipto_country,
						po_statuses.notes
					FROM
						" . DB_SCHEMA_ERP . ".po_statuses
					LEFT JOIN
						" . DB_SCHEMA_ERP . ".vendor_mappings
						ON
						LTRIM(RTRIM(vendor_mappings.vendor_mpn)) = LTRIM(RTRIM(po_statuses.part_number))
					WHERE
						LTRIM(RTRIM(po_statuses.po)) = " . $db->quote(trim($po['purno'])) . "
						AND
						(
							LTRIM(RTRIM(po_statuses.part_number)) = " . $db->quote(trim($lineitem['vpartno'])) . "
							OR
							LTRIM(RTRIM(vendor_mappings.vpartno)) = " . $db->quote(trim($lineitem['vpartno'])) . "
						)
				");
				$vendor_lineitem_details = $grab_vendor_lineitem_details->fetch();

				$automapped_partnumber = trim($lineitem['automapped_partnumber']);
				$manualmapped_partnumber = trim($lineitem['manualmapped_partnumber']);
				?>
				<tr class="lineitem <?php print !empty($automapped_partnumber) || !empty($manualmapped_partnumber) ? 'mapped' : 'unmapped';?>" lineitem-count="<?php print htmlentities($lineitem_ct, ENT_QUOTES);?>" vpartno="<?php print htmlentities(trim($lineitem['vpartno']), ENT_QUOTES);?>" vendor_mapping_id="<?php print htmlentities($lineitem['vendor_mapping_id'], ENT_QUOTES);?>">
					<td><i class="fa fa-plus toggle-details"></i></td>
					<td>#<?php print $lineitem_ct;?>.</td>

					<?php
						// Link the item to an overlay.
						$data = json_encode(array('item-number'=>$lineitem['item']));
					?>

					<td class="content content-item-number overlayz-link" overlayz-url="/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities($data) ?>"><?php print htmlentities($lineitem['item']);?></td>
					<td>
						<div class="vpartno"><?php print htmlentities(trim($lineitem['vpartno']));?></div>
						<?php
						if(!empty($automapped_partnumber)) {
							// Automatically mapped, nothing need to do or show.
							/*
							?>
							<div class="mappedto automapped" title=" Vendor Part Number">
								<i class="fa fa-times action action-remove" title="Remove Mapping"></i>
								(<span><?php print htmlentities($automapped_partnumber);?></span>)
							</div>
							<?php
							 */
						} else if(!empty($manualmapped_partnumber)) {
							?>
							<div class="mappedto manualmapped" title="Resolved Vendor Part Number">
								<i class="fa fa-times action action-remove" title="Remove Mapping"></i>
								(<span vendor_brand="<?php print htmlentities($lineitem['vendor_brand'], ENT_QUOTES);?>"><?php print htmlentities($manualmapped_partnumber);?></span>)
							</div>
							<?php
						} else {
							?>
							<div class="mappedto hidden" title="Resolved Vendor Part Number">
								<i class="fa fa-times action action-remove" title="Remove Mapping"></i>
								(<span></span>)
							</div>
							<?php
						}
						?>
					</td>
					<td><?php print htmlentities($lineitem['descrip']);?></td>
					<?php
						// Get a price difference if one exists.

							if(!empty($vendor_lineitem_details)){
								$diff = $vendor_lineitem_details['price'] - $price_ea;
								if($diff > 0.00) {
									$diff_style = 'color:#f00;';
									$diff_sign = '+';
								} else {
									$diff_style = 'color:#090;';
									$diff_sign = '';
								}
							}else{
								$diff = '';
								$diff_style = '';
								$diff_sign = '';
							}


					?>
					<td style="text-align:left;">$<?php print number_format($price_ea, 2);?>
						<?php
							if($diff!=''){
								?>
								(<span style="<?php print htmlentities($diff_style, ENT_QUOTES);?>">$<?php print $diff_sign . number_format($diff, 2);?></span>)
								<?php
							}
						?>
					</td>
					<td class="quantity" style="text-align:right;"><?php print number_format($ordered, 0);?></td>
					<td style="text-align:right;"><?php print htmlentities($lineitem['incoming']) ?></td>
					<td style="text-align:right;<?php print htmlentities($received_style, ENT_QUOTES);?>"><?php print number_format($received, 0);?></td>
				</tr>
				<?php
				if(!empty($vendor_lineitem_details)) {
					?>
					<tr class="lineitem-details lineitem-details-<?php print htmlentities($lineitem_ct, ENT_QUOTES);?>">
						<td colspan="8">
							<div class="details">
								<table>
									<tbody>
										<tr>
											<td style="width:50%;vertical-align:top;border:none !important;background-color:#fff;">
												<h3>Vendor Provided Details</h3>
												<table class="lineitem-vendor-details table table-small table-striped">
													<tbody>
														<tr>
															<th style="width:84px;">Supplier Code</th>
															<td><?php print htmlentities($vendor_lineitem_details['supplier_code']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Vendor SO #</th>
															<td><?php print htmlentities($vendor_lineitem_details['vendor_so']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Release #</th>
															<td><?php print htmlentities($vendor_lineitem_details['releaseno']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Brand</th>
															<td><?php print htmlentities($vendor_lineitem_details['brand']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Part #</th>
															<td><?php print htmlentities($vendor_lineitem_details['part_number']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Description</th>
															<td><?php print htmlentities($vendor_lineitem_details['part_description']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Ship-To PO #</th>
															<td><?php print htmlentities($vendor_lineitem_details['shipto_po']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Ship-To Address</th>
															<td>
																<div><?php print htmlentities($vendor_lineitem_details['shipto_company']);?></div>
																<div><?php print htmlentities($vendor_lineitem_details['shipto_address1']);?></div>
																<div><?php print htmlentities($vendor_lineitem_details['shipto_address2']);?></div>
																<div><?php print htmlentities($vendor_lineitem_details['shipto_city']);?> <?php print htmlentities($vendor_lineitem_details['shipto_state']);?> <?php print htmlentities($vendor_lineitem_details['shipto_zip']);?></div>
																<div><?php print htmlentities($vendor_lineitem_details['shipto_country']);?></div>
															</td>
														</tr>
														<tr>
															<th style="width:84px;">Ship Via</th>
															<td><?php print htmlentities($vendor_lineitem_details['shipvia']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">FOB</th>
															<td><?php print htmlentities($vendor_lineitem_details['fob']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Vendor Notes</th>
															<td><?php print htmlentities($vendor_lineitem_details['notes']);?></td>
														</tr>
														<tr>
															<th style="width:84px;">Price/Ea.</th>
															<?php
															$diff = $vendor_lineitem_details['price'] - $price_ea;
															if($diff > 0.00) {
																$diff_style = 'color:#f00;';
																$diff_sign = '+';
															} else {
																$diff_style = 'color:#090;';
																$diff_sign = '';
															}
															?>
															<td><?php print number_format($price_ea, 2) ?> (<span style="<?php print htmlentities($diff_style, ENT_QUOTES);?>">$<?php print $diff_sign . number_format($diff, 2);?></span>)</td>
														</tr>
													</tbody>
												</table>
											</td>
											<td style="width:50%;background-color:#fff;vertical-align:top;border:none !important;">
												<h3>Ship Dates</h3>
												<?php

												$grab_vendor_lineitem_shipdates = $db->query("
													WITH initial AS (
														SELECT
															part_number COLLATE Latin1_General_BIN2 AS part_number,
															adddate,
															quantity,
															NULL AS shipped_to_date,
															NULL AS prev_ship_date,
															ship_by_date AS new_ship_date,
															NULL AS shipdate_revision
														FROM ".DB_SCHEMA_ERP.".po_statuses
														WHERE po = ".$db->quote($po['purno'])."
															AND part_number = ".$db->quote($vendor_lineitem_details['part_number'])."
													), updates AS (
														SELECT
															po_datechanges.part_number,
															po_datechanges.adddate,
															po_datechanges.quantity,
															po_datechanges.shipped_to_date,
															po_datechanges.prev_ship_date,
															CASE WHEN new_ship_date = '1900-01-01 00:00:00.000'
																THEN NULL
															ELSE new_ship_date
															END as new_ship_date,
															--po_datechanges.new_ship_date,
															po_datechanges.shipdate_revision
														FROM ".DB_SCHEMA_ERP.".po_datechanges
														WHERE po_datechanges.po = ".$db->quote($po['purno'])."
															AND po_datechanges.part_number = ".$db->quote($vendor_lineitem_details['part_number'])."
													)
													SELECT * FROM initial
													UNION ALL
													SELECT * FROM updates
													ORDER BY new_ship_date DESC
												");

												// $grab_vendor_lineitem_shipdates = $db->query("
												// 	SELECT
												// 		po_datechanges.adddate,
												// 		po_datechanges.quantity,
												// 		po_datechanges.shipped_to_date,
												// 		po_datechanges.prev_ship_date,
												// 		po_datechanges.new_ship_date,
												// 		po_datechanges.shipdate_revision
												// 	FROM
												// 		" . DB_SCHEMA_ERP . ".po_datechanges
												// 	WHERE
												// 		po_datechanges.po = " . $db->quote($po['purno']) . "
												// 		AND
												// 		po_datechanges.part_number = " . $db->quote($vendor_lineitem_details['part_number']) . "
												// ");
												$grab_vendor_lineitem_shipdates = $grab_vendor_lineitem_shipdates->fetchall();
												if(!empty($grab_vendor_lineitem_shipdates)) {
													?>
													<table class="table table-small table-striped">
														<thead>
															<tr>
																<th>Notified On</th>
																<th>Expected Ship Date</th>
																<th style="text-align:right;">Shipped To Date</th>
																<th style="text-align:right;">Pending Shipment</th>
															</tr>
														</thead>
														<tbody>
															<?php
															$shipdate_ct = 0;
															foreach($grab_vendor_lineitem_shipdates as $vendor_lineitem_shipdates) {
																$shipdate_ct++;

																// Get the new ship date value.
																$nsd = $vendor_lineitem_shipdates['new_ship_date'];
																if(!$nsd){
																	$nsd = '';
																}else{
																	$nsd = date('Y-m-d', strtotime($nsd));
																}

																?>
				<tr style="<?php print $shipdate_ct > 1 ? 'color:#999;' : Null;?>">
					<td><?php print date('Y-m-d', strtotime($vendor_lineitem_shipdates['adddate']));?></td>
					<td><?php print $nsd?></td>
					<td style="text-align:right;"><?php print number_format($vendor_lineitem_shipdates['shipped_to_date'], 0);?></td>
					<td style="text-align:right;"><?php print number_format($vendor_lineitem_shipdates['quantity'], 0);?></td>
				</tr>
																<?php
															}
															?>
														</tbody>
													</table>
													<?php
												} else {
													?>Ship Dates not available<?php
												}
												?>
												<h3>Tracking</h3>
												Tracking not available - TODO - Once Colson data has been collected (couple of days) this needs to be implemented - Jake - 2018-03-21
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>

	<?php

	$grab_vendor_shipdate_changes = $db->query("
		SELECT DISTINCT
			d.adddate,
			d.part_number,
			d.quantity,
			d.shipped_to_date,
			d.new_ship_date,
			d.shipdate_revision,
			t.item
		FROM ".DB_SCHEMA_ERP.".po_datechanges AS d
		LEFT JOIN ".DB_SCHEMA_ERP.".po_statuses p
			ON p.part_number = d.part_number COLLATE Latin1_General_CI_AS
		LEFT JOIN ".DB_SCHEMA_ERP.".potran t
			ON t.vpartno = p.part_number
		LEFT JOIN ".DB_SCHEMA_ERP.".vendor_mappings v
			ON v.vpartno = t.vpartno
		WHERE d.po = " . $db->quote($po['purno']) . "
		ORDER BY d.part_number,
				d.adddate
	");
	$grab_vendor_shipdate_changes = $grab_vendor_shipdate_changes->fetchall();

	$grab_vendor_shipping = $db->query("
		SELECT
			po_shipping.part_number,
			po_shipping.adddate,
			po_shipping.fob,
			po_shipping.shipvia,
			CAST(po_shipping.shipdate AS date) AS shipdate,
			po_shipping.tracking,
			po_shipping.ship_quantity,
			po_shipping.revision,
			po_shipping.notes,
			po_shipping.packing_slip_no
		FROM
			" . DB_SCHEMA_ERP . ".po_shipping
		WHERE
			po_shipping.po = " . $db->quote($po['purno']) . "
		ORDER BY
			po_shipping.adddate
	");
	$grab_vendor_shipping = $grab_vendor_shipping->fetchall();

	if(!empty($grab_vendor_lineitems) || !empty($grab_vendor_shipdate_changes) || !empty($grab_vendor_shipping)) {
		/*
		?>
		<h2>Action Items</h2>
		<?php
		if(!empty($grab_vendor_lineitems)) {
			print_r($grab_vendor_lineitems);
			?>
			<fieldset>
				<legend>Line Items</legend>
				<table class="table table-small table-striped lineitems">
					<thead>
						<tr>
							<th>Imported On</th>
							<th>Vendor SO</th>
							<th>Brand</th>
							<th>Part Number</th>
							<th>Release #</th>
							<th>Description</th>
							<th style="text-align:right;">Quantity</th>
							<th style="text-align:right;">Price/Ea.</th>
							<th>FOB</th>
							<th>Ship Via</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($grab_vendor_lineitems as $vendor_lineitem) {
							?>
							<tr>
								<td><?php print date('Y-m-d', strtotime($vendor_lineitem['adddate']));?></td>
								<td><?php print htmlentities($vendor_lineitem['vendor_so']);?></td>
								<td><?php print htmlentities($vendor_lineitem['brand']);?></td>
								<td><?php print htmlentities($vendor_lineitem['part_number']);?></td>
								<td><?php print htmlentities($vendor_lineitem['releaseno']);?></td>
								<td><?php print htmlentities($vendor_lineitem['part_description']);?></td>
								<td style="text-align:right;"><?php print (int)$vendor_lineitem['quantity'];?></td>
								<td style="text-align:right;">$<?php print number_format($vendor_lineitem['price'], 6);?></td>
								<td><?php print htmlentities($vendor_lineitem['fob']);?></td>
								<td><?php print htmlentities($vendor_lineitem['shipvia']);?></td>
								<td><?php print htmlentities($vendor_lineitem['notes']);?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</fieldset>
			<?php
		}
		*/

		if(!empty($grab_vendor_shipdate_changes)) {
			?>
			<fieldset>
				<legend>Ship Date Changes</legend>
				<table class="table table-small table-striped lineitems">
					<thead>
						<tr>
							<th>Imported On</th>
							<th>Item</th>
							<th>Quantity</th>
							<th>Shipped To Date</th>
							<th>New Ship Date</th>
							<th>Ship Date Revision</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($grab_vendor_shipdate_changes as $date_change) {
							?>
							<tr>
								<td><?php print date('Y-m-d', strtotime($date_change['adddate']));?></td>

								<?php
									// Link item to overlay.
									$item = $date_change['item'];
									if($item!=null){
										$data = json_encode(array('item-number'=>trim($item)));
										?>
											<td class="content content-item-number overlayz-link" overlayz-url="/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities($data) ?>"><?php print htmlentities($date_change['item']) ?></td>
										<?php
									}else{
										?>
										<td></td>
										<?php
									}
								?>

								<td><?php print (int)$date_change['quantity'];?></td>
								<td><?php print (int)$date_change['shipped_to_date'];?></td>
								<td><?php print date('Y-m-d', strtotime($date_change['new_ship_date']));?></td>
								<td><?php print htmlentities($date_change['shipdate_revision']);?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</fieldset>
			<?php
		}

		if(!empty($grab_vendor_shipping)) {
			?>
			<fieldset>
				<legend>Tracking</legend>
				<table class="table table-small table-striped lineitems">
					<thead>
						<tr>
							<th>Imported On</th>
							<th>Part Number</th>
							<th>FOB</th>
							<th>Ship Via</th>
							<th>Ship Date</th>
							<th>Tracking</th>
							<th>Ship Quantity</th>
							<th>Revision</th>
							<th>Packing Slip #</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($grab_vendor_shipping as $shipping) {
							?>
							<tr>
								<td><?php print date('Y-m-d', strtotime($shipping['adddate']));?></td>
								<td><?php print htmlentities($shipping['part_number']);?></td>
								<td><?php print htmlentities($shipping['fob']);?></td>
								<td><?php print htmlentities($shipping['shipvia']);?></td>
								<td><?php print htmlentities($shipping['shipdate']);?></td>
								<td><?php print htmlentities($shipping['tracking']);?></td>
								<td><?php print (int)$shipping['ship_quantity'];?></td>
								<td><?php print htmlentities($shipping['revision']);?></td>
								<td><?php print htmlentities($shipping['packing_slip_no']);?></td>
								<td><?php print htmlentities($shipping['notes']);?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</fieldset>
			</fieldset>
			<?php
		}
	}
	?>
</div>

<script type="text/javascript">
	$(document).off('click', '.po-details .lineitems .lineitem .toggle-details');
	$(document).on('click', '.po-details .lineitems .lineitem .toggle-details', function(event) {
		var $icon = $(this);
		var $lineitem = $icon.closest('.lineitem');
		var $details_tr = $lineitem.next();
		var $details_inner = $details_tr.children('td').children('.details');
		if($details_tr.is(':visible')) {
			$details_inner.slideUp('fast', function() {
				$details_tr.hide();
			});
		} else {
			$details_tr.show();
			$details_inner.slideDown('fast');
		}
	});
	
	/**
	 * Bind to clicks on Edit Unmapped Vendor Item icon.
	 */
	$(document).off('click', '.po-details .unmapped-vendoritem .action-edit');
	$(document).on('click', '.po-details .unmapped-vendoritem .action-edit', function(event) {
		var $icon = $(this);
		var $vendoritem = $icon.closest('.unmapped-vendoritem');
		var $edit_container = $vendoritem.find('.foredit');
		var $edit_select = $edit_container.find('select').empty();
		$edit_select.append(
			$('<option value="" selected>')
		);

		var $unmapped_lineitems = $('.po-details .lineitems .lineitem.unmapped');
		$.each($unmapped_lineitems, function(offset, unmapped_lineitem) {
			var $unmapped_lineitem = $(unmapped_lineitem);
			var vpartno = $unmapped_lineitem.find('.vpartno').text().trim();
			var quantity = $unmapped_lineitem.find('.quantity').text().trim();
			$edit_select.append(
				$('<option>').attr('value', vpartno).text(vpartno + ' (' + quantity + ' Ordered)')	
			);
		});
		$edit_container.slideDown('fast');
	});
	
	/**
	 * Bind to click on edit Unmapped Vendor Item "Cancel" icon.
	 */
	$(document).off('click', '.po-details .unmapped-vendoritem .action-cancel');
	$(document).on('click', '.po-details .unmapped-vendoritem .action-cancel', function(event) {
		var $icon = $(this);
		var $edit_container = $icon.closest('.foredit');
		var $edit_select = $edit_container.find('select');
		$edit_container.slideUp('fast', function() {
			$edit_select.empty();
		});
	});

	/**
	 * Bind to click on edit Unmapped Vendor Item "Save" icon.
	 */
	$(document).off('click', '.po-details .unmapped-vendoritem .action-save');
	$(document).on('click', '.po-details .unmapped-vendoritem .action-save', function(event) {
		var $icon = $(this);
		var $vendoritem = $icon.closest('.unmapped-vendoritem');
		var $edit_container = $vendoritem.find('.foredit');
		var $edit_select = $edit_container.find('select');
		var $brand = $vendoritem.find('.brand');
		var $part_number = $vendoritem.find('.part_number');
		var vpartno = $edit_select.val();
		if(!vpartno) { // Ensure a part number has been selected from the dropdown before proceeding.
			return false;
		}
		var vendor_brand = $brand.text().trim();
		var vendor_mpn = $part_number.text().trim();

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');
		$.ajax({
			'url': BASE_URI + '/dashboard/purchaseorders/lineitems/map',
			'data': {
				'vendor_brand': vendor_brand,
				'vendor_partnumber': vendor_mpn,
				'vpartno': vpartno
			},
			'method': 'POST',
			'dataType': 'json',
			'beforeSend': function() {},
			'success': function(response) {
				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right');
					}
					return;
				}
				$vendoritem.remove();
				var $lineitem = $('.po-details .lineitems .lineitem[vpartno="' + vpartno + '"]');
				var $mappedto = $lineitem.find('.mappedto');
				$mappedto.hide().removeClass('hidden').slideDown('fast');
				$mappedto.find('span').text(vendor_mpn).attr('vendor_brand', vendor_brand);
				$lineitem.removeClass('unmapped').addClass('mapped');
				$lineitem.attr('vendor_mapping_id', response.vendor_mapping_id);
			},
			'complete': function() {
				$loading_overlay.fadeOut('fast', function() {
					$loading_overlay.remove();
				});
			}
		});
		return false;
	});

	/**
	 * Bind to clicks on "Remove Mapping" icon.
	 */
	$(document).off('click', '.po-details .lineitems .lineitem .mappedto .action-remove');
	$(document).on('click', '.po-details .lineitems .lineitem .mappedto .action-remove', function(event) {
		if(confirm('Are you sure you want to un-map these part numbers?')) {
			var $icon = $(this);
			var $mappedto = $icon.closest('.mappedto');
			var $lineitem = $mappedto.closest('.lineitem');
			var $vendor_mpn = $mappedto.find('span');
			var vendor_mapping_id = $lineitem.attr('vendor_mapping_id');
			var vendor_mpn = $vendor_mpn.text().trim();
			var vendor_brand = $vendor_mpn.attr('vendor_brand');

			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');
			$.ajax({
				'url': BASE_URI + '/dashboard/purchaseorders/lineitems/unmap',
				'data': {
					'vendor_mapping_id': vendor_mapping_id
				},
				'method': 'POST',
				'dataType': 'json',
				'beforeSend': function() {},
				'success': function(response) {
					if(!response.success) {
						if(response.message) {
							alert(response.message);
						} else {
							alert('Something didn\'t go right');
						}
						return;
					}
					$vendor_mpn.empty();
					$mappedto.removeClass('manualmapped');
					$mappedto.addClass('hidden').hide();
					$lineitem.removeClass('mapped').addClass('unmapped');
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});
		}
		return false;
	});



	function get_related_files(){

		// Get the related files for the PO.

		// Get the PO.
		var $div = $('.po-details')
		var po = $div.attr('purno')

		// The data required for the overlay.
		var data = {
			'type' : 'po',
			'assoc-id' : po
		}

		// Get the files.
		$.ajax({
			'url' : 'http://10.1.247.195/files/get-file-list',
			'method' : 'GET',
			'dataType' : 'JSONP',
			'data' : data,
			'success' : function(rsp){

				// Create a select for the files.
				var $select = $('<select>',{
					'id' : 'related-file-select'
				})
				$select.append($('<option>',{
					'value' : '',
					'text' : '-- Select File --'
				}))

				// Create an option for each file.
				var files = rsp.files
				$.each(files, function(idx, file){

					var $option = $('<option>', {
						'value' : file.file_id,
						'text' : file.filename
					})
					$select.append($option)

				})

				// Replace any existing select.
				var $container = $('#file-download-container')
				$container.empty()
				$container.html($select)

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function upload_file_overlay(){

		// Produce an overlay for file uploads.

		// Get the PO.
		var $div = $('.po-details')
		var po = $div.attr('purno')

		produce_file_upload_overlay('po', po)

	}

	function enable_download(){

		// Create and display a download button when a file is selected.

		// Remove a button if one exists.
		var $container = $('#file-download-container')
		$container.find('#download-button').remove()

		// Get the currently selected file.
		var $select = $('#related-file-select')
		var file_id = $select.val()

		// Add the button.
		var $button = $('<button>',{
			'id' : 'download-button',
			'class' : 'btn btn-small',
			'text' : 'Download'
		})

		if(file_id!=''){
			$container.append($button)
		}

	}

	function do_download_file(){

		// Download a related file.

		// Get the file ID.
		var $select = $('#related-file-select')
		var file_id = $select.val()

		// Do the download.
		download_file(file_id)

	}


	// Get any files related to the Client.
	get_related_files()

	// Support file uploads.
	$(document).off('click', '#upload-overlay-button')
	$(document).on('click', '#upload-overlay-button', upload_file_overlay)

	// Support enabling file downloads.
	$(document).off('change', '#related-file-select')
	$(document).on('change', '#related-file-select', enable_download)

	// Support file downloads.
	$(document).off('click', '#download-button')
	$(document).on('click', '#download-button', do_download_file)

</script>

<?php
