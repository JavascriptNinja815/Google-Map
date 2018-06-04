<?php

$session->ensureLogin();
$session->ensureRole('Supervisor');

ob_start(); // Start loading output into buffer.
?>
<style type="text/css">
	#timeofftabs-container button.add-notes {
		font-size:11px;
		height:23px;
		line-height:17px;
		padding:2px 4px;
	}
	#timeofftabs-container .input-append {
		margin-bottom:0;
	}
	#timeofftabs-container td {
		vertical-align:top;
	}
</style>

<div id="timeofftabs-container">
	<h2 style="display:inline-block;">Time Off</h2>
	<div class="tabs">
		<div class="tab" page="/pending">Pending</div>
		<div class="tab" page="/report">Search / Report</div>
		<div class="tab" page="/new">Add</div>
	</div>
	<div class="tab-content" id="timeoff-details-page"></div>
</div>

<script type="text/javascript">
	/**
	 * Bind to clicks on tabs.
	 */
	var timeoff_tab_xhr;
	$(document).off('click', '#timeofftabs-container .tabs .tab');
	$(document).on('click', '#timeofftabs-container .tabs .tab', function(event) {
		var $tab = $(this);
		var $tabs = $tab.closest('.tabs');
		$tabs.find('.tab.active').removeClass('active');
		$tab.addClass('active');
		var $page_container = $('#timeoff-details-page');
		var page = $tab.attr('page');

		var data = {};

		// Replace page contents w/ loading icon.
		$page_container.html('<div style="width:150px;margin:auto;padding-top:120px;"><img src="/interface/images/ajax-loading.gif" /></div>');

		// Cancel working AJAX request (if present,) to preventtab body skipping.
		if(timeoff_tab_xhr) {
			timeoff_tab_xhr.abort();
		}

		timeoff_tab_xhr = $.ajax({
			'url': BASE_URI + '/dashboard/supervisor/timesheets/timeoff' + page,
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'success': function(response) {
				$page_container.html(response.html);
			}
		});
	});
	// Force loading of first tab.
	$('#timeofftabs-container .tabs .tab').first().trigger('click');

	/**
	 * Bind to key presses on "notes" input.
	 */
	$(document).off('keyup', '#timeoff-details-page .request :input[name="notes"]');
	$(document).on('keyup', '#timeoff-details-page .request :input[name="notes"]', function(event) {
		var keycode = event.keyCode || event.which;
		if(keycode == 13) { // Enter key pressed.
			var $input = $(this);
			var $request = $input.closest('.request');
			var $button = $request.find('.add-notes');
			$button.trigger('click');
		}
	});

	/**
	 * Bind to clicks on Notes "Add" button.
	 */
	$(document).off('click', '#timeoff-details-page .request .add-notes');
	$(document).on('click', '#timeoff-details-page .request .add-notes', function(event) {
		var $button = $(this);
		var $request = $button.closest('.request');
		var $input = $request.find(':input[name="notes"]');
		var $notes_container = $request.find('.notes-container');
		var notes = $input.val();
		var timesheet_id = $request.attr('timesheet_id');
		if(!notes) {
			return;
		}
		$.ajax({
			'url': BASE_URI + '/dashboard/supervisor/timesheets/timeoff/addnotes',
			'dataType': 'json',
			'method': 'POST',
			'data': {
				'timesheet_id': timesheet_id,
				'notes': notes
			},
			'success': function(response) {
				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right');
					}
					return;
				}
				$notes_container.append(
					$('<div class="notes">').text(response.note)
				);
				$input.val('');
			}
		});
	});
</script>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
