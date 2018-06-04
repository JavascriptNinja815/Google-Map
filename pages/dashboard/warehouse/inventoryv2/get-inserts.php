<?php

$session->ensureLogin();

function get_inserts($in_stock=null){

	// Query for insert data.

	// The query to execute.
	$query = "
		WITH results AS (
			SELECT
				i.item,
				i.inrt_type,
				i.inrt_mtrl,
				i.inrt_fnsh,
				i.inrt_gage,
				i.inrt_tbod,
				i.inrt_tbid,
				i.inrt_od,
				i.inrt_hght,
				'missing' AS inrt_width,
				'missing' AS inrt_hole_number,
				'missing' AS inrt_hole_size,
				'missing' AS inrt_hole_shape,
				'missing' AS inrt_tabs,
				i.stm_type,
				i.stm_dia,
				i.thrd_cnt,
				SUM(q.qonhand) AS qty
			FROM ".DB_SCHEMA_ERP.".icspec i
			INNER JOIN ".DB_SCHEMA_ERP.".icitem c
				ON c.item = i.item
			LEFT JOIN ".DB_SCHEMA_ERP.".iciqty q
				ON q.item = i.item
			WHERE i.type = 'Insert'
			GROUP BY i.item, inrt_type,
				i.inrt_mtrl,
				i.inrt_fnsh,
				i.inrt_gage,
				i.inrt_tbod,
				i.inrt_tbid,
				i.inrt_od,
				i.inrt_hght,
				i.stm_type,
				i.stm_dia,
				i.thrd_cnt
		)
		SELECT *
		FROM results
	";

	if($in_stock==true){
		$query .= " WHERE qty > 0";
	}

	// Default sorting
	$query .= " ORDER BY item";

	// Run the query.
	$db = DB::get();
	$q = $db->query($query);

	return $q->fetchAll();

}

if(isset($_POST['action'])){
	if($_POST['action']=='get-inserts'){

		// Get the inserts.
		$in_stock=$_POST['in-stock'];
		$inserts = get_inserts($in_stock);

		// Create the HTML for the inserts table.
		$html = '<table id="inserts-table" class="table table-small table-striped table-hover columns-sortable columns-filterable">';
		$html .= '<thead>';
		$html .=	'<th class="sortable filterable">Item</th>';
		$html .=	'<th class="sortable filterable">Type</th>';
		$html .=	'<th class="sortable filterable">Material</th>';
		$html .=	'<th class="sortable filterable">Finish</th>';
		$html .=	'<th class="sortable filterable">Gauge</th>';
		$html .=	'<th class="sortable filterable">Tube OD</th>';
		$html .=	'<th class="sortable filterable">Tube OD</th>';
		$html .=	'<th class="sortable filterable">Outer Diameter</th>';
		$html .=	'<th class="sortable filterable">Height</th>';
		$html .=	'<th class="sortable filterable">Width</th>';
		$html .=	'<th class="sortable filterable">Number of Holes</th>';
		$html .=	'<th class="sortable filterable">Hole Size</th>';
		$html .=	'<th class="sortable filterable">Hole Shape</th>';
		$html .=	'<th class="sortable filterable">Tabs</th>';
		$html .=	'<th class="sortable filterable">Stem Type</th>';
		$html .=	'<th class="sortable filterable">Stem Diameter</th>';
		$html .=	'<th class="sortable filterable">Thread Count</th>';
		$html .= '</thead>';
		$html .= '<tbody>';
		foreach($inserts as $insert){
			//print_r($insert);
			$html .= '<tr>';
				$html .= '<td>'.htmlentities($insert['item']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_type']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_mtrl']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_fnsh']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_gage']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_tbid']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_tbod']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_od']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_hght']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_width']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_hole_number']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_hole_size']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_hole_shape']).'</td>';
				$html .= '<td>'.htmlentities($insert['inrt_tabs']).'</td>';
				$html .= '<td>'.htmlentities($insert['stm_type']).'</td>';
				$html .= '<td>'.htmlentities($insert['stm_dia']).'</td>';
				$html .= '<td>'.htmlentities($insert['thrd_cnt']).'</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		$html .='</table>';

		print json_encode(array(
			'success' => true,
			'html' => $html
		));

		return;

	}
}

print json_encode(array(
	'success' => false
));

?>