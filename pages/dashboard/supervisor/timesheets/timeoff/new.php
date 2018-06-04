<?php

$session->ensureLogin();

if($session->hasRole('Administration')) {
	$where = " 1 = 1 "; // Returns all.
} else if($session->hasRole('Supervisor')) {
	$where = "logins.initials IN ('" . implode(
		"','",
		$session->getPermissions('Supervisor', 'timesheets')
	) . "')";
} else {
	print json_encode([
		'success' => False,
		'message' => 'You do not have permission to be accessing this resource'
	]);
	exit;
}

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

$grab_employees = $db->query("
	SELECT
		logins.login_id,
		logins.initials,
		logins.first_name,
		logins.last_name
	FROM
		" . DB_SCHEMA_INTERNAL . ".logins
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".login_companies
		ON
		login_companies.login_id = logins.login_id
	WHERE
		login_companies.company_id = " . $db->quote(COMPANY) . "
		" . (!empty($where) ? " AND " . $where : Null) . "
	ORDER BY
		logins.initials,
		logins.last_name,
		logins.first_name
");

ob_start(); // Start loading output into buffer.
?>

<style type="text/css">
	#timeoffrequest-form .duration-hours {
		margin-top:20px;
		display:none;
	}
	#timeoffrequest-form .duration-hours .control-group .control-label {
		width:55px;
	}
	#timeoffrequest-form .duration-hours .control-group .controls {
		margin-left:62px;
	}
</style>

<form class="form-horizontal" id="timeoffrequest-form" method="post" action="<?php print BASE_URI;?>/dashboard/supervisor/timesheets/timeoff/create">
	<div class="control-group">
		<label class="control-label" for="date">Date</label>
		<div class="controls controls-row">
			<input type="date" name="date" value="<?php print date('Y-m-d', strtotime('tomorrow'));?>" />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Individual</label>
		<div class="controls">
			<select name="login_id" required>
				<option value="">-- Select --</option>
				<?php
				foreach($grab_employees as $employee) {
					?><option value="<?php print htmlentities($employee['login_id'], ENT_QUOTES);?>"><?php print htmlentities($employee['initials']);?> - <?php print htmlentities($employee['first_name'] . ' ' . $employee['last_name']);?></option><?php
				}
				?>
			</select>
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
		<label class="control-label">Notes</label>
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
	$(document).off('change', '#timeoffrequest-form :input[name="allday"]');
	$(document).on('change', '#timeoffrequest-form :input[name="allday"]', function(event) {
		var $checkbox = $(this);
		var $hours_container = $('#timeoffrequest-form .duration-hours');
		if($checkbox.is(':checked')) {
			$hours_container.slideUp('fast');
		} else {
			$hours_container.slideDown('fast');
		}
	});

	$(document).off('change', '#timeoffrequest-form :input[name="from-hour"]');
	$(document).on('change', '#timeoffrequest-form :input[name="from-hour"]', function(event) {
		var $from_hour = $(this);
		var $to_hour = $('#timeoffrequest-form :input[name="to-hour"]');
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
	 * Bind to form submissions.
	 */
	$(document).off('submit', '#timeoffrequest-form');
	$(document).on('submit', '#timeoffrequest-form', function(event) {
		var data = new FormData(this);
		var $form = $(this);
		$.ajax({
			'url': $form.attr('action'),
			'method': 'POST',
			'data': data,
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(response) {
				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right');
					}
					return;
				}
				alert('Successfully added, please verify information entered and approve under the "Pending" tab');
			}
		});
		return false;
	});
</script>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
