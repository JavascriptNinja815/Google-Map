
var $ajax_loading_prototype = $('<div class="ajax-loading-container" style="width:200px;text-align:center;margin:auto;">').append(
	$('<img src="/interface/images/ajax-loading.gif">')
);

function activateOverlayZ(url, post_data, beforesend_callback, success_callback, complete_callback, data_type) {

	var layz = $.overlayz({
		'html': '',
		'css': {}
	});

	var $ajax_loading_container = $ajax_loading_prototype.clone();
	var $overlayz_body = layz.find('.overlayz-body');

	if(beforesend_callback === undefined) {
		beforesend_callback = function(jqXHR, settings) {
			// Ensure the overlay is empty.
			$overlayz_body.empty();
			// Append the AJAX loading container to the overlay.
			$ajax_loading_container.appendTo($overlayz_body);
			// Fade in the overlay.
			layz.fadeIn();
		};
	}

	if(complete_callback === undefined) {
		complete_callback = function(jqXHR, status) {
			// Remove the AJAX loading animation.
			$ajax_loading_container.remove();
		};
	}

	if(data_type === undefined) {
		data_type = 'json';
		if(success_callback === undefined) {
			success_callback = function(data, status, jqXHR) {
				if(data.success) {
					$overlayz_body.html(data.html);
				} else {
					alert(data.message);
				}
			};
		}
	} else if(data_type === 'html') {
		if(success_callback === undefined) {
			success_callback = function(html, status, jqXHR) {
				$overlayz_body.html(html);
			};
		}
	}

	var request_data = {
		'url': url,
		'type': 'POST',
		'dataType': data_type,
		'data': post_data,
		'beforeSend': beforesend_callback,
		'success': success_callback,
		'complete': complete_callback
	};
	if(post_data instanceof FormData) {
		request_data['processData'] = false;
		request_data['contentType'] = false;
	}

	$.ajax(request_data);

	return layz;
};

$(function() {
	/**
	 * Automatically bind to clicks on elements with `overlayz-link` class.
	 */
	$(document).on('click', '.overlayz-link', function(event) {
		var $link = $(this);
		var url = $link.attr('overlayz-url');
		var post_data_string = $link.attr('overlayz-data');
		if(post_data_string && post_data_string != '{}') {
			// De-serialize JSON string into native Javascript object.
			var post_data = JSON.parse(post_data_string);
		} else {
			var post_data = {};
		}

		var response_type = $link.attr('overlayz-response-type');

		if(!response_type) {
			response_type = 'json';
		}

		//if(response_type === 'html') {
		createOverlay({}, url, post_data, 'POST', response_type, undefined);
		//} else {
			// Create the overlay, and load the page.
		//	createOverlay({}, url, post_data, 'POST', 'html', undefined);
		//}
	});

	/**
	 * Bind to overlay's Print Toggle Icon clicks (in overlay)
	 */
	$(document.body).off('click', '.overlayz .toggle-print-icon');
	$(document.body).on('click', '.overlayz .toggle-print-icon', function(event) {
		var $overlay_body = $(this).closest('.overlayz-body');
		var $checkbox = $overlay_body.find('input[name="print-flag"]:eq(0)');

		if($checkbox.is(':visible')) {
			// Hide "Print ?", "# Labels", and "Qty Per Box" input containers and headers.
			var $print_containers = $overlay_body.find('.content-print-container').hide();
		} else {
			// Grab and show all header containers.
			$overlay_body.find('#so-details-container thead .content-print-container').show();
			// Grab all checkbox containers.
			var $printcheckbox_containers = $overlay_body.find('.content-print-printcheckbox .content-print-container');
			// Show all checkbox containers, including the checkboxes within.
			$printcheckbox_containers.show();
			// Un-check all checkboxes.
			$printcheckbox_containers.find('input[name="print-flag"]').prop('checked', false);
			// Grab and show printer select.
			$overlay_body.find('.content-print-printlocation').show();
			$overlay_body.find('.content-print-printlabels').show();
		}
	});

	/**
	 * Bind to toggle of "Print?" checkbox (in overlay)
	 */
	$(document.body).off('change', '.overlayz input[name="print-flag"]');
	$(document.body).on('change', '.overlayz input[name="print-flag"]', function(event) {
		var $checkbox = $(this);
		var $row = $checkbox.closest('tr');
		var $numlabels_container = $row.find('.content-print-numlabels .content-print-container');
		var $qtyperbox_container = $row.find('.content-print-qtyperbox .content-print-container');

		if($checkbox.is(':checked')) {
			$numlabels_container.slideDown();
			$qtyperbox_container.slideDown();
		} else {
			$numlabels_container.slideUp();
			$qtyperbox_container.slideUp();
		}
	});

	/**
	 * Bind to "Print Label(s)" button presses (in overlay)
	 */
	$(document.body).off('click', '.overlayz .print-button');
	$(document.body).on('click', '.overlayz .print-button', function(event) {
		var $overlay_body = $(this).closest('.overlayz-body');
		var $checked_checkboxes = $overlay_body.find('.content-print input[name="print-flag"]:checked');

		var data = {
			'sales-order-number': $overlay_body.find('.sales-order-number').text().trim(),
			'printer': $overlay_body.find('[name="print-location"]').val(),
			'items': []
		};
		$.each($checked_checkboxes, function(checkbox_index, checkbox) {
			var $checkbox = $(checkbox);
			var $row = $checkbox.closest('tr');
			data.items.push({
				'item-number': $row.find('.content-item-number').text().trim(),
				'number-of-labels': $row.find('.content-print select[name="print-numlabels"]').val(),
				'qty-per-box': $row.find('.content-print input[name="print-qtyperbox"]').val()
			});
		});

		if(data.items) {
			$.ajax({
				'url': BASE_URI + '/dashboard/sales-order-status/print',
				'type': 'POST',
				'dataType': 'json',
				'data': data,
				'success': function() {
					alert('Your labels should be printed shortly');
				}
			});
		}
	});


	/**
	 * Bind to changes on "Web" checkbox within overlay.
	 */
	$(document.body).off('change', '.overlayz-body input[name="web-order-input"]'); // Prevents double-binding
	$(document.body).on('change', '.overlayz-body input[name="web-order-input"]', function() {
		var $web = $(this);
		var sales_order_number = $web.closest('.overlayz-body').find('.sales-order-number').text().trim();

		var post_data = {
			'sales-order-number': sales_order_number,
			'web': $web.is(':checked') ? 1 : 0
		};
		var $overlay = activateOverlayZ(
			BASE_URI + '/dashboard/sales-order-status/mark-so-web',
			post_data,
			undefined,
			function() {
				$overlay.fadeOut(function() {
					$(this).remove();
				});
			}
		);
	});

	/**
	 * Bind to changes on "Hot" checkbox within overlay.
	 */
	$(document.body).off('change', '.overlayz-body input[name="hot-order-input"]'); // Prevents double-binding
	$(document.body).on('change', '.overlayz-body input[name="hot-order-input"]', function() {
		var $hot = $(this);
		var sales_order_number = $hot.closest('.overlayz-body').find('.sales-order-number').text().trim();

		var post_data = {
			'sales-order-number': sales_order_number,
			'hot': $hot.is(':checked') ? 1 : 0
		};
		var $overlay = activateOverlayZ(
			BASE_URI + '/dashboard/sales-order-status/mark-so-hot',
			post_data,
			undefined,
			function() {
				$overlay.fadeOut(function() {
					$(this).remove();
				});
			}
		);
	});
});
