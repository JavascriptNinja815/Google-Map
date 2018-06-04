<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_opportunities = $db->query("
	SELECT
		opportunities.opportunity_id,
		logins.initials,
		enteredby_logins.initials AS entered_by,
		CONVERT(varchar(10), opportunities.entered_date, 120) AS entered_on,
		opportunities.name,
		opportunities.opportunity_type_id,
		opportunities.stage,
		opportunities.lost_reason,
		opportunities.lost_to,
		opportunities.next_step,
		opportunities.next_step_memo,
		opportunities.amount,
		opportunities.due_date,
		opportunities.close_date,
		opportunities.competitors,
		opportunities.vendor_lead,
		opportunities.source,
		opportunities.notes,
		opportunity_types.name AS opportunity_type
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = opportunities.login_id
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins AS enteredby_logins
		ON
		enteredby_logins.login_id = opportunities.entered_login_id
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.custno = opportunities.custno
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".opportunity_types
		ON
		opportunity_types.opportunity_type_id = opportunities.opportunity_type_id
	WHERE
		opportunities.custno = " . $db->quote(trim($_POST['custno'])) . "
	ORDER BY
		opportunities.due_date
	-- WHERE -- TODO: Constrain results to Sales permissions for viewing.
");

?>

<style type="text/css">
	#opportunities-page .padded {
		padding-left:16px;
		padding-right:16px;
	}
	#opportunities-page .opportunity.prototype {
		display:none;
	}
	button.new-opportunity,
	button.edit-opportunity {
		color:#fff;
	}
</style>

<div id="opportunities-page">
	<table class="table table-small table-striped columns-sortable columns-filterable">
		<thead>
			<tr>
				<th></th>
				<th class="sortable filterable">ID</th>
				<th class="sortable filterable">Entered By</th>
				<th class="sortable filterable">Entered On</th>
				<th class="sortable filterable">Salesman</th>
				<th class="sortable filterable">Name</th>
				<th class="sortable filterable">Client Type</th>
				<th class="sortable filterable">Client</th>
				<th class="sortable filterable">Opportunity Type</th>
				<th class="sortable filterable">Stage</th>
				<th class="sortable filterable">Next Step</th>
				<th class="sortable filterable">Amount</th>
				<th class="sortable filterable">Due Date</th>
				<th class="sortable filterable">Close Date</th>
				<th class="sortable filterable">Competitors</th>
				<th class="sortable filterable">Vendor Lead</th>
				<th class="sortable filterable">Source</th>
				<th class="sortable filterable">Notes</th>
			</tr>
		</thead>
		<tbody id="opportunities-tbody">
			<?php
			if($grab_opportunities->rowCount()) {
				foreach($grab_opportunities as $opportunity) {
					$due_date = new DateTime($opportunity['due_date']);
					$due_date = $due_date->format('Y-m-d h:i A');
					$close_date = '';
					if($opportunity['close_date']) {
						$close_date = new DateTime($opportunity['close_date']);
						$close_date = $close_date->format('Y-m-d');
					}
					$competitors = explode('|', $opportunity['competitors']);
					$overlayz_url = BASE_URI . '/dashboard/clients/details';
					$overlayz_data = json_encode([
						'custno' => trim($opportunity['custno'])
					]);
					?>
					<tr class="opportunity" opportunity_id="<?php print htmlentities($opportunity['opportunity_id'], ENT_QUOTES);?>">
						<td class="actions"><button type="button" class="btn btn-primary edit-opportunity overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/opportunities/edit" overlayz-data="<?php print htmlentities(json_encode(['opportunity_id' => $opportunity['opportunity_id']]));?>">Edit</button></td>
						<td class="opportunityid"><?php print number_format($opportunity['opportunity_id'], 0);?></td>
						<td class="enteredby"><?php print htmlentities($opportunity['entered_by']);?></td>
						<td class="enteredon"><?php print htmlentities($opportunity['entered_on']);?></td>
						<td class="salesman"><?php print htmlentities($opportunity['initials']);?></td>
						<td class="name"><?php print htmlentities($opportunity['name']);?></td>
						<td class="leadtype"><?php
							if(!empty($opportunity['custno'])) {
								print 'Client';
							} else {
								print 'Prospect';
							}
						?></td>
						<?php
						if(!empty($opportunity['custno'])) {
							?><td class="client overlayz-link" overlayz-url="<?php print htmlentities($overlayz_url, ENT_QUOTES);?>" overlayz-data="<?php print htmlentities($overlayz_data, ENT_QUOTES);?>"><?php
								print htmlentities($opportunity['custno']) . ' - ' . htmlentities($opportunity['client_name']);
							?></td><?php
						} else {
							?><td class="client"><?php
								print htmlentities($opportunity['client_name']);
							?></td><?php
						}
						?>
						<td class="opportunity"><?php print htmlentities($opportunity['opportunity_type']);?></td>
						<td class="stage"><?php
							print htmlentities($opportunity['stage']);
							if($opportunity['stage'] === 'Closed Lost') {
								print '<br />Lost To: ' . htmlentities($opportunity['lost_to']);
								print '<br />Reason: ' . htmlentities($opportunity['lost_reason']);
							}
						?></td>
						<td class="nextstep"><?php
							print htmlentities($opportunity['next_step']);
							if($opportunity['next_step'] === 'Other') {
								print ' - ' . $opportunity['next_step_memo'];
							}
						?></td>
						<td class="amount">$<?php print number_format($opportunity['amount'], 0);?></td>
						<td class="duedate"><?php print $due_date;?></td>
						<td class="closedate"><?php print $close_date;?></td>
						<td class="competitors"><?php
							foreach($competitors as $competitor) {
								?><div class="competitor"><?php print htmlentities($competitor);?></div><?php
							}
						?></td>
						<td class="vendorlead"><?php print htmlentities($opportunity['vendor_lead']);?></td>
						<td class="source"><?php print htmlentities($opportunity['source']);?></td>
						<td class="notes"><?php print htmlentities($opportunity['notes']);?></td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr class="opportunity no-opportunities" opportunity_id="">
					<td colspan="actions"></td>
					<td colspan="17">No opportunities found</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
