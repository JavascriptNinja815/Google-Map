<?php

$session->ensureLogin();

$args = array(
	'title' => 'Open WO Report',
	'breadcrumbs' => array(
		'Open WO Report' => BASE_URI . '/dashboard/warehouse/open-wo-report'
	),
	'body-class' => 'padded'
);

function get_sos(){

	// Set a temporary timeout of 5 minutes.
	ini_set('max_execution_time', 300);

	// Query for SO numbers.
	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT
			LTRIM(RTRIM(m.defloc)) AS defloc,
			LTRIM(RTRIM(m.sono)) AS sono,
			SUM(
				CASE WHEN w.wrawqty - i.lonhand > 0
				THEN 1 ELSE 0
				END
			) AS shortage
		FROM ".DB_SCHEMA_ERP.".somast m
		INNER JOIN ".DB_SCHEMA_ERP.".sotran t
			ON t.sono = m.sono
		INNER JOIN ".DB_SCHEMA_ERP.".wotran w
			ON w.sono = m.sono
			AND w.item = t.item
		INNER JOIN ".DB_SCHEMA_ERP.".iciloc i
			ON i.item = t.item
			AND i.loctid = w.wloctid
		WHERE m.orderstat = 'SHIPPED'
			AND w.wlntype = ''
			AND t.wono IS NOT NULL
			AND m.sostat != 'C'
		GROUP BY m.defloc, m.sono
		ORDER BY defloc, sono
	");

	// Restructure the results.
	$structured = array();
	$results = $q->fetchAll();
	foreach($results as $row){

		// Get the location value.
		$loc = $row['defloc'];

		// Make sure each location has an entry.
		if(!array_key_exists($loc, $structured)){
			$structured[$loc] = array();
		}

		/* Get the BOM-level shortage */
		// Get each item.
		$sono = $row['sono'];
		$items = get_sono_items($sono, $loc);

		// If any item has a component shortage,
		// set it on the row.
		$row['has_shortage'] = 0;
		foreach($items as $irow){
			if($irow['has_shortage']>0){
				$row['has_shortage'] = 1;
				break;
			}
		}

		// Add each SO to the location.
		if(!empty($items)){
			array_push($structured[$loc], $row);
		}

		/*-----*/

	}

	return $structured;

}

function get_item_shortage($item, $defloc){

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
		WHERE pb.bomno = ".$db->quote($item)."
			AND lo.loctid = ".$db->quote($defloc)."
	");

	return $q->fetch()['shortage'];

}

