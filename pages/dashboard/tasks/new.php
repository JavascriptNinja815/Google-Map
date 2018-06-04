<?php

$session->ensureLogin();

?>

<style type="text/css">
	#new-task-overlay textarea {
		width:350px;
		height:160px;
	}
	#new-task-overlay td {
		vertical-align:top;
	}
</style>

<form id="new-task-overlay">
	<h2>New Task</h2>
	<table>
		<tbody>
			<tr>
				<td>Task Name</td>
				<td><input type="text" name="subject" value="" /></td>
			</tr>
			<tr>
				<td>Priority</td>
				<td><select name="priority">
					<option value="1">1 (highest)</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
					<option value="6">6</option>
					<option value="7">7</option>
					<option value="8">8</option>
					<option value="9">9 (lowest)</option>
				</select></td>
			</tr>
			<tr>
				<td>Due Date</td>
				<td><input type="date" name="duedate" value="<?php print date('Y-m-d', strtotime('+1 week'));?>" /></td>
			</tr>
			<tr>
				<td>Status</td>
				<td><select name="status">
					<?php
					$grab_statuses = $db->query("
						SELECT
							task_statuses.status,
							task_statuses.name
						FROM
							" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".task_statuses
						WHERE
							task_statuses.account_id = " . $db->quote(ACCOUNT_ID) . "
						ORDER BY
							task_statuses.status
					");
					foreach($grab_statuses as $status) {
						?><option value="<?php print htmlentities($status['status'], ENT_QUOTES);?>"><?php print $status['name'];?></option><?php
					}
					?>
				</select></td>
			</tr>
			<tr>
				<td>Assigned To</td>
				<td>
					<select name="assigned_login_id">
						<option value="<?php print htmlentities($session->login['login_id'], ENT_QUOTES);?>">Myself</option>
						<?php
						if(ERP_SYSTEM === 'PRO') {
							if($session->hasRole('Administration')) {
								$where = '';
							} else if($session->hasRole('Supervisor')) {
								$initials = [];
								foreach($session->getPermissions('Supervisor', 'timesheets') as $initial) {
									$initials[] = $db->quote($initial);
								}
								$initials = implode(', ', $initials);
								$where = "AND logins.initials IN (" . $initials . ")";
							} else {
								$where = 'AND 1 = 0'; // Return No One
							}
							$grab_logins = $db->query("
								SELECT
									logins.login_id,
									logins.initials,
									logins.first_name,
									logins.last_name
								FROM
									" . DB_SCHEMA_INTERNAL . ".logins
								WHERE
									logins.login_id != " . $db->quote($session->login['login_id']) . "
									" . $where . "
								ORDER BY
									logins.initials
							");
							foreach($grab_logins as $login) {
								?><option value="<?php print htmlentities($login['login_id'], ENT_QUOTES);?>"><?php print htmlentities($login['initials'] . ' - ' . $login['first_name'] . ' ' . $login['last_name']);?></option><?php
							}
						} else {
							$grab_logins = $db->query("
								SELECT
									logins.login_id,
									logins.first_name,
									logins.last_name
								FROM
									public.logins
								WHERE
									logins.account_id = " . $db->quote(ACCOUNT_ID) . "
									AND
									logins.login_id != " . $db->quote($session->login['login_id']) . "
								ORDER BY
									logins.last_name,
									logins.first_name
							");
							foreach($grab_logins as $login) {
								?><option value="<?php print htmlentities($login['login_id'], ENT_QUOTES);?>"><?php print htmlentities($login['first_name'] . ' ' . $login['last_name']);?></option><?php
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>Description</td>
				<td><textarea name="description"></textarea></td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="2">
					<button type="submit" class="btn btn-primary">Add Task</button>
				</td>
			</tr>
		</tfoot>
	</table>
</form>

<script type="text/javascript">
	$(document).off('submit', 'form#new-task-overlay');
	$(document).on('submit', 'form#new-task-overlay', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);

		var $page_overlay = $form.closest('.overlayz');

		// Display the loading overlay.
		var $overlay = createOverlay({
			'width': '500px',
			'height': '350px'
		});
		$overlay.fadeIn('fast');
		var $overlay_body = $overlay.find('.overlayz-body');
		$overlay_body.empty().append(
			$loading_prototype.clone()
		);

		$.ajax({
			'url': window.location.pathname + '/create',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'method': 'POST',
			'data': data,
			'dataType': 'JSON',
			'success': function(response) {
				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right...');
					}
					return;
				}
				$page_overlay.fadeOut('fast', function() {
					$page_overlay.remove();
				});

				window.location.reload();
			},
			'complete': function() {
				// Close the loading overlay.
				$overlay.fadeOut('fast', function() {
					$overlay.remove();
				});
			}
		});

		return false;
	});

	$('#task-overlay :input[name="name"]').focus();
</script>
