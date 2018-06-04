$(document).ready(function() {
	// Define the overlay to be displayed while performing actions such as saving.
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

	var one_minute = 60 * 1000; // Calculated in milliseconds.

	/**
	 * POPULATE "Open AR" TABLE
	 */
	var loadOpenArXHR; // For tracking AJAX requests. When another one comes along, ensures existing XHR is cancelled.
	var loadOpenAr = function() {
		var invoice_numbers = [];

		// Check if rows are hidden. If they are, then we'll populate invoice_numbers. Otherwise, we'll leave blank which assumes calculation on all.
		if($('#calllist-table').find('tbody tr:not(:visible)').length) {
			// Get the invoice numbers on the visible rows
			$('#calllist-table').find('tbody tr:visible td.content-invoicenumber').each(function() {
				var invoice_number = $(this).text().trim();
				invoice_numbers.push(invoice_number);
			});
		}

		var $container = $('#openar-container');
		var $datetime = $('#openar-datetime');
		var $table = $('#openar-table');
		var $tbody = $table.find('tbody');

		if(loadOpenArXHR) {
			loadOpenArXHR.abort();
		}

		loadOpenArXHR = $.ajax({
			'url': BASE_URI + '/dashboard/ar-widgets/openar',
			'dataType': 'json',
			'method': 'POST',
			'data': {
				'invoice_numbers': invoice_numbers
			},
			'beforeSend': function() {
				$tbody.find('td').text('...');
			},
			'success': function(data) {
				// Grab the container's current height, and set it
				// as the container's min-height. This prevents the
				// window from scrolling out of position when the
				// container is re-populated with information.
				var container_height = $container.outerHeight(true);
				$container.css('min-height', container_height);

				// Populate Date/Time
				$datetime.text('Last updated on ' + data.datetime);

				// Populate sales.
				$tbody.empty();

				/*
				var $tr = $('<tr>').appendTo($tbody);
				$('<td>').addClass('right').text('$' + data.openar.fifteen).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.thirty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.forty_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.sixty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.seventy_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.ninety).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.hundred_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.hundred_twenty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.hundred_twenty_plus).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.total).appendTo($tr);
				*/

				// Add invioce row.
				var $tr = $('<tr>').appendTo($tbody);
				$('<td>').addClass('right').text('Invoices:').appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.fifteen).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.thirty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.forty_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.sixty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.seventy_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.ninety).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.hundred_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.hundred_twenty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.invoice.hundred_twenty_plus).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.totals.invoice).appendTo($tr);

				// Add credit row.
				var $tr = $('<tr>').appendTo($tbody);
				$('<td>').addClass('right').text('Credit Memos:').appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.fifteen).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.thirty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.forty_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.sixty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.seventy_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.ninety).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.hundred_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.hundred_twenty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.credit.hundred_twenty_plus).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.totals.credit).appendTo($tr);

				// Add net row.
				var $tr = $('<tr>').appendTo($tbody);
				$('<td>').addClass('right').text('Net Amount:').appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.fifteen).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.thirty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.forty_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.sixty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.seventy_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.ninety).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.hundred_five).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.hundred_twenty).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.net.hundred_twenty_plus).appendTo($tr);
				$('<td>').addClass('right').text('$' + data.openar.totals.net).appendTo($tr);
			}
		});
	};
	loadOpenAr();
	setInterval(loadOpenAr, one_minute);

	/**
	 * "Call List" Related Functionality.
	 */
	 
	//Bind to the tablesorter.endFilter event for the filtered table
	$('#calllist-table').on('filterEnd',function(){
		//reload the Open AR summary data for the filtered invoices
		loadOpenAr();
	});
	
	// Bind to changes on Call List filters.
	$(document).on('change', '.calllist-title input[name="arvic"]', function(event) {
		var $radio = $(this);
		var $calllist_table = $('#calllist-table');
		var value = $radio.val();
		if(value === 'non-arvic') {
			// Find non-arvic which are hidden, and show them.
			$calllist_table.find('tr.non-arvic:not(:visible)').show();
			
			// Find arvic which are shown, and hide them.
			$calllist_table.find('tr.arvic:visible').hide();
		} else if(value === 'arvic') {
			// Find arvic which are hidden, and show them.
			$calllist_table.find('tr.arvic:not(:visible)').show();
			
			// Find non-arvic which are shown, and hide them.
			$calllist_table.find('tr.non-arvic:visible').hide();
		} else if(value === 'both') {
			// Grab all non-visible, and show them.
			$calllist_table.find('tr:not(:visible)').show();
		}
	});
	
	// Bind to changes on Follow Up checkboxes.
	$(document).on('change', '#calllist-container input[name="followup"]', function(event) {
		var $checkbox = $(this);

		var $tr = $checkbox.closest('tr');
		var $client = $tr.find('.content-client');
		var custno = $client.text().trim();
		var followup = $checkbox.is(':checked') ? 1 : 0;

		$.ajax({
			'url': BASE_URI + '/dashboard/calllist/follow-up',
			'type': 'POST',
			'dataType': 'json',
			'data': {
				'custno': custno,
				'followup': followup
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

	/**
	 * Bind to clicks on Pay Date Edit icon.
	 */
	$(document).on('click', '.content-paydate .edit-icon', function(event) {
		var $edit_icon = $(this);
		var $container = $edit_icon.closest('.content-paydate');
		var $paydate_container = $container.find('.paydate-container');
		$paydate_container.hide();
		var $edit_container = $container.find('.edit-container');
		var $cancel_icon = $('<i class="edit-cancel-icon fa fa-times fw" title="Cancel">').appendTo($edit_container);
		var $input = $('<input type="date" name="paydate" style="width:100px;" />').appendTo($edit_container);
		var $button = $('<button type="button" class="edit-ok-button">').text('OK').appendTo($edit_container);
		$edit_icon.hide();

		// Bind to clicks on the "Cancel" icon.
		$cancel_icon.on('click', function(event) {
			$cancel_icon.remove();
			$input.remove();
			$button.off('click').remove();
			$edit_icon.show();
			$paydate_container.show();
		});

		// Bind to clicks on the "OK" button.
		$button.on('click', function(event) {
			var $tr = $edit_icon.closest('tr');
			var paydate = $input.val();

			$.ajax({
				'url': BASE_URI + '/dashboard/calllist/set-paydate',
				'data': {
					'invno': $tr.attr('invno'),
					'paydate': paydate
				},
				'type': 'POST',
				'dataType': 'json',
				'success': function(data) {
					if(data.success) {
						alert('Saved!');
					}
				}
			});

			$paydate_container.text(paydate);

			$cancel_icon.remove();
			$input.remove();
			$button.off('click').remove();
			$edit_icon.show();
			$paydate_container.show();
		});
	});
});
