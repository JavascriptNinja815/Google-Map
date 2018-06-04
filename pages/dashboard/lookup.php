<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

$session->ensureLogin();

$args = array(
	'title' => 'Search',
	'breadcrumbs' => array(
		'Search' => BASE_URI . '/dashboard/search'
	),
	'body-class' => 'padded'
);

Template::Render('header', $args, 'account');

?>

<script type="text/javascript">
	$(document).on('submit', '#lookup-form', function() {
		var $form = $(this);
		var search_string = $form.find('input[name="search-string"]').val();
		var $look_for = $form.find('input[name="look-for"]:checked');
		var look_for = $look_for.val();
		var url = BASE_URI + $look_for.attr('uri');

		if(look_for === 'sales-order') {
			var data = {
				'so-number': search_string
			};
		} else if(look_for === 'item') {
			var data = {
				'item-number': search_string
			};
		}

		activateOverlayZ(url, data);

		return false; // Prevents form propogation.
	});
</script>

<style type="text/css">
	#lookup-form .control-label {
		text-align:left;
		font-weight:bold;
	}
	#lookup-form .controls {
		text-align:left;
	}
</style>

<fieldset>
	<legend>Lookup</legend>
	<div class="notification-container notification-error hidden"></div>

	<form class="form-horizontal" id="lookup-form" method="post">
		<div class="control-group">
			<label class="control-label" for="search-string">ID / Number</label>
			<div class="controls controls-row">
				<input type="text" class="span4" name="search-string" id="search-string" required />
			</div>
		</div>
		<div class="control-group">
			<label class="control-label">Look For</label>
			<div class="controls">
				<label>
					<input type="radio" name="look-for" value="sales-order" checked="checked" uri="/dashboard/sales-order-status/so-details"  />
					Sales Order
				</label>
				<label>
					<input type="radio" name="look-for" value="item" uri="/dashboard/inventory/item-details" />
					Item
				</label>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">
					<i class="fa fa-search fa-fw"></i>
					Lookup
				</button>
			</div>
		</div>
	</form>
</fieldset>

<?php Template::Render('footer', 'account');
