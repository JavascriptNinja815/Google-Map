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
	'title' => 'Edit Role',
	'breadcrumbs' => array(
		'Administration' => BASE_URI . '/dashboard/administration',
		'Roles' => BASE_URI . '/dashboard/administration/roles',
		'Edit Role' => BASE_URI . '/dashboard/administration/roles/edit?role_id=' . $_GET['role_id'],
	)
);

Template::Render('header', $args, 'account');

$grab_role = $db->query("
	SELECT
		roles.role_id,
		roles.role,
		roles.permission_type
	FROM
		" . DB_SCHEMA_INTERNAL . ".roles
	WHERE
		roles.role_id = " . $db->quote($_GET['role_id']) . "
");
$role = $grab_role->fetch();

// Grab statically defined permissions.
$grab_static_permissions = $db->query("
	SELECT
		role_permissions.permission_id,
		role_permissions.permission
	FROM
		" . DB_SCHEMA_INTERNAL . ".role_permissions
	WHERE
		role_permissions.role_id = '" . $role['role_id'] . "'
	ORDER BY
		role_permissions.permission
");

// Grab dynamically defined permissions, as resulting by a query.
$grab_dynamic_permission_query = $db->query("
	SELECT
		role_permission_queries.permission_query_id,
		role_permission_queries.query
	FROM
		" . DB_SCHEMA_INTERNAL . ".role_permission_queries
	WHERE
		role_permission_queries.role_id = '" . $role['role_id'] . "'
");
$dynamic_pemission_query = $grab_dynamic_permission_query->fetch();
if($dynamic_pemission_query === False) {
	// No query returned.
	$dynamic_pemission_query = '';
} else {
	// Query returned, use it.
	$dynamic_pemission_query = $dynamic_pemission_query['query'];
}

if(isset($_GET['saved'])) {
	?>
	<div class="notification-container notification-success">
		<div class="notification">Changes successfully saved.</div>
	</div>
	<?php
}
?>

<form method="post" id="edit-role-container" class="form-horizontal">
	<input type="hidden" name="role_id" value="<?php print htmlentities($_GET['role_id'], ENT_QUOTES);?>" />

	<span class="fa-stack fa-lg delete">
		<i class="fa fa-square fa-stack-2x delete-bg"></i>
		<i class="fa fa-trash-o fa-stack-1x delete-fg"></i>
	</span>

	<div class="padded">
		<fieldset>
			<legend>General Information</legend>
			<div class="control-group">
				<label class="control-label" for="role-role">Role</label>
				<div class="controls">
					<input type="text" class="span4" name="role" id="role-role" placeholder="Role Name" value="<?php print htmlentities($role['role'], ENT_QUOTES);?>" required>
					<span class="text-error login-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label">Permission Type</label>
				<div class="controls">
					<label>
						<input type="radio" name="permission-type" id="permission-type-0" value="0" <?php print $role['permission_type'] == 0 ? 'checked="checked"' : Null;?> />
						Static Permissions
						<br />
						<span class="text-description">
							Permissions are statically defined, by manually adding entries under the "Permissions" section of this page.
						</span>
						<br />
						<span class="text-error login-error"></span>
					</label>
					<label>
						<input type="radio" name="permission-type" id="permission-type-1" value="1" <?php print $role['permission_type'] == 1 ? 'checked="checked"' : Null;?> />
						Dynamic Permissions
						<br />
						<span class="text-description">
							Permissions are dynamically defined, by the results of an SQL query, as defined under the "Permission Query" section of this page.
						</span>
						<br />
						<span class="text-error login-error"></span>
					</label>
				</div>
			</div>
		</fieldset>

		<fieldset class="permissions-container permissions-container-static" style="<?php print $role['permission_type'] != 0 ? 'display:none;' : Null;?>">
			<legend>Permissions</legend>
			<div class="permissions">
				<div class="permission prototype">
					<span class="fa-stack remove">
						<i class="fa fa-circle fa-stack-2x remove-bg"></i>
						<i class="fa fa-minus fa-stack-1x remove-fg"></i>
					</span>
					<div class="permission"></div>
				</div>
				<?php
				foreach($grab_static_permissions as $permission) {
					?>
					<div class="permission">
						<input type="hidden" name="static-permissions[]" value="<?php print htmlentities($permission['permission'], ENT_QUOTES);?>" />
						<span class="fa-stack remove">
							<i class="fa fa-circle fa-stack-2x remove-bg"></i>
							<i class="fa fa-minus fa-stack-1x remove-fg"></i>
						</span>
						<div class="permission"><?php print htmlentities($permission['permission']);?></div>
					</div>
					<?php
				}
				?>
				<span class="fa-stack add">
					<i class="fa fa-circle fa-stack-2x add-bg"></i>
					<i class="fa fa-plus fa-stack-1x add-fg"></i>
				</span>
			</div>
		</fieldset>

		<fieldset class="permissions-container permissions-container-dynamic" style="<?php print $role['permission_type'] != 1 ? 'display:none;' : Null;?>">
			<legend>Permission Query</legend>

			<div class="control-group">
				<label class="control-label" for="login-permission-query">Query</label>
				<div class="controls">
					<label>
						<textarea id="login-permission-query" name="dynamic-permission-query"><?php print htmlentities($dynamic_pemission_query);?></textarea>
						<br />
						<span class="text-error"></span>
						<span class="text-success"></span>
					</label>
				</div>
			</div>
			<div class="control-group">
				<div class="controls">
					<button type="button" id="test-dynamic-permission-query" class="btn btn-warning">
						<i class="fa fa-play fa-fw"></i>
						Test Query
					</button>
					<span class="text-info login-permission-query-error"></span>
				</div>
			</div>
		</fieldset>

		<br />
		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">
					<i class="fa fa-plus fa-fw"></i>
					Apply Changes
				</button>
			</div>
		</div>
	</div>
</form>

<?php Template::Render('footer', 'account');
