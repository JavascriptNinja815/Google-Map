<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */ 

$session->ensureLogin();

$args = array(
	'title' => 'Time Off',
	'breadcrumbs' => [
		'My Account' => BASE_URI . '/dashboard/account',
		'Time Off' => BASE_URI . '/dashboard/account/timeoff'
	],
	'body-class' => 'padded'
);

$current_date = date('Y-m-d', time());

Template::Render('header', $args, 'account');

$grab_pending = $db->query("
	SELECT
		timesheets.timesheet_id,
		timesheets.entered_date,
		timesheets.from_datetime,
		timesheets.to_datetime,
		timesheets.hours,
		timesheets.notes,
		timesheet_types.name AS type
	FROM
		" . DB_SCHEMA_ERP . ".timesheets
	INNER JOIN
		" . DB_SCHEMA_ERP . ".timesheet_types
		ON
		timesheet_types.timesheet_type_id = timesheets.timesheet_type_id
	WHERE
		timesheets.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		timesheets.timesheet_type_id != 1
		AND
		timesheets.status = 0
");

$grab_existing = $db->query("
	SELECT
		timesheets.timesheet_id,
		timesheets.entered_date,
		timesheets.from_datetime,
		timesheets.to_datetime,
		timesheets.notes,
		timesheets.status,
		timesheet_types.name AS type
	FROM
		" . DB_SCHEMA_ERP . ".timesheets
	INNER JOIN
		" . DB_SCHEMA_ERP . ".timesheet_types
		ON
		timesheet_types.timesheet_type_id = timesheets.timesheet_type_id
	WHERE
		timesheets.login_id = " . $db->quote($session->login['login_id']) . "
		AND
		timesheets.timesheet_type_id != 1
		AND
		timesheets.status != 0
");

$grab_types = $db->query("
	SELECT
		timesheet_types.timesheet_type_id,
		timesheet_types.name
	FROM
		" . DB_SCHEMA_ERP . ".timesheet_types
	WHERE
		timesheet_types.reserved = 0
	ORDER BY
		timesheet_types.name
");

?>
<div class="dashboard" id="timeoff-dashboard">
	<?php
	if($session->hasRole('Supervisor')) {
		?>
		<div class="blocks" style="max-width:200px;display:inline-block;vertical-align:top;">
			<div class="block-container block-pastdue">
				<div class="block-title">Time Off<br />Requests Pending</div>
				<div class="block">
					<div class="block-count" id="timeoff-request-pending-count">...</div>
				</div>
			</div>
		</div>

		<div id="timeofftoday-container" style="display:inline-block;vertical-align:top;padding-right:32px;">
			<h3>Off Today</h3>
			<table id="timeofftoday-table" class="table table-striped table-hover columns-sortable columns-filterable">
				<thead>
					<tr>
						<th>Initials</th>
						<th>First Name</th>
						<th>Last Name</th>
						<th class="right">Reason</th>
						<th class="right">When</th>
						<th class="right">Comments</th>
					</tr>
				</thead>
				<tbody id="timeofftoday-body"></tbody>
			</table>
		</div>
		<?php
	}
	?>
	<hr />
</div>

<style type="text/css">
	#pending-table button, #existing-table button {
		margin-right:12px;
	}
	#pending-table .timesheet .allday, #existing-table .timesheet .allday {
		background-color:#e6e6e6;
	}
	#request-form .duration-hours {
		margin-top:20px;
		display:none;
	}
	#request-form .duration-hours .control-group .control-label {
		width:55px;
	}
	#request-form .duration-hours .control-group .controls {
		margin-left:62px;
	}
	h4.red {
		color:#999;
		margin-left:22px;
		font-style:italic;
	}
	#existing-table .timesheet.approved td {
		background-color:#dfd;
	}
	#existing-table .timesheet.denied td {
		background-color:#fdd;
	}
</style>

<h3>Pending Requests</h3>

