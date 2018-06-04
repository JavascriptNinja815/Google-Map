// Define the overlay to be displayed while fetching a PO or saving changes.
var $layz = $.overlayz({
	'html': '<div style="width:100px;margin:auto;height:300px;padding-top:100px;padding-bottom:100px;"><img src="/interface/images/ajax-loading.gif" /></a>',
	'css': {
		'body': {
			'width': 300,
			'height': 300,
			'border-radius': 150,
			'border': 0,
			'padding': 0
		}
	},
	'close-actions': false // Prevent the user from being able to close the overlay on demand.
});

// Wait for window to finish loading before binding to elements.
$(function() {
	var $po_update_container = $('#po-update-container');
	var $po_update_form = $('#purchase-order-update');
	var $po_item_container = $po_update_form.find('.po-update-items');
	var $po_item_prototype = $po_item_container.find('.po-update-item-prototype').detach();

	/**
	 * Bind to "po-retrieve" form submissions
	 */
	$(document).off('submit', '#purchase-order-retrieve');
	$(document).on('submit', '#purchase-order-retrieve', function(event) {
		var $form = $(this);
		var $po_input = $form.find('#po-number-input');
		var po_number = $po_input.val();

		if(!po_number.length) { // Don't perform any action if PO is blank.
			return false;
		}
		var $po_number_error = $form.find('.po-number-error');

		$.ajax({
			'url': BASE_URI + '/dashboard/purchase-order-rejections/retrieve',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'po-number': po_number
			},
			'beforeSend': function() {
				$layz.fadeIn();
				$po_number_error.slideUp();
				$po_update_container.slideUp();
			},
			'success': function(data, status, jqXHR) {
				if(data.success) {
					$po_item_container.empty();

					var $po_info_title = $('.po-update-info-title');
					var $po_info_ponumber = $('.po-update-ponumber');
					var $po_info_vendor = $('.po-update-vendor');
					var $po_info_purchasedate = $('.po-update-purchasedate');

					$po_info_title.text('Purchase Order #' + data.po.info.po_number.trim());
					$po_info_ponumber.text(data.po.info.po_number.trim());
					$po_info_vendor.text(data.po.info.vendor.trim());
					$po_info_purchasedate.text(data.po.info.purchase_date.trim());

					$.each(data.po.items, function(po_item_index, po_item) {
						var $po_item = $po_item_prototype.clone();
						$po_item.removeClass('po-update-item-prototype');
						$po_item.appendTo($po_item_container);

						$po_item.find('.po-update-item-name').text(po_item.item_name.trim());
						$po_item.find('.po-update-item-partnumber').text(po_item.vendor_part_number.trim());
						$po_item.find('.po-update-quantity').val(po_item.return_quantity.trim());
						$po_item.find('.po-update-notes').val(po_item.return_notes.trim());
					});
					$po_update_container.slideDown();
				} else {
					$po_number_error.text(data.message).slideDown();
				}
			},
			'complete': function() {
				$layz.fadeOut();
			}
		});

		return false; // Prevent form submission from propogating further.
	});

	/**
	* Bind to "po-update" form submissions.
	 */
	$(document).off('submit', '#purchase-order-update'); // Prevents double-binding
	$(document).on('submit', '#purchase-order-update', function() {
		var $po_update_success = $('.notification-container.notification-success');
		console.log($po_update_success);

		var $po_number = $('.po-update-ponumber');
		var po_number = $po_number.text();

		var po_item_data = [];
		var $po_items = $po_update_form.find('.po-update-items .po-update-item');
		$.each($po_items, function(po_item_index, po_item) {
			var $po_item = $(po_item);
			var $vendor_part_number = $po_item.find('.po-update-item-partnumber');
			var $quantity = $po_item.find('.po-update-quantity');
			var $notes = $po_item.find('.po-update-notes');

			var vendor_part_number = $vendor_part_number.text();
			var quantity = $quantity.val();
			var notes = $notes.val();

			po_item_data.push({
				'vendor-part-number': vendor_part_number,
				'quantity': quantity,
				'notes': notes
			});
		});

		$.ajax({
			'url': BASE_URI + '/dashboard/purchase-order-rejections/save',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'po-number': po_number,
				'items': po_item_data
			},
			'beforeSend': function() {
				$layz.fadeIn();
				$po_update_success.slideUp();
			},
			'success': function(data, status, jqXHR) {
				if(data.success) {
					$po_update_success.slideDown();
				} else {
					$po_number_error.text(data.message).slideDown();
				}
			},
			'complete': function() {
				$layz.fadeOut();
			}
		});

		return false // Prevent form submission from propogating.
	});
});