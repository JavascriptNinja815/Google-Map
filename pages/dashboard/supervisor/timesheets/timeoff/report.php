<?php

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
	#timeoffsearch-form .search-fields {
		display:inline-block;
		min-width:340px;
	}
</style>

<form class="form-horizontal" id="timeoffsearch-form" method="post" action="/dashboard/supervisor/timesheets/timeoff/report/retrieve">
	<div class="search-fields">
		<div class="search-field">
			<div class="control-group">
				<label class="control-label" for="date">From</label>
				<div class="controls controls-row">
					<input type="date" name="from" value="<?php print date('Y-m-d', time());?>" required />
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="date">To</label>
				<div class="controls controls-row">
					<input type="date" name="to" value="<?php print date('Y-m-d', strtotime('+1 week'));?>" required />
				</div>
			</div>
		</div>
	</div>
	<div class="search-fields">
		<div class="search-field">
			<div class="control-group">
				<label class="control-label">Individual</label>
				<div class="controls">
					<select name="login_id">
						<option value="">-- Any --</option>
						<?php
						foreach($grab_employees as $employee) {
							?><option value="<?php print htmlentities($employee['login_id'], ENT_QUOTES);?>"><?php print htmlentities($employee['initials']);?> - <?php print htmlentities($employee['first_name'] . ' ' . $employee['last_name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">Status</label>
				<div class="controls">
					<select name="status">
						<option value="">-- Any --</option>
						<option value="-2">Denied</option>
						<option value="-1">Canceled</option>
						<option value="0">Pending Approval</option>
						<option value="1">Approved</option>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">Reason</label>
				<div class="controls">
					<select name="reason">
						<option value="">-- Any --</option>
						<?php
						foreach($grab_types as $type) {
							?><option value="<?php print htmlentities($type['timesheet_type_id'], ENT_QUOTES);?>"><?php print htmlentities($type['name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-primary">
				<i class="fa fa-search"></i> Search
			</button>
		</div>
	</div>
</form>

<div id="timeoffsearch-results"></div>

<script type="text/javascript">
	$(document).off('submit', '#timeoffsearch-form');
	$(document).on('submit', '#timeoffsearch-form', function(event) {
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
				$('#timeoffsearch-results').html(response.html);
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
