<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();
$session->ensureRole('Administration');

$args = array(
	'title' => 'Logins',
	'breadcrumbs' => array(
		'Administration' => BASE_URI . '/dashboard/administration',
		'Logins' => BASE_URI . '/dashboard/administration/logins'
	)
);

Template::Render('header', $args, 'account');

?>

<style type="text/css">
	#logins-list .status-disabled {
		background-color:#fee;
	}
	#logins-list .status-enabled .status-container {
		color:#090;
	}
	#logins-list .status-disabled .status-container {
		font-weight:bold;
		color:#f00;
	}
	#create-login-container .error-container {
		color:#f00;
		font-weight:bold;
	}
</style>

<table id="logins-list" class="table table-striped table-hover columns-sortable columns-filterable rows-navigate headers-sticky">
	<thead>
		<tr>
			<th class="">ID</th>
			<th class="filterable sortable">Login</th>
			<th class="filterable sortable">Initials</th>
			<th class="filterable">Roles</th>
			<th class="filterable">Companies</th>
			<th class="filterable">Location</th>
			<th class="filterable sortable">First Name</th>
			<th class="filterable sortable">Last Name</th>
			<th class="filterable sortable">Created On</th>
			<th class="filterable filter-select sortable">Status</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$grab_logins = $db->query("
			SELECT
				logins.login_id,
				logins.login,
				logins.initials,
				logins.first_name,
				logins.last_name,
				logins.created_on,
				logins.status,
				STUFF(
					(
						SELECT
							',' + companies.company 
						FROM
							" . DB_SCHEMA_INTERNAL . ".login_companies
						INNER JOIN
							" . DB_SCHEMA_INTERNAL . ".companies
							ON
							login_companies.company_id = companies.company_id
						WHERE
							login_companies.login_id = logins.login_id
						FOR
							XML PATH('')
					), 1, 1, ''
				) as companies,
				STUFF(
					(
						SELECT
							',' + login_locations.location 
						FROM
							" . DB_SCHEMA_INTERNAL . ".login_locations
						WHERE
							login_locations.login_id = logins.login_id
						FOR
							XML PATH('')
					), 1, 1, ''
				) as locations
			FROM
				" . DB_SCHEMA_INTERNAL . ".logins
			ORDER BY
				logins.last_name,
				logins.first_name
		");
		foreach($grab_logins as $login) {
			$status = $login['status'] == 1 ? 'enabled' : 'disabled';
			$created_on = strtotime($login['created_on']);
			$created_on = date('n/j/Y', $created_on);
			$grab_roles = $db->query("
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
				ORDER BY
					roles.role
			");
			?>
			<tr class="stripe status-<?php print $status;?>" navigate-to="<?php print BASE_URI;?>/dashboard/administration/logins/edit?login_id=<?php print htmlentities($login['login_id'], ENT_QUOTES);?>">
				<td class="login_id-container"><?php print $login['login_id'];?></td>
				<td class="login-container"><?php print htmlentities($login['login']);?></td>
				<td class="initials-container"><?php print htmlentities($login['initials']);?></td>
				<td class="roles-container"><?php
					foreach($grab_roles as $role) {
						?><div class="role" data-role_id="<?php print htmlentities($role['role_id'], ENT_QUOTES);?>"><?php print htmlentities($role['role']);?></div><?php
					}
				?></td>
				<td class="company-container"><?php
					foreach(explode(',', $login['companies']) as $company) {
						?><div class="company"><?php print htmlentities($company);?></div><?php
					}
				?></td>
				<td class="company-container"><?php
					foreach(explode(',', $login['locations']) as $location) {
						?><div class="locatioN"><?php print htmlentities($location);?></div><?php
					}
				?></td>
				<td class="first_name-container"><?php print htmlentities($login['first_name']);?></td>
				<td class="last_name-container"><?php print htmlentities($login['last_name']);?></td>
				<td class="created_on-container"><?php print htmlentities($created_on);?></td>
				<td class="status-container"><?php print ucwords($status);?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
<br />
<div class="padded">
	<fieldset>
		<legend>Create Login</legend>
		<form class="form-horizontal" id="create-login-container" method="post" action="<?php print BASE_URI;?>/dashboard/administration/logins/create">
			<div class="control-group">
				<label class="control-label" for="create-login">E-Mail</label>
				<div class="controls">
					<input type="email" class="span4" name="login" id="create-login" placeholder="E-Mail Address" required>
					<span class="text-error login-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="create-password">Password</label>
				<div class="controls">
					<input type="password" class="span4" name="password" id="create-password" placeholder="Password" required>
					<span class="text-error password-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="create-first_name">Name</label>
				<div class="controls controls-row">
					<input type="text" class="span2" name="first_name" id="create-first_name" placeholder="First Name" required />
					<input type="text" class="span2" name="last_name" id="create-last_name" placeholder="Last Name" />
					<span class="text-error first_name-error"></span>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="create-initials">Initials</label>
				<div class="controls">
					<input type="text" class="span1" name="initials" id="create-initials" placeholder="Initials" required>
					<span class="text-error last_name-error"></span>
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