function get_sono_items($sono, $defloc){

	// Query for the SO items.
	$db = DB::get();
	$q = $db->query("
		SELECT DISTINCT
			LTRIM(RTRIM(m.defloc)) AS defloc,
			LTRIM(RTRIM(m.sono)) AS sono,
			m.orderstat,
			t.wono,
			CAST(t.qtyord - t.qtyshp AS integer) AS open_qty,
			LTRIM(RTRIM(w.item)) AS item,
			CAST(w.wrawqty AS integer) AS wrawqty,
			CAST(i.lonhand AS integer) AS lonhand,
			CASE
				WHEN w.wrawqty - i.lonhand > 0
				THEN CAST(w.wrawqty - i.lonhand AS integer)
				ELSE 0
			END AS shortage
		FROM ".DB_SCHEMA_ERP.".somast m
		INNER JOIN ".DB_SCHEMA_ERP.".sotran t
			ON t.sono = m.sono
		INNER JOIN ".DB_SCHEMA_ERP.".wotran w
			ON w.sono = m.sono
			AND w.item = t.item
		INNER JOIN ".DB_SCHEMA_ERP.".womast wm
			ON wm.wono = w.wono
		INNER JOIN ".DB_SCHEMA_ERP.".iciloc i
			ON i.item = t.item
			AND i.loctid = w.wloctid
		WHERE m.orderstat = 'SHIPPED'
			AND w.wlntype = ''
			--AND m.sostat NOT IN ('C', 'V')
			AND m.sostat != 'C'
			AND t.sostat != ''
			AND wm.wostat != 'C'
			AND t.wono != ''
			AND LTRIM(RTRIM(m.sono)) = ".$db->quote($sono)."

	");

	$results = $q->fetchAll();
	$rows = array();

	// Get the shortages.
	foreach($results as $row){

		// Get the item and shortage.
		$item = $row['item'];
		$shortage = get_item_shortage($item, $defloc);

		// Set a shortage indicator.
		$is_short = 0;
		if($shortage>0){$is_short = 1;}
		$row['has_shortage'] = $is_short;

		array_push($rows, $row);

	}

	return $rows;

}

function get_bom_items($location, $item){

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

	return $q->fetchAll();

}

// Handle AJAX.
if(isset($_POST['action'])){

	// Get items for an SO.
	if($_POST['action'] == 'get-sono-items'){

		// Get the items.
		$items = get_sono_items($_POST['sono'], $_POST['defloc']);

		// Create the HTML to display.
		$html =  '<div class="item-row row-fluid">';
		$html .=	'<table class="table table-striped table-hover">';
		$html .=		'<thead>';
		$html .=			'<th>Item</th>';
		$html .=			'<th>WO #</th>';
		$html .=			'<th class="text-center">Open Qty</th>';
		$html .=			'<th class="text-center">Required Qty</th>';
		$html .=			'<th class="text-center">On-Hand</th>';
		$html .=			'<th class="text-center">Shortage</th>';
		$html .=		'</thead>';
		$html .=		'<tbody>';
		foreach($items as $item){

			// Check to see if the item should have an exlamation point.
			$exc = '';
			if($item['has_shortage']>0){$exc = '<i class="fa fa-exclamation"></i>';};

			$html .= 		'<tr data-item="'.htmlentities($item['item']).'" data-location="'.htmlentities($item['defloc']).'" data-sono="'.htmlentities($item['sono']).'">';


// class="overlayz-link" overlayz-data="{"item-number":"'.htmlentities($item['item']).'"}"
			// $html .= 			'<td ><i class="fa fa-plus"></i> '.htmlentities($item['item']).' '.$exc.'</td>';
			// Get the data
			$data = json_encode(array('item-number' => $item['item']));
			$html .= 			'<td >
			<div class="pull-left"><i class="fa fa-plus"></i></div>
			<div class="item-level pull-left overlayz-link" overlayz-data="'.htmlentities($data).'" overlayz-url="/dashboard/inventory/item-details">'.htmlentities(trim($item['item'])).'</div>'.$exc.'</td>';



			$html .= 			'<td>'.htmlentities($item['wono']).'</td>';
			$html .= 			'<td class="text-center">'.htmlentities($item['open_qty']).'</td>';
			$html .= 			'<td class="text-center">'.htmlentities($item['wrawqty']).'</td>';
			$html .= 			'<td class="text-center">'.htmlentities($item['lonhand']).'</td>';
			$html .= 			'<td class="text-center">'.htmlentities($item['shortage']).'</td>';
			$html .= 		'</tr>';
			$html .= 		'<tr class="wo-bom-row"></tr>';
		}
		$html .=		'</tbody>';
		$html .=	'</table>';
		$html .= '</div>';

		print json_encode(array(
			'success' => true,
			'html' => $html
		));

		return;

	}
	if($_POST['action'] == 'get-bom-items'){

		// Get the POSTed data.
		$defloc = $_POST['defloc'];
		$item = $_POST['item'];

		// Get the BOM items.
		$bom = get_bom_items($defloc, $item);

		// Create the HTML to display.
		$html =  '<div class="bom-container row-fluid">';
		$html .=	'<table class="table table-striped table-hover">';
		$html .=		'<thead>';
		$html .=			'<th>Item</th>';
		$html .=			'<th class="text-center">On Hand</th>';
		$html .=			'<th class="text-center">Required Qty</th>';
		$html .=			'<th class="text-center">Shortage</th>';
		$html .=		'</thead>';
		$html .=		'<tbody>';
		foreach($bom as $item){
			$html .= 		'<tr>';
			$data = json_encode(array('item-number'=>trim($item['item'])));
			$url = '/dashboard/inventory/item-details';
			$html .= 			'<td class="overlayz-link" overlayz-url="'.$url.'" overlayz-data="'.htmlentities($data).'">'.htmlentities($item['item']).'</td>';
			$html .= 			'<td class="text-center">'.htmlentities($item['onhand']).'</td>';
			$html .= 			'<td class="text-center">'.htmlentities($item['used']).'</td>';
			$html .= 			'<td class="text-center">'.htmlentities($item['shortage']).'</td>';
			$html .= 		'</tr>';
		}
		$html .=		'</tbody>';
		$html .=	'</table>';
		$html .= '</div>';

		print json_encode(array(
			'success' => true,
			'html' => '<td colspan="6">'.$html.'</td>'
		));

		return;

	}

}

// Get the SO numbers.
$sos = get_sos();

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	.loc-row {
		padding-top: 20px;
	}
	.sono {
		cursor: pointer;
		border-bottom: 1px solid gray;
		padding-top: 10px;
		padding-bottom: 10px;
		padding-left: 10px;
		margin-left: 5px;
	}
	.item-container {
		margin-left: 20px;
	}
	.text-center {
		text-align: center !important;
	}

	.wo-bom-row {
		display: none;
	}
	.fa-plus {
		font-size: 10px;
		cursor: pointer;
	}
	.fa-minus {
		font-size: 10px;
		cursor: pointer;
	}
	.fa-exclamation {
		color: red;
		padding-bottom: 3px;
	}
	.bom-container {
		margin-left: 40px;
	}
	.item-level {
		padding-left: 5px;
		padding-right: 5px;
	}
</style>

<div id="main-container">
	<h2>Open Shipped WO Report</h2>
	<div id="so-container" class="container-fluid">

		<?php
		foreach($sos as $loc => $so){
			?>
			<div class="loc-row row-fluid">
				<!-- Location: DC/VA/ETC -->
				<div class="row-fluid">
					<b><?php print htmlentities($loc) ?></b>
				</div>

				<!-- Each SONO in the location -->
				<?php
				foreach($so as $sono){
					?>
					<div class="so-row row-fluid">
						<?php
							// The SO#
							$so = htmlentities($sono['sono']);

							// Check to see if an exlamation piont should be shown.
							$exc = '';
							//if($sono['shortage']>0){$exc='<i class="fa fa-exclamation"></i>';};
							if($sono['has_shortage']>0){$exc='<i class="fa fa-exclamation"></i>';};
						?>
						<div class="sono row-fluid" data-sono="<?php print $so ?>" data-opened="0" data-location="<?php print htmlentities($loc) ?>">
							SO: <?php print $so ?>
							<?php print $exc ?>
						</div>
						<div class="item-container row-fluid"></div>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>

	</div>

</div>
<script type="text/javascript">
$(document).ready(function(){

	function get_so_items(){

		// Get the items for the SO.

		// Get the SO.
		var $so = $(this)
		var so = $so.attr('data-sono')
		var lo = $so.attr('data-location')

		// The data to POST.
		var data = {
			'action' : 'get-sono-items',
			'sono' : so,
			'defloc' : lo
		}

		// Get the item-container.
		var $so_row = $so.parents('.so-row')
		var $item_container = $so_row.find('.item-container')

		// Check if the row has already been expanded.
		var opened = $so.attr('data-opened')

		// If it was "opened", "close" it.
		if(opened=='1'){
			$item_container.html('')
			$so.attr('data-opened', '0')
			return
		}

		// POST the data.
		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'JSON',
			'data' : data,
			'success' : function(rsp){

				// Get the HTML and add it to the response container.
				$item_container.html(rsp.html)

				// Mark the SO as open.
				$so.attr('data-opened', '1')

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function get_bom_items(){

		// Get a minus icons.
		var minus = '<i class="fa fa-minus"></i>'

		// Get the row and identifiers.
		var $icon = $(this)
		var $row = $icon.parents('tr')
		var defloc = $row.attr('data-location')
		var sono = $row.attr('data-sono')
		var item = $row.attr('data-item')

		// Get the row that will hold the BOM.
		var $bom_row = $row.next('tr')

		// The data to POST.
		var data = {
			'action' : 'get-bom-items',
			'defloc' : defloc,
			'item' : item
		}

		$.ajax({
			'url' : '',
			'method' : 'POST',
			'dataType' : 'JSON',
			'data' : data,
			'success' : function(rsp){

				// Populate and show the BOM container.
				$bom_row.html(rsp.html)
				$bom_row.show()

				// Show a minus icon.
				$icon.replaceWith(minus)

			},
			'error' : function(rsp){
				console.log('error')
				console.log(rsp)
			}
		})

	}

	function hide_bom(){

		// Get the icon and row.
		var $icon = $(this)
		var $row = $icon.parents('tr')

		// Hide the BOM row.
		var $bom_row = $row.next('tr')
		$bom_row.hide()

		// Replace the minus with a plus.
		$icon.replaceWith('<i class="fa fa-plus"></i>')

	}

	// Allow SO item expansion
	$(document).on('click', '.sono', get_so_items)

	// Allow item expansion.
	$(document).on('click', '.fa-plus', get_bom_items)

	// Allow collapsing an itemw
	$(document).on('click', '.fa-minus', hide_bom)

})
</script>