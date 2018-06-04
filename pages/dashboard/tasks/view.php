<?php

$session->ensureLogin();

$grab_task = $db->query("
	SELECT
		tasks.task_id,
		tasks.subject,
		tasks.added_on,
		tasks.description,
		tasks.status,
		tasks.priority,
		tasks.due_on,
		assigner.login_id AS assigner_login_id,
		assigner.first_name AS assigner_first_name,
		assigner.last_name AS assigner_last_name
		" . (ERP_SYSTEM === 'PRO' ? ', assigner.initials AS assigner_initials' : Null) . "
	FROM
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".tasks
	INNER JOIN
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins AS assigner
		ON
		assigner.login_id = tasks.assignedby_login_id
	WHERE
		tasks.task_id = " . $db->quote($_POST['task_id']) . "
		" . (ERP_SYSTEM === 'PRO' ? Null : "AND assigner.account_id = " . $db->quote(ACCOUNT_ID)) . "
		AND
		tasks.account_id = " . $db->quote(ACCOUNT_ID) . "
");
$task = $grab_task->fetch();

$grab_assignees = $db->query("
	SELECT
		logins.login_id,
		logins.first_name,
		assigner.first_name AS assigner_first_name,
		logins.last_name,
		task_assignees.added_on,
		assigner.last_name AS assigner_last_name
		" . (ERP_SYSTEM === 'PRO' ? ', logins.initials, assigner.initials AS assigner_initials' : Null) . "
	FROM
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".task_assignees
	LEFT JOIN
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins
		ON
		task_assignees.login_id = logins.login_id
	LEFT JOIN
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins AS assigner
		ON
		assigner.login_id = task_assignees.assignedby_login_id
	WHERE
		" . (ERP_SYSTEM === 'PRO' ? Null : "logins.account_id = " . $db->quote(ACCOUNT_ID) . " AND ") . "
		" . (ERP_SYSTEM === 'PRO' ? Null : "assigner.account_id = " . $db->quote(ACCOUNT_ID) . " AND ") . "
		task_assignees.task_id = " . $db->quote($task['task_id']) . "
	ORDER BY
		" . (ERP_SYSTEM === 'PRO' ? 'logins.initials' : 'logins.last_name, logins.first_name') . "
");

$grab_notes = $db->query("
	SELECT
		task_entries.task_entry_id,
		task_entries.added_on,
		task_entries.description,
		logins.first_name,
		logins.last_name
		" . (ERP_SYSTEM === 'PRO' ? ', logins.initials' : Null) . "
	FROM
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".tasks
	INNER JOIN
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".task_entries
		ON
		task_entries.task_id = tasks.task_id
	INNER JOIN
		" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins
		ON
		logins.login_id = task_entries.login_id
	WHERE
		tasks.task_id = " . $db->quote($task['task_id']) . "
		" . (ERP_SYSTEM === 'PRO' ? Null : "AND logins.account_id = " . $db->quote(ACCOUNT_ID)) . "
		AND
		tasks.account_id = " . $db->quote(ACCOUNT_ID) . "
	ORDER BY
		task_entries.added_on ASC
");

$tasks_obj = new \PM\Tasks();
$statuses = $tasks_obj->getStatuses();

?>

<style type="text/css">
	#edit-task-overlay .notes .note .note-body {
		color:#999;
		padding:12px 0 12px 24px;
	}
	#edit-task-overlay .task-box {
		margin:16px;
		padding:24px;
		vertical-align:middle;
		display:inline-block;
		text-align:center;
		position:relative;
	}
	#edit-task-overlay .task-box .task-box-title {
		vertical-align:middle;
		display:block;
		font-size:200%;
	}
	#edit-task-overlay .task-box .task-box-value {
		vertical-align:middle;
		display:block;
		padding:8px;
		font-weight:bold;
	}
	#edit-task-overlay .task-box .task-box-edit {
		display:none;
		font-size:225%;
		position:absolute;
		top:0;
		right:0;
		color:#00f;
		cursor:pointer;
	}
	#edit-task-overlay .task-box:hover .task-box-edit {
		display:block;
	}
</style>

