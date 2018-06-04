<?php

ob_start(); // Start loading output into buffer.

function get_item_shortage($item, $location){

	// Query for the shortage of an SO.
	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT
			SUM(CASE WHEN lo.lonhand-pb.bqtyusd < 0
				THEN 1
				ELSE 0
			END) AS shortage
		FROM ".DB_SCHEMA_ERP.".pebmdt pb
		LEFT JOIN ".DB_SCHEMA_ERP.".iciloc lo
			ON lo.item = pb.item
		LEFT JOIN ".DB_SCHEMA_ERP.".wotran wo
			ON wo.item = pb.item
			AND lo.loctid = wo.wloctid
		WHERE LTRIM(RTRIM(pb.bomno)) = ".$db->quote($item)."
			AND LTRIM(RTRIM(lo.loctid)) = ".$db->quote($location)."
	");

	return $q->fetch()['shortage'];

}

function get_bom_pos($item){

	// Query for the open POs for an item.

	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT purno
		FROM PRO01.dbo.potran p
		WHERE  postat NOT IN ('V', 'X', 'C')
			AND potype != 'B'
			AND qtyord != 0
			AND LTRIM(RTRIM(item)) = ".$db->quote($item)."
	");

	// Return an array of POs.
	$pos = array();
	foreach($q->fetchAll() as $r){
		array_push($pos, trim($r['purno']));
	}

	return $pos;

}

function get_bom_items($item, $location){

	// Query for the bill of materials for an item, and see which (if any) have
	// a shortage.

	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT
			il. loctid,
			ii.item,
			CAST(il.lonhand AS integer) AS onhand,
			CAST(p.bqtyusd AS integer) AS used,
			CASE WHEN
				il.lonhand - p.bqtyusd < 0
				THEN CAST(il.lonhand - p.bqtyusd AS integer)
				ELSE 0
			END AS shortage
		FROM ".DB_SCHEMA_ERP.".pebmdt p
		LEFT JOIN ".DB_SCHEMA_ERP.".icitem ii
			ON ii.item = p.item
		LEFT JOIN ".DB_SCHEMA_ERP.".iciloc il
			ON il.item = ii.item
		WHERE il.loctid = ".$db->quote($location)."
			AND p.bomno = ".$db->quote($item)."
	");

	$items = $q->fetchAll();

	// Get the POs for each item.
	foreach($items as $index=>$item){

		$i = $item['item'];
		$pos = get_bom_pos($i);

		$items[$index]['pos'] = $pos;

	}

	return $items;

}

