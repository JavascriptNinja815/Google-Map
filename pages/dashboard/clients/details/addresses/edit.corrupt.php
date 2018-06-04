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

$grab_territories = $db->query("
	SELECT
		settings.value AS territory
	FROM
		" . DB_SCHEMA_INTERNAL . ".settings
	WHERE
		settings.name = 'Territories'
	ORDER BY
		settings.value
");

$grab_countries = $db->query("
	SELECT
		countries.country_id,
		countries.country,
		countries.alpha3
	FROM
		" . DB_SCHEMA_INTERNAL . ".countries
	ORDER BY
		countries.country
");

$grab_states = $db->query("
	SELECT
		country_states.state_id,
		country_states.state,
		country_states.alpha2,
		countries.alpha3 AS country_alpha3
	FROM
		" . DB_SCHEMA_INTERNAL . ".country_states
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".countries
		ON
		countries.country_id = country_states.country_id
	ORDER BY
		country_states.state
");
$states = [];
foreach($grab_states as $state) {
	if(!array_key_exists($state['country_alpha3'], $states)) {
		$states[$state['country_alpha3']] = [];
	}
	$states[$state['country_alpha3']][] = [
		'state_id' => $state['state_id'],
		'state' => $state['state'],
		'alpha2' => $state['alpha2']
	];
}

if(!empty($_POST['cshipno'])) {
	$grab_address = $db->query("
		SELECT
			arcadr.cshipno AS ship_to_number,
			arcadr.company AS company,
			arcadr.contact,
			arcadr.title,
			arcadr.email,
			arcadr.phone,
			arcadr.faxno,
			arcadr.address1,
			arcadr.address2,
			arcadr.city,
			arcadr.addrstate AS state,
			arcadr.zip,
			arcadr.country,
			arcadr.resdntl AS residential,
			arcadr.fob,
			arcadr.shipvia,
			arcadr.shipchg AS frt_pay_method,
			arcadr.upsshpact AS carrier_account_number,

			arcadr.salesmn AS salesman,
			arcadr.terr AS territory,
			arcadr.tax AS address_tax,
			arcadr.entered,
			arcadr.defaship,
			arcadr.comment
		FROM
			" . DB_SCHEMA_ERP . ".arcadr
		WHERE
			RTRIM(LTRIM(arcadr.cshipno)) = " . $db->quote(trim($_POST['cshipno'])) . "
	");
	$address = $grab_address->fetch();
	
	if(!$address['country']) {
		$address['country'] = 'USA';
	}
} else {
	$address = [
		'cshipno' => '',
		'company' => '',
		'contact' => '',
		'title' => '',
		'email' => '',
		'phone' => '',
		'faxno' => '',
		'address1' => '',
		'address2' => '',
		'city' => '',
		'state' => '',
		'zip' => '',
		'country' => 'USA',
		'residential' => '0',
		'fob' => '',
		'shipvia' => '',
		'frt_pay_method' => '',
		'carrier_account_number' => '',
		'salesman' => '',
		'territory' => '',
		'address_tax' => '',
		'defaship' => 'N',
		'comment' => ''
	];

	// Determine whether the client has any other addresses already set to default.
	// If no other address is set to Default, automatically check the "Default" box.
	$grab_address_count = $db->query("
		SELECT
			arcadr.cshipno
		FROM
			" . DB_SCHEMA_ERP . ".arcadr
		WHERE
			arcadr.custno = " . $db->quote($_POST['custno']) . "
			AND
			defaship = 'Y'
	");
	if(!$grab_address_count->rowCount()) {
		$address['default'] = 'Y';
	}

	// Automatically generate a suggested "cshipno" for this address.
	$grab_all_cshipnos = $db->query("
		SELECT
			arcadr.cshipno
		FROM
			" . DB_SCHEMA_ERP . ".arcadr
		WHERE
			arcadr.custno = " . $db->quote($_POST['custno']) . "
	");
	// Load all values into a single array.
	$existing_cshipnos = [];
	foreach($grab_all_cshipnos as $cshipno) {
		if(!in_array($cshipno, $existing_cshipnos)) {
			$existing_cshipnos[] = trim($cshipno['cshipno']);
		}
	}
	// Find and assign a cshipno that isn't used yet.
	foreach(range('A', 'Z') as $end_1) {
		if(!in_array($client['custno'] . $end_1, $existing_cshipnos)) {
			$address['cshipno'] = $client['custno'] . $end_1;
			break; // Found one, we're done!
		}
		foreach(range('A', 'Z') as $end_2) {
			if(!in_array($client['custno'] . $end_1, $existing_cshipnos)) {
				$address['cshipno'] = $client['custno'] . $end_1;
				break; // Found one, we're done!
			}
		}
	}
	if(empty($address['cshipno'])) {
		 // A-Z is taken up, move onto AA, AB, ... ZZ.
		foreach(range('A', 'Z') as $end_1) {
			foreach(range('A', 'Z') as $end_2) { // A-Z is taken up, move onto AA, AB, ... - ZZ.
				if(!in_array($client['custno'] . $end_1 . $end_2, $existing_cshipnos)) {
					$address['cshipno'] = $client['custno'] . $end_1 . $end_2;
					break; // Found one, we're done!
				}
			}
		}
	}
}

ob_start(); // Start loading output into buffer.
?>

<style type="text/css">
	#edit-address-container th {
		padding:14px;
	}
	#edit-address-container td.name {
		width:120px;
		line-height:30px;
		vertical-align:middle;
	}
	#edit-address-container td.value input[type="text"] {
		width:98%;
		margin:0;
		line-height:30px;
		vertical-align:middle;
	}
	#edit-address-container td.value textarea {
		height:80px;
		width:98%;
	}
</style>

<form method="POST" id="edit-address-container" action="<?php print BASE_URI;?>/dashboard/clients/details/addresses/<?php print empty($_POST['cshipno']) == 'add' ? 'create' : 'save';?>">
	<h2><?php
	if(empty($_POST['cshipno'])) {
		print 'Add Address';
	} else {
		print 'Edit Existing Address ' . htmlentities($_POST['cshipno']);
	}
	?></h2>
	<input type="hidden" name="custno" value="<?php print htmlentities($client['custno'], ENT_QUOTES);?>" />
	<table class="address-table">
		<tbody>
			<tr>
				<td class="name">Ship To No</td>
				<td class="value"><?php
					if(!empty($_POST['cshipno'])) {
						// Editing address.
						print htmlentities(trim($address['ship_to_number']));
					} else {
						// Adding address.
						?><input type="text" name="cshipno" value="<?php print htmlentities(trim($address['cshipno']), ENT_QUOTES);?>" maxlength="6" /><?php
					}
				?></td>
			</tr>
			<tr>
				<th colspan="2">Contact Information</th>
			</tr>
			<tr>
				<td class="name">Company Name</td>
				<td class="value"><input type="text" name="company" value="<?php print htmlentities(trim($address['company']), ENT_QUOTES);?>" maxlength="35" /></td>
			</tr>
			<tr>
				<td class="name">Contact Title</td>
				<td class="value"><input type="text" name="title" value="<?php print htmlentities(trim($address['title']), ENT_QUOTES);?>" maxlength="20" /></td>
			</tr>
			<tr>
				<td class="name">Contact Name</td>
				<td class="value"><input type="text" name="contact" value="<?php print htmlentities(trim($address['contact']), ENT_QUOTES);?>" maxlength="20" /></td>
			</tr>
			<tr>
				<td class="name">Email</td>
				<td class="value"><input type="text" name="email" value="<?php print htmlentities(trim($address['email']), ENT_QUOTES);?>" /></td>
			</tr>
			<tr>
				<td class="name">Phone</td>
				<td class="value"><input type="phone" name="phone" value="<?php print htmlentities(trim($address['phone']), ENT_QUOTES);?>" maxlength="20" /></td>
			</tr>
			<tr>
				<td class="name">Fax</td>
				<td class="value"><input type="phone" name="fax" value="<?php print htmlentities(trim($address['faxno']), ENT_QUOTES);?>" maxlength="20" /></td>
			</tr>
			<tr>
				<th colspan="2">Shipping Information</th>
			</tr>
			<tr>
				<td class="name">Default Address</td>
				<td class="value"><input type="checkbox" name="defaship" value="Y" <?php print trim($address['defaship']) == 'Y' ? 'checked' : Null;?> /></td>
			</tr>
			<tr>
				<td class="name">Residential Address</td>
				<td class="value"><input type="checkbox" name="resdntl" value="1" <?php print trim($address['residential']) == 1 ? 'checked' : Null;?> /></td>
			</tr>
			<tr>
				<td class="name">FOB</td>
				<td class="value"><input type="text" name="fob" value="<?php print htmlentities(trim($address['fob']), ENT_QUOTES);?>" maxlength="12" /></td>
			</tr>
			<tr>
				<td class="name">Ship Via</td>
				<td class="value"><input type="text" name="shipvia" value="<?php print htmlentities(trim($address['shipvia']), ENT_QUOTES);?>" maxlength="12" /></td>
			</tr>
			<tr>
				<td class="name">Frt Pay Method</td>
				<td class="value">
					<select name="frt_pay_method">
						<option value="" <?php print !trim($address['frt_pay_method']) ? 'selected' : Null;?>></option>
						<option value="1" <?php print trim($address['frt_pay_method']) == 1 ? 'selected' : Null;?>>PrePaid & Add</option>
						<option value="2" <?php print trim($address['frt_pay_method']) == 2 ? 'selected' : Null;?>>PrePaid</option>
						<option value="3" <?php print trim($address['frt_pay_method']) == 3 ? 'selected' : Null;?>>Collect</option>
						<option value="5" <?php print trim($address['frt_pay_method']) == 5 ? 'selected' : Null;?>>3rd Party</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="name">Carrier Account No.</td>
				<td class="value"><input type="text" name="carrier_account_number" value="<?php print htmlentities(trim($address['carrier_account_number']), ENT_QUOTES);?>" /></td>
			</tr>
			<tr>
				<td class="name">Tax %</td>
				<td class="value"><input type="number" name="tax" step="0.001" min="0" max="100" value="<?php print number_format($address['tax'], 3);?>" /></td>
			</tr>
			<tr>
				<td class="name">Territory</td>
				<td class="value">
					<select name="terr">
						<option value=""></option>
						<?php
						foreach($grab_territories as $territory) {
							?><option value="<?php print htmlentities($territory['territory'], ENT_QUOTES);?>" <?php print $address['territory'] == $territory['territory'] ? 'selected' : Null;?>><?php print htmlentities($territory['territory']);?></option><?php
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="name">Country</td>
				<td class="value">
					<select name="country"><?php
						foreach($grab_countries as $country) {
							?><option value="<?php print $country['alpha3'];?>" <?php print $country['alpha3'] == $address['country'] ? 'selected' : Null;?>><?php print $country['country'];?></option><?php
						}
					?></select>
				</td>
			</tr>
			<tr>
				<td class="name">Address Line 1</td>
				<td class="value"><input type="text" name="address1" value="<?php print htmlentities(trim($address['address1']), ENT_QUOTES);?>" maxlength="30" /></td>
			</tr>
			<tr>
				<td class="name">Address Line 2</td>
				<td class="value"><input type="text" name="address2" value="<?php print htmlentities(trim($address['address2']), ENT_QUOTES);?>" maxlength="30" /></td>
			</tr>
			<tr>
				<td class="name">City</td>
				<td class="value"><input type="text" name="city" value="<?php print htmlentities(trim($address['city']), ENT_QUOTES);?>" maxlength="20" /></td>
			</tr>
			<tr>
				<td class="name">State</td>
				<td class="value states-container"><?php
					if(in_array(trim($address['country']), $states)) {
						?><select name="state"><?php
							foreach($states[$address['country']] as $state) {
								?><option value="<?php print htmlentities($state['alpha2'], ENT_QUOTES);?>"><?php print htmlentities($state['state']);?></option><?php
							}
						?></select><?php
					} else {
						?><input type="text" name="state" value="<?php print htmlentities(trim($address['state']), ENT_QUOTES);?>" /><?php
					}
				?></td>
			</tr>
			<tr>
				<td class="name">Zip/Postal Code</td>
				<td class="value"><input type="text" name="zip" value="<?php print htmlentities(trim($address['zip']), ENT_QUOTES);?>" maxlength="10" /></td>
			</tr>
			<tr>
				<td class="name">Comments</td>
				<td class="value"><input type="text" name="comment" value="<?php print htmlentities(trim($address['comment']), ENT_QUOTES);?>" maxlength="65" /></td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="2">
					<button type="submit">Save</button>
					<button type="button" class="cancel">Cancel</button>
				</th>
			</tr>
		</tfoot>
	</table>
</form>

<pre>
<?php print_r($address);?>
</pre>

<script type="text/javascript">
	var states = <?php print json_encode($states);?>;

	// Bind to form submissions.
	$(document).off('submit', 'form#edit-address-container');
	$(document).on('submit', 'form#edit-address-container', function(event) {
		var $form = $(this);
		var data = new FormData(this);
		var $submit_address_overlay = activateOverlayZ(
			$form.attr('action'),
			data,
			undefined,
			function(response) { // Success Callback
				$submit_address_overlay.fadeOut(function() {
					$submit_address_overlay.remove();
				});
			}
		);
		return false; // Prevent form submission propagation.
	});

	// Bind to clicks on Cancel button.
	$(document).off('click', 'form#edit-address-container button.cancel');
	$(document).on('click', 'form#edit-address-container button.cancel', function(event) {
		$(this).closest('.overlayz').fadeOut(function(event) {
			$(this).remove();
		});
	});

	// Bind to changes on Countries drop-down.
	$(document).off('change', 'form#edit-address-container select[name="country"]');
	$(document).on('change', 'form#edit-address-container select[name="country"]', function(event) {
		var $country = $(this);
		var $address_table = $country.closest('.address-table');
		var $states_container = $address_table.find('.states-container');
		var $state = $states_container.find('[name="state"]'); // May be an input or select
		var state = $state.val();
		var country = $country.val();
		if(states[country] !== undefined) {
			// Use text input.
			var $input = $('<input type="text" name="state" />');
		} else {
			// Use select input.
			var $input = $('<select name="state">');
			$input.append(
				$('<option value="">').text('')
			);
			$.each(states[country], function(index, state) {
				$input.append(
					$('<option>').val(state['alpha2']).text(state['state'])
				);
			});
		}
		$input.val(state);
		$states_container.empty().append($input);
	});
</script>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
