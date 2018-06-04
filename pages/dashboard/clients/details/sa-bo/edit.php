<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_client = $db->query("
	SELECT
		LTRIM(RTRIM(arcust.custno)) AS custno,
		arcust.company
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
");
$client = $grab_client->fetch();

$grab_logins = $db->query("
	SELECT
		logins.login_id,
		logins.initials,
		logins.first_name,
		logins.last_name
	FROM
		" . DB_SCHEMA_INTERNAL . ".logins
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".login_companies
		ON
		login_companies.login_id = logins.login_id
	WHERE
		login_companies.company_id = " . $db->quote(COMPANY) . "
	ORDER BY
		logins.initials,
		logins.last_name,
		logins.first_name
");
$logins = [];
foreach($grab_logins as $login) {
	$logins[] = [
		'login_id' => $login['login_id'],
		'initials' => $login['initials'],
		'first_name' => $login['first_name'],
		'last_name' => $login['last_name']
	];
}

ob_start(); // Start loading output into buffer.

?>

<style type="text/css">
	#add-sabo-container th {
		padding:14px;
	}
	#add-sabo-container td.name {
		width:120px;
		line-height:30px;
		vertical-align:middle;
	}
	#add-sabo-container td.value input[type="text"] {
		width:98%;
		margin:0;
		line-height:30px;
		vertical-align:middle;
	}
	#add-sabo-container td.value textarea {
		height:80px;
		width:98%;
	}
	#add-sabo-container .prototype {
		display:none;
	}
	#add-sabo-container .saboitems-table td,
	#add-sabo-container .saboitems-table th {
		margin:1px;
		padding:1px;
	}
	#add-sabo-container .saboitems .saboitem input[type="text"],
	#add-sabo-container .saboitems .saboitem input[type="number"] {
		width:auto;
		margin:1px;
		padding:1px;
		font-size:12px;
	}
	#add-sabo-container .saboitems .saboitem input[type="number"] {
		width:60px;
	}
	#add-sabo-container .saboitems .saboitem input[type="text"] {
		width:110px;
	}
	#add-sabo-container .action-add-saboitem {
		padding:5px;
		margin:12px;
	}
	#add-sabo-container [required]:placeholder-shown {
		background-color:#fdd;
	}
</style>