<?php
if($grab_pending->rowCount()) {
	?>
	<table id="pending-table" class="table table-striped table-hover columns-sortable">
		<thead>
			<tr>
				<th></th>
				<th>Date Entered</th>
				<th>Reason</th>
				<th>Requested Date/Time</th>
				<th>Notes</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_pending as $timeoff) {
				$requested = new DateTime($timeoff['entered_date']);
				$from = new DateTime($timeoff['from_datetime']);
				if($timeoff['to_datetime']) {
					$to = new DateTime($timeoff['to_datetime']);
				}
				?>
				<tr timesheet_id="<?php print htmlentities($timeoff['timesheet_id'], ENT_QUOTES);?>" class="timesheet">
					<td><a href="<?php print BASE_URI;?>/dashboard/account/timeoff/cancel?timesheet_id=<?php print htmlentities($timeoff['timesheet_id'], ENT_QUOTES);?>"><button type="button" class="btn btn-inverse">Cancel</button></a></td>
					<td><?php print $requested->format('Y-m-d');?></td>
					<td><?php print $timeoff['type'];?></td>
					<?php
					if($timeoff['to_datetime']) {
						// Specific hours, partial day.
						?>
						<td><?php print $from->format('Y-m-d');?> - <?php print $from->format('g A');?> to <?php print $to->format('g A');?></td>
						<?php
					} else {
						// All day.
						?>
						<td class="allday"><?php print $from->format('Y-m-d');?> - All Day</td>
						<?php
					}
					?>
					<td><?php
						$notes = str_replace(
							"\n\n",
							'<br />',
							str_replace(
								"\r",
								'',
								htmlentities(trim($timeoff['notes']))
							)
						);
						print $notes;
					?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
} else {
	?><h4 class="red">None</h4><?php
}
?>

<h3>Approved/Denied Requests</h3>

<?php
if($grab_existing->rowCount()) {
	?>
	<table id="existing-table" class="table table-striped table-hover columns-sortable">
		<thead>
			<tr>
				<th>Status</th>
				<th>Date Entered</th>
				<th>Reason</th>
				<th>Requested Date/Time</th>
				<th>Notes</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_existing as $timeoff) {
				$requested = new DateTime($timeoff['entered_date']);
				$from = new DateTime($timeoff['from_datetime']);
				if($timeoff['to_datetime']) {
					$to = new DateTime($timeoff['to_datetime']);
				} else {
					$to = Null;
				}
				$notes = str_replace("\r", '', $timeoff['notes']);
				$notes = explode("\n\n", $notes);
				if($timeoff['status'] == 1) {
					$timesheet_class = 'approved';
					$status = 'APPROVED';
				} else if($timeoff['status'] == -2) {
					$timesheet_class = 'denied';
					$status = 'DENIED';
				}
				?>
				<tr timesheet_id="<?php print htmlentities($timeoff['timesheet_id'], ENT_QUOTES);?>" class="timesheet <?php print $timesheet_class;?>">
					<td><?php print $status;?></td>
					<td><?php print $requested->format('m/d/y');?></td>
					<td><?php print $timeoff['type'];?></td>
					<?php
					if($to) {
						// Specific hours, partial day.
						?>
						<td><?php print $from->format('m/d/y');?> - <?php print $from->format('g A');?> to <?php print $to->format('g A');?></td>
						<?php
					} else {
						// All day.
						?>
						<td class="allday"><?php print $from->format('m/d/y');?> - All Day</td>
						<?php
					}
					?>
					<td>
						<div class="notes-container"><?php
							foreach($notes as $note) {
								$note = trim($note);
								if($note) {
									?><div class="notes"><?php print htmlentities($note);?></div><?php
								}
							}
						?></div>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
} else {
	?><h4 class="red">None</h4><?php
}
?>

