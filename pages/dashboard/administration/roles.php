<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Administration');

$args = array(
	'title' => 'Roles',
	'breadcrumbs' => array(
		'Administration' => BASE_URI . '/dashboard/administration',
		'Roles' => BASE_URI . '/dashboard/administration/roles'
	)
);

Template::Render('header', $args, 'account');

?>

<table id="roles-list" class="table table-striped table-hover columns-sortable columns-filterable rows-navigate">
	<thead>
		<tr>
			<th class="">ID</th>
			<th class="filterable sortable">Role</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$grab_roles = $db->query("
			SELECT
				roles.role_id,
				roles.role
			FROM
				" . DB_SCHEMA_INTERNAL . ".roles
			ORDER BY
				roles.role
		");
		foreach($grab_roles as $role) {
			?>
			<tr class="stripe" navigate-to="<?php print BASE_URI;?>/dashboard/administration/roles/edit?role_id=<?php print htmlentities($role['role_id'], ENT_QUOTES);?>">
				<td class="role_id"><?php print $role['role_id'];?></td>
				<td class="role"><?php print htmlentities($role['role']);?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
<br />
<div class="padded">
	<fieldset>
		<legend>Create Role</legend>
		<form class="form-horizontal" id="create-role-container" method="post" action="<?php print BASE_URI;?>/dashboard/administration/roles/create">
			<div class="control-group">
				<label class="control-label" for="create-role">Role</label>
				<div class="controls">
					<input type="text" class="span4" name="role" id="role-rolex" placeholder="Role Name" required>
					<span class="text-error role-error"></span>
				</div>
			</div>
			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn btn-primary">
						<i class="fa fa-plus fa-fw"></i>
						Create
					</button>
				</div>
			</div>
		</form>
	</fieldset>
</div>

<?php Template::Render('footer', 'account');
