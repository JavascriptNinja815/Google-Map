<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_logs = $db->query("
	SELECT
		opportunity_logs.logged_on,
		opportunity_logs.login_id,
		opportunity_logs.field,
		opportunity_logs.from_value,
		opportunity_logs.to_value,
		logins.initials
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_logs
	LEFT JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		opportunity_logs.login_id = logins.login_id
	WHERE
		opportunity_logs.opportunity_id = " . $db->quote($_REQUEST['opportunity_id']) . "
");

?>

<div id="opportunity-activity-body">
	<table id="opportunities-table" class="table table-small table-striped table-hover columns-sortable columns-filterable">
		<thead>
			<tr>
				<th class="sortable filterable">Logged On</th>
				<th class="sortable filterable">By</th>
				<th class="sortable filterable">Field</th>
				<th class="sortable filterable">Details</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_logs as $log) {
				?>
				<tr>
					<td><?php print htmlentities($log['logged_on']);?></td>
					<td><?php print htmlentities($log['initials']);?></td>
					<td><?php print htmlentities($log['field']);?></td>
					<td><?php 
						if($log['field'] == 'quote') {
							$filename = basename($log['to_value']);
							?>
							<div>Sent To:<?php print htmlentities($log['from_value']);?></div>
							<div>Quote Generated: <span class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/opportunities/quote" overlayz-data="<?php print htmlentities(json_encode(['view' => 'existing', 'filename' => $filename]), ENT_QUOTES);?>"><?php print htmlentities($filename);?></span></div>
							<?php
						} else {
							?>
							<div>From: <?php print htmlentities($log['from_value']);?></div>
							<div>To: <?php print htmlentities($log['to_value']);?></div>
							<?php
						}
					?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$.each($([
		'#opportunity-activity-body table.columns-filterable',
		'#opportunity-activity-body table.columns-sortable',
		'#opportunity-activity-body table.headers-sticky'
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
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
