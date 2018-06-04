<?php

$session->ensureLogin();

function get_floor_locks($in_stock=null){

	// Query for floor locks data.

	// The query to execute.
	$query = "
		WITH results AS (
			SELECT
				i.item,
				i.fl_type,
				i.whl_dia AS wheel_diameter,
				i.fl_cstrhght AS caster_height,
				i.fl_exth AS extended_height,
				i.fl_rech AS retracted_height,
				i.fl_mtrl AS material,
				i.fl_finish AS finish,
				i.ss AS stainless_steel,
				i.plt_size AS top_plate_size,
				i.mntgcode AS mounting_code,
				i.fl_shoemtrl AS show_material,
				i.mfg AS brand,
				i.mfg_ser AS brand_series,
				SUM(q.qonhand) AS qty
			FROM ".DB_SCHEMA_ERP.".icspec i
			INNER JOIN ".DB_SCHEMA_ERP.".icitem c
				ON c.item = i.item
			LEFT JOIN ".DB_SCHEMA_ERP.".iciqty q
				ON q.item = i.item
			WHERE i.type = 'Floor Lock'
			GROUP BY i.item,
				i.fl_type,
				i.whl_dia,
				i.fl_cstrhght,
				i.fl_exth,
				i.fl_rech,
				i.fl_mtrl,
				i.fl_finish,
				i.ss,
				i.plt_size,
				i.mntgcode,
				i.fl_shoemtrl,
				i.mfg,
				i.mfg_ser
		) SELECT *
		FROM results
	";

	if($in_stock==true){
		$query .= " WHERE qty > 0";
	}

	// Run the query.
	$db = DB::get();
	$q = $db->query($query);

	return $q->fetchAll();

}

// Handle AJAX.

if(isset($_POST['action'])){

	// Get floor lock data.
	if($_POST['action']=='get-floor-locks'){

		$in_stock = $_POST['in-stock'];
		$locks = get_floor_locks($in_stock);

		// Create the HTML for the lokcs table.
		$html = '<table id="floor-locks-table" class="table table-small table-striped table-hover columns-sortable columns-filterable">';
			$html .= '<thead>';
			$html .=	'<th class="sortable filterable">Item</th>';
			$html .=	'<th class="sortable filterable">Type</th>';
			$html .=	'<th class="sortable filterable">Wheel Diameter</th>';
			$html .=	'<th class="sortable filterable">Caster OAH</th>';
			$html .=	'<th class="sortable filterable">Extended Height</th>';
			$html .=	'<th class="sortable filterable">Retracted Height</th>';
			$html .=	'<th class="sortable filterable">Material</th>';
			$html .=	'<th class="sortable filterable">Finish</th>';
			$html .=	'<th class="sortable filterable">Stainless Steel</th>';
			$html .=	'<th class="sortable filterable">Top Plate Size</th>';
			$html .=	'<th class="sortable filterable">Mounting Code</th>';
			$html .=	'<th class="sortable filterable">Show Material</th>';
			$html .=	'<th class="sortable filterable">Brand</th>';
			$html .=	'<th class="sortable filterable">Brand Series</th>';
			$html .= '</thead>';
			$html .= '<tbody>';
			foreach($locks as $lock){
				$html .= '<tr>';
				$html .= 	'<td>'.htmlentities($lock['item']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['fl_type']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['wheel_diameter']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['caster_height']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['extended_height']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['retracted_height']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['material']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['finish']).'</td>';

				if($lock['stainless_steel']==1){
					$ss = '<input type="checkbox" checked>';
				}else{
					$ss = '<input type="checkbox">';
				}

				$html .= 	'<td class="text-center">'.$ss.'</td>';
				$html .= 	'<td>'.htmlentities($lock['top_plate_size']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['mounting_code']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['show_material']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['brand']).'</td>';
				$html .= 	'<td>'.htmlentities($lock['brand_series']).'</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
		$html .= "</table>";

		print json_encode(array(
			'success' => true,
			'html' => $html
		));

		return;

	}

}

print json_encode(array(
	'success' => false,
	'wtf' => 'wtf'
));

?>