<form method="POST" id="add-sabo-container" action="<?php print BASE_URI;?>/dashboard/clients/details/sa-bo/create">
	<h2>Add SA/BO</h2>
	<input type="hidden" name="custno" value="<?php print htmlentities($client['custno'], ENT_QUOTES);?>" />
	<table class="sabo-table">
		<tbody>
			<tr>
				<td class="name">Type</td>
				<td class="value">
					<label>
						<input type="radio" name="type" value="SA" required placeholder="SA" /> Stocking Agreement
					</label>
					<br />
					<label>
						<input type="radio" name="type" value="BO" required placeholder="BO" /> Blanket Order
					</label>
				</td>
			</tr>
			<tr>
				<td class="name">Start Date</td>
				<td class="value"><input type="date" name="startdate" required placeholder="Start Date" /></td>
			</tr>
			<tr>
				<td class="name">Expires Date</td>
				<td class="value"><input type="date" name="enddate" required placeholder="Expires Date" /></td>
			</tr>
			<tr>
				<td class="name">Client PO</td>
				<td class="value"><input type="text" name="ponum" maxlength="25" placeholder="Client PO" /></td>
			</tr>
			<tr>
				<td class="name">Client Approver Name</td>
				<td class="value"><input type="text" name="custsig" maxlength="50" required placeholder="Client Approver Name" /></td>
			</tr>
			<tr>
				<td class="name">Client Approver Title</td>
				<td class="value"><input type="text" name="custsigtit" maxlength="50" required placeholder="Client Approver Title" /></td>
			</tr>
			<tr>
				<td class="name">CD Approver 1</td>
				<td class="value">
					<select name="cdab1" required placeholder="CD Approver 1">
						<option value="" selected></option>
						<?php
						foreach($logins as $login) {
							?><option value="<?php print htmlentities($login['login_id']);?>"><?php print htmlentities($login['initials'] . ' - ' . $login['first_name'] . ' ' . $login['last_name']);?></option><?php
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="name">CD Approver 2</td>
				<td class="value">
					<select name="cdab2" placeholder="CD Approver 2">
						<option value="" selected></option>
						<?php
						foreach($logins as $login) {
							?><option value="<?php print htmlentities($login['login_id']);?>"><?php print htmlentities($login['initials'] . ' - ' . $login['first_name'] . ' ' . $login['last_name']);?></option><?php
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="name">Notes</td>
				<td class="value"><input type="text" name="notes" placeholder="Notes" /></td>
			</tr>
		</tbody>
	</table>

	<table class="saboitems-table">
		<thead>
			<tr>
				<th></th>
				<th>Customer PO</th>
				<th>Item</th>
				<th>Vendor</th>
				<th>Vendor P/N</th>
				<th>?stkumid?</th>
				<th>Qty</th>
				<th>Min</th>
				<th>Max</th>
				<th>?dayss?</th>
				<th>Price</th>
				<th>Monthly</th>
				<th>Annual</th>
				<th>Location</th>
				<th>Notes</th>
			</tr>
		</thead>
		<tbody class="saboitems">
			<tr class="saboitem prototype">
				<td>
					<i class="fa fa-minus action action-remove action-remove-saboitem action-link"></i>
				</td>
				<td><input type="text" name="items[ponum][]" placeholder="Customer PO" /></td>
				<td><input type="text" name="items[item][]" maxlength="50" placeholder="Item" /></td>
				<td><input type="text" name="items[vendno][]" maxlength="50" placeholder="Vendor" /></td>
				<td><input type="text" name="items[vpartno][]" maxlength="50" placeholder="Vendor P/N" /></td>
				<td><input type="text" name="items[stkumid][]" maxlength="50" placeholder="stkumid" /></td>
				<td><input type="number" name="items[qty][]" placeholder="Qty" /></td>
				<td><input type="number" name="items[min][]" placeholder="Min" /></td>
				<td><input type="number" name="items[max][]" placeholder="Max" /></td>
				<td><input type="number" name="items[dayss][]" placeholder="dayss" /></td>
				<td><input type="number" name="items[price][]" placeholder="Price" /></td>
				<td><input type="number" name="items[monthly][]" placeholder="Monthly" /></td>
				<td><input type="number" name="items[annual][]" placeholder="Annual" /></td>
				<td><input type="text" name="items[loctid][]" maxlength="50" placeholder="Location" /></td>
				<td><input type="text" name="items[notes][]" placeholder="Notes" /></td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="2">
					<span class="action action-add action-add-saboitem action-link">
						<i class="fa fa-plus"></i>
						Add Item
					</span>
				</td>
			</tr>
		</tfoot>
	</table>

	<button type="submit">Save</button>
	<button type="button" class="cancel">Cancel</button>
</form>

<script type="text/javascript">
	// Bind to form submissions.
	$(document).off('submit', 'form#add-sabo-container');
	$(document).on('submit', 'form#add-sabo-container', function(event) {
		var $form = $(this);
		var data = new FormData(this);

		// Ensure at least one item.
		var $saboitems = $form.find('.saboitems-table .saboitems .saboitem:not(.prototype)');
		if(!$saboitems.length) {
			alert('At least one item must be specified');
			return false;
		} 

		var $submit_address_overlay = activateOverlayZ(
			$form.attr('action'),
			data,
			undefined,
			function(response) { // Success Callback
				if(response.success) {
					// Click the "SA & BO" tab to force re-loading of SAs/BOs to include added SA/BO.
					$('#client-details-container .tabs .tab[page="sa-bo"]').click();
					// Close the "add" overlay now that it's been successfully submitted.
					var $add_overlay = $form.closest('.overlayz');
					$add_overlay.fadeOut(function() {
						remove();
					});
				} else if(response.message) {
					alert('Error: ' + response.message);
				} else {
					alert('Something went wrong.');
				}
				$submit_address_overlay.fadeOut(function() {
					$submit_address_overlay.remove();
				});
			}
		);
		return false; // Prevent form submission propagation.
	});

	// Bind to clicks on Cancel button.
	$(document).off('click', 'form#add-sabo-container button.cancel');
	$(document).on('click', 'form#add-sabo-container button.cancel', function(event) {
		$(this).closest('.overlayz').fadeOut(function() {
			$(this).remove();
		});
	});

	// Bind to clicks on "Add Item" icon.
	$(document).off('click', 'form#add-sabo-container .action-add-saboitem');
	$(document).on('click', 'form#add-sabo-container .action-add-saboitem', function(event) {
		var $icon = $(this);
		var $saboitems_container = $icon.closest('.saboitems-table');
		var $saboitems = $saboitems_container.find('.saboitems');

		var $sabo_item = $saboitems_container.find('.saboitem.prototype').clone();
		$sabo_item.removeClass('prototype');
		$sabo_item.find('[name="items[item][]"]').prop('required', true);
		$sabo_item.find('[name="items[vendno][]"]').prop('required', true);
		$sabo_item.find('[name="items[vpartno][]"]').prop('required', true);
		$sabo_item.find('[name="items[qty][]"]').prop('required', true);
		$sabo_item.find('[name="items[min][]"]').prop('required', true);
		$sabo_item.find('[name="items[max][]"]').prop('required', true);
		$sabo_item.find('[name="items[price][]"]').prop('required', true);
		$sabo_item.find('[name="items[loctid][]"]').prop('required', true);
		$sabo_item.appendTo($saboitems);
	});

	// Bind to clicks on "Remove Item" icon.
	$(document).off('click', 'form#add-sabo-container .action-remove-saboitem');
	$(document).on('click', 'form#add-sabo-container .action-remove-saboitem', function(event) {
		if(confirm('Are you sure you want to remove this entry?')) {
			var $icon = $(this);
			var $saboitem = $icon.closest('.saboitem')
			$saboitem.remove();
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
