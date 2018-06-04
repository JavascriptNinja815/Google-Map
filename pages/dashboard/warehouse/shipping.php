<?php

$session->ensureLogin();

$args = array(
	'title' => 'Shipping',
	'breadcrumbs' => array(
		'Warehouse' => BASE_URI . '/dashboard/warehouse/shipping',
		'Shipping' => BASE_URI . '/dashboard/warehouse/shipping',
	)
);

$grab_warehouses = $db->query("
	SELECT
		warehouses.warehouse_id,
		warehouses.name
	FROM
		" . DB_SCHEMA_INTERNAL . ".warehouses
	WHERE
		warehouses.company_id = " . $db->quote(COMPANY) . "
	ORDER BY
		warehouses.name
");

function get_admin_printers(){

	// Get all printers for admin roles.
	$db = DB::get();
	return $db->query("
		SELECT
			printers.printer_id,
			printers.printer,
			printers.real_printer_name
		FROM " . DB_SCHEMA_INTERNAL . ".printers
		WHERE printers.company_id = " . $db->quote(COMPANY) . "
		ORDER BY printers.printer
	");

};

function get_user_printers(){

	global $session;
	$db = DB::get();

	// Get the user's locations.
	$user = $session->login;
	$company_id = $user['company_id'];
	$locations = $user['location_ids'];

	// Hack together a string for SQL IN operation.
	$arr = [];
	foreach($locations as $location){
		array_push($arr, $db->quote($location));
	};
	$locations = implode(',',$arr);

	// Constrain by user's location.
	return $db->query("
		SELECT
			printers.printer_id,
			printers.printer,
			printers.real_printer_name
		FROM " . DB_SCHEMA_INTERNAL . ".printers
		WHERE printers.company_id = " . $db->quote(COMPANY) . "
			AND printers.location_id IN (" . ($locations) .")
		ORDER BY printers.printer
	");

};

function get_printers(){

	global $session;
	$is_admin = $session->hasRole("Administration");
	if($is_admin){
		return get_admin_printers();
	}else {
		return get_user_printers();
	};

};
$grab_printers = get_printers();
// $grab_printers = $db->query("
// 	SELECT
// 		printers.printer_id,
// 		printers.printer,
// 		printers.real_printer_name
// 	FROM
// 		" . DB_SCHEMA_INTERNAL . ".printers
// 	WHERE
// 		printers.company_id = " . $db->quote(COMPANY) . "
// 	ORDER BY
// 		printers.printer
// ");

Template::Render('header', $args, 'account');

?>
<style type="text/css">
	#shipping-form .address-group .address,
	#shipping-form .contact-group .contact {
		display:inline-block;
	}
	#shipping-form .address-group .edit-address,
	#shipping-form .contact-group .edit-contact {
		vertical-align:top;
		font-size:22px;
	}
	#shipping-form .edit-address,
	#shipping-form .edit-contact,
	#shipping-form .good-address {
		font-size:22px;
		vertical-align:top;
	}
	#shipping-form .good-address {
		color:#0b0;
	}

	#shipping-form #packages-container th,
	#shipping-form #packages-container td {
		padding-right:14px;
	}
	#shipping-form #packages-container .actions {
		width:38px;
	}
	/* Hide "remove" icon on first package's' row. */
	#shipping-form #packages-container .packages tr.package:first-of-type .actions .action-remove {
		display:none;
	}
	#shipping-form .packages-group input {
		width:56px;
	}
	#shipping-form .insurance-group input[name="packages[insurance][]"] {
		width:70px;
	}
	#shipping-form .packages-group td {
		vertical-align:top;
	}
	#shipping-form .packages-group select[name="packages[type][]"] {
		width:110px;
	}
	#shipping-form .services-group .service {
		margin:20px;
		text-align:center;
		width:200px;
		height:200px;
		border:1px solid #000;
		border-radius:8px;
		display:inline-block;
		cursor:pointer;
		vertical-align:top;
		position:relative;
	}
	#shipping-form .package .dimensions-override {
		display:none;
	}
	#shipping-form .service:hover {
		background-color:#eee;
	}
	#shipping-form .service.selected {
		background-color:#ffec00;
	}
	#shipping-form .service .price {
		padding-top:12px;
		padding-bottom:12px;
		font-size:26px;
		font-weight:bold;
		color:#f00;
		border-bottom:1px solid #999;
	}
	#shipping-form .service .carrier {
		padding-top:12px;
		font-size:24px;
		padding-bottom:12px;
	}
	#shipping-form .service .method {
		font-size:20px;
		font-weight:bold;
	}
	#shipping-form .service .eta {
		font-style:italic;
		position:absolute;
		bottom:0px;
		left:0;
		right:0;
		padding:4px;
		text-align:center;
		border-top:1px solid #999;
	}
	#shipping-form .start-over {
		color:#00f;
		margin-left:24px;
		display:none;
		cursor:pointer;
	}
	#shipping-form .start-over:hover {
		text-decoration:underline;
	}
	#shipping-form .shipvia-value,
	#shipping-form .freight-payment-method,
	#shipping-form .ups-account-number {
		vertical-align:middle;
		line-height:30px;
	}
	#shipping-form .shipvia-value {
		font-weight:bold;
	}
	#shipping-form textarea[name="notes"] {
		width:400px;
		height:80px;
	}

	#third-party-banner {
		border: 5px solid red;
		height: 75px;
		align-items: center;
		align-content: center;
		display: flex;
		margin-bottom: 15px;
	}
	#third-party-banner-text-container {
		margin: 0 auto;
		font-size: 30px;
	}