<div id="edit-task-overlay">
	<input type="hidden" name="task_id" value="<?php print htmlentities($task['task_id'], ENT_QUOTES);?>" />

	<h2><?php print htmlentities($task['subject']);?></h2>
	Created by <?php print htmlentities(ERP_SYSTEM === 'PRO' ? $task['assigner_initials'] : $task['assigner_first_name'] . ' ' . $task['assigner_last_name']);?> on <?php print date('m/d/y h:i:sa', strtotime($task['added_on']));?>

	<br />
	<div class="task-box">
		<i class="fa fa-pencil task-box-edit edit-priority"></i>
		<div class="task-box-title">Priority</div>
		<div class="task-box-value"><?php print htmlentities($task['priority']);?></div>
	</div>
	<div class="task-box">
		<i class="fa fa-pencil task-box-edit edit-status"></i>
		<div class="task-box-title">Status</div>
		<div class="task-box-value"><?php
			if(!empty($statuses[$task['status']])) {
				print htmlentities($statuses[$task['status']]);
			} else {
				print htmlentities($task['status']);
			}
		?></div>
	</div>

	<br />
	<br />
	<fieldset>
		<legend>Task Description</legend>
		<?php print str_replace("\r", '', str_replace("\n", '<br />', htmlentities($task['description'])));?>
	</fieldset>
	<br />
	<br />

	<table style="width:100%;">
		<tbody>
			<tr>
				<td width="40%" style="vertical-align:top;">
					<fieldset style="padding-right:48px;">
						<legend>Assigned To</legend>
						<table>
							<thead>
								<tr>
									<th></th>
									<th>Assigned On</th>
									<th>Assigned By</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach($grab_assignees as $assignee) {
									?>
									<tr>
										<td><?php print htmlentities(ERP_SYSTEM === 'PRO' ? $assignee['initials'] : $assignee['first_name'] . ' ' . $assignee['last_name']);?></td>
										<td><?php print date('m/d/y h:i:sa', strtotime($assignee['added_on']));?></td>
										<td><?php print htmlentities(ERP_SYSTEM === 'PRO' ? $assignee['assigner_initials'] : $assignee['assigner_first_name'] . ' ' . $assignee['assigner_last_name']);?></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
					</fieldset>
				</td>
				<td width="60%" style="vertical-align:top;">
					<form id="edit-task-notes-form" method="POST" action="<?php print BASE_URI;?><?php print ERP_SYSTEM === 'PRO' ? '/dashboard' : Null;?>/tasks/notes/create">
						<input type="hidden" name="task_id" value="<?php print htmlentities($task['task_id'], ENT_QUOTES);?>" />
						<fieldset>
							<legend>Task Log & Notes</legend>
							<div class="notes">
								<?php
								$notes_exist = False;
								foreach($grab_notes as $note) {
									if(!$notes_exist) {
										$notes_exist = True;
									}
									?>
									<div class="note">
										<div class="who-when">
											<?php print htmlentities(ERP_SYSTEM === 'PRO' ? $note['initials'] : $note['first_name'] . ' ' . $note['last_name']);?> on <?php print date('m/d/y h:i:sa', strtotime($note['added_on']));?>
										</div>
										<div class="note-body"><?php print str_replace("\r", '', str_replace("\n", '<br />', htmlentities($note['description'])));?></div>
									</div>
									<?php
								}
								if(!$notes_exist) {
									?><i class="nonotes">Nothing here yet, time to get busy!</i><?php
								}
								?>
							</div>
						</fieldset>
						<br />
						<fieldset>
							<legend>Add a Note</legend>
							<textarea name="note" style="width:100%;height:130px;"></textarea>
							<br />
							<button type="submit" class="btn btn-primary">Submit</button>
						</fieldset>
					</form>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$(document).off('submit', '#edit-task-notes-form');
	$(document).on('submit', '#edit-task-notes-form', function(event) {
		var form = this;
		var $form = $(form);
		var data = new FormData(form);
		
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
			'url': $form.attr('action'),
			'method': 'POST',
			'data': data,
			'processData': false,
			'contentType': false,
			'dataType': 'json',
			'enctype': 'multipart/form-data',
			'success': function(response) {
				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right...');
					}
					return;
				}
				var $notes = $form.find('.notes');
				var $note = $('<div class="note">').append(
					$('<div class="who-when">').text(response.note.whowhen),
					$('<div class="note-body">').append(response.note.text)
				);
				$notes.append($note);
				$form.find('.nonotes').remove();
			},
			'complete': function() {
				$overlay.fadeOut('fast', function() {
					$overlay.remove();
				});
			}
		});

		return false;
	});
	
	$(document).off('click', '#edit-task-overlay .edit-priority, #edit-task-overlay .edit-status');
	$(document).on('click', '#edit-task-overlay .edit-priority, #edit-task-overlay .edit-status', function(event) {
		alert('Not fixed yet');
	});
</script>
