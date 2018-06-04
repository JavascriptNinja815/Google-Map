<?php

$session->ensureLogin();

$args = array(
	'title' => 'Opportunities',
	'breadcrumbs' => [
		'Opportunities' => BASE_URI . '/dashboard/opportunities'
	]
);
Template::Render('header', $args, 'account');

if($session->hasRole('Administration')) {
	$permission_constraint = "1 = 1"; // Show all.
} else if($session->hasRole('Sales')) {
	$permissions = $session->getPermissions('Sales', 'view-orders');
	if(!empty($permissions)) {
		// Sanitize values for DB querying.
		$permissions = array_map(function($value) {
			$db = \PM\DB\SQL::connection();
			return $db->quote($value);
		}, $permissions);
		
		$permission_constraint = "logins.initials IN (" . implode(',', $permissions) . ")";
	} else {
		// If the user has not been granted any privs, then finish the query which will already return nothing.
		// TODO: There has to be a more elegant method of handling this...
		$permission_constraint = "1 != 1";
	}
} else {
	$permission_constraint = "1 != 1"; // Don't show any.
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
		logins.login_id = " . $db->quote($session->login['login_id']) . "
		OR
		" . $permission_constraint . "
	ORDER BY
		logins.initials
");

?>

<style type="text/css">
	#opportunities-page h2 {
		display:inline-block;
	}
	#opportunities-page .padded {
		padding-left:16px;
		padding-right:16px;
		position:relative;
	}
	#opportunities-page .opportunity.prototype {
		display:none;
	}
	#switches-container {
		position:absolute;
		top:20px;
		right:300px;
	}
	#viewmode-switch-container {
		position:absolute;
		top:20px;
		right:530px;
	}
	#additional-filters {
		position:absolute;
		top:20px;
		right:700px;
	}
	#viewmode-switch-container button {
		zoom:0.8;
		margin-bottom:6px;
	}

	button.new-opportunity,
	button.edit-opportunity,
	button.overlayz-link {
		color:#fff;
	}

	/* The switch - the box around the slider */
	.switch {
	  position: relative;
	  display: inline-block;
	  width: 60px;
	  height: 34px;
	  margin-bottom:0;
	}
	.total-amount {
		position:absolute;
		top:44px;
		right:22px;
		font-size:48px;
		font-weight:bold;
		color:#090;
	}

	/* Hide default HTML checkbox */
	.switch input {display:none;}

	/* The slider */
	.slider {
	  position: absolute;
	  cursor: pointer;
	  top: 0;
	  left: 0;
	  right: 0;
	  bottom: 0;
	  background-color: #ccc;
	  -webkit-transition: .4s;
	  transition: .4s;
	}
	.switch-container{
		text-align:center;
	}

	.slider:before {
	  position: absolute;
	  content: "";
	  height: 26px;
	  width: 26px;
	  left: 4px;
	  bottom: 4px;
	  background-color: white;
	  -webkit-transition: .4s;
	  transition: .4s;
	}

	input:checked + .slider {
		/*background-color: #2196F3;*/
	}

	input:focus + .slider {
		box-shadow: 0 0 1px #2196F3;
	}

	input:checked + .slider:before {
		-webkit-transform: translateX(26px);
		-ms-transform: translateX(26px);
		transform: translateX(26px);
	}

	/* Rounded sliders */
	.slider.round {
	  border-radius: 34px;
	}

	.slider.round:before {
	  border-radius: 50%;
	}
	.switch-text {
		display:inline-block;
		line-height:36px;
		vertical-align:top;
		font-weight:bold;
		width:84px;
	}
	.left-side {
		text-align:right;
	}
	.right-side {
		text-align:left;
	}
	#viewmode-switch-container #viewmode-columns i {
		/* Safari */
		-webkit-transform: rotate(90deg);
		/* Firefox */
		-moz-transform: rotate(90deg);
		/* IE */
		-ms-transform: rotate(90deg);
		/* Opera */
		-o-transform: rotate(90deg);
		/* Internet Explorer */
		filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=1);
		padding-bottom:-4px;
	}
	#opportunities-columns-container {}
	#opportunities-columns {
		display:none;
		display:flex;
		flex-direction:row;
		flex-wrap:nowrap;
	}

	#opportunities-columns .stage {
		vertical-align:top;
		flex-grow:1;
		border-right:1px solid #bbb;
	}
	#opportunities-columns .stage .title {
		padding:24px;
		background-color:#04c;
		color:#fff;
		position:relative;
	}
	#opportunities-columns .stage .title .amount-container {
		position:absolute;
		top:12px;
		right:12px;
		font-size:0.8em;
		font-weight:normal;
		text-align:right;
	}
	#opportunities-columns .stage .list {
		padding-right:1px;
	}
	#opportunities-columns .opportunity {
		background-color:#eee;
		padding:12px;
		margin:12px;
		border-radius:12px;
		border:1px solid #ddd;
		position:relative;
		overflow:hidden;
	}
	#opportunities-columns .opportunity .actions-container {
		position:absolute;
		top:0;
		right:0;
		padding:6px;
		border-radius:6px;
		text-align:right;
	}
	#opportunities-columns .opportunity .actions-container i {
		font-size:1.6em;
		color:#090;
		vertical-align:top;
		cursor:pointer;
	}
	#opportunities-columns .opportunity .actions-container i.fa-times-circle {
		color:#c00;
	}
	#opportunities-columns .opportunity .actions-container.bg {
		background-color:#fff;
		border:1px solid #ddd;
	}
	#opportunities-columns .opportunity .stages-container {
		display:inline-block;
		padding-right:12px;
	}
	#opportunities-columns .opportunity .name {
		padding-top:18px;
	}
	#opportunities-columns .opportunity .opportunity_id {
		position:absolute;
		top:6px;
		left:12px;
		font-size:0.8em;
		color:#00f;
	}
	#opportunities-columns .opportunity .age {
		float:left;
		padding-top:4px;
		font-size:0.8em;
		color:#aaa;
	}
	#opportunities-columns .opportunity .salesman {
		float:right;
		padding-top:4px;
		font-size:0.8em;
		color:#aaa;
	}
	#opportunities-columns .opportunity .amount-container {
		position:absolute;
		top:6px;
		right:34px;
		font-size:0.8em;
	}
