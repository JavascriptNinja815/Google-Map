<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$args = array(
	'title' => 'Log In',
	'breadcrumbs' => array(
		'Dashboard' => BASE_URL . '/',
		'Log In' => BASE_URL . '/login',
	),
	'body-class' => 'padded'
);

$errors = array();
if(isset($_REQUEST['login']) && isset($_REQUEST['password'])) {
	$login = $session->authenticate($_REQUEST['login'], $_REQUEST['password']);

	if($login['success'] === True) {
		$session->logIn($login['data']['login_id'], $_REQUEST['company']);
		header('Location: ' . BASE_URL . '/dashboard?login');
		exit();
	} else {
		if(!empty($login['errors']['login'])) {
			if($login['errors']['login'] == 'empty') {
				$errors['login'] = 'E-Mail must be specified';
			} else if($login['errors']['login'] == '!exist') {
				$errors['login'] = 'E-Mail does not exist';
			} else if($login['errors']['login'] == 'suspended') {
				$errors['login'] = 'Account has been suspended';
			}
		}
		if(!empty($login['errors']['password'])) {
			if($login['errors']['password'] == 'empty') {
				$errors['password'] = 'Password must be specified';
			} else if($login['errors']['password'] == 'incorrect') {
				$errors['password'] = 'Password incorrect';
			}
		}
	}
}

if(isset($_REQUEST['login'])) {
	$login = $_REQUEST['login'];
} else {
	$login = '';
}

Template::Render('header', $args, 'public');
?>

<div id="login-container">
	<div class="maven-logo" style="text-align:center">
		<div style="padding-bottom:16px;">
			<?php
			if(COMPANY === '1') {
				?><img src="<?php print BASE_URI;?>/interface/images/casterdepot-cd-logo.png"/><?php
				?><img src="<?php print BASE_URI;?>/interface/images/maven-logo.png" /><?php
			} else if(COMPANY === '2') {
				?><img src="<?php print BASE_URI;?>/interface/images/dorodo-logo-large.png" /><?php
				?><img src="<?php print BASE_URI;?>/interface/images/dorodo-maven.png" /><?php
			} else {
				?><img src="<?php print BASE_URI;?>/interface/images/casterdepot-cd-logo.png" /><?php
				?><img src="<?php print BASE_URI;?>/interface/images/maven-logo.png" /><?php
			}
			?>
		</div>
	</div>
	<form method="post" class="form-horizontal" action="<?php print BASE_URL;?>/login">
		<input type="hidden" name="company" value="<?php print COMPANY;?>" />
		<?php
		if(isset($_REQUEST['s'])) {
			// Session has ended because logged in from another location, or because
			// the cookie was deleted or has expired.
			?>
			<div class="session-error">
				Please login to gain access to the dashboard.
			</div>
			<br />
			<?php
		}
		?>

		<div class="control-group">
			<label class="control-label" for="login">E-Mail</label>
			<div class="controls">
				<input type="text" id="login" name="login" autofocus="autofocus" required="" placeholder="E-Mail Address" />
				<span class="text-error login-error">
					<?php !empty($errors) && isset($errors['login']) ? print $errors['login'] : Null;?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="password">Password</label>
			<div class="controls">
				<input type="password" id="password" name="password" required="" placeholder="Password" />
				<span class="text-error login-error">
					<?php !empty($errors) && isset($errors['password']) ? print $errors['password'] : Null;?>
				</span>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">
					<i class="fa fa-unlock fa-fw"></i>
					Login
				</button>
			</div>
		</div>
	</form>
</div>

<?php Template::Render('footer', False, 'public');
