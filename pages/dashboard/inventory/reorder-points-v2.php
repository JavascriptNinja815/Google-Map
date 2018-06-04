<?php

$session->ensureLogin();

$args = array(
	'title' => 'ReOrder Point V2',
	'breadcrumbs' => array(
		'ReOrder Points V2' => BASE_URI . '/dashboard/inventory/reorder-points-v2'
	),
	'body-class' => 'padded'
);

function get_locations(){

	// Query for locations.

	$db = DB::get();
	$q = $db->query("
	SELECT
		LTRIM(RTRIM(icloct.loctid)) AS loctid
	FROM " . DB_SCHEMA_ERP . ".icloct
	WHERE icloct.loctid IN ('DC', 'DET', 'EGV', 'VA')
	ORDER BY icloct.loctid
	");

	return $q->fetchAll();
}

function get_reorder_points($loctids=null){

	// Query for reorder points by location id.

	// If no location IDs were passed, return an empty array.
	if(empty($loctids)){
		return array();
	}

	$db = DB::get();
	$query = "
		SELECT
			l.loctid,
			t.item,
			l.orderpt AS cur_reorder_point,
			'TODO' AS new_reorder_point,
			s.ordmin AS min_order_qty,
			s.ordincr AS box_qty,
			s.lead AS lead_time,
			'TODO' AS cost_variance,
			'TODO' AS days_of_inventory
		FROM ".DB_SCHEMA_ERP.".icitem t
		INNER JOIN ".DB_SCHEMA_ERP.".iciloc l
			ON l.item = t.item
		INNER JOIN ".DB_SCHEMA_ERP.".icsupl s
			ON s.item = t.item
		WHERE orderpt != 0
	";

	if(!is_null($loctids) and !empty($loctids)){

		// Create a new array for POSTed location IDs.
		$sanitized = array();

		foreach($loctids as $l){
			$s = $db->quote($l);
			array_push($sanitized, $s);
		}

		// Turn the array into something useful for a query.
		$qloctids = implode(',', $sanitized);

		// Constrain the query.
		$query .= " AND l.loctid IN (".$qloctids.")";

	}else{
		// If no locations were passed, don't return any rows.
		$query.= " AND l.loctid = 0";
	}

	// Execute the query.
	$q = $db->query($query);

	return $q->fetchAll();

}

// Handle AJAX requests.
if(isset($_POST['action'])){

	// Get the reorder points for the location.
	if($_POST['action'] == 'get-points'){

		// Get the POST data.
		if(isset($_POST['loctids'])){
			$loctids = $_POST['loctids'];
		}else{
			$loctids = array();
		}

		// Get reorder points.
		$points = get_reorder_points($loctids);

		// Create a table for the reorder points.
		$html = '<table id="reorder-points-table" class="table table-small table-striped table-hover columns-sortable columns-filterable">';
		$html.=		'<thead>';
		$html.=			'<th class="filterable sortable">Location</th>';
		$html.=			'<th class="filterable sortable">Item</th>';
		$html.=			'<th class="filterable sortable">Current ReOrder Point</th>';
		$html.=			'<th class="filterable sortable">New ReOrder Point</th>';
		$html.=			'<th class="filterable sortable">Minimum Order Qty</th>';
		$html.=			'<th class="filterable sortable">Box Qty</th>';
		$html.=			'<th class="filterable sortable">Lead Time</th>';
		$html.=			'<th class="filterable sortable">Cost of Variance</th>';
		$html.=			'<th class="filterable sortable">Days of Inventory</th>';
		$html.=			'<th><th>';
		$html.=		'</thead>';
		$html.=		'<tbody>';
		foreach($points as $point){
			$html .=	'<tr>';
			$html .=		'<td>'.htmlentities($point['loctid']).'</td>';
			$html .=		'<td>'.htmlentities($point['item']).'</td>';
			$html .=		'<td>'.number_format($point['cur_reorder_point']).'</td>';
			$html .=		'<td>'.htmlentities($point['new_reorder_point']).'</td>';
			$html .=		'<td>'.number_format($point['min_order_qty']).'</td>';
			$html .=		'<td>'.number_format($point['box_qty']).'</td>';
			$html .=		'<td>'.htmlentities($point['lead_time']).'</td>';
			$html .=		'<td>'.htmlentities($point['cost_variance']).'</td>';
			$html .=		'<td>'.htmlentities($point['days_of_inventory']).'</td>';
			$html .=		'<td><button class="update-row btn btn-primary btn-small">Update</button></td>';
			$html .=	'</tr>';
		}
		$html.=		'</tbody>';
		$html.= '</table>';

		print json_encode(array(
			'success' => true,
			'html' => $html
		));

		return;

	}

}

// Get the available locations.
$locations = get_locations();

Template::Render('header', $args, 'account');
?>

<h2>ReOrder Points V2</h2>

<fieldset>
	<div class="row-fluid">
	<legend>Location</legend>

		<?php
			foreach($locations as $location){
				?>
				<div class="span1">
					<label class="checkbox">
						<input class="location-input" type="checkbox" value="<?php print htmlentities($location['loctid']) ?>"> <?php print htmlentities($location['loctid']) ?>
					</label>
				</div>
				<?php
			}
		?>
	</div>
</fieldset>

<div class="row-fluid">
	<table id="reorder-points-table"></table>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		function get_points(){

			// Get all checked inputs.
			var $inputs = $('.location-input:checked')
			
			// Get the locations.
			locations = []
			$.each($inputs, function(i, e){
				var location = $(e).val()
				locations.push(location)
			})

			// The data to POST.
			var data = {
				'action' : 'get-points',
				'loctids' : locations
			}

			// Get the location's reorder points.
			$.ajax({
				'url' : '',
				'method' : 'POST',
				'dataType' : 'JSON',
				'data' : data,
				'success' : function(rsp){
					
					// Add the HTML to the page.
					$('#reorder-points-table').replaceWith(rsp.html)

					// Make the table sortable/filterable.
					setTimeout(function(){
						bindTableSorter($('#reorder-points-table'))
					}, 500)

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		function update_row(){

			// Update a row.

			console.log("TODO")

		}

		// Support location-based refinement.
		$(document).on('change', '.location-input', get_points)

		// Support updating a row.
		$(document).on('click', '.update-row', update_row)

	})
</script>
