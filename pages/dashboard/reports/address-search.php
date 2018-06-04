<?php

function get_data($filter, $filter_type){

	// Query for the report data.

	$db = DB::get();

	// Construct the 'WHERE' clause.
	$filter = '%'.$filter.'%';
	if($filter_type=='zip-code'){
		$where = "zip LIKE ".$db->quote($filter);

	}elseif($filter_type=='company'){
		$where = "company LIKE ".$db->quote($filter);
	}

	$q = $db->query("

		SELECT DISTINCT
			a.custno AS client,
			a.cshipno AS shipto_code,
			a.company,
			a.address1,
			a.address2,
			a.city,
			a.addrstate AS state,
			a.zip,
			a.country,
			a.terr AS office,
			a.salesmn
		FROM ".DB_SCHEMA_ERP.".arcadr a
		WHERE a.cshipno NOT IN ('AP','INV')
			AND ".$where." COLLATE SQL_Latin1_General_CP1_CI_AS

		UNION ALL

		SELECT DISTINCT
			c.custno AS client,
			NULL AS shipto_code,
			c.company,
			c.address1,
			c.address2,
			c.city,
			c.addrstate AS state,
			c.zip,
			c.country,
			c.terr AS office,
			c.salesmn
		FROM ".DB_SCHEMA_ERP.".arcust c
		WHERE ".$where." COLLATE SQL_Latin1_General_CP1_CI_AS
	");

	return $q->fetchAll();

}

// Handle AJAX POSTs.
if(isset($_POST['action'])){

	if($_POST['action']=='address-search'){

		// Get the zip code and company
		$filter = $_POST['filter'];
		$filter_type = $_POST['filter-type'];

		// Search for addresses.
		$data = get_data($filter, $filter_type);

		// Build the table HTML.
		$html = '';
		$html .= '<table id="address-search-table" class="table table-striped table-hover table-small columns-filterable columns-sortable">';
		$html .= 	'<thead>';
		$html .= 		'<th class="filterable sortable">Client</th>';
		$html .= 		'<th class="filterable sortable">Ship to Code</th>';
		$html .= 		'<th class="filterable sortable">Company</th>';
		$html .= 		'<th class="filterable sortable">Address 1</th>';
		$html .= 		'<th class="filterable sortable">Address 2</th>';
		$html .= 		'<th class="filterable sortable">City</th>';
		$html .= 		'<th class="filterable sortable">State</th>';
		$html .= 		'<th class="filterable sortable">Zip</th>';
		$html .= 		'<th class="filterable sortable">Country</th>';
		$html .= 		'<th class="filterable sortable">Office</th>';
		$html .= 		'<th class="filterable sortable">Sales Person</th>';
		$html .= 	'</thead>';
		$html .=	'<tbody>';
		foreach($data as $row){
			$html .= '<tr>';

			// Get the client details for an overlay.
			$data = htmlentities(json_encode(array('custno'=>trim($row['client']))));
			$url = '/dashboard/clients/details';

			$html .=	'<td class="overlayz-link" overlayz-url="'.$url.'" overlayz-data="'.$data.'">'.$row['client'].'</td>';
			$html .= 	'<td>'.$row['shipto_code'].'</td>';
			$html .= 	'<td>'.$row['company'].'</td>';
			$html .= 	'<td>'.$row['address1'].'</td>';
			$html .= 	'<td>'.$row['address2'].'</td>';
			$html .= 	'<td>'.$row['city'].'</td>';
			$html .= 	'<td>'.$row['state'].'</td>';
			$html .= 	'<td>'.$row['zip'].'</td>';
			$html .= 	'<td>'.$row['country'].'</td>';
			$html .= 	'<td>'.$row['office'].'</td>';
			$html .= 	'<td>'.$row['salesmn'].'</td>';
			$html .= '</tr>';
		}
		$html .=	'</tbody>';
		$html .= '</table>';

		print json_encode(array(
			'success' => true,
			'html' => $html
		));

		return;

	}

}

ob_start(); // Start loading output into buffer.

?>

<style type="text/css">
	#address-search-button {
		margin-bottom: 10px;
	}
	#filter-type-container {
		padding-bottom: 5px;
	}
