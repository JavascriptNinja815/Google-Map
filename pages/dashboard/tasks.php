<?php

$session->ensureLogin();

$args = array(
	'title' => 'Tasks',
	'breadcrumbs' => array(
		'Tasks' => BASE_URI . '/tasks'
	)
);

$tasks = new \PM\Tasks();
// Status
if(isset($_REQUEST['status']) && strlen($_REQUEST['status']) > 0) {
	$tasks->filterByStatus($_REQUEST['status']);
}
// Priority
if(isset($_REQUEST['priority']) && strlen($_REQUEST['priority']) > 0) {
	$tasks->filterByPriority($_REQUEST['priority']);
}
// Login ID
if(isset($_REQUEST['login_id']) && strlen($_REQUEST['login_id']) > 0) {
	$tasks->filterByAssignee($_REQUEST['login_id']);
} else if(!isset($_REQUEST['login_id'])) {
	$tasks->filterByAssignee($session->login['login_id']);
}
if(!isset($_REQUEST['archive'])) {
	$tasks->filterByOmitArchive();
}

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#tasks-container {
		padding:12px;
	}
</style>

<form class="content" id="tasks-container" action="<?php print BASE_URI;?><?php print ERP_SYSTEM === 'PRO' ? '/dashboard' : Null;?>/tasks">
	<h1>Tasks</h1>
	<fieldset>
		<legend>Find Tasks</legend>
		<table>
			<tbody>
				<tr>
					<th>Assigned To:</th>
					<td>
						<select name="login_id">
							<option value="" <?php print isset($_REQUEST['login_id']) && $_REQUEST['login_id'] == '' ? 'selected' : Null;?>>Anyone</option>
							<option value="<?php print htmlentities($session->login['login_id'], ENT_QUOTES);?>" <?php print !isset($_REQUEST['login_id']) || ($_REQUEST['login_id'] == $session->login['login_id']) ? 'selected' : Null;?>>Only Me</option>
							<?php
							if(ERP_SYSTEM === 'Neuron' || (ERP_SYSTEM === 'PRO' && ($session->hasRole('Administration') || $session->hasRole('Supervisor')))) {
								?>
								<optgroup label="Others">
								<?php
								$where = '';
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
								}
								$grab_logins = $db->query("
									SELECT
										logins.login_id,
										logins.first_name,
										logins.last_name
										" . (ERP_SYSTEM === 'PRO' ? ', logins.initials' : Null) . "
									FROM
										" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins
									WHERE
										" . (ERP_SYSTEM === 'PRO' ? Null : "logins.account_id = " . $db->quote(ACCOUNT_ID) . " AND ") . "
										logins.login_id != " . $db->quote($session->login['login_id']) . "
										" . ($where ? $where : Null) . " 
									ORDER BY
										" . (ERP_SYSTEM === 'PRO' ? 'logins.initials' : 'logins.last_name, logins.first_name') . "
								");
								foreach($grab_logins as $login) {
									?><option value="<?php print htmlentities($login['login_id'], ENT_QUOTES);?>" <?php print !empty($_REQUEST['login_id']) && $_REQUEST['login_id'] == $login['login_id'] ? 'selected' : Null;?>><?php print htmlentities((ERP_SYSTEM === 'PRO' ? $login['initials'] . ' - ' : Null) . $login['first_name'] . ' ' . $login['last_name']);?></option><?php
								}
								?>
								</optgroup>
								<?php
							}?>
						</select>
					</td>
				</tr>
				<tr>
					<th>Priority:</th>
					<td>
						<select name="priority">
							<option value="" <?php print empty($_REQUEST['priority']) ? 'selected' : Null;?>>Any</option>
							<option value="1" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 1 ? 'selected' : Null;?>>1 (highest)</option>
							<option value="2" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 2 ? 'selected' : Null;?>>2</option>
							<option value="3" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 3 ? 'selected' : Null;?>>3</option>
							<option value="4" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 4 ? 'selected' : Null;?>>4</option>
							<option value="5" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 5 ? 'selected' : Null;?>>5</option>
							<option value="6" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 6 ? 'selected' : Null;?>>6</option>
							<option value="7" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 7 ? 'selected' : Null;?>>7</option>
							<option value="8" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 8 ? 'selected' : Null;?>>8</option>
							<option value="9" <?php print !empty($_REQUEST['priority']) && $_REQUEST['priority'] == 9 ? 'selected' : Null;?>>9 (lowest)</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>Status:</th>
					<td>
						<select name="status">
							<option value="" <?php print empty($_REQUEST['status']) ? 'selected' : Null;?>>Any</option>
							<?php
							foreach($tasks->getStatuses() as $status_code => $name) {
								?><option value="<?php print htmlentities($status_code, ENT_QUOTES);?>" <?php print isset($_REQUEST['status']) && $_REQUEST['status'] === (string)$status_code ? 'selected' : Null;?>><?php print htmlentities($name);?></option><?php
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th>Include Archived:</th>
					<td>
						<input type="checkbox" name="archive" value="1" <?php print isset($_REQUEST['archive']) && $_REQUEST['archive'] == 1 ? 'checked' : Null;?> />
					</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td></td>
					<td>
						&nbsp;<br /><button type="submit" class="btn btn-primary">List Tasks</button>
					</td>
				</tr>
			</tfoot>
		</table>
	</fieldset>

	<hr />

	<fieldset>
		<legend>Tasks</legend>
		<button type="button" class="add_task btn btn-primary">Add Task</button>
		<br />
		<br />
		<table class="table table-striped table-hover columns-sortable columns-filterable headers-sticky">
			<thead>
				<tr>
					<!--th></th-->
					<th class="filterable sortable">Task ID</th>
					<th class="filterable sortable">Priority</th>
					<th class="filterable sortable">Status</th>
					<th class="filterable sortable">Task Name</th>
					<th class="filterable sortable">Assigned To</th>
					<th class="filterable sortable">Created On</th>
					<th class="filterable sortable">Created By</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$statuses = $tasks->getStatuses();
				$has_tasks = False;
				foreach($tasks as $task) {
					$has_tasks = True;
					?>
					<tr class="task" task_id="<?php print $task['task_id'];?>">
						<!--td>
							<i class="fa fa-remove action action-remove"></i>
						</td-->
						<td class="id"><?php print $task['task_id'];?></td>
						<td class="priority"><?php print htmlentities($task['priority']);?></td>
						<td class="status"><?php
							
							if(!empty($statuses[$task['status']])) {
								print htmlentities($statuses[$task['status']]);
							} else {
								print htmlentities($task['status']);
							}
						?></td>
						<td class="subject">
							<div class="overlayz-link" overlayz-url="<?php print BASE_URI;?><?php print ERP_SYSTEM === 'PRO' ? '/dashboard' : Null;?>/tasks/view" overlayz-data="<?php print htmlentities(json_encode(['task_id' => $task['task_id']]), ENT_QUOTES);?>" overlayz-response-type="html">
								<i class="fa fa-search action action-view"></i>
								<?php print htmlentities($task['subject']);?>
							</div>
						</td>
						<td class="assigned-to">
							<?php
							$grab_assignees = $db->query("
								SELECT
									logins.first_name,
									logins.last_name
									" . (ERP_SYSTEM === 'PRO' ? ', logins.initials' : Null) . "
								FROM
									" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".logins
								INNER JOIN
									" . (ERP_SYSTEM === 'PRO' ? DB_SCHEMA_INTERNAL : 'public') . ".task_assignees
									ON
									task_assignees.login_id = logins.login_id
								WHERE
									" . (ERP_SYSTEM === 'PRO' ? Null : "logins.account_id = " . $db->quote(ACCOUNT_ID) . " AND ") . "
									task_assignees.task_id = " . $db->quote($task['task_id']) . "
								ORDER BY
									" . (ERP_SYSTEM === 'PRO' ? 'logins.initials' : 'logins.last_name, logins.first_name') . "
							");
							foreach($grab_assignees as $assignee) {
								?>
								<span class="assignee"><?php print htmlentities(ERP_SYSTEM === 'PRO' ? $assignee['initials'] : $assignee['first_name'] . ' ' . $assignee['last_name']);?></span>
								<?php
							}
							?>
						</td>
						<td class="created"><?php print date('m/d/y h:ia', strtotime($task['added_on']));?></td>
						<td class="assigned-from"><?php print htmlentities(ERP_SYSTEM === 'PRO' ? $task['assigner_initials'] : $task['assigner_first_name'] . ' ' . $task['assigner_last_name']);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</fieldset>

	<?php
	if($has_tasks) {
		?><button type="button" class="add_task btn btn-primary">Add Task</button><?php
	}
	?>
</div>

<script type="text/javascript">
	$(document).off('click', 'button.add_task');
	$(document).on('click', 'button.add_task', function(event) {
		var $overlay = createOverlay({
			'width': '540px',
			'height': '400px'
		});
		$overlay.fadeIn('fast');
		var $overlay_body = $overlay.find('.overlayz-body');
		$overlay_body.empty().append(
			$loading_prototype.clone()
		);

		$.ajax({
			'url': window.location.pathname + '/new',
			'success': function(html) {
				$overlay_body.html(html);
				$overlay_body.find('input[name="name"]').focus();
			}
		});
	});

	$(document).off('click', '.tasks-container .task .action-view');
	$(document).on('click', '.tasks-container .task .action-view', function(event) {
		var $task = $(this).closest('.task');
		var task_id = $task.attr('task_id');
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
			'url': window.location.pathname + '/view',
			'type': 'POST',
			'data': {
				'task_id': task_id
			},
			'success': function(html) {
				$overlay_body.html(html);
			}
		});
	});

	/**
	$(document).off('click', '#tasks-container .task .action-remove');
	$(document).on('click', '#tasks-container .task .action-remove', function(event) {
		var $task = $(this).closest('.task');
		var task_id = $task.attr('task_id');
		var name = $task.find('.name').text().trim();
		if(confirm('Are you sure you want to delete this task?')) {
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
				'url': BASE_URI + '/tasks/delete',
				'method': 'POST',
				'dataType': 'json',
				'data': {
					'task_id': task_id
				},
				'succesrs': function(response) {
					if(response.success) {
						$task.remove();
					} else if(response.message) {
						alert(response.message);
					} else {
						alert('Something didnt go right');
					}
					$overlay.fadeOut('fast', function() {
						$overlay.remove();
					});
				}
			});
		}
	});
	**/
</script>

<?php Template::Render('footer', 'account');
