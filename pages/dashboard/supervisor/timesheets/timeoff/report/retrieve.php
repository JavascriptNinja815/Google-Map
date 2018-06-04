<?php

ob_start(); // Start loading output into buffer.

$where = [];
if($session->hasRole('Administration')) {
	// Returns all, nothing to do.
} else if($session->hasRole('Supervisor')) {
	$where[] = "logins.initials IN ('" . implode(
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

if(!empty($_REQUEST['login_id'])) {
	$where[] = "timesheets.login_id = " . $db->quote($_REQUEST['login_id']);
}
if(!empty($_REQUEST['status'])) {
	$where[] = "timesheets.status = " . $db->quote($_REQUEST['status']);
}
if(!empty($_REQUEST['reason'])) {
	$where[] = "timesheets.timesheet_type_id = " . $db->quote($_REQUEST['reason']);
}
if(!empty($_REQUEST['from'])) {
	$where[] = "timesheets.from_datetime >= " . $db->quote($_REQUEST['from']);
}
if(!empty($_REQUEST['to'])) {
	$where[] = "timesheets.from_datetime <= " . $db->quote($_REQUEST['to']);
}

$grab_timeoff_requests = $db->query("
	SELECT
		enteredby_logins.initials AS entered_by,
		logins.initials,
		logins.first_name,
		logins.last_name,
		timesheets.timesheet_id,
		timesheets.entered_date,
		timesheets.from_datetime,
		timesheets.to_datetime,
		timesheets.notes,
		timesheets.status,
		timesheet_types.name AS reason
	FROM
		" . DB_SCHEMA_ERP . ".timesheets
	INNER JOIN
		" . DB_SCHEMA_ERP . ".timesheet_types
		ON
		timesheet_types.timesheet_type_id = timesheets.timesheet_type_id
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = timesheets.login_id
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins AS enteredby_logins
		ON
		enteredby_logins.login_id = timesheets.entered_login_id
	WHERE
		" . (!empty($where) ? implode(' AND ', $where) : Null) . "
");

?>

<style type="text/css">
	#searchrequests-container .request.approved td {
		background-color:#dfd !important;
	}
	#searchrequests-container .request.denied td {
		background-color:#fdd !important;
	}
	#searchrequests-container .request.canceled td {
		background-color:#eee !important;
	}
	#searchrequests-container .request.pending td {
		background-color:#ffd !important;
	}
</style>

<div id="searchrequests-container">
	<table class="table table-small table-striped table-hover columns-sortable columns-filterable">
		<thead>
			<tr>
				<th class="filterable sortable">Status</th>
				<th class="filterable sortable">Date Entered</th>
				<th class="filterable sortable">Entered By</th>
				<th class="filterable sortable">Initials</th>
				<th class="filterable sortable">First Name</th>
				<th class="filterable sortable">Last Name</th>
				<th class="filterable sortable">Reason</th>
				<th class="filterable sortable">Requested Off Date/Time</th>
				<th>Notes</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_timeoff_requests as $timeoff) {
				$requested = new DateTime($timeoff['entered_date']);
				$from = new DateTime($timeoff['from_datetime']);
				if($timeoff['to_datetime']) {
					$to = new DateTime($timeoff['to_datetime']);
				}
				$notes = str_replace("\r", '', $timeoff['notes']);
				$notes = explode("\n\n", $notes);
				if($timeoff['status'] == -2) {
					$status_text = 'DENIED';
					$status_class = 'denied';
				} else if($timeoff['status'] == -1) {
					$status_text = 'CANCELED BY USER';
					$status_class = 'canceled';
				} else if($timeoff['status'] == 0) {
					$status_text = 'PENDING APPROVAL';
					$status_class = 'pending';
				} else if($timeoff['status'] == 1) {
					$status_text = 'APPROVED';
					$status_class = 'approved';
				}
				?>
				<tr class="request <?php print $status_class;?>" timesheet_id="<?php print htmlentities($timeoff['timesheet_id'], ENT_QUOTES);?>">
					<td><?php print $status_text;?></td>
					<td><?php print $requested->format('Y-m-d');?></td>
					<td><?php print htmlentities($timeoff['entered_by']);?></td>
					<td><?php print htmlentities($timeoff['initials']);?></td>
					<td><?php print htmlentities($timeoff['first_name']);?></td>
					<td><?php print htmlentities($timeoff['last_name']);?></td>
					<td><?php print htmlentities($timeoff['reason']);?></td>
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
					<td>
						<div class="notes-container"><?php
							foreach($notes as $note) {
								$note = trim($note);
								if($note) {
									?><div class="notes"><?php print htmlentities($note);?></div><?php
								}
							}
						?></div>
						<div class="input-append input-block-level">
							<input type="text" name="notes" />
							<button class="btn add-notes" type="button">Add</button>
						</div>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
$(document).off('click', '#pendingrequests-container .action-approve, #pendingrequests-container .action-deny');
$(document).on('click', '#pendingrequests-container .action-approve, #pendingrequests-container .action-deny', function(event) {
	var $button = $(this);
	if($button.hasClass('action-approve')) {
		var url = BASE_URI + '/dashboard/supervisor/timesheets/timeoff/pending/approve';
	} else if($button.hasClass('action-deny')) {
		var url = BASE_URI + '/dashboard/supervisor/timesheets/timeoff/pending/deny';
	}
	var $request = $button.closest('.request');
	var timesheet_id = $request.attr('timesheet_id');
	var data = {
		'timesheet_id': timesheet_id
	};
	$.ajax({
		'url': url,
		'data': data,
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
			$request.slideUp('fast', function(event) {
				$request.remove();
			});
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