</style>

<div class="container-fluid">
	<h2>Address-Search</h2>
</div>

<div id="address-search-container" class="container-fluid">
	<form id="address-search-form">
		<fieldset>
			<legend>Filter</legend>
			<div id="filter-type-container" class="row">
				<div class="span2"><label class="radio"><input class="filter-check" id="zip-check" type="radio" name="filter-input" data-filter-type="zip-code" checked>Zip Code</label></div>
				<div class="span2"><label class="radio"><input class="filter-check" id="company-check" type="radio" name="filter-input" data-filter-type="company">Company</label></div>
			</div>
			<input id="filter-input" type="text" placeholder="Zip Code">
			<button id="address-search-button" type="button" class="btn btn-primary">Submit</button>
		</fieldset>
	</form>
</div>

<div id="address-table-container" class="row-fluid"></div>

<script type="text/javascript">
	$(document).ready(function(){
		//bindTableSorter($('#address-search-table'))

		function get_address(){

			// Get the addresses that match the searched zip code.

			// Get the filter value.
			var $input = $('#filter-input')
			var filter = $input.val()

			// Get the selected filter type.
			var $input = $('.filter-check:checked')
			var filter_type = $input.attr('data-filter-type')

			// Get the container for the table.
			var $container = $('#address-table-container')

			// The data to POST.
			var data = {
				'action' : 'address-search',
				'filter' : filter,
				'filter-type' : filter_type
			}

			// Create a loading animation.
			var $loading = $('<div/>',{
				'class' :  'ajax-loading-container'
			})
			var $animation = $('<img/>', {
				'src' : '/interface/images/ajax-loading.gif'
			})
			$loading.append($animation)

			// Get the addresses.
			$.ajax({
				'url' : '/dashboard/reports/address-search',
				'method' : 'POST',
				'dataType' : 'JSON',
				'data' : data,
				'success' : function(rsp){
					
					// Set the table HTML in the container.
					$container.html(rsp.html)

					var c = 0
					var i = setInterval(function(){
						bindTableSorter($('#address-search-table'))

						// Clearing the interval will not work without this
						// for some reason.
						c++
						if(c>=0){
							clearInterval(i)
						}
					},200)

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				},
				'beforeSend' : function(){

					// Empty the container.
					$container.empty()

					// Give the loading container to the report container.
					$container.append($loading)
				},
				'complete' : function(){

					// Remove the loading animation.
					$loading.remove()

				}
			})

		}

		function toggle_filter_placeholder(){

			// Change the placeholder text based on which filter has been
			// selected.

			// The placeholders by filter type.
			var placeholders = {
				'zip-code' : 'Zip Code',
				'company' : 'Company Name'
			}

			// Get the filter type.
			var $input = $(this)
			var filter_type = $input.attr('data-filter-type')

			// Update the placeholder.
			var placeholder = placeholders[filter_type]
			$search = $('#filter-input')
			$search.attr('placeholder', placeholder)

		}

		function search_on_enter(e){

			// Search on <Enter>.
			if(e.which==13){

				// Don't submit the form.
				e.preventDefault()
				get_address()
			}

		}

		// Enable zip-code search.
		$(document).off('click', '#address-search-button')
		$(document).on('click', '#address-search-button', get_address)

		// Let the placeholder text update.
		$(document).off('change', '.filter-check')
		$(document).on('change', '.filter-check', toggle_filter_placeholder)

		// Support searching on <Enter>.
		$(document).keypress(function(e){search_on_enter(e)})

	})
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode(array(
	'success' => True,
	'html' => $html
));
?>