</style>

<div id="opportunities-page">
	<form method="post" id="opportunities-filter-container">
		<input type="hidden" name="view" value="<?php print !empty($_POST['view']) ? htmlentities($_POST['view'], ENT_QUOTES) : 'columns';?>" />

		<div class="padded">
			<h2>Quotes / Opportunities</h2>
			<br />
			<button type="button" class="btn btn-primary new-opportunity overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/opportunities/edit" overlayz-data="{}">New Quote/Opportunity</button>
		</div>

		<div id="additional-filters">
			<div class="dale-container">
				<select name="date-filter">
					<option value="" <?php empty($_POST['date-filter']) ? 'selected' : Null;?>>-- Any Date --</option>
					<optgroup label="Created">
						<option value="created-today" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'created-today' ? 'selected' : Null;?>>Today</option>
						<option value="created-this-week" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'created-this-week' ? 'selected' : Null;?>>This Week</option>
						<option value="created-this-month" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'created-this-month' ? 'selected' : Null;?>>This Month</option>
						<option value="created-last-month" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'created-last-month' ? 'selected' : Null;?>>Last Month</option>
						<!--option value="created-custom" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'created-custom' ? 'selected' : Null;?>>Custom Range</option-->
					</optgroup>
					<!--optgroup label="Updated">
						<option value="updated-today" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'updated-today' ? 'selected' : Null;?>>Today</option>
						<option value="updated-this-week" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'updated-this-week' ? 'selected' : Null;?>>This Week</option>
						<option value="updated-this-month" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'updated-this-month' ? 'selected' : Null;?>>This Month</option>
						<option value="updated-last-month" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'updated-last-month' ? 'selected' : Null;?>>Last Month</option>
						<!--option value="updated-custom" <?php print !empty($_POST['date-filter']) && $_POST['date-filter'] == 'updated-custom' ? 'selected' : Null;?>>Custom Range</option-->
					</optgroup-->
				</select>
			</div>
			<?php
			if(!empty($_POST['me-vs-team'])) {
				?>
				<div class="salesmen-container">
					<select name="salesman">
						<option value="" <?php empty($_POST['salesman']) ? 'selected' : Null;?>>-- All Salesmen --</option>
						<?php
						foreach($grab_logins as $login) {
							?><option value="<?php print htmlentities($login['login_id'], ENT_QUOTES);?>" <?php print !empty($_POST['salesman']) && $_POST['salesman'] == $login['login_id'] ? 'selected' : Null;?>><?php print htmlentities($login['initials'] . ' - ' . $login['first_name'] . ' ' . $login['last_name']);?></option><?php
						}
						?>
					</select>
				</div>
				<?php
			}
			?>
		</div>

		<div id="viewmode-switch-container">
			<button type="button" class="btn btn-large <?php print empty($_POST['view']) || $_POST['view'] == 'columns' ? 'btn-primary' : Null;?>" id="viewmode-columns" title="Column View"><i class="fa fa-th-list"></i></button>
			<br />
			<button type="button" class="btn btn-large <?php print !empty($_POST['view']) && $_POST['view'] == 'rows' ? 'btn-primary' : Null;?>" id="viewmode-rows" title="Table View"><i class="fa fa-align-justify"></i></button>
		</div>

		<div id="switches-container">
			<div class="switch-container">
				<div class="switch-text left-side"><?php print htmlentities($session->login['initials']);?></div>
				<label class="switch">
					<input name="me-vs-team" type="checkbox" value="1" <?php print !empty($_POST['me-vs-team']) && $_POST['me-vs-team'] == 1 ? 'checked' : Null;?>>
					<div class="slider round"></div>
				</label>
				<div class="switch-text right-side">My Team</div>
			</div>
			<div class="switch-container">
				<div class="switch-text left-side">Open</div>
				<label class="switch">
					<input name="open-vs-all" type="checkbox" value="1" <?php print !empty($_POST['open-vs-all']) && $_POST['open-vs-all'] == 1 ? 'checked' : Null;?>>
					<div class="slider round"></div>
				</label>
				<div class="switch-text right-side">All</div>
			</div>
		</div>
	</form>

	<div class="total-amount">...</div>

	<table style="display:none;">
		<tbody>
			<tr class="opportunity prototype">
				<td class="opportunityid overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/opportunities/details"></td>
				<td class="enteredby"></td>
				<td class="enteredon"></td>
				<td class="salesman"></td>
				<td class="office"></td>
				<td class="name"></td>
				<td class="leadtype"></td>
				<td class="client"></td>
				<td class="opportunitytype"></td>
				<td class="stage"></td>
				<!--td class="nextstep"></td-->
				<td class="amount"></td>
				<!--td class="duedate"></td-->
				<td class="closedate"></td>
				<td class="expires"></td>
				<td class="competitors"></td>
				<td class="vendorlead"></td>
				<td class="vendorref"></td>
				<td class="source"></td>
				<td class="notes"></td>
			</tr>
		</tbody>
	</table>

	<table id="opportunities-table" style="<?php print empty($_POST['view']) || (!empty($_POST['view']) && $_POST['view'] != 'rows') ? 'display:none;' : '';?>">
		<thead>
			<tr>
				<th class="sortable filterable">ID</th>
				<th class="sortable filterable">Entered By</th>
				<th class="sortable filterable">Entered On</th>
				<th class="sortable filterable">Salesman</th>
				<th class="sortable filterable">Office</th>
				<th class="sortable filterable">Name</th>
				<th class="sortable filterable">Client Type</th>
				<th class="sortable filterable">Client</th>
				<th class="sortable filterable">Opportunity Type</th>
				<th class="sortable filterable">Stage</th>
				<!--th class="sortable filterable">Next Step</th-->
				<th class="sortable filterable">Amount</th>
				<!--th class="sortable filterable">Due Date</th-->
				<th class="sortable filterable">Close Date</th>
				<th class="sortable filterable">Expires</th>
				<th class="sortable filterable">Competitors</th>
				<th class="sortable filterable">Vendor Lead</th>
				<th class="sortable filterable">Vendor Ref</th>
				<th class="sortable filterable">Source</th>
				<th class="sortable filterable">Notes</th>
			</tr>
		</thead>
		<tbody id="opportunities-tbody">
		</tbody>
	</table>

	<div id="opportunities-columns-container" style="<?php print !empty($_POST['view']) && $_POST['view'] != 'columns' ? 'display:none;' : '';?>">
		<div id="opportunities-columns">
			<!--div class="stage stage-prospecting">
				<div class="title">Prospecting</div>
				<div class="list"></div>
			</div--><!--
			--><div class="stage stage-discovery">
				<div class="title">
					Discovery
					<div class="amount-container">
						<div class="amount" title="Total Projected Amount"></div>
						<div class="sum" title="Total Line Item Sum"></div>
					</div>
				</div>
				<div class="list"></div>
			</div><!--
			--><div class="stage stage-quoted">
				<div class="title">
					Quoted
					<div class="amount-container">
						<div class="amount" title="Total Projected Amount"></div>
						<div class="sum" title="Total Line Item Sum"></div>
					</div>
				</div>
				<div class="list"></div>
			</div><!--
			--><div class="stage stage-negotiation">
				<div class="title">
					Negotiation
					<div class="amount-container">
						<div class="amount" title="Total Projected Amount"></div>
						<div class="sum" title="Total Line Item Sum"></div>
					</div>
				</div>
				<div class="list"></div>
			</div><!--
			--><div class="stage stage-commitment">
				<div class="title">
					Commitment
					<div class="amount-container">
						<div class="amount" title="Total Projected Amount"></div>
						<div class="sum" title="Total Line Item Sum"></div>
					</div>
				</div>
				<div class="list"></div>
			</div><!--
			--><div class="stage stage-closed">
				<div class="title">
					Closed
					<div class="amount-container">
						<div class="amount" title="Total Projected Amount"></div>
						<div class="sum" title="Total Line Item Sum"></div>
					</div>
				</div>
				<div class="list"></div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var $stages_select = $('<select name="stage">').append(
		//$('<option value="Prospecting">1. Prospecting</option>'),
		$('<optgroup label="1. Discovery">').append(
			$('<option value="Discovery - Site Survey" selected="">Site Survey</option>'),
			$('<option value="Discovery - Gather Info">Gather Info</option>'),
			$('<option value="Discovery - Other">Other</option>'),
		),
		$('<option value="Quoted">2. Quoted</option>'),
		$('<optgroup label="3. Negotiation">').append(
			$('<option value="Negotiation - Samples">Samples</option>'),
			$('<option value="Negotiation - Price">Price</option>'),
			$('<option value="Negotiation - Delivery">Delivery</option>'),
			$('<option value="Negotiation - Other">Other</option>'),
		),
		$('<option value="Verbal Commitment">4. Verbal Commitment</option>'),
		$('<optgroup label="5. Won/Lost">').append(
			$('<option value="Closed Won">Closed Won</option>'),
			$('<option value="Closed - Never Ordered">Closed - Never Ordered</option>'),
			$('<option value="Closed Lost">Closed Lost</option>'),
		)
	);
	
	var opportunities;

	var $opportunity_prototype = $('#opportunities-page .opportunity.prototype');
	var total_amount = 0;
	var $total_amount = $('.total-amount');

	var $opportunities_table = $('#opportunities-table');
	var $opportunities_tbody = $('#opportunities-tbody');
	
	var $opportunities_columns_container = $('#opportunities-columns-container');
	var $opportunities_columns = $('#opportunities-columns');

	var $opportunities_form = $('#opportunities-page #opportunities-filter-container');

	function renderTableView() {
		total_amount = 0;
		$opportunities_tbody.empty();
		$.each(opportunities, function(index, opportunity) {
			var $tr = $opportunity_prototype.clone().removeClass('prototype');

			$tr.find('.opportunityid').text(opportunity.opportunity_id).attr('overlayz-data', JSON.stringify({'opportunity_id': opportunity.opportunity_id}));
			$tr.find('.enteredby').text(opportunity.entered_by);
			$tr.find('.enteredon').text(opportunity.entered_on);
			$tr.find('.salesman').text(opportunity.initials);
			$tr.find('.office').text(opportunity.office === null ? '' : opportunity.office);
			$tr.find('.name').text(opportunity.name);
			if(opportunity.custno) {
				$tr.find('.leadtype').text('Client');
				$tr.find('.client').text(opportunity.custno + ' - ' + opportunity.client_name).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/clients/details').attr('overlayz-data', JSON.stringify({'custno': opportunity.custno}));
			} else {
				$tr.find('.leadtype').text('Prospect');
				$tr.find('.client').text(opportunity.client_name);
			}
			$tr.find('.opportunitytype').text(opportunity.opportunity_type);
			if(opportunity.stage === 'Closed Lost') {
				$tr.find('.stage').append(
					$('<span>').text(opportunity.stage),
					$('<br />'),
					$('<span>').text('Lost To: ' + opportunity.lost_to),
					$('<br />'),
					$('<span>').text('Reason: ' + opportunity.lost_reason)
				);
			} else {
				$tr.find('.stage').text(opportunity.stage);
			}
			//if(opportunity.next_step === 'Other') {
			//	$tr.find('.nextstep').text(opportunity.next_step + ' - ' + opportunity.next_step_memo);
			//} else {
			//	$tr.find('.nextstep').text(opportunity.next_step);
			//}
			$tr.find('.amount').text('$' + opportunity.amount);
			//$tr.find('.duedate').text(opportunity.due_date);
			$tr.find('.closedate').text(opportunity.close_date);
			$tr.find('.expires').text(opportunity.expires);
			$tr.find('.competitors').text(opportunity.competitors.join(', '));
			$tr.find('.vendorlead').text(opportunity.vendor_lead);
			$tr.find('.vendorref').text(opportunity.vendor_ref ? opportunity.vendor_ref : '');
			$tr.find('.source').text(opportunity.source);
			$tr.find('.notes').text(opportunity.notes);
			$tr.appendTo($opportunities_tbody);

			total_amount += parseInt(opportunity.amount.replace(/\,/g, ''));
		});

		$total_amount.text('$' + Intl.NumberFormat().format(total_amount));

		setTimeout(function() {
			applyTableFeatures($opportunities_table);
		}, 1000);

		$opportunities_table.addClass('table table-small table-striped columns-sortable columns-filterable');

		$.each($([
			'table.columns-filterable',
			'table.columns-sortable',
			'table.headers-sticky'
		]), function(index, table) {
			var $table = $(table);
			var options = {
				'selectorHeaders': [],
				'widgets': [],
				'widgetOptions': {}
			};

			if($table.hasClass('columns-sortable')) {
				options.selectorHeaders.push('> thead > tr > th.sortable');
				options.selectorHeaders.push('> thead > tr > td.sortable');
				options.onRenderHeader = function() {
					// Fixes text that wraps when it doesn't necessarily need to.
					$(this).find('div').css('width', '100%');
				};
			}
			if($table.hasClass('columns-filterable')) {
				options.selectorHeaders.push('> thead > tr > th.filterable');
				options.selectorHeaders.push('> thead > tr > td.filterable');
				options.widgets.push('filter');
				options.widgetOptions.filter_ignoreCase = true;
				options.widgetOptions.filter_searchDelay = 100;
				options.widgetOptions.filter_childRows = true;
			}
			if($table.hasClass('headers-sticky')) {
				options.widgets.push('stickyHeaders');
				options.widgetOptions.stickyHeaders_attachTo = $('#body');
			}

			// Convert array to comma-separated string.
			options.selectorHeaders = options.selectorHeaders.join(',');

			$table.tablesorter(options);
		});

		var $table_rows_navigate = $('table.rows-navigate').find('> tbody > tr');
		$table_rows_navigate.on('click', function(event) {
			var $tr = $(this);
			var navigate_to = $tr.attr('navigate-to');
			window.location = navigate_to;
		});
	}

	function renderAmountTotals() {
		// Iterate through each column group.
		var $columns = $('#opportunities-columns .stage');
		$.each($columns, function(offset, column) {
			var $column = $(column);
			var $title = $column.find('.title');
			var total_amount = 0.00;
			var total_sum = 0.00;
			$.each($column.find('.list .opportunity'), function(offset, opportunity) {
				var $opportunity = $(opportunity);
				var $amount_container = $opportunity.find('.amount-container');
				var amount = $amount_container.find('.amount').text().replace(/\$/g, '').replace(/\,/g, '').trim();
				var sum = $amount_container.find('.sum').text().replace(/\$/g, '').replace(/\,/g, '').trim();
				total_amount += parseFloat(amount);
				total_sum += parseFloat(sum);
			});
			var $column_amount_container = $column.find('.title .amount-container');
			$column_amount_container.find('.amount').text('$' + Intl.NumberFormat().format(total_amount));
			$column_amount_container.find('.sum').text('$' + Intl.NumberFormat().format(total_sum));
		});
	}

	function renderColumnView() {
		$opportunities_columns.find('.stage .list').empty();
		$.each(opportunities, function(index, opportunity) {
			var $opportunity = $('<div class="opportunity">').append(
				$('<div class="opportunity_id">').text(opportunity.opportunity_id).addClass('overlayz-link').attr('overlayz-url', BASE_URI + '/dashboard/opportunities/details').attr('overlayz-data', JSON.stringify({'opportunity_id': opportunity.opportunity_id})),
				$('<div class="name">').text(opportunity.name),
				$('<div class="client">').text(
					opportunity.custno ?
						opportunity.custno + ' - ' + opportunity.client_name
					:
						opportunity.client_name
				),
				$('<div class="age" title="Age / Last Edited (days)">').text(opportunity.age + ' / ' + opportunity.edit_age),
				$('<div class="salesman" title="Salesman / Entered By">').text(opportunity.initials + ' / ' + opportunity.entered_by),
				$('<div class="amount-container" title="Projected Amount / Line Item Sum">').append(
					'$',
					$('<span class="amount">').text(opportunity.amount),
					' / $',
					$('<span class="sum">').text(opportunity.lineitem_sum)
				),
				$('<div class="actions-container">').append(
					$('<i class="fa fa-arrow-circle-right">')
				)
			).attr('stage', opportunity.stage).attr('opportunity_id', opportunity.opportunity_id);
			if(opportunity.stage.startsWith('Prospecting')) {
				$opportunities_columns.find('.stage-prospecting .list').append($opportunity);
			} else if(opportunity.stage.startsWith('Discovery')) {
				$opportunities_columns.find('.stage-discovery .list').append($opportunity);
			} else if(opportunity.stage.startsWith('Quoted')) {
				$opportunities_columns.find('.stage-quoted .list').append($opportunity);
			} else if(opportunity.stage.startsWith('Negotiation')) {
				$opportunities_columns.find('.stage-negotiation .list').append($opportunity);
			} else if(opportunity.stage.startsWith('Verbal Commitment')) {
				$opportunities_columns.find('.stage-commitment .list').append($opportunity);
			} else if(opportunity.stage.startsWith('Closed')) {
				$opportunities_columns.find('.stage-closed .list').append($opportunity);
			}
		});
		renderAmountTotals();
	}

	var reload = false;
	$(document).off('submit', '#opportunities-page #opportunities-filter-container');
	$(document).on('submit', '#opportunities-page #opportunities-filter-container', function(event) {
		if(reload) {
			return true;
		}
		var data = new FormData(this);
		var $form = $(this);
		var $view_input = $('#opportunities-filter-container :input[name="view"]');
		var view = $view_input.val();

		var total_amount = 0;

		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/list',
			'method': 'POST',
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'data': data,
			'success': function(response) {
				opportunities = response.opportunities;
				renderTableView();
				renderColumnView();
			}
		});

		reload = true;
		return false;
	});
	// Force initial load.
	$('#opportunities-page #opportunities-filter-container').submit();

	/**
	 * Bind to change events on "Me vs Team" checkbox.
	 */
	$(document).off('change', '#opportunities-page :input[name="me-vs-team"]');
	$(document).on('change', '#opportunities-page :input[name="me-vs-team"]', function(event) {
		$opportunities_form.submit();
	});

	/**
	 * Bind to change events on "Open vs All" checkbox.
	 */
	$(document).off('change', '#opportunities-page :input[name="open-vs-all"]');
	$(document).on('change', '#opportunities-page :input[name="open-vs-all"]', function(event) {
		$opportunities_form.submit();
	});
	
	/**
	* Bind to change events on "Salesman" drop-down.
	 */
	$(document).off('change', '#opportunities-page :input[name="salesman"]');
	$(document).on('change', '#opportunities-page :input[name="salesman"]', function(event) {
		$opportunities_form.submit();
	});
	
	/**
	* Bind to change events on "Date Type" dropdown.
	 */
	$(document).off('change', '#opportunities-page :input[name="date-filter"]');
	$(document).on('change', '#opportunities-page :input[name="date-filter"]', function(event) {
		$opportunities_form.submit();
	});

	/**
	 * Bind to clicks on "View Mode" buttons.
	 */
	$(document).off('click', '#viewmode-switch-container .btn');
	$(document).on('click', '#viewmode-switch-container .btn', function(event) {
		var $button = $(this);
		var $form = $button.closest('form');
		var $search_input = $('#opportunities-filter-container :input[name="view"]');
		if($button.attr('id') === 'viewmode-rows') {
			//$opportunities_table.show();
			//$opportunities_columns_container.hide();
			//renderTableView();
			$search_input.val('rows');
		} else if($button.attr('id') === 'viewmode-columns') {
			//$opportunities_table.hide();
			//$opportunities_columns_container.show();
			//renderColumnView();
			$search_input.val('columns');
		}
		$form.trigger('submit');
		//$button.addClass('btn-primary');
		//$button.parent().find('.btn').not($button).removeClass('btn-primary');
	});

	/**
	 * Bind to clicks on Opportunity "Change Stage" arrow.
	 */
	$(document).off('click', '#opportunities-columns .opportunity .actions-container i');
	$(document).on('click', '#opportunities-columns .opportunity .actions-container i', function(event) {
		var $icon = $(this);
		var $actions_container = $icon.closest('.actions-container');
		var $opportunity = $actions_container.closest('.opportunity');
		if($icon.hasClass('fa-times-circle')) {
			// Cancel edit.
			$icon.removeClass('fa-times-circle');
			$icon.addClass('fa-arrow-circle-right');
			$icon.attr('title', '');
			$actions_container.find('.stages-container').remove();
			$actions_container.removeClass('bg');
		} else {
			// Initiate edit.
			$icon.addClass('fa-times-circle');
			$icon.removeClass('fa-arrow-circle-right');
			$icon.attr('title', 'Cancel');
			$actions_container.prepend(
				$('<div class="stages-container">').append(
					$stages_select.clone().val($opportunity.attr('stage'))
				)
			);
			$actions_container.addClass('bg');
		}
	});

	/**
	 * Bind to changes on the Opportunity Stage dropdown.
	 */
	$(document).off('change', '#opportunities-columns .opportunity .actions-container select[name="stage"]');
	$(document).on('change', '#opportunities-columns .opportunity .actions-container select[name="stage"]', function(event) {
		var $select = $(this);
		var $opportunity = $select.closest('.opportunity');
		var $actions_container = $opportunity.find('.actions-container');
		var $icon = $opportunity.find('i.fa');
		var opportunity_id = $opportunity.attr('opportunity_id');
		var stage = $select.val();
		if(stage.startsWith('Closed')) {
			activateOverlayZ(BASE_URI + '/dashboard/opportunities/edit', {
				'opportunity_id': opportunity_id
			});
		} else {
			$.ajax({
				'url': BASE_URI + '/dashboard/opportunities/set/stage',
				'method': 'POST',
				'dataType': 'json',
				'data': {
					'opportunity_id': opportunity_id,
					'stage': stage
				}
			});
			$opportunity.slideUp('fast', function() {
				if(stage.startsWith('Prospecting')) {
					$opportunities_columns.find('.stage-prospecting .list').append($opportunity);
				} else if(stage.startsWith('Discovery')) {
					$opportunities_columns.find('.stage-discovery .list').append($opportunity);
				} else if(stage.startsWith('Quoted')) {
					$opportunities_columns.find('.stage-quoted .list').append($opportunity);
				} else if(stage.startsWith('Negotiation')) {
					$opportunities_columns.find('.stage-negotiation .list').append($opportunity);
				} else if(stage.startsWith('Verbal Commitment')) {
					$opportunities_columns.find('.stage-commitment .list').append($opportunity);
				} else if(stage.startsWith('Closed')) {
					$opportunities_columns.find('.stage-closed .list').append($opportunity);
				}
				$opportunity.slideDown('fast', function() {
					renderAmountTotals();
				});
				$opportunity.attr('stage', stage);
			});
		}

		// Cancel edit.
		$icon.removeClass('fa-times-circle');
		$icon.addClass('fa-arrow-circle-right');
		$icon.attr('title', '');
		$actions_container.find('.stages-container').remove();
		$actions_container.removeClass('bg');
	});
</script>

<?php Template::Render('footer', 'account');
