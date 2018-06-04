$(function() {
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

	/**
	 * Bind to clicks on Client Code and Client Name.
	 */
	/*
	var client_details_overlay;
	$(document).off('click', '#clients-container .content-code, #clients-container .content-client');
	$(document).on('click', '#clients-container .content-code, #clients-container .content-client', function(event) {
		var $tr = $(this).closest('tr');
		var code = $tr.attr('code');

		var post_data = {
			'custno': code
		};

		$.ajax({
			'url': BASE_URI + '/dashboard/clients/details',
			'type': 'POST',
			'dataType': 'html',
			'data': post_data,
			'beforeSend': function() {
				$loading_overlayz.fadeIn('fast');
			},
			'success': function(html_response, status, jqXHR) {
				client_details_overlay = $.overlayz({
					'html': html_response,
					'css': {
						'body': {
						}
					},
					'close-actions': false // Prevent the user from being able to close the overlay on demand.
				}).show();
			},
			'complete': function() {
				$loading_overlayz.hide();
			}
		});
	});
	*/

	// Bind to VIC checkbox toggle.
	$(document).on('change', '#clients-container input[name="vic"]', function(event) {
		// Define the overlay to be displayed while fetching a PO or saving changes.
		var $layz = $.overlayz({
			'html': '<div style="padding-left:100px;"><img src="/interface/images/ajax-loading.gif" /></div>',
			'css': {
				'body': {
					'width': 300,
					'height': 300,
					'border-radius': 150,
					'border': 0,
					'padding': 0,
					'line-height': '300px',
				}
			},
			'close-actions': false // Prevent the user from being able to close the overlay on demand.
		});

		var $checkbox = $(this);
		var $row = $checkbox.closest('tr');
		var $custno = $row.find('.content-code');
		var custno = $custno.text().trim();
		$.ajax({
			'url': BASE_URI + '/dashboard/clients/mark-vic',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'custno': custno,
				'vic': $checkbox.is(':checked')
			},
			'beforeSend': function() {
				$layz.fadeIn();
			},
			'success': function(data, status, jqXHR) { },
			'complete': function() {
				$layz.fadeOut();
			}
		});
	});
});
