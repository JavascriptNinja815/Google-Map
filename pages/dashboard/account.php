<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'My Account',
	'breadcrumbs' => array(
		'My Account' => BASE_URI . '/dashboard/account'
	),
	'body-class' => 'padded'
);

Template::Render('header', $args, 'account');

?>

<h2>My Account</h2>

<div class="notification-container notification-success hidden"></div>
<div class="notification-container notification-error hidden"></div>

<form class="form-horizontal" id="account-submit-form" method="post">
	<div class="control-group">
		<label class="control-label" for="account-first_name">Name</label>
		<div class="controls controls-row">
			<input type="text" class="span2" name="first_name" id="account-first_name" placeholder="First Name" value="<?php print htmlentities($session->login['first_name'], ENT_QUOTES);?>" required />
			<input type="text" class="span2" name="last_name" id="account-last_name" placeholder="Last Name" value="<?php print htmlentities($session->login['last_name'], ENT_QUOTES);?>" required />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="account-password">Password</label>
		<div class="controls">
			<input type="password" class="span4" name="password" id="account-password" placeholder="Password" />
			<br />
			<span class="help-inline">Leave blank to retain current password</span>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="account-initials">Initials</label>
		<div class="controls">
			<input type="text" class="span1" id="account-initials" value="<?php print htmlentities($session->login['initials'], ENT_QUOTES);?>" disabled>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="account-emailpassword">E-Mail Password</label>
		<div class="controls">
			<input type="password" class="span4" name="email_password" id="account-emailpassword" value="<?php print htmlentities($session->login['email_password'], ENT_QUOTES);?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-success">
				<i class="fa fa-check fa-fw"></i>
				Apply
			</button>
		</div>
	</div>
</form>

<script type="text/javascript">
	$(document).off('dblclick', '#account-initials');
	$(document).on('dblclick', '#account-initials', function(event) {
		$.ajax({
			'url': BASE_URI + '/dashboard/account/credits',
			'dataType': 'html',
			'success': function(response) {
				var $overlay = $.overlayz({
					'html': response
				}).fadeIn('fast');
			}
		});

	});
</script>

<?php Template::Render('footer', 'account');
