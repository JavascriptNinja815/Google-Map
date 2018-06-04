<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'Warehouse: Labels',
	'breadcrumbs' => array(
		'Warehouse: Labels' => BASE_URI . '/dashboard/labels'
	)
);

Template::Render('header', $args, 'account');
?>

<div class="padded" id="labels-container">
	<fieldset>
		<legend>Labels</legend>
		<form id="label-form" class="form-horizontal" method="get" action="<?php print BASE_URI;?>/dashboard/labels/review">
			<div class="control-group">
				<label class="control-label" for="location">Location / Printer</label>
				<div class="controls">
					<select name="printer" id="location">
						<?php
						$printers = $db->query("
							SELECT
								printers.printer_id,
								printers.printer
							FROM
								" . DB_SCHEMA_INTERNAL . ".printers
							ORDER BY
								printers.printer
						");
						foreach($printers as $printer) {
							?><option value="<?php print htmlentities($printer['printer_id'], ENT_QUOTES);?>" <?php print $printer['printer_id'] == 102 ? 'selected' : Null;?>><?php print htmlentities($printer['printer']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="location">Type Of Label</label>
				<div class="controls">
					<div style="padding:6px;">
						<label>
							Bin Location
							<input type="radio" name="print" value="bin" checked />
						</label>
					</div>
					<div style="padding:6px;">
						<label>
							Product Box
							<input type="radio" name="print" value="product" />
						</label>
					</div>
					<div style="padding:6px;">
						<label>
							Ship To
							<input type="radio" name="print" value="shipto" />
						</label>
					</div>
				</div>
			</div>

			<div class="control-group definedby-container">
				<label class="control-label">Defined By</label>
				<div class="controls" id="itembin">
					<div style="float:left;padding-right:20px;" class="bin-location">
						<label>
							Bin Location
							<input type="radio" name="query-by" value="bin" checked="checked" />
						</label>
					</div>
					<div style="float:left;padding-right:20px;display:none;" class="item-number">
						<label>
							Item #
							<input type="radio" name="query-by" value="item" />
						</label>
					</div>
					<div style="clear:both;">
						<input type="text" name="query-by-input" />
						<span class="help-inline hidden">Cannot be blank or contain only wildcards</span>
						<br />
						<small><i>Use % for wildcards.</i></small>
					</div>
				</div>
			</div>
			
			<div class="control-group sono-container hidden">
				<label class="control-label">Sales Order #</label>
				<div class="controls">
					<input type="text" name="sono" />
				</div>
			</div>
			
			<div class="control-group">
				<label class="control-label">Quantity To Print</label>
				<div class="controls">
					<select name="quantity">
						<?php
						foreach(range(1,10) as $quantity) {
							?><option value="<?php print htmlentities($quantity, ENT_QUOTES);?>"><?php print htmlentities($quantity);?></option><?php
						}
						?>
					</select>
				</div>
			</div>

			<div class="control-group">
				<div class="controls">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-search fa-fw"></i>
						Review
					</button>
				</div>
			</div>
		</form>
	</fieldset>
</div>

<script type="text/javascript">
	/**
	 * Bind to changes on "Type Of Label" options.
	 */
	$(document).off('change', '#labels-container :input[name="print"]');
	$(document).on('change', '#labels-container :input[name="print"]', function(event) {
		var $input = $(this);
		var $form = $input.closest('form');
		var value = $input.val();
		var $submit_button = $form.find('button[type="submit"]');
		var $sono_container = $form.find('.sono-container');
		var $definedby_container = $form.find('.definedby-container');
		console.log('Value:', value);
		if(['bin', 'product'].indexOf(value) > -1) {
			var $item_number = $form.find('.item-number');
			var $bin_option = $form.find(':input[name="query-by"][value="bin"]');
			$sono_container.hide();
			$definedby_container.show();
			$submit_button.empty().append(
				$('<i class="fa fa-search fa-fw">'),
				' Review'
			);
			if(value === 'bin') {
				$item_number.slideUp();
				$bin_option.prop('checked', true);
			} else if(value === 'product') {
				$item_number.slideDown();
			}
			return true; // Allow form submission to propogate.
		} else if(value === 'shipto') {
			$definedby_container.hide();
			$sono_container.show();
			$submit_button.text('Print');
		}
	});

	$(document.body).on('submit', '#label-form', function() {
		var $form = $(this);
		var $print_type = $form.find(':input[name="print"]:checked');
		var print_type = $print_type.val();

		if(print_type === 'shipto') {
			var $sono = $form.find(':input[name="sono"]');
			var $printer = $form.find(':input[name="printer"]');
			var $quantity = $form.find(':input[name="quantity"]');

			var sono = $sono.val();
			var printer_id = $printer.val();
			var quantity = $quantity.val();

			var $loading_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone(),
				'css': ajax_loading_styles
			}).fadeIn('fast');

			$.ajax({
				'url': BASE_URI + '/api/sales-orders/shipto-print.json',
				'data': {
					'so': sono,
					'printer_id': printer_id,
					'quantity': quantity
				},
				'type': 'POST',
				'dataType': 'json',
				'success': function(response) {
					if(!response.success) {
						if(response.message) {
							alert(response.message);
						} else {
							alert('Something didn\'t go right');
						}
						return;
					}
					alert('Labels have been sent to printer');
				},
				'complete': function() {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
				}
			});

			return false; // Prevent form submission from propodating.
		}

		var $query_input = $form.find('input[name="query-by-input"]');
		var query_value = $query_input.val().replace(/\%/g, '').replace(/\_/g, '');

		if(query_value.length === 0) {
			$('.help-inline').slideDown();
			return false;
		} else {
			$('.help-inline').hide();
		}

		return true;
	});

</script>

<?php Template::Render('footer', 'account');