<form class="form-horizontal" id="request-form" method="post" action="<?php print BASE_URI;?>/dashboard/account/timeoff/request">
	<h3>Time Off Request</h3>
	<div class="control-group">
		<label class="control-label" for="date">Date</label>
		<div class="controls controls-row">
			<input type="date" name="date" value="<?php print date('Y-m-d', strtotime('tomorrow'));?>" min="<?php print date('Y-m-d', strtotime('tomorrow'));?>" required />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Duration</label>
		<div class="controls">
			<label class="checkbox">
				<input type="checkbox" name="allday" value="1" checked />
				All Day
			</label>
			<div class="duration-hours">
				<div class="control-group">
					<label class="control-label">From</label>
					<div class="controls">
						<select name="from-hour">
							<option value="00">12 AM</option>
							<option value="01">1 AM</option>
							<option value="02">2 AM</option>
							<option value="03">3 AM</option>
							<option value="04">4 AM</option>
							<option value="05">5 AM</option>
							<option value="06">6 AM</option>
							<option value="07">7 AM</option>
							<option value="08" selected>8 AM</option>
							<option value="09">9 AM</option>
							<option value="10">10 AM</option>
							<option value="11">11 AM</option>
							<option value="12">12 PM</option>
							<option value="13">1 PM</option>
							<option value="14">2 PM</option>
							<option value="15">3 PM</option>
							<option value="16">4 PM</option>
							<option value="17">5 PM</option>
							<option value="18">6 PM</option>
							<option value="19">7 PM</option>
							<option value="20">8 PM</option>
							<option value="21">9 PM</option>
							<option value="22">10 PM</option>
							<option value="23" disabled>11 PM</option>
						</select>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">To</label>
					<div class="controls">
						<select name="to-hour">
							<option value="00" disabled>12 AM</option>
							<option value="01" disabled>1 AM</option>
							<option value="02" disabled>2 AM</option>
							<option value="03" disabled>3 AM</option>
							<option value="04" disabled>4 AM</option>
							<option value="05" disabled>5 AM</option>
							<option value="06" disabled>6 AM</option>
							<option value="07" disabled>7 AM</option>
							<option value="08" disabled>8 AM</option>
							<option value="09">9 AM</option>
							<option value="10">10 AM</option>
							<option value="11">11 AM</option>
							<option value="12" selected>12 PM</option>
							<option value="13">1 PM</option>
							<option value="14">2 PM</option>
							<option value="15">3 PM</option>
							<option value="16">4 PM</option>
							<option value="17">5 PM</option>
							<option value="18">6 PM</option>
							<option value="19">7 PM</option>
							<option value="20">8 PM</option>
							<option value="21">9 PM</option>
							<option value="22">10 PM</option>
							<option value="23">11 PM</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Reason</label>
		<div class="controls">
			<select name="reason" required>
				<option value="">-- Select --</option>
				<?php
				foreach($grab_types as $type) {
					?><option value="<?php print htmlentities($type['timesheet_type_id'], ENT_QUOTES);?>"><?php print htmlentities($type['name']);?></option><?php
				}
				?>
			</select>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Comments</label>
		<div class="controls">
			<textarea name="notes"></textarea>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary">
				Submit
			</button>
		</div>
	</div>
</form>

