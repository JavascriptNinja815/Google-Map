<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Administration');

$errors = array();
if(isset($_POST['submit'])) {
	// Handle saving operations.
	require('save.php');
}

$args = array(
	'title' => 'Edit Login',
	'breadcrumbs' => array(
		'Administration' => BASE_URI . '/dashboard/administration',
		'Logins' => BASE_URI . '/dashboard/administration/logins',
		'Edit Login' => BASE_URI . '/dashboard/administration/logins/edit?login_id=' . $_GET['login_id'],
	)
);

Template::Render('header', $args, 'account');

$grab_login = $db->query("
	SELECT
		logins.login_id,
		logins.login,
		logins.first_name,
		logins.last_name,
		logins.initials,
		logins.status,
		logins.email_password,
		CONVERT(varchar(10), logins.birthday, 120) AS birthday,
		CONVERT(varchar(10), logins.hire_date, 120) AS hire_date,
		logins.avail_sick_hours,
		logins.avail_vacation_hours,
		logins.location_id,
		logins.label_printer_id
	FROM
		" . DB_SCHEMA_INTERNAL . ".logins
	WHERE
		logins.login_id = " . $db->quote($_GET['login_id']) . "
");
$login = $grab_login->fetch();

$grab_login_roles = $db->query("
	SELECT
		roles.role_id,
		roles.role
	FROM
		" . DB_SCHEMA_INTERNAL . ".login_roles
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".roles
		ON
		roles.role_id = login_roles.role_id
	WHERE
		login_roles.login_id = " . $db->quote($login['login_id']) . "
");
$login['roles'] = array();
foreach($grab_login_roles as $login_role) {
	$login['roles'][$login_role['role_id']] = array(
		'role' => $login_role['role'],
		'permissions' => array()
	);
}

$grab_permissions = $db->query("
	SELECT
		login_role_permissions.permission_id,
		login_role_permissions.permission_type,
		login_role_permissions.permission_value,
		login_role_permissions.role_id
	FROM
		" . DB_SCHEMA_INTERNAL . ".login_role_permissions
	WHERE
		login_role_permissions.login_id = " . $db->quote($login['login_id']) . "
");
foreach($grab_permissions as $permission) {
	if(!isset($login['roles'][$permission['role_id']]['permissions'][$permission['permission_type']])) {
		$login['roles'][$permission['role_id']]['permissions'][$permission['permission_type']] = array();
	}
	$login['roles'][$permission['role_id']]['permissions'][$permission['permission_type']][] = $permission['permission_value'];
}

$grab_roles = $db->query("
	SELECT
		roles.role_id,
		roles.role
	FROM
		" . DB_SCHEMA_INTERNAL . ".roles
	ORDER BY
		roles.role
");

$grab_login_companies = $db->query("
	SELECT
		login_companies.company_id
	FROM
		" . DB_SCHEMA_INTERNAL . ".login_companies
	WHERE
		login_companies.login_id = " . $db->quote($login['login_id']) . "
");
$companies = array();
foreach($grab_login_companies as $company) {
	$companies[] = $company['company_id'];
}

$grab_goals = $db->prepare("
	SELECT
		goal_id,
		start_date,
		end_date,
		goal,
		title,
		type
	FROM
		" . DB_SCHEMA_INTERNAL . ".goals
	WHERE
		login_id = " . $db->quote($login['login_id']) . "
	ORDER BY
		type,
		title
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed required for retrieving rowCount.
$grab_goals->execute();

$companies = [];
$grab_companies = $db->query("
	SELECT
		companies.company_id,
		companies.dbname,
		companies.company
	FROM
		" . DB_SCHEMA_INTERNAL . ".companies
	ORDER BY
		companies.company
");
foreach($grab_companies as $company) {
	$companies[] = [
		'company_id' => $company['company_id'],
		'dbname' => $company['dbname'],
		'company' => $company['company']
	];
}

?>

<style type="text/css">
	#edit-login-container .role .permissions-container {
		padding-left:32px;
	}
</style>

<?php
if(isset($_GET['saved'])) {
	?>
	<div class="notification-container notification-success">
		<div class="notification">Changes successfully saved.</div>
	</div>
	<?php
}
?>

<div class="padded">
	<form method="post" id="edit-login-container" class="form-horizontal">
		<input type="hidden" name="login_id" value="<?php print htmlentities($_GET['login_id'], ENT_QUOTES);?>" />

		<span class="fa-stack fa-lg delete delete-login">
			<i class="fa fa-square fa-stack-2x delete-bg"></i>
			<i class="fa fa-trash-o fa-stack-1x delete-fg"></i>
		</span>

		<fieldset>
			<legend>General</legend>

			<div class="control-group">
				<label for="login-login" class="control-label">Login</label>
				<div class="controls">
					<input type="text" id="login-login" name="login" class="span4" value="<?php print htmlentities($login['login'], ENT_QUOTES);?>" required>
					<span class="text-error login-login-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-status" class="control-label">Status</label>
				<div class="controls">
					<select name="status" id="login-status" class="span4">
						<option value="1"<?php $login['status'] == 1 ? print ' selected="selcted"' : Null;?>>Enabled</option>
						<option value="0"<?php $login['status'] == 0 ? print ' selected="selcted"' : Null;?>>Disabled</option>
					</select>
					<br />
					<span class="status-disabled-warning text-warning hidden"><i><small>Setting to "Disabled" will log the user out and prevent any future logins.</small></i></span>
					<span class="text-error login-status-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-password" class="control-label">Password</label>
				<div class="controls">
					<input type="password" name="password" id="login-password" />
					<br />
					<span class="muted login-password-info"><i><small>Leave blank to retain current password.</small></i></span>
					<span class="text-error login-password-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-first_name" class="control-label">First Name</label>
				<div class="controls">
					<input type="text" name="first_name" id="login-first_name" class="span4" value="<?php print htmlentities($login['first_name'], ENT_QUOTES);?>" />
					<span class="text-error login-first_name-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-last_name" class="control-label">Last Name</label>
				<div class="controls">
					<input type="text" name="last_name" id="login-last_name" class="span4" value="<?php print htmlentities($login['last_name'], ENT_QUOTES);?>" />
					<span class="text-error login-last_name-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-initials" class="control-label">Initials</label>
				<div class="controls">
					<input type="text" name="initials" id="login-initials" class="span1" value="<?php print htmlentities($login['initials'], ENT_QUOTES);?>" />
					<span class="text-error login-initials-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-emailpassword" class="control-label">E-Mail Password</label>
				<div class="controls">
					<input type="password" name="email_password" id="login-emailpassword" class="span4" value="<?php print htmlentities($login['email_password'], ENT_QUOTES);?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="login-birthday" class="control-label">Birthday</label>
				<div class="controls">
					<input type="date" name="birthday" id="login-birthday" class="span4" value="<?php print htmlentities($login['birthday'], ENT_QUOTES);?>" />
					<span class="text-error login-birthday-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-hire_date" class="control-label">Hire Date</label>
				<div class="controls">
					<input type="date" name="hire_date" id="login-hire_date" class="span4" value="<?php print htmlentities($login['hire_date'], ENT_QUOTES);?>" />
					<span class="text-error login-hire_date-error"></span>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Vacation & Sick Days</legend>
			<div class="control-group">
				<label for="login-avail_vacation_hours" class="control-label">Available Vacation Hours</label>
				<div class="controls">
					<input type="text" name="avail_vacation_hours" id="login-avail_vacation_hours" class="span1" value="<?php print htmlentities($login['avail_vacation_hours'], ENT_QUOTES);?>" />
					<span class="text-error login-avail_vacation_hours-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label for="login-avail_sick_hours" class="control-label">Available Sick Hours</label>
				<div class="controls">
					<input type="text" name="avail_sick_hours" id="login-avail_sick_hours" class="span1" value="<?php print htmlentities($login['avail_sick_hours'], ENT_QUOTES);?>" />
					<span class="text-error login-avail_sick_hours-error"></span>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Location-Specific</legend>
			<div class="control-group">
				<label class="control-label">Companies</label>
				<div class="controls companies-container">
					<?php
					foreach($companies as $company) {
						$check_existing = $db->query("
							SELECT
								login_companies.entry_id
							FROM
								" . DB_SCHEMA_INTERNAL . ".login_companies
							WHERE
								login_companies.login_id = " . $db->quote($login['login_id']) . "
								AND
								login_companies.company_id = " . $db->quote($company['company_id']) . "
						");
						$existing = $check_existing->fetch();
						?><label><input type="checkbox" name="companies[<?php print $company['company_id'];?>]" value="<?php print $company['company_id'];?>" <?php print !empty($existing) ? 'checked' : Null;?> /> <?php print htmlentities($company['company']);?></label><?php
					}
					?>
				</div>
			</div>
			<div class="control-group">
				<label for="login-" class="control-label">Locations</label>
				<div class="controls locations-container">
					<?php
					foreach($companies as $company) {
						?>
						<div>
							<b><?php print htmlentities($company['company']);?></b>
							<blockquote>
								<?php
								$grab_locations = $db->query("
									SELECT
										LTRIM(RTRIM(somast.defloc)) AS defloc
									FROM
										" . $company['dbname'] . ".somast
									WHERE
										somast.defloc != 'DROPSH'
									GROUP BY
										somast.defloc
									ORDER BY
										somast.defloc
								");
								foreach($grab_locations as $location) {
									$check_existing = $db->query("
										SELECT
											login_locations.entry_id
										FROM
											" . DB_SCHEMA_INTERNAL . ".login_locations
										WHERE
											login_locations.login_id = " . $db->quote($login['login_id']) . "
											AND
											login_locations.location = " . $db->quote($location['defloc']) . "
											AND
											login_locations.company_id = " . $db->quote($company['company_id']) . "
									");
									$existing = $check_existing->fetch();
									?>
									<label>
										<input type="checkbox" name="locations[<?php print htmlentities($company['company_id'], ENT_QUOTES);?>][]" value="<?php print htmlentities($location['defloc']);?>" <?php print !empty($existing) ? 'checked' : Null;?> /> <?php print htmlentities($location['defloc']);?>
									</label>
									<?php
								}
								?>
							</blockquote>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<div class="control-group">
				<label for="login-" class="control-label">Default Label Printer</label>
				<div class="controls">
					<select id="login-printer_id" name="label_printer_id" class="span5">
						<?php
						foreach($companies as $company) {
							$grab_locations = $db->query("
								SELECT
									LTRIM(RTRIM(somast.defloc)) AS defloc
								FROM
									" . $company['dbname'] . ".somast
								WHERE
									somast.defloc != 'DROPSH'
								GROUP BY
									somast.defloc
								ORDER BY
									somast.defloc
							");
							foreach($grab_locations as $location) {
								?>
								<optgroup label="<?php print htmlentities($company['company'] . ' - ' . $location['defloc'], ENT_QUOTES);?>">
									<?php
									$grab_printers = $db->query("
										SELECT
											printers.printer_id,
											printers.printer
										FROM
											" . DB_SCHEMA_INTERNAL . ".printers
										WHERE
											printers.company_id = " . $db->quote($company['company_id']) . "
											AND
											printers.location_id = " . $db->quote($location['defloc']) . "
									");
									foreach($grab_printers as $printer) {
										?><option value="<?php print htmlentities($printer['printer_id'], ENT_QUOTES);?>" <?php print $login['label_printer_id'] == $printer['printer_id'] ? 'selected' : Null;?>><?php print htmlentities($printer['printer']);?></option><?php
									}
									?>
								</optgroup>
								<?php
								/**
								//<label>
								//	<input type="checkbox" name="locations[]" value="<?php print htmlentities($location['defloc']);?>" /> <?php print htmlentities($location['defloc']);?>
								//</label>**/
							}
						}
						?>
					</select>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<legend>Roles & Permissions</legend>

			<div class="control-group">
				<label class="control-label">Role(s)</label>
				<div class="controls">
					<?php
					foreach($grab_roles as $role) {
						$role_permissions = isset($login['roles'][$role['role_id']]) ? $login['roles'][$role['role_id']] : array();
						?>
						<div class="role">
							<label>
								<input type="checkbox" name="roles[<?php print htmlentities($role['role_id'], ENT_QUOTES);?>]" value="<?php print htmlentities($role['role_id'], ENT_QUOTES);?>"<?php isset($login['roles'][$role['role_id']]) ? print ' checked="checked"' : Null;?> /> <?php print htmlentities($role['role']);?>
							</label>
							<?php
							if($role['role_id'] == 2) { // SALES ROLE.
								?>
								<div class="permissions-container<?php isset($login['roles'][$role['role_id']]) ? Null : print ' hidden';?>">
									<fieldset>
										<legend><?php print htmlentities($role['role']);?> Permissions</legend>
										<div class="permission-container permission-view-orders">
											<?php
											$vieworder_permissions = isset($role_permissions['permissions']['view-orders']) ? $role_permissions['permissions']['view-orders'] : array();
											?>
											<div class="permission-title title">Orders Viewable</div>
											<div class="permission-checkall">[Check All]</div>
											<div class="permission-uncheckall">[Un-Check All]</div>
											<div class="permissions">
												<?php
													$grab_salespersons = $db->query("
														SELECT
															LTRIM(RTRIM(SOSLSM.salesmn)) AS salesman,
															LTRIM(RTRIM(SOSLSM.lname)) AS last_name,
															LTRIM(RTRIM(SOSLSM.fname)) AS first_name,
															LTRIM(RTRIM(SOSLSM.minit)) AS middle_initial
														FROM
															" . DB_SCHEMA_ERP . ".SOSLSM
														ORDER BY
															SOSLSM.salesmn
													");
													foreach($grab_salespersons as $sales_person) {
														?>
														<div class="permission">
															<label>
																<input type="checkbox" name="permissions[<?php print $role['role_id'];?>][view-orders][<?php print htmlentities($sales_person['salesman'], ENT_QUOTES);?>]" value="<?php print htmlentities($sales_person['salesman'], ENT_QUOTES);?>"<?php in_array($sales_person['salesman'], $vieworder_permissions) ? print ' checked="checked"' : Null;?> />
																<?php print htmlentities($sales_person['salesman']);?>:
																<?php print htmlentities($sales_person['first_name'] . ' ' . $sales_person['middle_initial'] . ' ' . $sales_person['last_name']);?>
															</label>
														</div>
														<?php
													}
												?>
											</div>
										</div>
										<div class="permission-container clear permission-edit-orders">
											<?php
											$editorder_permissions = isset($role_permissions['permissions']['edit-orders']) ? $role_permissions['permissions']['edit-orders'] : array();
											?>
											<div class="permission-title title">Orders Editable</div>
											<div class="permission-checkall">[Check All]</div>
											<div class="permission-uncheckall">[Un-Check All]</div>
											<div class="permissions">
												<?php
													$grab_salespersons = $db->query("
														SELECT
															LTRIM(RTRIM(SOSLSM.salesmn)) AS salesman,
															LTRIM(RTRIM(SOSLSM.lname)) AS last_name,
															LTRIM(RTRIM(SOSLSM.fname)) AS first_name,
															LTRIM(RTRIM(SOSLSM.minit)) AS middle_initial
														FROM
															" . DB_SCHEMA_ERP . ".SOSLSM
														ORDER BY
															SOSLSM.salesmn
													");
													foreach($grab_salespersons as $sales_person) {
														?>
														<div class="permission">
															<label>
																<input type="checkbox" name="permissions[<?php print $role['role_id'];?>][edit-orders][<?php print htmlentities($sales_person['salesman'], ENT_QUOTES);?>]" value="<?php print htmlentities($sales_person['salesman'], ENT_QUOTES);?>"<?php in_array($sales_person['salesman'], $editorder_permissions) ? print ' checked="checked"' : Null;?> />
																<?php print htmlentities($sales_person['salesman']);?>
																<?php print htmlentities($sales_person['first_name'] . ' ' . $sales_person['middle_initial'] . ' ' . $sales_person['last_name']);?>
															</label>
														</div>
														<?php
													}
												?>
											</div>
										</div>
									</fieldset>
								</div>
								<?php
							} else if($role['role_id'] == 11) { // SUPERVISOR ROLE
								?>
								<div class="permissions-container<?php isset($login['roles'][$role['role_id']]) ? Null : print ' hidden';?>">
									<fieldset>
										<legend><?php print htmlentities($role['role']);?> Permissions</legend>
										<div class="permission-container permission-view-orders">
											<?php
											$timesheet_permissions = [];
											if(isset($role_permissions['permissions']['timesheets'])) {
												$timesheet_permissions = $role_permissions['permissions']['timesheets'];
											}
											?>
											<div class="permission-title title">Timesheets</div>
											<div class="permission-checkall">[Check All]</div>
											<div class="permission-uncheckall">[Un-Check All]</div>
											<div class="permissions">
												<?php
													$grab_employees = $db->query("
														SELECT
															logins.login_id,
															logins.initials,
															logins.first_name,
															logins.last_name
														FROM
															" . DB_SCHEMA_INTERNAL . ".logins
														WHERE
															logins.login_id != " . $db->quote($session->login['login_id']) . "
														ORDER BY
															logins.initials
													");
													foreach($grab_employees as $employee) {
														?>
														<div class="permission">
															<label>
																<input type="checkbox" name="permissions[<?php print $role['role_id'];?>][timesheets][<?php print htmlentities($employee['login_id'], ENT_QUOTES);?>]" value="<?php print htmlentities($employee['login_id'], ENT_QUOTES);?>"<?php in_array($employee['login_id'], $timesheet_permissions) ? print ' checked="checked"' : Null;?> />
																<?php print htmlentities($employee['initials']);?>:
																<?php print htmlentities($employee['first_name'] . ' ' . $employee['last_name']);?>
															</label>
														</div>
														<?php
													}
												?>
											</div>
										</div>
										<div class="permission-container permission-view-orders">
											<?php
											$timesheet_permissions = [];
											if(isset($role_permissions['permissions']['tasks'])) {
												$timesheet_permissions = $role_permissions['permissions']['tasks'];
											}
											?>
											<div class="permission-title title">Tasks</div>
											<div class="permission-checkall">[Check All]</div>
											<div class="permission-uncheckall">[Un-Check All]</div>
											<div class="permissions">
												<?php
													$grab_employees = $db->query("
														SELECT
															logins.login_id,
															logins.initials,
															logins.first_name,
															logins.last_name
														FROM
															" . DB_SCHEMA_INTERNAL . ".logins
														WHERE
															logins.login_id != " . $db->quote($session->login['login_id']) . "
														ORDER BY
															logins.initials
													");
													foreach($grab_employees as $employee) {
														?>
														<div class="permission">
															<label>
																<input type="checkbox" name="permissions[<?php print $role['role_id'];?>][tasks][<?php print htmlentities($employee['login_id'], ENT_QUOTES);?>]" value="<?php print htmlentities($employee['login_id'], ENT_QUOTES);?>"<?php in_array($employee['login_id'], $timesheet_permissions) ? print ' checked="checked"' : Null;?> />
																<?php print htmlentities($employee['initials']);?>:
																<?php print htmlentities($employee['first_name'] . ' ' . $employee['last_name']);?>
															</label>
														</div>
														<?php
													}
												?>
											</div>
										</div>
									</fieldset>
								</div>
								<?php
							}
							?>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</fieldset>

		<div class="control-group">
			<div class="controls">
				<button class="btn btn-primary" type="submit">
					<i class="fa fa-plus fa-fw"></i>
					Apply Changes
				</button>
			</div>
		</div>
	</form>

	<fieldset class="goals-container">
		<legend>Goals</legend>
		<table>
			<thead>
				<tr>
					<th></th>
					<th>Type</th>
					<th>Title</th>
					<th>Goal</th>
					<th>From Date</th>
					<th>To Date</th>
				</tr>
			</thead>
			<tbody class="goals" login_id="<?php print htmlentities($_GET['login_id'], ENT_QUOTES);?>">
				<tr class="prototype goal-prototype goal goal-new hidden">
					<td>
						<i class="fa fa-edit edit hidden" title="Edit"></i>
						<i class="fa fa-times-circle delete hidden" title="Delete"></i>

						<i class="fa fa-check save" title="Save"></i>
						<i class="fa fa-times cancel" title="Cancel"></i>
					</td>

					<td class="type"></td>
					<td class="title"></td>
					<td class="amount"></td>
					<td class="start"></td>
					<td class="end"></td>
				</tr>
				<?php
				if($grab_goals->rowCount()) {
					?>
					<script type="text/javascript">
						$(function() {
							<?php
							foreach($grab_goals as $goal) {
								// Yes, we're calling a javascript function...
								?>
								render_goal(
									<?php print $goal['goal_id'];?>,
									"<?php print htmlentities($goal['type'], ENT_QUOTES);?>",
									"<?php print htmlentities($goal['title'], ENT_QUOTES);?>",
									"<?php print number_format($goal['goal']);?>",
									"<?php print htmlentities($goal['start_date']);?>",
									"<?php print htmlentities($goal['end_date']);?>"
								);
								<?php
							}
							?>
						});
					</script>
					<?php
				}
				?>
			</tbody>
		</table>
		<div>
			<button type="button" class="new-goal">New Goal</button>
		</div>
	</fieldset>
</div>

<style type="text/css">
	.goals-container table {
		width:100%;
	}
	.goals .goal .fa {
		font-size:1.4em;
		cursor:pointer;
	}
	.goals .goal .title {
		font-weight:normal;
	}
</style>

<?php Template::Render('footer', 'account');
