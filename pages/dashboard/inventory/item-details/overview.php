<?php

$session->ensureLogin();

function get_revisions(){

	// Query for all revision numbers for the BOM.

	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT
			CASE WHEN LTRIM(RTRIM(revlev)) = ''
				THEN '- Original -'
				ELSE LTRIM(RTRIM(revlev))
			END AS revlev,
			CASE WHEN LTRIM(RTRIM(revlev)) = ''
				THEN '-'
				ELSE LTRIM(RTRIM(revlev))
			END AS revlev_val
		FROM ".DB_SCHEMA_ERP.".pebmdt
		WHERE bomno = ".$db->quote($_POST['item-number'])."
			ORDER BY revlev
	");

	return $q->fetchAll();

}

// Get revisions.
$revisions = get_revisions();

ob_start(); // Start loading output into buffer.

?>
<style type="text/css">
	#bom-header {
		align-items: center;
		align-content: center;
		display: flex;
	}
</style>
<script type="text/javascript">
	$(document).ready(function(){

		function filter_revisions(){

			// Filter BOM entries by revision.
			
			// Get the revision value.
			var $select = $(this)
			var revision = $select.val()

			// Hide all rows.
			var $all_rows = $('.bom-row')
			$all_rows.hide()

			// Show filtered or all.
			if(!revision){
				$all_rows.show()
				return false
			}

			// Get and show matching rows.
			var $rows = $('.bom-row[data-revision="'+revision+'"]')
			$rows.show()

		}

		// Enable revision filtering.
		$(document).off('change', '#revision-select')
		$(document).on('change', '#revision-select', filter_revisions)

	})
</script>
<?php