<script type="text/javascript">
$(document).ready(function() {
	var one_minute = 60 * 1000; // Calculated in milliseconds.
	var five_minutes = one_minute * 5;

	$(document).off('change', '#request-form :input[name="allday"]');
	$(document).on('change', '#request-form :input[name="allday"]', function(event) {
		var $checkbox = $(this);
		var $hours_container = $('#request-form .duration-hours');
		if($checkbox.is(':checked')) {
			$hours_container.slideUp('fast');
		} else {
			$hours_container.slideDown('fast');
		}
	});

	$(document).off('change', '#request-form :input[name="from-hour"]');
	$(document).on('change', '#request-form :input[name="from-hour"]', function(event) {
		var $from_hour = $(this);
		var $to_hour = $('#request-form :input[name="to-hour"]');
		var from = parseInt($from_hour.val());
		var to = parseInt($to_hour.val());
		$.each($to_hour.find('option'), function(offset, option) {
			var $option = $(option);
			var to = parseInt($option.attr('value'));
			if(to <= from) {
				$option.prop('disabled', true);
			} else {
				$option.prop('disabled', false);
			}
		});
		if(from >= to) {
			var target_hour = from + 1;
			if(target_hour < 10) {
				target_hour = '0' + target_hour;
			}
			$to_hour.val(target_hour);
		}
	});

	/**
	 * Bind to Request Form submissions.
	 */
	$(document).off('submit', 'form#request-form');
	$(document).on('submit', 'form#request-form', function(event) {
		var form = this;
		var $form = $(this);
		var data = new FormData(form);

		var $loading_overlay = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': ajax_loading_styles
		}).fadeIn('fast');

		$.ajax({
			'url': $form.attr('action'),
			'data': data,
			'method': 'POST',
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'beforeSend': function() {
				$form.find('').slideUp('fast', function() {
					$(this).remove();
				});
			},
			'success': function(response) {
				if(!response.success) {
					$loading_overlay.fadeOut('fast', function() {
						$loading_overlay.remove();
					});
					// Add a new notification w/ the message returned.
					$form.prepend(
						$('<div class="notification-container notification-error">').append(
							$('<div class="notification">').text(response.message)
						).hide()
					);
					$form.find('.notification-container').slideDown();
					return;
				}
				
				// Everything is good, reload.
				window.location.reload();
			}
		});

		return false;
	});
	
	<?php
	if($session->hasRole('Supervisor')) {
		?>
		/**
		 * POPULATE "TIME OFF REQUESTS PENDING" BLOCK.
		 */
		var loadTimeOffRequestsPendingBlock = function() {
			$.ajax({
				'url': BASE_URI + '/dashboard/supervisor/timesheets/timeoff/pending/count',
				'dataType': 'json',
				'success': function(response) {
					if(!response.success) {
						if(response.message) {
							alert(response.mesage);
						} else {
							alert('Something didn\'t go right');
						}
						return;
					}
					var $count = $('#timeoff-request-pending-count');
					$count.text(response.count);
				}
			});
		};
		loadTimeOffRequestsPendingBlock();
		setInterval(loadTimeOffRequestsPendingBlock, five_minutes);

		/**
		 * POPULATE "OFF TODAY" TABLE.
		 */
		var loadTimeOffTodayTable = function() {
			$.ajax({
				'url': BASE_URI + '/dashboard/supervisor/timesheets/timeoff/today',
				'dataType': 'json',
				'success': function(response) {
					if(!response.success) {
						if(response.message) {
							alert(response.mesage);
						} else {
							alert('Something didn\'t go right');
						}
						return;
					}
					var $offtoday_table = $('#timeofftoday-table');
					var $offtoday_tbody = $offtoday_table.find('#timeofftoday-body');
					$offtoday_tbody.empty();
					if(response.timeoff.length) {
						$.each(response.timeoff, function(offset, timeoff) {
							var $tr = $('<tr>').append(
								$('<td>').text(timeoff.initials),
								$('<td>').text(timeoff.first_name),
								$('<td>').text(timeoff.last_name),
								$('<td>').text(timeoff.reason),
								$('<td>').text()
							).appendTo($offtoday_tbody);
						});
					} else {
						var $tr = $('<tr>').append(
							$('<td colspan="6">').text('No One')
						).appendTo($offtoday_tbody);
					}
				}
			});
		};
		loadTimeOffTodayTable();
		setInterval(loadTimeOffTodayTable, five_minutes);

		/**
		* BIND TO CLICKS ON "TIME OFF REQUESTS PENDING" BLOCK
		 */
		$(document).off('click', '#timeoff-request-pending-count');
		$(document).on('click', '#timeoff-request-pending-count', function(event) {
			var $requests_overlay = $.overlayz({
				'html': $ajax_loading_prototype.clone()
			}).fadeIn();
			var $requests_overlay_body = $requests_overlay.find('.overlayz-body');
			$.ajax({
				'url': BASE_URI + '/dashboard/supervisor/timesheets/timeoff',
				'method': 'POST',
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
					$requests_overlay_body.empty();
					$requests_overlay_body.html(response.html);
				}
			});
		});
		<?php
	}
	?>
});
</script>

<?php Template::Render('footer', 'account');