</style>

<div class="padded">
	<h2>Print Shipping Label(s)</h2>

	<form method="post" class="form-horizontal" id="shipping-form">
		<input type="hidden" name="action" value="sono" />

		<div class="sono-group control-group">
			<label class="control-label" for="sono">Sales Order Number</label>
			<div class="controls">
				<input type="text" name="sono" id="sono" required />
				<span class="start-over">Start Over</span>
			</div>
		</div>

		<div class="location-group control-group hidden">
			<label class="control-label" for="location">Shipping From</label>
			<div class="controls">
				<select name="warehouse_id">
					<?php
					foreach($grab_warehouses as $warehouse) {
						?><option value="<?php print htmlentities($warehouse['warehouse_id'], ENT_QUOTES);?>"><?php print htmlentities($warehouse['name']);?></option><?php
					}
					?>
				</select>
			</div>
		</div>

		<div class="address-group control-group hidden">
			<label class="control-label" for="address">Ship-To Address</label>
			<div class="controls">
				<div class="address-inputs hidden">
					<input type="hidden" name="address[company]" value="" placeholder="Company" />
					<br />
					<input type="hidden" name="address[address1]" value="" placeholder="Address Line 1" />
					<br />
					<input type="hidden" name="address[address2]" value="" placeholder="Address Line 2" />
					<br />
					<input type="hidden" name="address[address3]" value="" placeholder="Address Line 3"/>
					<br />
					<input type="hidden" name="address[city]" value="" placeholder="City" class="input-small" />, 
					<input type="hidden" name="address[state]" value="" placeholder="State" class="input-mini" />
					<input type="hidden" name="address[zip]" value="" placeholder="Zip" class="input-mini" />
					<br />
					<input type="hidden" name="address[country]" value="" placeholder="Country" />
				</div>
				<i class="fa fa-pencil action action-edit edit-address"></i>
				<div class="address"></div>
			</div>
		</div>

		<div class="contact-group control-group hidden">
			<label class="control-label" for="contact">Ship-To Contact</label>
			<div class="controls">
				<div class="contact-inputs hidden">
					<input type="hidden" name="contact[name]" value="" placeholder="Name" />
					<br />
					<input type="hidden" name="contact[phone]" value="" placeholder="Phone" />
					<br />
					<input type="hidden" name="contact[email]" value="" placeholder="E-Mail" />
				</div>
				<i class="fa fa-pencil action action-edit edit-contact"></i>
				<div class="contact"></div>
			</div>
		</div>

		<div class="notes-group control-group hidden">
			<label class="control-label" for="notes">
				Shipment Notes
			</label>
			<div class="controls">
				<textarea name="notes" id="notes"></textarea>
			</div>
		</div>

		<div class="notes-group control-group hidden">
			<label class="control-label" for="printer_id">
				Label Printer
			</label>
			<div class="controls">
				<select name="printer_id" id="printer_id" class="span5">
					<?php
					$default_printer = '';
					foreach($grab_printers as $printer) {
						?><option value="<?php print htmlentities($printer['printer_id'], ENT_QUOTES);?>" <?php print $session->login['label_printer_id'] == $printer['printer_id'] ? 'selected' : Null;?>><?php print htmlentities($printer['printer']);?></option><?php
					}
					?>
				</select>
			</div>
		</div>

		<div class="packages-group control-group hidden">
			<label class="control-label" for="address">
				Packages
			</label>
			<div class="controls">
				<table id="packages-container">
					<thead>
						<tr>
							<th class="actions"></th>
							<th class="type">Type</th>
							<th class="class">Class</th>
							<th class="quantity">Quantity</th>
							<th class="weight">Weight</th>
							<th class="dimensions">Dimensions (L" x W" x H")</th>
							<th class="dimensions">Insurance</th>
						</tr>
					</thead>
					<tbody class="packages">
						<tr class="package">
							<td class="actions"><i class="action action-remove fa fa-minus"></i></td>
							<td class="type">
								<select name="packages[type][]">
									<option value="18">Envelope</option>
									<option value="9" selected>Box</option>
									<option value="1">Pallet</option>
								</select>
							</td>
							<td class="class">
								<div class="class-container hidden">
									<input type="text" name="packages[class][]" value="65" />
								</div>
							</td>
							<td class="quantity"><input type="number" name="packages[quantity][]" step="1" value="1" /></td>
							<td class="weight"><input type="number" name="packages[weight][]" step="0.1" placeholder="Lbs." /></td>
							<td class="dimensions">
								<select name="packages[package_id][]">
									<?php
									$grab_packages = $db->query("
										SELECT
											shipping_packages.package_id,
											shipping_packages.name
										FROM
											" . DB_SCHEMA_INTERNAL . ".shipping_packages
										WHERE
											shipping_packages.type = 'box'
											AND
											shipping_packages.company_id = " . $db->quote(COMPANY) . "
										ORDER BY
											shipping_packages.position,
											shipping_packages.name
									");
									foreach($grab_packages as $package) {
										?><option value="<?php print htmlentities($package['package_id'], ENT_QUOTES);?>"><?php print htmlentities($package['name']);?></option><?php
									}
									?>
									<option value="other">Other</option>
								</select>
								<div class="dimensions-override">
									<span class="length"><input type="number" name="packages[length][]" step="1" placeholder="L&quot;" value="40" /></span>
									x
									<span class="width"><input type="number" name="packages[width][]" step="1" placeholder="W&quot;" value="32" /></span>
									x
									<span class="height"><input type="number" name="packages[height][]" step="1" placeholder="H&quot;" value="36" /></span>
								</div>
							</td>
							<td class="insurance">$<input type="number" name="packages[insurance][]" step="1" placeholder="None" /></td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4">
								<i class="action action-add fa fa-plus" title="Add Package"></i>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>

		<div class="shipvia-group control-group hidden">
			<label class="control-label">Ship Via</label>
			<div class="controls">
				<div class="shipvia-value"></div>
			</div>
		</div>

		<div class="orderstatus-group control-group hidden">
			<label class="control-label">Order Status</label>
			<div class="controls">
				<div class="orderstatus-value">
					<label><input type="radio" name="orderstatus" value="BACKORDER" /> Partially Shipped</label>
					<br />
					<label><input type="radio" name="orderstatus" value="SHIPPED" /> Fully Shipped</label>
				</div>
			</div>
		</div>

		<div class="freight-group control-group hidden">
			<label class="control-label">Freight Charges</label>
			<div class="controls">
				<div class="freight-payment-method"></div>
			</div>
		</div>

		<div class="ups-group control-group hidden">
			<label class="control-label">Account #</label>
			<div class="controls">
				<div class="ups-account-number"></div>
			</div>
		</div>

		<button class="btn btn-primary" type="submit">Retrieve »</button>

		<div class="notifications">
			<div class="notification-container hidden">
				<div class="notification"></div>
			</div>
		</div>

		<div class="services-group control-group hidden">
			<label class="control-label" for="contact"></label>
			<div class="controls">
				<h3>Double-click to purchase and print shipping label(s)</h3>
				<div class="services"></div>
			</div>
		</div>

		<div class="summary-group control-group hidden">
			<label class="control-label" for="contact"></label>
			<div class="controls">
				<div class="summary"><b>Your shipping label(s) should be printing shortly.</b></div>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">

$(function() {
	var $sono = $('#sono');
	$sono.focus();
});

// Grab the notifications container and notification prototype, detaching the prototype from the DOM.
var $notifications = $('#shipping-form .notifications');
var $notification_prototype = $notifications.find('.notification-container').hide().detach();

var $form, $sono;

/**
 * Bind to clicks on "Edit Address" icon.
 */
$(document).off('click', '#shipping-form .edit-address, #shipping-form .edit-contact');
$(document).on('click', '#shipping-form .edit-address, #shipping-form .edit-contact', function(event) {
	var $address_container = $form.find('.address-group');
	var $address = $address_container.find('.address');

	var $contact_container = $form.find('.contact-group');
	var $contact = $contact_container.find('.contact');
	
	var $address_overlay = $.overlayz({
		'html': $ajax_loading_prototype.clone(),
		'css': {
			'body': {
				'max-width': '500px',
				'max-height': '500px'
			}
		}
	}).fadeIn();
	var $address_overlay_body = $address_overlay.find('.overlayz-body');
	$.ajax({
		'url': BASE_URI + '/dashboard/warehouse/shipping/details/edit',
		'data': {},
		'method': 'POST',
		'dataType': 'json',
		'success': function(response) {
			if(!response.success) {
				var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
				if(response.message) {
					$notification.find('.notification').text(response.message);
				} else {
					$notification.find('.notification').text('Error: Something didn\'t go right');
				}
				$notification.slideDown('fast');
				$address_overlay.fadeOut('fast', function() {
					$address_overlay.remove();
				});
				return false;
			}

			// Set the overlay's body to the returned HTML.
			$address_overlay_body.html(response.html);

			var $current_address_company = $address_container.find(':input[name="address[company]"]');
			var $current_address_address1 = $address_container.find(':input[name="address[address1]"]');
			var $current_address_address2 = $address_container.find(':input[name="address[address2]"]');
			var $current_address_address3 = $address_container.find(':input[name="address[address3]"]');
			var $current_address_city = $address_container.find(':input[name="address[city]"]');
			var $current_address_state = $address_container.find(':input[name="address[state]"]');
			var $current_address_zip = $address_container.find(':input[name="address[zip]"]');
			var $current_address_country = $address_container.find(':input[name="address[country]"]');

			var $current_contact_name = $contact_container.find(':input[name="contact[name]"]');
			var $current_contact_phone = $contact_container.find(':input[name="contact[phone]"]');
			var $current_contact_email = $contact_container.find(':input[name="contact[email]"]');

			$shipping_address_form = $address_overlay_body.find('#shipping-address-form');
			var $new_address_company = $shipping_address_form.find(':input[name="address[company]"]').val($current_address_company.val());
			var $new_address_address1 = $shipping_address_form.find(':input[name="address[address1]"]').val($current_address_address1.val());
			var $new_address_address2 = $shipping_address_form.find(':input[name="address[address2]"]').val($current_address_address2.val());
			var $new_address_address3 = $shipping_address_form.find(':input[name="address[address3]"]').val($current_address_address3.val());
			var $new_address_city = $shipping_address_form.find(':input[name="address[city]"]').val($current_address_city.val());
			var $new_address_state = $shipping_address_form.find(':input[name="address[state]"]').val($current_address_state.val());
			var $new_address_zip = $shipping_address_form.find(':input[name="address[zip]"]').val($current_address_zip.val());
			var $new_address_country = $shipping_address_form.find(':input[name="address[country]"]').val($current_address_country.val());

			var $new_contact_name = $shipping_address_form.find(':input[name="contact[name]"]').val($current_contact_name.val());
			var $new_contact_phone = $shipping_address_form.find(':input[name="contact[phone]"]').val($current_contact_phone.val());
			var $new_contact_email = $shipping_address_form.find(':input[name="contact[email]"]').val($current_contact_email.val());

			// Set the overlay account value.
			var current_account_number = $('.ups-account-number').text().trim()
			var $new_account_number = $shipping_address_form.find(':input[name="acct-no"]').val(current_account_number)

			// Remove the account number option if there is no account number.
			if(!current_account_number){
				$shipping_address_form.find('#acc-no-container').remove()
			}

			/**
			 * Bind to clicks on Apply button.
			 */
			$shipping_address_form.on('submit', function(event) {
				var address = {
					'company': $new_address_company.val(),
					'address1': $new_address_address1.val(),
					'address2': $new_address_address2.val(),
					'address3': $new_address_address3.val(),
					'city': $new_address_city.val(),
					'state': $new_address_state.val(),
					'zip': $new_address_zip.val(),
					'country': $new_address_country.val()
				};

				var contact = {
					'name': $new_contact_name.val(),
					'phone': $new_contact_phone.val(),
					'email': $new_contact_email.val()
				};

				setAddress(address);
				setContact(contact);
				setUPSAccountNo($new_account_number.val())

				$address_overlay.fadeOut('fast', function() {
					$(this).remove();
				});

				return false; // Prevent form submission.
			});

			/**
			 * Bind to clicks on Cancel button.
			 */
			$shipping_address_form.find('button.cancel').on('click', function(event) {
				$address_overlay.fadeOut('fast', function() {
					$(this).remove();
				});
			});
		}
	});
});

/**
 * Bind to clicks on "Add Package" icon.
 */
$(document).off('click', '#shipping-form #packages-container .action-add');
$(document).on('click', '#shipping-form #packages-container .action-add', function(event) {
	var $icon = $(this);
	var $packages_container = $icon.closest('#packages-container');
	var $packages = $packages_container.find('.packages');
	var $package = $('#shipping-form #packages-container .packages .package:first-of-type').clone();
	$package.find(':input').val('');
	$package.find('.class-container').hide();
	$package.find('[name="packages[class][]"]').val('65'); // Reset dropdown to default value.
	$package.find(':input[name="packages[quantity][]"]').val('1');
	$package.find(':input[name="packages[length][]"]').val('12');
	$package.find(':input[name="packages[width][]"]').val('12');
	$package.find(':input[name="packages[height][]"]').val('12');
	$package.find(':input[name="packages[type][]"]').val('9'); // Box
	$package.appendTo($packages);
});

/**
 * Bind to clicks on "Remove Package" icon.
 */
$(document).off('click', '#shipping-form #packages-container .package .action-remove');
$(document).on('click', '#shipping-form #packages-container .package .action-remove', function(event) {
	if(confirm('Are you sure you want to remove this row?')) {
		var $icon = $(this);
		var $package = $icon.closest('.package');
		$package.remove();
	}
});

/**
 * Bind to changes on Package Type drop-down.
 */
$(document).off('change', '#shipping-form :input[name="packages[type][]"]');
$(document).on('change', '#shipping-form :input[name="packages[type][]"]', function(event) {
	var $input = $(this);
	var $package = $input.closest('.package');
	var $class_container = $package.find('.class-container');
	if($input.val() === '1') { // Pallet
		$class_container.slideDown('fast');
	} else { // Anything else.
		$class_container.slideUp('fast');
	}
});

/**
 * Bind to clicks on services.
 */
$(document).off('dblclick', '#shipping-form .services-group .service');
$(document).on('dblclick', '#shipping-form .services-group .service', function(event) {
	var $service = $(this);
	
	var carrier = $service.attr('carrier');
	var method = $service.attr('method');
	var transitdays = $service.attr('transitdays');
	var cost = $service.attr('cost');

	var $carrier = $form.find(':input[name="carrier"]');
	var $method = $form.find(':input[name="method"]');
	var $transitdays = $form.find(':input[name="transitdays"]');
	var $cost = $form.find(':input[name="cost"]');
	
	$carrier.val(carrier);
	$method.val(method);
	$transitdays.val(transitdays);
	$cost.val(cost);

	// Set the form's action.
	var $action = $form.find(':input[name="action"]');
	$action.val('print');

	// Submit the form.
	$form.submit();
});

/**
 * Bind to clicks on "Start Over" text.
 */
$(document).off('click', '#shipping-form .start-over');
$(document).on('click', '#shipping-form .start-over', function(event) {
	window.location.reload(true);
});

/**
 * Bind to form submissions.
 */
$(document).off('submit', '#shipping-form');
$(document).on('submit', '#shipping-form', function(event) {
	var form = this;
	$form = $(form);
	var $submit = $form.find('button[type="submit"]');
	var $action = $form.find(':input[name="action"]');
	var action = $action.val();

	// Remove any notifications currently being displayed.
	var $notification_containers = $notifications.find('.notification-container');
	$notification_containers.slideUp('fast', function() {
		$(this).remove();
	});

	// Try to add an account number to the form.
	var acc_no = $('.ups-account-number').text().trim()
	var $acc_input = $('<input>',{
		'name' : 'ups_account_no',
		'value' : acc_no,
		'type' : 'hidden'
	})
	$form.append($acc_input)

	if(action === 'sono') {
		/**
		 * SUBMIT A SONO, RETRIEVING THE SHIPPING ADDRESS AND DISPLAYING PACKAGES TO BE CONFIGURED.
		 */

		$sono = $form.find(':input[name="sono"]');
		var sono = $sono.val();

		// Ensure sales order number is not empty.
		if(!sono) {
			var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
			$notification.find('.notification').text('Sales Order Number must be entered');
			$notification.slideDown('fast');
			return false;
		}

		var form_data = {
			'sono': sono
		};

		// Retrieve and populate address fields. Pass callback to act when finished.
		addressHandler(form_data, function(address_success) {
			if(address_success) {
				var $start_over = $('#shipping-form .start-over');

				// Now that we've loaded info from a sales order, don't allow the sales order to be edited.
				$sono.prop('readonly', true);
				$start_over.slideDown('fast');

				$action.val('quote');
				$submit.text('Request Quote(s)');
			}
		});
	} else if(action === 'quote') {
		/**
		 * SUBMIT ADDRESS AND PACKAGES TO BE SHIPPED, RETRIEVING A LIST OF CARRIERS TO SHIP THROUGH.
		 */

		// Retrieve and populate address fields.
		var form_data = new FormData(form);
		quoteHandler(form_data, function(quote_success) {
			if(quote_success) {
				var $edit_address_icon = $form.find('.action.edit-address');
				$edit_address_icon.removeClass('fa-pencil').addClass('fa-check').removeClass('edit-address').addClass('good-address');

				//var $edit_contact_icon = $form.find('.action.edit-contact');
				//$edit_contact_icon.removeClass('fa-pencil').addClass('fa-check').removeClass('edit-contact').addClass('good-address');
			}
		});
	} else if(action === 'print') {
		/**
		 * SUBMIT FORM, WHICH WILL PAY FOR AND PRINT SHIPPING LABELS.
		 */
		var form_data = new FormData(form);
		printHandler(form_data, function(print_success) {
			if(print_success) {
				// Perform anything we need to after we're done handling the form.
			}
		});
	}

	return false; // Prevent form submission from propagating.
});

/**
 * Bind to changes on Package Type drop-down.
 */
$(document).off('change', '#shipping-form .package :input[name="packages[type][]"]');
$(document).on('change', '#shipping-form .package :input[name="packages[type][]"]', function(event) {
	var $type = $(this);
	var $package = $type.closest('.package');
	var $dimensions_dropdown = $package.find(':input[name="packages[package_id][]"]');
	var $dimensions_override = $package.find('.dimensions-override');
	if($type.val() === '9') { // Box
		$dimensions_override.slideUp('fast');
		$dimensions_dropdown.slideDown('fast');
		$dimensions_dropdown.val($dimensions_dropdown.find('option').first().attr('value'));
	} else { // Anything else.
		$dimensions_dropdown.slideUp('fast');
		$dimensions_override.slideDown('fast');
		$dimensions_dropdown.val('other'); // Ensure the custom dimensions inputs are used.
	}
});

/**
 * Bind to changes on Package Dimensions drop-down.
 */
$(document).off('change', '#shipping-form .package :input[name="packages[package_id][]"]');
$(document).on('change', '#shipping-form .package :input[name="packages[package_id][]"]', function(event) {
	var $dimensions_dropdown = $(this);
	var $package = $dimensions_dropdown.closest('.package');
	var $dimensions_override = $package.find('.dimensions-override');
	if($dimensions_dropdown.val() === 'other') {
		$dimensions_override.slideDown('fast');
	} else {
		$dimensions_override.slideUp('fast');
	}
});

function addressHandler(form_data, address_callback) {
	var $loading_overlayz = $.overlayz({
		'html': $ajax_loading_prototype.clone(),
		'css': {
			'body': {
				'width': 300,
				'height': 300,
				'border-radius': 150,
				'border': 0,
				'padding': 0,
				'line-height': '300px'
			}
		},
		'close-actions': false // Prevent the user from being able to close the overlay on demand.
	}).hide();
	$loading_overlayz.fadeIn();

	// Retrieve shipping details.
	$.ajax({
		'url': BASE_URI + '/dashboard/warehouse/shipping/details',
		'data': form_data,
		'method': 'POST',
		'dataType': 'json',
		'success': function(response) {
			if(!response.success) { // Handle errors.
				var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
				if(response.message) {
					$notification.find('.notification').text(response.message);
				} else {
					$notification.find('.notification').text('Unable to retrieve sales order, unknown error encountered');
				}
				$notification.slideDown('fast');
				$sono.prop('readonly', false);
				$loading_overlayz.fadeOut('fast', function() {
					$(this).remove();
				});

				// Perform the callback, passing a success status of false.
				address_callback(false);
			}

			// Check to see if a label has already been printed.
			check_is_printed()

			// Check to see if an SO is set to ship third-party.
			check_is_third_party()

			// Set address input fields and displayed contact info.
			setAddress(response.address);
			setContact(response.contact);
			setLocation(response.location);
			setShipVia(response.shipvia);
			setFreightPaymentMethod(response.freight_pay_method, response.freight_pay_method_id, response.shipping_account_number);

			// Show the Packages container.
			var $packages_group = $('#shipping-form .packages-group');
			$packages_group.slideDown('fast');

			// Show the Order Status container.
			var $orderstatus_group = $('#shipping-form .orderstatus-group');
			$orderstatus_group.slideDown('fast');
			$orderstatus_group.find(':input[name="orderstatus"]').prop('required', true);

			// Show the Notes container.
			var $notes_group = $('#shipping-form .notes-group');
			$notes_group.slideDown('fast');

			// Now that package inputs are shown, ensure they are all required.
			$packages_group.find(':input:not([name="packages[insurance][]"]):not([name="packages[class][]"])').prop('required', true);

			$loading_overlayz.fadeOut('fast', function() {
				$(this).remove();
			});

			// Perform the callback, passing a success status of true.
			address_callback(true);

		}
	});
}

function quoteHandler(form_data, quote_callback) {
	var $loading_overlayz = $.overlayz({
		'html': $ajax_loading_prototype.clone(),
		'css': {
			'body': {
				'width': 300,
				'height': 300,
				'border-radius': 150,
				'border': 0,
				'padding': 0,
				'line-height': '300px'
			}
		},
		'close-actions': false // Prevent the user from being able to close the overlay on demand.
	}).hide();
	$loading_overlayz.fadeIn();

	$.ajax({
		'url': BASE_URI + '/dashboard/warehouse/shipping/ship',
		'method': 'POST',
		'data': form_data,
		'dataType': 'json',
		'processData': false,
		'contentType': false,
		'enctype': 'multipart/form-data',
		'success': function(response) {
			if(!response.success) { // Handle errors.
				var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
				if(response.message) {
					$notification.find('.notification').text(response.message);
				} else {
					$notification.find('.notification').text('Unable to retrieve sales order, unknown error encountered');
				}
				$notification.slideDown('fast');

				// Perform the callback, passing a success status of false.
				quote_callback(false);
				return false;
			}

			var $services_group = $('#shipping-form .services-group');
			var $services_container = $services_group.find('.services');
			$services_container.empty();

			$services_container.append(
				$('<input type="hidden" name="carrier" />'),
				$('<input type="hidden" name="method" />'),
				$('<input type="hidden" name="transitdays" />'),
				$('<input type="hidden" name="cost" />')
			);

			$.each(response.services, function(offset, service) {
				var $service = $('<div class="service">');
				// Add metadata to the service div.
				$.each(service, function(service_key, service_value) {
					$service.attr(service_key, service_value);
				});
				$service.append(
					$('<div class="price">').text('$' + service.cost),
					$('<div class="carrier">').text(service.carrier),
					$('<div class="method">').text(service.method),
					$('<div class="eta">').text('ETA: ' + service.eta)
				);
				$service.attr('carrier', service.carrier);
				$service.attr('method', service.method);
				$service.attr('transitdays', service.days_in_transit);
				$service.attr('cost', service.cost);
				$service.appendTo($services_container);
			});
			$services_group.slideDown('fast');

			// Call quote callback, passing failure status.
			quote_callback(true);
		},
		'error': function(error) {
			var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
			$notification.find('.notification').text('Unable to retrieve sales order, something didn\'t go right');
			$notification.slideDown('fast');

			// Perform the callback, passing a success status of false.
			quote_callback(false);
			return false;
		},
		'complete': function() {
			$loading_overlayz.fadeOut('fast', function() {
				$loading_overlayz.remove();
			});
		}
	});
}

function printHandler(form_data, print_callback) {
	var $summary_container = $form.find('.summary-group');
	var $summary = $summary_container.find('.summary');

	var $loading_overlayz = $.overlayz({
		'html': $ajax_loading_prototype.clone(),
		'css': {
			'body': {
				'width': 300,
				'height': 300,
				'border-radius': 150,
				'border': 0,
				'padding': 0,
				'line-height': '300px'
			}
		},
		'close-actions': false // Prevent the user from being able to close the overlay on demand.
	}).hide();
	$loading_overlayz.fadeIn();

	$.ajax({
		'url': BASE_URI + '/dashboard/warehouse/shipping/ship',
		'method': 'POST',
		'data': form_data,
		'dataType': 'json',
		'processData': false,
		'contentType': false,
		'enctype': 'multipart/form-data',
		'success': function(response) {
			if(!response.success) { // Handle errors.
				var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
				if(response.message) {
					$notification.find('.notification').text(response.message);
				} else {
					$notification.find('.notification').text('Unable to retrieve sales order, unknown error encountered');
				}
				$notification.slideDown('fast');

				$loading_overlayz.fadeOut('fast', function() {
					$loading_overlayz.remove();
				});

				// Perform the callback, passing a success status of false.
				print_callback(false);
				return false; // Prevent any logic from continuing.
			}

			// Hide mostly everything.
			$form.find('.location-group').slideUp('fast');
			$form.find('.address-group').slideUp('fast');
			$form.find('.contact-group').slideUp('fast');
			$form.find('.notes-group').slideUp('fast');
			$form.find('.packages-group').slideUp('fast');
			$form.find('.shipvia-group').slideUp('fast');
			$form.find('.orderstatus-group').slideUp('fast');
			$form.find('.freight-group').slideUp('fast');
			$form.find('.ups-group').slideUp('fast');
			$form.find('.services-group').slideUp('fast');
			$form.find('button[type="submit"]').slideUp('fast');

			$summary_container.slideDown('fast');

			$loading_overlayz.fadeOut('fast', function() {
				$loading_overlayz.remove();
			});

			print_callback(true);
		},
		'error': function() {
			var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
			$notification.find('.notification').text('Unable to retrieve sales order, unknown error encountered');
			$notification.slideDown('fast');

			// Perform the callback, passing a success status of false.
			print_callback(false);

			$loading_overlayz.fadeOut('fast', function() {
				$loading_overlayz.remove();
			});
		}
	});
}

function setAddress(address) {

	var $address_container = $form.find('.address-group');
	var $address = $address_container.find('.address');

	// Populate hidden inputs.
	$address_container.find(':input[name="address[company]"]').val(address.company);
	$address_container.find(':input[name="address[address1]"]').val(address.address1);
	$address_container.find(':input[name="address[address2]"]').val(address.address2);
	$address_container.find(':input[name="address[address3]"]').val(address.address3);
	$address_container.find(':input[name="address[city]"]').val(address.city);
	$address_container.find(':input[name="address[state]"]').val(address.state);
	$address_container.find(':input[name="address[zip]"]').val(address.zip);
	$address_container.find(':input[name="address[country]"]').val(address.country);

	// Populate displayed text.
	$address.empty();
	$address.append($('<span>').addClass('company').text(address.company));
	$address.append('<br />');
	$address.append($('<span>').addClass('street').text(address.address1));
	if(address.address2) {
		$address.append('<br />');
		$address.append($('<span>').addClass('street').text(address.address2));
	}
	if(address.address3) {
		$address.append('<br />');
		$address.append($('<span>').addClass('street').text(address.address3));
	}
	$address.append('<br />');
	$address.append(
		$('<span>').text(address.city),
		$('<span>').text(', '),
		$('<span>').text(address.state),
		$('<span>').text(' '),
		$('<span>').text(address.zip)
	);
	$address.append('<br />');
	$address.append($('<span>').addClass('country').text(address.country));

	$address_container.slideDown('fast');
}

function setContact(contact) {
	var $contact_container = $form.find('.contact-group');
	var $contact = $contact_container.find('.contact');

	// Populate hidden inputs.
	$contact_container.find(':input[name="contact[name]"]').val(contact.name);
	$contact_container.find(':input[name="contact[phone]"]').val(contact.phone);
	$contact_container.find(':input[name="contact[email]"]').val(contact.email);

	// Populate displayed text.
	$contact.empty();
	if(contact.name) {
		$contact.append($('<div>').addClass('name').text(contact.name));
	}
	if(contact.phone) {
		$contact.append($('<div>').addClass('phone').text(contact.phone));
	}
	if(contact.email) {
		$contact.append($('<div>').addClass('email').text(contact.email));
	}
	$contact_container.slideDown('fast');
}

function setUPSAccountNo(account_number){

	// Update the account number in the interface.

	var $container = $('.ups-account-number')
	$container.text(account_number)

}

function setLocation(location) {
	var $location_container = $form.find('.location-group');
	var $select = $location_container.find('select[name="location"]');
	var $option = $select.find('option[value="' + location + '"]')
	$select.val($option.attr('value'));

	$location_container.slideDown('fast');
}

function setShipVia(shipvia) {
	var $shipvia_container = $form.find('.shipvia-group');
	$shipvia_container.find('.shipvia-value').text(shipvia);
	$shipvia_container.slideDown('fast');
}

function setFreightPaymentMethod(freight_pay_method, freight_pay_method_id, shipping_account_number) {
	var $freight_container = $form.find('.freight-group');
	$freight_container.find('.freight-payment-method').text(freight_pay_method);
	$freight_container.slideDown('fast');

	if(freight_pay_method_id == '3') { // Collect.
		var $ups_container = $form.find('.ups-group');
		$ups_container.slideDown('fast');
		if(shipping_account_number) {
			if(shipping_account_number.trim()) {
				$ups_container.removeClass('error');
				$ups_container.find('.ups-account-number').text(shipping_account_number);
			} else {
				$ups_container.addClass('error');
				$ups_container.find('.ups-account-number').text('MISSING ACCOUNT NUMBER');
			}
		} else { // "Collect" accounts must have a UPS account # on file.
			// Display an error notification.
			var $notification = $notification_prototype.clone().addClass('notification-error').appendTo($notifications);
			$notification.find('.notification').text('Warning: This "Freight Collect By Carrier" order cannot be shipped until the customer\'s UPS account number has been added.');
			$notification.slideDown('fast');

			// Hide Request Quote button to prevent shipping label requests.
			var $submit = $form.find('button[type="submit"]');
			$submit.hide();
		}
	}
}

function check_is_printed(){

	// Get the SO.
	var $input = $('#sono')
	var sono = $input.val()

	// The data to POST.
	var data = {'sono' : sono.trim()}

	// Check if the SO has been printed.
	$.ajax({
		'url' : '/dashboard/warehouse/shipping/check',
		'method' : 'POST',
		'dataType' : 'JSON',
		'data' : data,
		'success' : function(rsp){

			// If the SO has been shipped, let the user know, but allow them
			// to continue shipping if necessary.
			if(rsp.printed){
				var msg = "SO "+sono+" has already been printed.\nContinue?"
				if(!confirm(msg)){
					location.reload()
				}
			}

		},
		'error' : function(rsp){
			console.log('error')
			console.log(rsp)
		}
	})

}

function check_is_third_party(){

	// Check if the order is a third-party-pay order and alert the user.
	// Do not allow them to continue shipping.

	// Get the SO.
	var $input = $('#sono')
	var sono = $input.val().trim()

	// The data to POSt.
	var data = {'sono' : sono}

	// Check for third-party.
	$.ajax({
		'url' : '/dashboard/warehouse/shipping/check_third_party',
		'method' : 'POST',
		'dataType' : 'JSON',
		'data' : data,
		'success' : function(rsp){

			// If the SO is not set to ship third-party, don't do anything.
			if(!rsp.third_party){
				return fase
			}

			// Disable all form inputs and buttons.
			var $form = $('#shipping-form')
			$form.find('input').attr('disabled','disabled')
			$form.find('button').attr('disabled', 'disabled')

			// Create a banner to display at the top of the page.
			var $bdiv = $('<div>',{
				'id' : 'third-party-banner',
				'class' : 'row-fluid'
			})

			var $tdiv = $('<div>',{
				'text':'This SO is set to ship third-party. This is not yet supported - please use WorldShip',
				'id' : 'third-party-banner-text-container'
			})
			$tdiv.appendTo($bdiv)

			// Add the banner above the form.
			$form.prepend($bdiv)

		},
		'error' : function(rsp){
			console.log('error')
			console.log(rsp)
		}
	})

}

</script>
<?php

Template::Render('footer', 'account');