$grab_item = $db->query("
	SELECT
		icitem.item
	FROM
		" . DB_SCHEMA_ERP . ".icitem
	WHERE
		icitem.item = " . $db->quote(strtoupper(trim($_POST['item-number']))) . "
");
$item = $grab_item->fetch();

$html = '<div id="item-overview-body">';

/**
 * BOM
 */
$is_bom = False;
$grab_bom = $db->query("
	SELECT DISTINCT
		pebmdt.item,
		pebmdt.bqtyusd as qty,
		icitem.lstcost * pebmdt.bqtyusd AS cost,
		CASE WHEN LTRIM(RTRIM(revlev)) = ''
			THEN '-'
			ELSE LTRIM(RTRIM(revlev))
		END AS revision
	FROM
		" . DB_SCHEMA_ERP . ".pebmdt
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".icitem
		ON
		LTRIM(RTRIM(icitem.item)) = LTRIM(RTRIM(pebmdt.item))
	WHERE
		LTRIM(RTRIM(pebmdt.bomno)) = " . $db->quote($item['item']) . "
	ORDER BY
		pebmdt.item,
		revision
");
$bom_html = '';
$cost = 0.00;

foreach($grab_bom as $bom) {
	$is_bom = True;

	$overlayz_data = json_encode(array(
		'item-number' => trim($bom['item'])
	));
	$overlayz_url = BASE_URI . '/dashboard/inventory/item-details';

	$bom_html .= '<tr class="bom-row" data-revision="'.htmlentities($bom['revision']).'">';
	$bom_html .= 	'<td>'.htmlentities($bom['revision']).'</td>';
	$bom_html .=	'<td class="overlayz-link" overlayz-data="' . htmlentities($overlayz_data, ENT_QUOTES) . '" overlayz-url="' . $overlayz_url . '">' . htmlentities($bom['item']) . '</td>';
	$bom_html .=	'<td>' . number_format($bom['qty']) . '</td>';
	$bom_html .=	'<td>$' . number_format($bom['cost'], 2) . '</td>';
	$bom_html .= '</tr>';
	$cost += $bom['cost'];
}

$bom_header = "
	<div id='bom-header' class='row-fluid'>
		<div class='span3'>
			<h3>BOM (bill of materials)</h3>
		</div>
		<div id='revisions-container' class='span3'>
			<select id='revision-select'>
				<option value=''>-- Select Revision --</option>";
				foreach($revisions as $revision){
					$bom_header.="<option value='".htmlentities($revision['revlev_val'])."'>".htmlentities($revision['revlev'])."</option>";
				}
$bom_header .= "
			</select>
		</div>
	</div>
";

if($is_bom) {
	$html .= $bom_header;
	$html .= '<table>';
	$html .=	'<thead>';
	$html .=		'<tr>';
	$html .=			'<th>Revision</th>';
	$html .=			'<th>Item</th>';
	$html .=			'<th>Quantity</th>';
	$html .=			'<th>Cost</th>';
	$html .=		'</tr>';
	$html .=	'</thead>';
	$html .=	'<tbody>';
	$html .=		$bom_html;
	$html .=		'<tr>';
	$html .=			'<td colspan="3" style="text-align:right;">Total Cost</td>';
	$html .=			'<td>$' . number_format($cost, 2) . '</td>';
	$html .=		'</tr>';
	$html .=	'</tbody>';
	$html .= '</table>';
}

/**
 * AVAILABLE
 */

$grab_available = $db->query("
	SELECT
		iciloc.loctid AS location,
		iciloc.lonhand AS on_hand,
		iciloc.lsoaloc AS so_allocation,
		iciloc.lwoaloc AS wo_allocation,
		iciloc.lonwkor AS wo_order,
		iciloc.lonordr AS incoming_po,
		iciloc.lonhand - (iciloc.lsoaloc  + iciloc.lwoaloc) AS available,
		(iciloc.lonhand +  iciloc.lonordr ) - (iciloc.lsoaloc  + iciloc.lwoaloc) AS forecast,
		CONVERT(varchar(10), iciloc.lrecv, 120) AS last_receipt
	FROM
		" . DB_SCHEMA_ERP . ".iciloc
	WHERE
		iciloc.item = " . $db->quote($item['item']) . "
		AND
		(
			iciloc.lonhand != 0
			OR
			iciloc.lsoaloc != 0
			OR
			iciloc.lwoaloc != 0
			OR
			iciloc.lonwkor != 0
			OR
			iciloc.lonordr != 0
			OR
			iciloc.lonhand - (iciloc.lsoaloc  + iciloc.lwoaloc) != 0
			OR
			(iciloc.lonhand +  iciloc.lonordr ) - (iciloc.lsoaloc  + iciloc.lwoaloc) != 0
		)
	ORDER BY
		iciloc.loctid
");
$available_html = '';
foreach($grab_available as $available) {
	$available_html .= '<tr>';
	$available_html .=	'<td>' . $available['location'] . '</td>';
	$available_html .=	'<td>' . number_format($available['on_hand'], 0) . '</td>';
	$available_html .=	'<td>' . number_format($available['so_allocation'], 0) . '</td>';
	$available_html .=	'<td>' . number_format($available['wo_allocation'], 0) . '</td>';
	$available_html .=	'<td>' . number_format($available['wo_order'], 0) . '</td>';
	$available_html .=	'<td>' . number_format($available['incoming_po'], 0) . '</td>';
	$available_html .=	'<td>' . number_format($available['available'], 0) . '</td>';
	$available_html .=	'<td>' . number_format($available['forecast'], 0) . '</td>';
	$available_html .=	'<td>' . $available['last_receipt'] . '</td>';
	$available_html .= '</tr>';
}
if(!empty($available_html)) {
	$html .= '<h3>Available</h3>';
	$html .= '<table>';
	$html .=	'<thead>';
	$html .=		'<tr>';
	$html .=			'<th>Location</th>';
	$html .=			'<th>On Hand</th>';
	$html .=			'<th>SO Allocation</th>';
	$html .=			'<th>WO Allocation</th>';
	$html .=			'<th>WO Order</th>';
	$html .=			'<th>Incoming PO</th>';
	$html .=			'<th>Available</th>';
	$html .=			'<th>Forecast</th>';
	$html .=			'<th>Last Receipt</th>';
	$html .=		'</tr>';
	$html .=	'</thead>';
	$html .=	'<tbody>';
	$html .=		$available_html;
	$html .=	'</tbody>';
	$html .= '</table>';
}

/**
 * OPEN WO
 */
$grab_openwo_allocations = $db->query("
	SELECT
		wotran.custno AS cust_code,
		wotran.sono AS so_number,
		wotran.wono AS wo_number,
		wotran.wrawtqty AS qty,
		wotran.wloctid AS location,
		CONVERT(varchar(10), womast.reqdate, 120) AS req_date
	FROM
		" . DB_SCHEMA_ERP . ".wotran
	INNER JOIN
		" . DB_SCHEMA_ERP . ".womast
		ON
		womast.wono = wotran.wono
	WHERE
		womast.wostat != 'C'
		AND
		wotran.item = " . $db->quote($item['item']) . "
		AND
		wotran.wrawtqty != 0
	GROUP BY
		wotran.custno,
		wotran.sono,
		wotran.wono,
		wotran.wrawtqty,
		wotran.wloctid,
		womast.reqdate
	ORDER BY
		womast.reqdate,
		wotran.sono
");
$openwo_allocations_html = '';
foreach($grab_openwo_allocations as $openwo_allocation) {
	$openwo_allocations_html .= '<tr>';
	$openwo_allocations_html .=	'<td>' . $openwo_allocation['cust_code'] . '</td>';
	$openwo_allocations_html .=	'<td>' . $openwo_allocation['so_number'] . '</td>';
	$openwo_allocations_html .=	'<td>' . $openwo_allocation['wo_number'] . '</td>';
	$openwo_allocations_html .=	'<td>' . number_format($openwo_allocation['qty'], 0) . '</td>';
	$openwo_allocations_html .=	'<td>' . $openwo_allocation['location'] . '</td>';
	$openwo_allocations_html .=	'<td>' . $openwo_allocation['req_date'] . '</td>';
	$openwo_allocations_html .= '</tr>';
}
if(!empty($openwo_allocations_html)) {
	$html .= '<h3>Open WO Allocation</h3>';
	$html .= '<table>';
	$html .=	'<thead>';
	$html .=		'<tr>';
	$html .=			'<th>Customer Code</th>';
	$html .=			'<th>SO #</th>';
	$html .=			'<th>WO #</th>';
	$html .=			'<th>Quantity</th>';
	$html .=			'<th>Location</th>';
	$html .=			'<th>Req Date</th>';
	$html .=		'</tr>';
	$html .=	'</thead>';
	$html .=	'<tbody>';
	$html .=		$openwo_allocations_html;
	$html .=	'</tbody>';
	$html .= '</table>';
}

/**
 * OPEN SO
 */
$grab_opensos = $db->query("
	SELECT
		sotran.custno AS cust_code,
		sotran.sono AS so_number,
		sotran.qtyord AS open_qty,
		sotran.loctid AS location,
		CONVERT(varchar(10), sotran.rqdate, 120) AS req_date
	FROM
		" . DB_SCHEMA_ERP . ".sotran
	INNER JOIN
		" . DB_SCHEMA_ERP . ".somast
		ON
		somast.sono = sotran.sono
	WHERE
		somast.sotype != 'b'
		AND
		somast.sostat NOT IN ('C', 'V', 'X')
		AND
		sotran.item = " . $db->quote($item['item']) . "
		AND
		sotran.qtyord != 0
	ORDER BY
		sotran.rqdate,
		sotran.sono
");
$opensos_html = '';
foreach($grab_opensos as $openso) {
	$opensos_html .= '<tr>';
	$opensos_html .=	'<td class="overlayz-link" overlayz-url="' . BASE_URI . '/dashboard/clients/details" overlayz-data="' . htmlentities(json_encode(['custno' => $openso['cust_code']]), ENT_QUOTES) . '">' . $openso['cust_code'] . '</td>';
	$opensos_html .=	'<td class="overlayz-link" overlayz-url="' . BASE_URI . '/dashboard/sales-order-status/so-details" overlayz-data="' . htmlentities(json_encode(['so-number' => $openso['so_number']]), ENT_QUOTES) . '">' . $openso['so_number'] . '</td>';
	$opensos_html .=	'<td>' . number_format($openso['open_qty'], 0) . '</td>';
	$opensos_html .=	'<td>' . $openso['location'] . '</td>';
	$opensos_html .=	'<td>' . $openso['req_date'] . '</td>';
	$opensos_html .= '</tr>';
}
if(!empty($opensos_html)) {
	$html .= '<h3>Open SOs</h3>';
	$html .= '<table>';
	$html .=	'<thead>';
	$html .=		'<tr>';
	$html .=			'<th>Customer Code</th>';
	$html .=			'<th>SO #</th>';
	$html .=			'<th>Open Quantity</th>';
	$html .=			'<th>Location</th>';
	$html .=			'<th>Req Date</th>';
	$html .=		'</tr>';
	$html .=	'</thead>';
	$html .=	'<tbody>';
	$html .=		$opensos_html;
	$html .=	'</tbody>';
	$html .= '</table>';
}

/**
 * OPEN PO
 */
$grab_openpos = $db->query("
	SELECT
		potran.vendno AS vendor_code,
		potran.purno AS po_number,
		potran.qtyord - potran.qtyrec AS open_qty,
		potran.forloct AS location,
		CONVERT(varchar(10), potran.reqdate, 120) AS req_date
	FROM
		" . DB_SCHEMA_ERP . ".potran
	WHERE
		potran.item = " . $db->quote($item['item']) . "
		AND
		potran.potype != 'B'
		AND
		potran.postat != 'V'
		AND
		potran.postat != 'X'
		AND
		potran.qtyord - potran.qtyrec != 0
	ORDER BY
		potran.reqdate,
		potran.purno
");
$openpos_html = '';
foreach($grab_openpos as $openpo) {
	$openpos_html .= '<tr>';
	$openpos_html .=	'<td>' . $openpo['vendor_code'] . '</td>';
$openpos_html .=	'<td class="overlayz-link" overlayz-url="' . BASE_URI . '/dashboard/purchaseorders/details" overlayz-response-type="html" overlayz-data="' . htmlentities(json_encode(['purno' => trim($openpo['po_number'])]), ENT_QUOTES) . '">' . $openpo['po_number'] . '</td>';
	$openpos_html .=	'<td>' . number_format($openpo['open_qty'], 0) . '</td>';
	$openpos_html .=	'<td>' . $openpo['location'] . '</td>';
	$openpos_html .=	'<td>' . $openpo['req_date'] . '</td>';
	$openpos_html .= '</tr>';
}
if(!empty($openpos_html)) {
	$html .= '<h3>Open POs</h3>';
	$html .= '<table>';
	$html .=	'<thead>';
	$html .=		'<tr>';
	$html .=			'<th>Vendor Code</th>';
	$html .=			'<th>PO #</th>';
	$html .=			'<th>Open Quantity</th>';
	$html .=			'<th>Location</th>';
	$html .=			'<th>Req Date</th>';
	$html .=		'</tr>';
	$html .=	'</thead>';
	$html .=	'<tbody>';
	$html .=		$openpos_html;
	$html .=	'</tbody>';
	$html .= '</table>';
}

/**
 * OPEN LOCATIONS
 */
$grab_locations = $db->query("
	SELECT
		iciqty.loctid AS location,
		iciqty.qstore AS store,
		iciqty.qbin AS bin,
		iciqty.qonhand AS qty
	FROM
		" . DB_SCHEMA_ERP . ".iciqty
	WHERE
		iciqty.item = " . $db->quote($item['item']) . "
		AND
		iciqty.qonhand != 0
	ORDER BY
		iciqty.loctid,
		iciqty.qbin
");
$locations_html = '';
foreach($grab_locations as $location) {
	$locations_html .= '<tr>';
	$locations_html .=	'<td>' . $location['location'] . '</td>';
	$locations_html .=	'<td>' . $location['store'] . '</td>';
	$locations_html .=	'<td>' . $location['bin'] . '</td>';
	$locations_html .=	'<td>' . number_format($location['qty'], 0) . '</td>';
	$locations_html .= '</tr>';
}
if(!empty($locations_html)) {
	$html .= '<h3>Locations</h3>';
	$html .= '<table>';
	$html .=	'<thead>';
	$html .=		'<tr>';
	$html .=			'<th>Location</th>';
	$html .=			'<th>Store</th>';
	$html .=			'<th>Bin</th>';
	$html .=			'<th>Quantity</th>';
	$html .=		'</tr>';
	$html .=	'</thead>';
	$html .=	'<tbody>';
	$html .=		$locations_html;
	$html .=	'</tbody>';
	$html .= '</table>';
}
$html .= '</div>';
print $html;

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);