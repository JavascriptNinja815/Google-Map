$(function() {
	/**
	 * Bind DatePickers to Date Range "From" and "To" input boxes.
	 */
	var $datetime_from = $('#datepicker-from');
	var $datetime_to = $('#datepicker-to');

	var datepicker_config = {
		'lang': 'en',
		'datepicker': true,
		'timepicker': false,
		'formatDate': 'n/j/Y', //'formatDate': 'n/j/Y g:ia',
		'format': 'n/j/Y',
		'closeOnDateSelect': true
	};

	var datepicker_from_config = {};
	$.extend(datepicker_from_config, datepicker_config, {
		'onShow': function(selected_datetime) {
			this.setOptions({
				'maxDate': $datetime_to.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		},
		'onChangeDateTime': function(selected_datetime) {
			this.setOptions({
				'maxDate': $datetime_to.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		}
	});

	var datepicker_to_config = {};
	$.extend(datepicker_to_config, datepicker_config, {
		'onShow': function(selected_datetime) {
			this.setOptions({
				'minDate': $datetime_from.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		},
		'onChangeDateTime': function(selected_datetime) {
			this.setOptions({
				'minDate': $datetime_from.val(),
				'value': selected_datetime.getMonth()+1 + '/' + selected_datetime.getDate() + '/' + selected_datetime.getFullYear()
			});
		}
	});

	$datetime_from.datetimepicker(datepicker_from_config);
	$datetime_to.datetimepicker(datepicker_to_config);

	/**
	* Bind to Order Status "Edit" icon presses
	 */
	$(document).off('click', '#orders-container .order-status-edit-icon');
	$(document).on('click', '#orders-container .order-status-edit-icon', function(event) {
		var $order_container = $(event.target).closest('tr');
		var $existing_order_notes_container = $order_container.find('.order-notes-container');
		var order_id = $order_container.attr('order-id');
		var $edit_container = $order_container.find('.order-status-edit-container');
		var $status_container = $order_container.find('.order-status-container');
		var current_status = $status_container.text().trim();
		var $edit_icon = $edit_container.find('.order-status-edit-icon');
		var $status_select = $('<select class="order-status-edit-select">');
		var $cancel_icon = $('<i class="order-status-edit-cancel-icon fa fa-times fw" title="Cancel"></i>');

		// Populate the options of the `$status_select`.
		$.each(order_statuses, function(order_status_value, order_status_name) {
			$status_select.append(
				$('<option>').attr('value', order_status_value).text(order_status_name)
			);
		});

		$status_container.hide(); // Hide status container
		$edit_icon.hide(); // Hide edit icon
		$cancel_icon.appendTo($edit_container); // Show cancel icon
		$status_select.appendTo($edit_container); // Show status select

		$status_select.val(current_status);

		var $ajax_loading_container = $('<div class="ajax-loading-container">').append(
			$('<img src="' + STATIC_PATH + '/images/ajax-loading-horizontal.gif" />')
		);

		/**
		 * Bind to Select changes.
		 */
		$status_select.on('change', function(event) {
			var order_status = $status_select.val();

			$.ajax({
				'url': BASE_URI + '/dashboard/sales-order-status/order-status-update',
				'type': 'POST',
				'dataType': 'json',
				'data': {
					'order-id': order_id,
					'order-status': order_status
				},
				'beforeSend': function(jqXHR, settings) {
					$status_select.hide(); // Hide status select
					$cancel_icon.hide(); // Hide cancel icon

					$ajax_loading_container.appendTo($edit_container);
				},
				'success': function(data, status, jqXHR) {
					if(data.success) {
						$status_container.text(order_status);
						$existing_order_notes_container.append(
							$('<div class="order-notes-row">').text(data.note)
						);
					} else {
						alert(data.message);
					}
				},
				'complete': function(jqXHR, status) {
					$status_select.remove(); // Remove status select
					$cancel_icon.remove(); // Remove cancel icon
					$edit_icon.show(); // Show edit icon
					$status_container.show(); // Show status container
					$ajax_loading_container.remove();
				}
			});
		});

		/**
		 * Bind to "Cancel" icon clicks.
		 */
		$cancel_icon.on('click', function(event) {
			$status_select.remove(); // Remove status select
			$cancel_icon.remove(); // Remove cancel icon
			$edit_icon.show(); // Show edit icon
			$status_container.show(); // Show status container
		});
	});
	
	/**
	 * Bind to page loads in which an AJAX request is fired to retrieve which
	 * orders contain tracking information, adding icons appropriately.
	 */
	$(function() {
		// Grab all sales order IDs displayed within the interface and pack them
		// into an array.
		var sales_order_ids = [];
		var $sales_order_trs = $('#orders-container tr');
		$.each($sales_order_trs, function(index, tr) {
			var $tr = $(tr);
			var sales_order_number = $tr.attr('sales-order-number');
			if(sales_order_number) {
				sales_order_ids.push(sales_order_number);
			}
		});

		// Perform ana AJAX query, which will return Sales Order IDs which
		// we have tracking information for.
		$.ajax({
			'url': BASE_URI + '/dashboard/sales-order-status/shipping-icons',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'sales-order-ids': sales_order_ids
			},
			'success': function(data) {
				if(data.success) {
					$.each(data['sales-order-ids'], function(index, sales_order_id) {
						var $tr = $('#orders-container .sales-order-' + sales_order_id);
						var $po_status_container = $tr.find('.content-po-status');

						// Check if a shipping icon is already present.
						var $icon = $po_status_container.find('.content-po-status-shipped');
						if(!$icon.length) {
							// When icon is not present, add it.
							$icon = $('<i>').addClass('content-po-status-shipped').addClass('fa').addClass('fw');

							// Determine whether to show open box or closed box icon.
							if($po_status_container.hasClass('content-po-status-shipped')) {
								var icon_class = 'fa-archive fa-2x';
								$icon.attr('title', 'Fully Shipped');
							} else if($po_status_container.hasClass('content-po-status-partial')) {
								var icon_class = 'fa-dropbox fa-2x';
								$icon.attr('title', 'Partially Shipped');
							} else if($po_status_container.hasClass('content-po-status-open')) {
								var icon_class = 'fa-building-o fa-2x';
								$icon.attr('title', 'Not Yet Shipped');
							} else {
								// Better to show the wrong icon than no icon at all :)
								var icon_class = 'fa-building-o fa-2x';
							}
							$icon.addClass(icon_class);
							$icon.prependTo($po_status_container);
						}
					});
				}
			}
		});
	});

	/**
	 * Defines the variable used in storing touch events for later comparison.
	 */
	var touch_target;

	/**
	 * Ensure options within "Available" drop-down are never selected.
	 */
	var available_option_click_fn = function(event) {
		var $option = $(this);

		var $available_container = $option.closest('.search-field-available');
		var $filterby_container = $available_container.closest('.search-field').find('.search-field-filterby');

		// Grab the name for the hiddne input to be added to the option.
		var input_name = $available_container.attr('input-name');

		// Grab the value for the hidden input to be added to the option.
		var input_value = $option.attr('input-value');

		$option.appendTo($filterby_container).append(
			$('<input type="hidden">').attr('name', input_name).attr('value', input_value)
		);

		// Sort Filter By options by name.
		var $filterby_options = $filterby_container.find('.search-field-value');
		$filterby_options.sort(function(a, b) {
			var a_name = a.getAttribute('input-value'),
				b_name = b.getAttribute('input-value');
			return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
		});
		$filterby_options.detach().appendTo($filterby_container);
	};
	$(document).on('click', '#dates-container .search-field-available .search-field-value', available_option_click_fn);
	$(document).on('touchstart', '#dates-container .search-field-available .search-field-value', function(event) {
		touch_target = event.target;
	});
	$(document).on('touchend', '#dates-container .search-field-available .search-field-value', function(event) {
		if(touch_target == event.target) {
			// Only activate when touch start and touch end elements match up.
			filterby_option_click_fn(event);
		}
	});

	/**
	 * Bind to change events on Selected Statuses drop-down.
	 */
	var filterby_option_click_fn = function(event) {
		var $option = $(this);

		var $filterby_container = $option.closest('.search-field-filterby');
		var $available_container = $filterby_container.closest('.search-field').find('.search-field-available');

		$option.appendTo($available_container);
		$option.find('input').remove();

		// Sort Available options by name.
		var $available_options = $available_container.find('.search-field-value');
		$available_options.sort(function(a, b) {
			var a_name = a.getAttribute('input-value'),
				b_name = b.getAttribute('input-value');
			return a_name > b_name ? 1 : a_name < b_name ? -1 : 0;
		});
		$available_options.detach().appendTo($available_container);
	};
	$(document).on('click', '#dates-container .search-field-filterby .search-field-value', filterby_option_click_fn);
	$(document).on('touchstart', '#dates-container .search-field-filterby .search-field-value', function(event) {
		touch_target = event.target;
	});
	$(document).on('touchend', '#dates-container .search-field-filterby .search-field-value', function(event) {
		if(touch_target == event.target) {
			// Only activate when touch start and touch end elements match up.
			filterby_option_click_fn(event);
		}
	});
	
	/**
	 * Bind to clicks on "+" and "-" icons within search fields.
	 */
	$(document).off('click', '.search-field-add'); // Prevents double-binding.
	$(document).on('click', '.search-field-add', function() {
		var $icon = $(this);
		var $search_field_container = $icon.closest('.search-field');
		var $available_container = $search_field_container.find('.search-field-available');
		$available_container.find('.search-field-value').click();
	});
	$(document).off('click', '.search-field-remove'); // Prevents double-binding.
	$(document).on('click', '.search-field-remove', function() {
		var $icon = $(this);
		var $search_field_container = $icon.closest('.search-field');
		var $filter_by_container = $search_field_container.find('.search-field-filterby');
		$filter_by_container.find('.search-field-value').click();
	});
});