function relate_item_po($so, $po, $item, $parent){

	// Create a relationship between Items from an SO and a PO.

	$db = DB::get();
	$q = $db->query("
		INSERT INTO Neuron.dbo.item_po_relationships (
			item, sono, po, parent
		)
		SELECT
			".$db->quote($item).",
			".$db->quote($so).",
			".$db->quote($po).",
			".$db->quote($parent)."

		WHERE NOT EXISTS (
			SELECT 1
			FROM Neuron.dbo.item_po_relationships
			WHERE sono =  ".$db->quote($so)."
				AND item = ".$db->quote($item)."
				AND parent = ".$db->quote($parent)."
		);
		UPDATE Neuron.dbo.item_po_relationships
		SET po = ".$db->quote($po)."
		WHERE sono = ".$db->quote($so)."
			AND item = ".$db->quote($item)."
			AND parent = ".$db->quote($parent)."
	");

}

function unrelate_item_po($so, $item){

	// Remove the item/PO relationship.

	$db = DB::get();
	$q = $db->query("
		DELETE FROM Neuron.dbo.item_po_relationships
		WHERE item = ".$db->quote($item)."
			AND sono = ".$db->quote($so)."
	");

}

function get_item_po_relationships($item, $sono){

	// Query for the Item/PO relationship within a PO.

	$db = DB::get();
	$q = $db->query("
		SELECT po
		FROM Neuron.dbo.item_po_relationships
		WHERE sono = ".$db->quote($sono)."
			AND item = ".$db->quote($item)."
	");

	$result = $q->fetch();

	if(isset($result['po'])){
		$po = $result['po'];
	}else{
		$po = null;
	}

	return $po;

}

function get_po_shipdate($po){

	// Query for a PO's shipdate.

	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT
			CAST(COALESCE(sh.shipdate, s.ship_by_date) AS DATE) AS shipping_date
		FROM PRO01.dbo.po_statuses s
		LEFT JOIN PRO01.dbo.po_shipping sh
			ON sh.po = s.po COLLATE Latin1_General_CI_AS
		WHERE LTRIM(RTRIM(s.po)) = ".trim($db->quote($po))."
	");

	$result = $q->fetch();
	if(isset($result['shipping_date'])){
		return $result['shipping_date'];
	}

}

// Handle AJAX.
if(isset($_POST['action'])){

	// Item diescrpancies.
	if($_POST['action']=='get-item-discrepancy'){

		// Get the items.
		$items = json_decode($_POST['items'], true);

		// Get the item shortages.
		foreach($items AS $index => $row){

			// Get the shortage.
			$item = $row['item'];
			$loc = $row['loc'];
			$shortage = get_item_shortage($item, $loc);

			// Keep the shortage on the row.
			$items[$index]['shortage'] = $shortage;

		}

		print json_encode(array(
			'success' => true,
			'items' => $items
		));

		return;

	}

	// Expand the item row.
	if($_POST['action']=='get-bom-items'){

		// Get the POSTed data.
		$item = $_POST['item'];
		$location = $_POST['location'];

		// Get the item's components.
		$components = get_bom_items($item, $location);

		// Create the HTML for the expanded section.
		$html = '<tr class="component-container-row">';
		$html .=	'<td colspan="17"';
		$html .=		'<div>';
		$html .=			'<table id="item-expansion-table" class="table table-stiped table-hover">';
		$html .=				'<thead>';
		$html .=					'<th></th>';
		$html .=					'<th></th>';
		$html .=					'<th>Item</th>';
		$html .=					'<th>PO</th>';
		$html .=					'<th>Ship Date</th>';
		$html .=					'<th>Location</th>';
		$html .=					'<th class="text-center">Required</th>';
		$html .=					'<th class="text-center">On Hand</th>';
		$html .=					'<th class="text-center">Shortage</th>';
		$html .=				'</thead>';
		$html .=				'<tbody>';
		foreach($components as $component){

			// Create an indicator for shortages.
			if($component['shortage']!=0){
				//$i = $('<i/>',{'class':'fa fa-exclamation'});
				$i = '<i class="fa fa-exclamation"></i>';
			}else{
				$i = '';
			}

			// Get the PO assigned to the item if one has been assigned.
			$po = get_item_po_relationships($component['item'], $_POST['sono']);

			// Add a 'remove' icon.
			$shipdate = '';
			if(!empty($po)){
				$shipdate = get_po_shipdate($po);
				$po .= '<i class="fa fa-fw fa-times"></i>';
			}

			// The POs of the component.
			$pos = htmlentities(json_encode($component['pos']));

			// The data for the item overlay.
			$data = htmlentities(json_encode(array('item-number'=>trim($component['item']))));

			// The component item number.
			$citem = htmlentities(trim($component['item']));

			$html .=				'<tr class="component-row" data-item="'.$citem.'" data-pos="'.$pos.'">';
			$html .=					'<td></td>';
			$html .=					'<td></td>';
			$html .=					'<td class="overlayz-link" overlayz-url="/dashboard/inventory/item-details" overlayz-data="'.$data.'">'.htmlentities(trim($component['item'])).' '.$i.'</td>';
			$html .=					'<td class="po-td">'.$po.'</td>';
			$html .=					'<td class="ship-td">'.$shipdate.'</td>';
			$html .=					'<td>'.htmlentities($component['loctid']).'</td>';
			$html .=					'<td class="text-center">'.htmlentities($component['used']).'</td>';
			$html .=					'<td class="text-center">'.htmlentities($component['onhand']).'</td>';
			$html .=					'<td class="text-center">'.htmlentities($component['shortage']).'</td>';
			$html .=				'</tr>';
		}
		$html .=				'</tbody>';
		$html .=			'</table>';
		$html .=		'</div>';
		$html .=	'</td>';
		$html .= '</tr>';

		print json_encode(array(
			'success' => true,
			'item' => $item,
			'location' => $location,
			'components' => $components,
			'html' => $html
		));

		return;

	}

	// Relate the Item and PO.
	if($_POST['action']=='relate-item-po'){

		// Get the item, PO, and SO
		$item = $_POST['item'];
		$parent = $_POST['parent'];
		$po = $_POST['po'];
		$so = $_POST['so'];

		// Create the relationship.
		relate_item_po($so, $po, $item, $parent);

		// Get the PO shipdate.
		$shipdate = get_po_shipdate($po);

		print json_encode(array(
			'success' => true,
			'shipdate' => $shipdate
		));

		return;

	}

	// Remove Item/PO relationship.
	if($_POST['action']=='remove-item-po-relationship'){

		// Get the Item and SO.
		$item = $_POST['item'];
		$so = $_POST['so'];

		// Remove the relationship.
		unrelate_item_po($so, $item);

		print json_encode(array(
			'success' => true
		));

		return;

	}

}

$grab_sales_order = $db->query("
	SELECT
		somast.sono,
		somast.web,
		somast.hot,
		arcust.cstmemo AS customer_notes,  -- Customer Notes
		somast.notes AS order_notes,       -- Order Notes
		arcust.custno,
		CONVERT(varchar(10), somast.adddate, 120) AS adddate,
		somast.salesmn AS salesman,
		somast.printed,
		somast.adduser,
		somast.id_col,
		LTRIM(RTRIM(somast.orderstat)) AS orderstat
	FROM
		" . DB_SCHEMA_ERP . ".somast
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
		RTRIM(LTRIM(somast.sono)) = " . $db->quote(trim($_POST['so-number'])) . "
");
$sales_order = $grab_sales_order->fetch();

/**
 * SO Details
 */
$grab_sodetails = $db->query("
	WITH dates AS (
		SELECT DISTINCT
			sono,
			parent,
			MAX(CAST(COALESCE(sh.shipdate, s.ship_by_date) AS DATE)) AS shipping_date
		FROM ".DB_SCHEMA_ERP.".po_statuses s
		INNER JOIN Neuron.dbo.item_po_relationships r
			ON r.po COLLATE Latin1_General_BIN = s.po
		LEFT JOIN ".DB_SCHEMA_ERP.".po_shipping sh
			ON sh.po = s.po COLLATE Latin1_General_CI_AS
		WHERE LTRIM(RTRIM(s.po)) = ANY(
			SELECT po COLLATE Latin1_General_CI_AS
			FROM Neuron.dbo.item_po_relationships
			WHERE sono  = ".$db->quote(trim($_POST['so-number']))."
		)
		GROUP BY sono, parent
	)

	SELECT
		CASE
			WHEN EXISTS (
				SELECT 1
				FROM Neuron.dbo.warehouse_shipments
				WHERE sono = LTRIM(RTRIM(sotran.sono)) COLLATE Latin1_General_BIN
					AND item = LTRIM(RTRIM(sotran.item))COLLATE Latin1_General_BIN
			) THEN 1
			ELSE 0
		END AS captured,
		CASE WHEN CHARINDEX('PKG', sotran.item)>0
			THEN sotran.descrip
			ELSE ''
		END AS tracking,
		sotran.tranlineno AS line_number,
		icitem.comcode AS type,
		sotran.item AS item,
		icitem.itmdesc AS part_number,
		sotran.cpartno AS client_number,
		sotran.price AS price,
		sotran.origextpri AS ext_price,
		sotran.origqtyord AS order_qty,
		sotran.qtyshp AS shipped_qty,
		sotran.qtyord AS open_qty,
		sotran.loctid AS location,
		CONVERT(varchar(10), sotran.rqdate, 120) AS req_date,
		dates.shipping_date,
		CASE WHEN shipping_date > sotran.rqdate
			THEN 1
			ELSE 0
		END AS late,
		DATEDIFF(DAY, shipping_date, sotran.rqdate) AS diff
	FROM ".DB_SCHEMA_ERP.".sotran
	LEFT JOIN dates
		ON LTRIM(RTRIM(dates.sono)) = LTRIM(RTRIM(sotran.sono)) COLLATE Latin1_General_BIN
		AND LTRIM(RTRIM(dates.parent)) = LTRIM(RTRIM(sotran.item)) COLLATE Latin1_General_BIN
	INNER JOIN ".DB_SCHEMA_ERP.".icitem
		ON icitem.item = sotran.item
	WHERE LTRIM(RTRIM(sotran.sono)) = ".$db->quote(trim($_POST['so-number']))."
	ORDER BY sotran.tranlineno
");

// Grab and iterate over printers, constructing a list of options.
$grab_printers = $db->query("
	SELECT
		printers.printer_id,
		printers.printer
	FROM
		" . DB_SCHEMA_INTERNAL . ".printers
	ORDER BY
		printers.printer
");

if($sales_order === False) {
	print json_encode(array(
		'success' => False,
		'html' => '<h2>SO specified doesn\'t exist...</h2>'
	));
	exit;
}

function get_issue_codes(){

	// Get all issue codes and issue code IDs.
	$db = DB::get();
	$q = $db->query("
		SELECT
			issue_code_id,
			(CAST(issue_code AS varchar) +
			CAST(' - ' AS varchar) +
			CAST(issue_code_desc AS varchar)) AS issue_code
		FROM Neuron.dbo.issue_codes
	");

	return $q;

}

function get_issues(){

	// Get all issues for the SO.
	$db = DB::get();
	return $db->query("
		SELECT
			i.issue_id,
			(CAST(issue_code AS varchar) +
			CAST(' - ' AS varchar) +
			CAST(issue_code_desc AS varchar)) AS issue_code,
			c.issue_code_desc,
			i.details,
			l.first_name,
			i.created_on
		FROM Neuron.dbo.order_issues i
		INNER JOIN Neuron.dbo.issue_codes c
			ON c.issue_code_id = i.issue_code_id
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = i.login_id
		WHERE i.sono = ".$db->quote($_POST['so-number'])."
		ORDER BY i.created_on
	");

}

// Get issues and codes.
$issues = get_issues();
$issue_codes = get_issue_codes();

?>
<style type="text/css">
	#so-details-container .order-status-container .fa {
		font-size:1.4em;
		cursor:pointer;
	}
	#so-details-container .order-status-edit-select {
		margin:0;
	}
	.abnormal {
		padding-top: 5px;
	}
	#abnormal-details {
		max-width: 400px;
		max-height: 200px;
		min-width: 400px;
		min-height: 200px;
		box-sizing: border-box;
	}
	#abnormal-select {
		padding-top:5px;
		width:400px;
		box-sizing: border-box;
	}
	.comment-textarea {
		height:20px;
		width:95%;
	}
	.btn-add-comment {
		margin-bottom: 10px;
	}

	/* Camera Stuff */
	.cam-icon {
		cursor: pointer;
		display: none;
	}
	.captured {
		display: inline;
	}
	.fa-exclamation {
		color: red;
		padding-bottom: 3px;
	}
	.fa-plus {
		cursor: pointer;
	}
	.fa-minus {
		cursor: pointer;
	}
	.text-center {
		text-align: center !important;
	}
	.po-select {
		width: 130px;
	}
	.fa-times {
		padding-bottom: 2px;
		color: red;
		cursor: pointer;
	}
	.content-late {
		color: red;
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

<div id="so-details-container" order-id="<?php print htmlentities(trim($sales_order['id_col']))?>">
	<i class="fa fa-print toggle-print-icon" title="Toggle Print Options"></i>
	<h2 style="display:inline-block;">Sales Order #: <span class="sales-order-number"><?php print htmlentities($_POST['so-number']);?></span> (<?php print htmlentities(trim($sales_order['custno']));?>)</h2>

	<div class="picking-ticket" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<button type="button" class="print-picking-ticket overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/sales-order-status/pickticket/details" overlayz-data="<?php print htmlentities(json_encode(['sono' => $sales_order['sono']]), ENT_QUOTES);?>">Picking Ticket</button>
	</div>
	<div class="web-order-container" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<label>
			<input type="checkbox" name="web-order-input" <?php print $sales_order['web'] == 1 ? 'checked' : Null;?> />
			Web
		</label>
	</div>
	<div class="hot-order-container" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<label>
			<input type="checkbox" name="hot-order-input" <?php print $sales_order['hot'] == 1 ? 'checked' : Null;?>/>
			Hot
		</label>
	</div>




	<div id="file-actions-container" style="display:inline-block;line-height:40px;height:40px;vertical-align:middle;padding-left:30px;">
		<div id="init-file-upload-container" class="container span2">
			<button id="upload-overlay-button" class="btn btn-small">Upload File</button>
		</div>
		<div id="file-download-container" class="container span4"></div>
	</div>




	<table class="table table-small table-striped">
		<tbody>
			<tr>
				<th style="width:84px;">Added</th>
				<td><i><?php print htmlentities($sales_order['adddate']);?></i> by <b><?php print htmlentities($sales_order['adduser']);?></b></td>
			</tr>
			<?php
			if($sales_order['printed']) {
				?>
				<tr>
					<th>Printed</th>
					<td>Scanned as "Printed" on <?php print date('Y-m-d \@ g:ia', strtotime($sales_order['printed']));?></td>
				</tr>
				<?php
			}
			?>
			<tr class="order-status-container">
				<th>Order Status</th>
				<td>
					<i class="action-edit fa fa-pencil fa-fw" title="Edit"></i>
					<span class="status-selected"><?php print htmlentities($sales_order['orderstat']);?></span>
					<span class="status-options hidden">
						<select class="order-status-edit-select">
							<option value=""></option>
							<?php
							$options = [
								// 'BACKORDER',
								// 'CARD DECLINED',
								// 'CREDIT HOLD',
								// 'HOLD',
								// 'ISS',
								// 'NSP',
								// 'ON HOLD',
								// 'OPEN',
								// 'OTHER',
								// 'PICKING',
								// 'PICKUP',
								// 'PRINTED',
								// 'PRODUCTION',
								// 'PURCHASING',
								// 'QCUSTOM',
								// 'QUEUED',
								// 'SHIPPED',
								// 'SHIPPING',
								// 'SSP',
								// 'STAGED',
								// 'STAGED FOR SHIP',
								// 'TRANSFER',
								// 'VENDOR',
								// 'WAIT ON PAYMENT',
								// 'WAIT ON PICKUP',
								// 'WAIT ON PRODUCT',
								// 'WAIT ON TRANSFR',
								// 'WALKIN'
								'BACKORDER',
								'HOLD: PAYMENT',
								'HOLD: SHIP DATE',
								'IN STOCK',
								'MILKRUN',
								'NON STOCK',
								'OTHER',
								'PLEASE VOID',
								'PICK-UP',
								'PRINTED',
								'IN PRODUCTION',
								'QUEUED',
								'SHIPPED',
								'IN SHIPPING',
								'TRANSFERRING',
								'VENDOR',
								'VENDOR ERROR',
								'WAIT: CLIENT',
								'WAIT: SALES',
								'WALK-IN',
								'WATCHING'
							];
							foreach($options as $option) {
								?><option value="<?php print htmlentities($option, ENT_QUOTES);?>" <?php print $sales_order['orderstat'] == $option ? 'selected' : Null;?>><?php print htmlentities($option);?></option><?php
							}
							?>
						</select>
					</span>
				</td>
			</tr>
		</tbody>
	</table>

	<?php
	if($grab_sodetails->rowCount()) {
		?>
		<h3>Line Items</h3>
		<table>
			<thead>
				<tr>
					<th></th>
					<th>
						<div class="hidden content-print-container">Print?</div>
					</th>
					<th>
						<div class="hidden content-print-container"># Labels</div>
					</th>
					<th>
						<div class="hidden content-print-container">Qty Per Box</div>
					</th>
					<th>Line Number</th>
					<th>Tracking</th>
					<th></th>
					<th></th>
					<th>Type</th>
					<th>Item</th>
					<th>Part Number</th>
					<th>Client Number</th>
					<th>Price</th>
					<th>Ext Price</th>
					<th>Ordered Qty</th>
					<th>Shipped Qty</th>
					<th>Open Qty</th>
					<th>Location</th>
					<th>Req Date</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($grab_sodetails as $sodetails) {
					$overlayz_url = BASE_URI . '/dashboard/inventory/item-details';
					$overlayz_data = htmlentities(
						json_encode(array(
							'item-number' => trim($sodetails['part_number'])
						)),
						ENT_QUOTES
					);
					?>
					<tr class="item-row" data-sono="<?php print htmlentities($_POST['so-number']);?>" data-itemno="<?php print htmlentities($sodetails['item']);?>">
						<td class="content content-expand"><i class="fa fa-plus"></i></td>
						<td class="content content-print content-print-printcheckbox">
							<div class="hidden content-print-container">
								<input type="checkbox" name="print-flag" value="<?php print htmlentities($sodetails['part_number'], ENT_QUOTES);?>" />
							</div>
						</td>
						<td class="content content-print content-print-numlabels">
							<div class="hidden content-print-container">
								<select name="print-numlabels">
									<?php
									while(++$curr <= 20) {
										?><option value="<?php print $curr;?>"><?php print $curr;?></option><?php
									}
									?>
								</select>
							</div>
						</td>
						<td class="content content-print content-print-qtyperbox">
							<div class="hidden content-print-container">
								<input type="text" placeholder="Qty/Box" name="print-qtyperbox" />
							</div>
						</td>
						<td class="content content-line-number"><?php print htmlentities($sodetails['line_number']);?></td>
						<td class="content content-tracking"><?php print htmlentities($sodetails['tracking']) ?></td>

						<?php

							// Check if the item is going to be late.
							$late = $sodetails['late'];
							$diff = $sodetails['diff'];
							if($late>0 && $diff){
								$td = '('.$diff.')';
							}else{
								$td = '';
							}
						?>
						<td class="content content-late"><?php print htmlentities($td) ?></td>

						<?php
							// See if the "captured" class should be put on the icon.;
							$captured = array(
								true => ' captured',
								false => ''
							)[(bool)$sodetails['captured']];
						?>

						<td><i class="cam-icon fa fa-camera<?php print htmlentities($captured) ?>"></i></td>

						<td class="content content-type"><?php print htmlentities($sodetails['type']);?></td>
						<td class="content content-item-number overlayz-link" overlayz-url="<?php print $overlayz_url;?>" overlayz-data="<?php print $overlayz_data;?>"><?php print htmlentities($sodetails['item']);?></td>
						<td class="content content-part-number"><?php print htmlentities($sodetails['part_number']);?></td>
						<td class="content content-client-number"><?php print htmlentities($sodetails['client_number']);?></td>
						<td class="content content-price">$<?php print number_format($sodetails['price'], 2);?></td>
						<td class="content content-ext-price">$<?php print number_format($sodetails['ext_price'], 2);?></td>
						<td class="content content-ordered-qty"><?php print number_format($sodetails['order_qty']);?></td>
						<td class="content content-shipped-qty"><?php print number_format($sodetails['shipped_qty'], 0);?></td>
						<td class="content content-open-qty"><?php print number_format($sodetails['open_qty'], 0);?></td>
						<td class="content content-location"><?php print htmlentities($sodetails['location']);?></td>
						<td class="content content-req-date"><?php print htmlentities($sodetails['req_date']);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>

		<div class="hidden content-print-container content-print-printlocation">
			Select a printer:
			<select name="print-location">
				<?php
				foreach($grab_printers as $printer) {
					?><option value="<?php print htmlentities($printer['printer_id'], ENT_QUOTES);?>"><?php print htmlentities($printer['printer']);?></option><?php
				}
				?>
			</select>
		</div>

		<div class="hidden content-print-container content-print-printlabels">
			<button class="btn btn-primary print-button">Print Label(s)</button>
		</div>
		<?php
	}

	$customer_notes = trim($sales_order['customer_notes']);
	if(!empty($customer_notes)) {
		?>
		<div class="customer-notes-wrapper">
			<h3>Customer Notes</h3>
			<div class="notes customer-notes-container">
				<?php
				foreach(explode("\n", $customer_notes) as $note_part) {
					$note_part = trim(str_replace("\r", '', $note_part));
					if(!empty($note_part)) {
						?><div class="customer-notes-row"><?php print htmlentities($note_part);?></div><?php
					}
				}
				?>
			</div>
		</div>
		<?php
	}

	$order_notes = trim($sales_order['order_notes']);
	?>

	<div id="nav-container" style="padding-top: 10px;">
		
		<ul class="nav nav-tabs">
			<li class="nav-item nav-tab active" data-target="notes-tab">
				<a class="nav-link" href="#">Order Notes</a>
			</li>
			<li class="nav-item nav-tab" data-target="issues-tab">
				<a class="nav-link" href="#">Issues</a>
			</li>
		</ul>

	</div>

	<div id="notes-tab" class="order-notes-wrapper tab-target">
		<h3>Order Notes</h3>
		<div class="notes order-notes-container">
			<?php
			foreach(explode("\n", $order_notes) as $note_part) {
				$note_part = trim(str_replace("\r", '', $note_part));
				if(!empty($note_part)) {
					?><div class="order-notes-row"><?php print htmlentities($note_part);?></div><?php
				}
			}
			?>
		</div>
		<div class="order-notes-new-container input-append input-block-level">
			<input type="text" class="order-notes-new-input" />
			<button type="button" class="order-notes-new-button btn">Add</button>
		</div>
	</div>

	<div id="issues-tab" class="tab-target" style="display:none;">
		<h3>Issues</h3>

		<div id="existing-issues-container">
			<table id="existing-issues-table" class="table table-striped table-hover">
				<thead>
					<th>User</th>
					<th>Issue Code</th>
					<th>Details</th>
					<th>Created On</th>
					<?php
						if($session->hasRole('Administration')){
							?>
								<th>Edit</th>
							<?php
						}
					?>
				</thead>
				<tbody>

					<?php
					foreach($issues as $issue){
						?>
							<tr class="issue-row" data-issue-id="<?php print htmlentities($issue['issue_id']) ?>">
								<td class="commentable"><?php print htmlentities($issue['first_name']) ?></td>
								<td class="commentable"><?php print htmlentities($issue['issue_code']) ?></td>
								<td class="details-td commentable""><?php print htmlentities($issue['details']) ?></td>
								<td class="commentable"><?php print htmlentities($issue['created_on']) ?></td>
								<?php
									if($session->hasRole('Administration')){
										?>
											<td class="issue-edit" align="center"><a href="#"><i class="fa fa-fw fa-pencil-square-o"></i></a></td>
										<?php
									}
								?>
							</tr>
						<?php
					}
					?>

				</tbody>
			</table>

		</div>

		<button id="abnormal-button" class="btn btn-danger" type="button">Add Issue</button>
		<div id="abnormal-orders-container" style="display:none;">
			<div class="abnormal abnormal-order-select pad-top abnormal">
				<select id="abnormal-select">
					<option value="">Issue Code</option>
					<?php
						foreach($issue_codes as $code){
							?><option value="<?php print htmlentities($code['issue_code_id']) ?>"><?php print htmlentities($code['issue_code']) ?></option>
						<?php
						};
					?>
				</select>
			</div>
			<div class="abnormal abnormal-orders-details">
				<textarea id="abnormal-details"></textarea>
			</div>
			<div class="abnormal">
				<button id="submit-issue" class="btn btn-primary">Submit</button>
			</div>
		</div>
	</div>

</div>

<script type="text/javascript">
	/**
	 * Bind to `Add` button clicks and `Enter/Return` key-presses for Order
	 * Notes.
	 */
	var $so_details_container = $('#so-details-container');
	
	var new_order_notes_callback = function(event) {
		if(event.type === 'keydown' && event.keyCode !== 13) {
			return;
		}

		var $order_notes_wrapper = $(event.target).closest('.order-notes-wrapper');
		var sono = <?php print json_encode($_POST['so-number']);?>;

		var $order_notes_container = $order_notes_wrapper.find('.order-notes-container');

		var $new_order_notes_container = $order_notes_wrapper.find('.order-notes-new-container');
		var $new_order_input = $new_order_notes_container.find('.order-notes-new-input');
		var order_note = $new_order_input.val();

		// If the input is empty, there is nothing to append.
		if(order_note === undefined || !order_note.length) {
			return;
		}

		var $ajax_loading_container;

		$.ajax({
			'url': BASE_URI + '/dashboard/sales-order-status/order-notes-append',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'order-id': sono,
				'order-note': order_note
			},
			'beforeSend': function(jqXHR, settings) {
				$ajax_loading_container = $('<div class="ajax-loading-container">').append(
					$('<img src="' + STATIC_PATH + '/images/ajax-loading-horizontal.gif" />')
				);
				$order_notes_container.append($ajax_loading_container);
			},
			'success': function(data, status, jqXHR) {
				if(data.success) {
					$order_notes_container.append(
						$('<div class="order-notes-row">').text(data.note)
					);
					$new_order_input.val('');
				} else {
					alert(data.message);
				}
			},
			'complete': function(jqXHR, status) {
				$ajax_loading_container.remove();
			}
		});
	};
	$(document).off('click', '#so-details-container .order-notes-new-button');
	$(document).on('click', '#so-details-container .order-notes-new-button', new_order_notes_callback);
	$(document).off('keydown', '#so-details-container .order-notes-new-input');
	$(document).on('keydown', '#so-details-container .order-notes-new-input', new_order_notes_callback);

	$(document).off('click', '#so-details-container .order-status-container .action-edit');
	$(document).on('click', '#so-details-container .order-status-container .action-edit', function(event) {
		var order_id = <?php print json_encode($_POST['so-number']);?>;
		var $icon = $(this);
		var $container = $icon.closest('.order-status-container');
		var $selected = $container.find('.status-selected');
		var $available_container = $container.find('.status-options');
		var $select = $available_container.find('.order-status-edit-select');
		if($icon.hasClass('fa-pencil')) {
			// View -> Edit
			$icon.removeClass('fa-pencil');
			$icon.addClass('fa-times');
			$icon.attr('title', 'Cancel');
			$selected.hide();
			$available_container.show();
			$select.off('change');
			$select.on('change', function() {
				var status = $(this).val();
				$selected.text(status);
				// Click "X" icon to show/hide as appropriate.
				$('#so-details-container .order-status-container .action-edit').trigger('click');

				// When viewing orders in Sales Order Status interface, update the order's status within the page.
				//$('#orders-container tr[sales-order-number="' +  + '"] .order-status-container').text(status);

				// Fire off AJAX request to save the change.
				$.ajax({
					'url': BASE_URI + '/dashboard/sales-order-status/order-status-update',
					'type': 'POST',
					'dataType': 'json',
					'data': {
						'order-id': $so_details_container.attr('order-id'),
						'order-status': status
					}
				});
			});
		} else {
			// Edit -> View
			$icon.removeClass('fa-times');
			$icon.addClass('fa-pencil');
			$icon.attr('title', 'Edit');
			$available_container.hide();
			$selected.show();
		}
	});

	function display_abnormal(){

		// The "Abnormal" button was clicked - show the options for
		// marking an order as abnormal.

		// Get and show the abnormal orders tools.
		var $container = $('#abnormal-orders-container')
		$container.show()

		// Scroll to the bottom of the overlay.
		var $div = $('.overlayz-body')
		$div.scrollTop($div.prop('scrollHeight'))

	}

	function mark_abnormal(){

		// An issue has been submitted from the client.
		// Make the order as abnormal and save the details.

		// Get the selected issue code.
		var $select = $('#abnormal-select')
		var issue_code_id = $select.val()

		// Get the issue details.
		var $details = $('#abnormal-details')
		var details = $details.val().trim()

		// Make sure an issue code was set.
		if(!issue_code_id){
			alert("Please Select an Issue Code")
			return
		}

		// Make sure details have been supplied.
		if(!details){
			alert("Please Provide Additional Details")
			return;
		}

		// Define the data to POST.
		var data = {
			'action' : 'add-issue',
			'sono' : <?php print json_encode($_POST['so-number']);?>,
			'issue_code_id' : issue_code_id,
			'details' : details
		}

		// POST the data
		$.ajax({
			'url' : BASE_URI + '/dashboard/sales-order-status/add-order-issue',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
		}).error(function(){
			console.log('error')
		}).success(function(rsp){

			// Get the new issue ID.
			var issue_id = rsp['issue_id']

			// If the issue ID is null, the submission was likely a perfect duplicate.
			if(!issue_id){
				alert("Issue Already Exists")
				return
			}

			// Create the row.
			var $tr = $('<tr></tr>')
			$tr.addClass('issue-row')
			$tr.attr('data-issue-id', issue_id)

			// Get the TD values.
			var user = rsp['name']
			var issue_code = $('#abnormal-select option:selected').text()
			var created_on = rsp['created_on']

			// Append TDs to the row.
			var $user_td = $('<td class="commentable">'+user+'</td>')
			var $issue_td = $('<td class="commentable">'+issue_code+'</td>')
			var $details_td = $('<td class="details-td commentable">'+details+'</td>')
			var $created_td = $('<td class="commentable">'+created_on+'</td>')

			// Add the TDs to the row.
			$tr.append($user_td)
			$tr.append($issue_td)
			$tr.append($details_td)
			$tr.append($created_td)

			// Add an edit TD for administrators.
			if(rsp['admin']){

				var $edit = $('<td class="issue-edit" align="center"><a href="#"><i class="fa fa-fw fa-pencil-square-o"></i></a></td>')
				$tr.append($edit)

			}

			// Add the row to the table.
			var $table = $('#existing-issues-table')
			var $tbody = $table.find('tbody')
			$tbody.append($tr)

			// Empty the textarea.
			$details.val('')

			// Reset the select.
			$select.prop('selectedIndex', 0)

			// Hide issue tools.
			$('#abnormal-orders-container').hide()

		})

	}

	function begin_edit_issue(){

		// Turn th details section into a textara for editing.

		// Get the TR.
		var $this = $(this)
		var $tr = $this.parents('tr')

		// Get the details TD and value.
		var $details_td = $tr.find('.details-td')
		var details = $details_td.text()

		// Get the dimensions of the td.
		var width = $details_td.width() + 'px'
		var height = $details_td.height() + 'px'

		// Replace the TR with a textarea for editing.
		var $textarea = $('<textarea class="detail-edit"></textarea>')
		$details_td.replaceWith($textarea)

		// Expand the textarea.
		$textarea.css({'width':width, 'height':height})

		// Add the details to the textarea.
		$textarea.val(details)

		// Focus on the textara to fascilitate editing.
		$textarea.focus()

	}

	function edit_issue(){


		// The textarea has lost focus and should be updated.

		// Get the textarea and TR.
		var $this = $(this)
		var $tr = $this.parents('tr')

		// First turn the textarea back into a td.
		var $td = $('<td class="details-td"></td>')
		var details = $this.val().trim()
		$td.text(details)
		$this.replaceWith($td)

		// Get the issue ID for updating.
		var issue_id = $tr.attr('data-issue-id')

		// Get the ID of the editing user.
		var login_id = <?php print json_encode($session->login['login_id']) ?>

		// The data to POST.
		var data = {
			'action' : 'edit-issue',
			'issue_id' : issue_id,
			'login_id' : login_id,
			'details' : details
		}

		// POST the data - do the update.
		$.ajax({
			'url' : BASE_URI + '/dashboard/sales-order-status/edit-order-issue',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
		}).error(function(){
			console.log('error')
		}).success(function(rsp){
			//console.log(rsp)
		})

	}

	// Activate a new tab.
	function activate_tab(tab, div){

		// JQuery makes things easy.
		var $tab = $(tab)
		var $div = $(div)

		// Remove active status from other tabs.
		var $tabs = $("li.nav-tab")
		$tabs.removeClass('active')

		// Add active status to the selected tab.
		$tab.addClass('active')

		// Hide all tab targets.
		var $targets = $(".tab-target")
		$targets.hide()

		// Show the selected tab target.
		$div.show()

	}

	// Support tab switching.
	function switch_tabs(){

		// Get the selected tab.
		var $tab = $(this)

		// Get the ID of the target tab.
		var target_id = $tab.attr('data-target')

		// Get the proper div
		var $div = $('#'+target_id)

		// Switch to the selected tab.
		activate_tab($tab, $div)

	}

	function reveal_comments(){

		// Reveal existing comments on an issue.
		// Show form for submitting a new comment.

		// Get the selected issue.
		var $this = $(this)
		$tr = $this.parents('tr')

		// Remove all comment rows.
		$('.add-comment-row').remove()
		$('.comment-row').remove()

		// Get the issue ID.
		var issue_id = $tr.attr('data-issue-id')


		// The data to POST.
		var data = {
			'issue_id' : issue_id
		}

		// Get the comments.
		$.ajax({
			'url' : BASE_URI + '/dashboard/sales-order-status/get-order-issue-comments',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
		}).error(function(){
			console.log('error')
		}).success(function(rsp){

			// Create a row to add a comment.
			$new_tr = $('<tr class="add-comment-row" data-issue-id="'+issue_id+'"></tr>')
			$new_tr.append($('<td></td>'))
			$new_tr.append($('<td><textarea class="comment-textarea"></textarea></td>'))
			$new_tr.append($('<td><button class="btn btn-default btn-small btn-add-comment">Submit</button></td>'))

			// Add the row to the table.
			$tr.after($new_tr)

			// Get the existing comments.
			$.each($(rsp['comments']), function(idx, c){

				// Get the comment details.
				var comment_id = c['comment_id']
				var comment = c['comment']
				var name = c['first_name']
				var created_on = c['created_on']

				// // Create a row for the comment.
				var $xtr = $('<tr class="comment-row" data-issue-id="'+issue_id+'"></tr>')
				$xtr.append($('<td></td>'))
				$xtr.append($('<td>'+name+'</td>'))
				$xtr.append($('<td>'+comment+'</td>'))
				$xtr.append($('<td>'+created_on+'</td>'))

				// // Add the row to the table.
				if(idx == 0){
					$tr.after($xtr)
				}else{
					$prev.after($xtr)
				}
				$prev = $xtr

			})

		})

	}

	function add_comment(){

		// A comment has been submitted - insert it into SQL Server.

		// Get the tr and issue ID.
		var $this = $(this)
		var $tr = $this.parents('tr')
		var issue_id = $tr.attr('data-issue-id')

		// Get the comment text.
		var $textarea = $tr.find('.comment-textarea')
		var comment = $textarea.val()

		// Get the ID of the commenting user.
		var login_id = <?php print json_encode($session->login['login_id']) ?>

		// The data to POST.
		var data = {
			'issue_id' : issue_id,
			'login_id' : login_id,
			'comment' : comment
		}

		// Insert the comment.
		$.ajax({
			'url' : BASE_URI + '/dashboard/sales-order-status/add-order-issue-comment',
			'method' : 'POST',
			'dataType' : 'json',
			'data' : data,
		}).error(function(){
			console.log('error')
		}).success(function(rsp){

			// Get the comment ID - if the comment ID is null, the comment is a duplicate.
			var comment_id = rsp['comment_id']
			if(!comment_id){
				alert("Duplicate Comment")
				return
			}

			// Get the name of the user and the created on timestamp.
			var name = '<?php print json_encode($session->login['first_name']) ?>'.replace(/\"/g, "")
			var created_on = rsp['created_on']

			// Create a row for the newly added comment.
			var $ntr = $('<tr class="comment-row" data-issue-id="'+issue_id+'"></tr>')
			$ntr.append($('<td></td>'))
			$ntr.append($('<td>'+name+'</td>'))
			$ntr.append($('<td>'+comment+'</td>'))
			$ntr.append($('<td>'+created_on+'</td>'))

			// Get the target row.
			// Add the row to the table.
			if($('.comment-row').length == 0){
				$tr.before($ntr)
			}else{
				$tr.prev('.comment-row').after($ntr)
			}

			// Clear the textarea.
			$textarea.val('')

		})

	}

	function show_image(){

		// Show the image taken in the warehouse.

		// Get the row, so and item.
		var $row = $(this).parents('.item-row')
		var sono = $row.attr('data-sono')
		var item = $row.attr('data-itemno')

		// The data to POST.
		var data = {
			'sono' : sono,
			'item' : item,
			'company_id' : '<?php print COMPANY ?>'
		}

		// The URL for overlayz
		var url = BASE_URI+'/dashboard/sales-order-status/so-image'

		// Create the overlay.
		activateOverlayZ(url, data)

	}

	function get_item_discrepancy(){

		// Get the discrepancy between what components are required for the
		// item's components and what is on hand for each line-item.

		// Get the item and location from each row.
		var items = []
		var $rows = $('.item-row')
		$.each($rows, function(idx, row){

			// Get the item and location.
			var $row = $(row)
			var item = $row.find('.content-item-number').text().trim()
			var loc = $row.find('.content-location').text().trim()

			// Skip the freight-line.
			if(item=='FRT'){
				return true;
			}

			// Keep the items.
			items.push({
				'item' : item,
				'loc' : loc
			})

		})

		// The data to POST.
		var data = {
			'action' : 'get-item-discrepancy',
			'items' : JSON.stringify(items)
		}

		// Get all items with a discrepancy.
		$.ajax({
			'url' : '/dashboard/sales-order-status/so-details',
			'method' : 'POST',
			'dataType' : 'JSON',
			'data' : data,
			'success' : function(rsp){

				// Mark items that have a shortage.
				$.each(rsp.items, function(idx, obj){

					// Get the item details.
					var item = obj.item
					var loc = obj.loc
					var shor = parseInt(obj.shortage)

					// Find the item's td.
					var $td = $('.content-item-number:contains("'+item+'")')
					var $tr = $td.parents('tr')

					// Create a shortage indicator.
					var $i = $('<i/>',{
						'class' : 'fa fa-exclamation'
					})

					// // Create an expansion indicator.
					// var $e = $('<i/>',{
					// 	'class' : 'fa fa-plus'
					// })

					// Add the shortage indicator next to items with a
					// shortage and a "+" sign to support expansion.
					if(shor>0){

						// Shortage indicator.
						$td.append($i)

						// // Expansion indicator.
						// var $xtd = $tr.find('.content-expand')
						// $xtd.append($e)
					}

				})

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function expand_item(){

		// Expand an item to display its constituent components.

		// Get the row.
		var $tr = $(this).parents('tr')

		// Get the item and location TDs.
		var $item = $tr.find('.content-item-number')
		var $location = $tr.find('.content-location')

		// Get the textual item and location.
		var item = $item.text().trim()
		var location = $location.text().trim()

		// The data to POST.
		var data = {
			'action' : 'get-bom-items',
			'item' : item,
			'location' : location,
			'sono' : $tr.attr('data-sono')
		}

		// Get the BOM items.
		$.ajax({
			'url' : '/dashboard/sales-order-status/so-details',
			'method' : 'POST',
			'dataType' : 'JSON',
			'data' : data,
			'success' : function(rsp){

				// Add the HTML after the current row.
				$tr.after(rsp.html)

				// Get the plus sign.
				var $td = $tr.find('.content-expand')
				var $i = $td.find('.fa-plus')

				// Close any other open line items.
				$('.content-reduce').click()

				// Replace the "+" with a "-".
				$i.removeClass('fa-plus')
				$i.addClass('fa-minus')

				// Change the td class.
				$td.removeClass('content-expand')
				$td.addClass('content-reduce')

				// Show the PO selection.
				show_pos()

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function show_pos(){

		// Show the open POs for a component.

		// The table with components.
		var $table = $('#item-expansion-table')

		// Get each tr.
		var $trs = $table.find('.component-row')
		
		// Get the POs for each tr and create a dropdown for them.
		$.each($trs, function(idx, tr){

			// Get the POs.
			var $tr = $(tr)
			var $pos = $.parseJSON($tr.attr('data-pos'))
			var $item = $tr.attr('data-item')

			// Don't do anything for items without an open PO.
			if($.isEmptyObject($pos)){
				return true;
			}

			// Create a dropdown.
			var $dropdown = $('<select>',{
				'class' : 'po-select'
			})

			// Add the default option.
			var $doption = $('<option>',{
				'value' : '',
				'text' : '-- Select PO --'
			})
			$dropdown.append($doption)

			// Create the select options.
			$.each($pos, function(idx, po){

				var $option = $('<option>',{
					'value' : po,
					'text' : po
				})
				$dropdown.append($option)

			})

			// Add the select to the correct TD.
			var $td = $tr.find('.po-td')
			if($td.is(':empty')){$td.html($dropdown)}

		})

	}

	function reduce_item(){

		// Remove the expanded area that shows items components.
		var $td = $(this)
		var $tr = $td.parents('tr')

		// Get and remove the row that contains the item components.
		var $expanded = $tr.next('.component-container-row')
		$expanded.remove()

		// Update the icon.
		var $i = $td.find('.fa-minus')
		$i.removeClass('fa-minus')
		$i.addClass('fa-plus')

		// Reclassify the td.
		$td.removeClass('content-reduce')
		$td.addClass('content-expand')

	}

	function assign_po(){

		// Establish an Item/PO relationship.

		// Get the SO.
		var so = $('.sales-order-number').text()

		// Get the row.
		var $select = $(this)
		var $tr = $($select.parents('tr')[0])

		// Get the item and PO.
		var item = $tr.attr('data-item')
		var po = $select.val()

		// Get the parent item.
		var $ptr = $tr.parents('.component-container-row').prev('.item-row')
		var $ptd = $ptr.find('.content-item-number')
		var parent = $ptd.text().trim()

		// The data to POST.
		var data = {
			'action' : 'relate-item-po',
			'item' : item,
			'parent' : parent,
			'po' : po,
			'so' : so
		}

		// Create the relationship.
		$.ajax({
			'url' : '/dashboard/sales-order-status/so-details',
			'method' : 'POST',
			'dataType' : 'JSON',
			'data' : data,
			'success' : function(rsp){

				// Replace the dropdown with the PO.
				$select.replaceWith(po)

				// Add a "remove" icon.
				var $x = $('<i/>',{
					'class' : 'fa fa-fw fa-times'
				})
				var $td = $tr.find('.po-td')
				$td.append($x)

				// Set the shipdate.
				$tr.find('.ship-td').html(rsp.shipdate)

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function unassign_po(){

		// Remove an Item/PO relationship.

		// Get the Item and SO.
		var $tr = $($(this).parents('tr')[0])
		var item = $tr.attr('data-item')
		var so = $('.sales-order-number').text()

		// The data to POST.
		var data = {
			'action' : 'remove-item-po-relationship',
			'item' : item,
			'so' : so
		}

		// Remvoe the relationship.
		$.ajax({
			'url' : '/dashboard/sales-order-status/so-details',
			'method' : 'POST',
			'dataType' : 'JSON',
			'data' : data,
			'success' : function(rsp){

				// Get the POs.
				var $pos = $.parseJSON($tr.attr('data-pos'))

				// Create a new dropdown.
				var $dropdown = $('<select>',{
					'class' : 'po-select'
				})

				// Add the default option.
				var $doption = $('<option>',{
					'value' : '',
					'text' : '-- Select PO --'
				})
				$dropdown.append($doption)

				// Create the select options.
				$.each($pos, function(idx, po){

					var $option = $('<option>',{
						'value' : po,
						'text' : po
					})
					$dropdown.append($option)

				})

				// Set the dropdown in the td.
				$tr.find('.po-td').html($dropdown)

				// Remove the shipdate.
				$tr.find('.ship-td').empty()

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function get_related_files(){

		// Get the related files for the SO.

		// Get the SO.
		var $span = $('.sales-order-number')
		var sono = $span.text()

		// The data required for the overlay.
		var data = {
			'type' : 'so',
			'assoc-id' : sono
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

		// Get the SO.
		var $span = $('.sales-order-number')
		var sono = $span.text()

		produce_file_upload_overlay('so', sono)

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

	// Get all item discrepancies.
	get_item_discrepancy()

	// Get any files related to the SO.
	get_related_files()

	// Show the tools for marking the order as abnormal.
	$(document).off('click', '#abnormal-button') // Prevent multiple executions.
	$(document).on('click', '#abnormal-button', display_abnormal)

	// Mark the order as abnormal.
	$(document).off('click', '#submit-issue')
	$(document).on('click', '#submit-issue', mark_abnormal)

	// Show a textarea to allow issue editing.
	$(document).off('click', '.issue-edit')
	$(document).on('click', '.issue-edit', begin_edit_issue)

	// Actually edit an issue.
	$(document).off('focusout', '.detail-edit')
	$(document).on('focusout', '.detail-edit', edit_issue)

	// Enable tab switching.
	$(document).on('click', '.nav-tab', switch_tabs)

	// Support commenting on issues.
	$(document).off('click', '.commentable')
	$(document).on('click', '.commentable', reveal_comments)
	$(document).off('click', '.btn-add-comment')
	$(document).on('click', '.btn-add-comment', add_comment)

	// Display recorded first-piece images.
	$(document).off('click', '.cam-icon')
	$(document).on('click', '.cam-icon', show_image)

	// Support expanding items with a discrepancy.
	$(document).off('click', '.content-expand')
	$(document).on('click', '.content-expand', expand_item)

	// Support removing the expansion.
	$(document).off('click', '.content-reduce')
	$(document).on('click', '.content-reduce', reduce_item)

	// Support PO-assignment.
	$(document).off('change', '.po-select')
	$(document).on('change', '.po-select', assign_po)

	// Support PO-unassignment.
	$(document).off('click', '.fa-times')
	$(document).on('click', '.fa-times', unassign_po)

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

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode(array(
	'success' => True,
	'html' => $html
));
