<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_opportunity = $db->query("
	SELECT
		opportunities.opportunity_id,
		logins.initials,
		enteredby_logins.initials AS enteredby_initials,
		opportunities.entered_date,
		opportunities.custno,
		LTRIM(RTRIM(
			CASE WHEN opportunities.custno IS NULL OR LTRIM(RTRIM(opportunities.custno)) = '' THEN
				opportunities.client_name
			ELSE
				arcust.company
			END
		)) AS client_name,
		opportunities.name,
		opportunities.opportunity_type_id,
		opportunity_types.name AS opportunity_type,
		opportunities.stage,
		opportunities.lost_reason,
		opportunities.lost_to,
		opportunities.next_step,
		opportunities.next_step_memo,
		opportunities.amount,
		opportunities.due_date,
		opportunities.close_date,
		opportunities.expires,
		opportunities.competitors,
		opportunities.vendor_lead,
		opportunities.source,
		opportunities.notes,
		opportunities.login_id,
		arcust.pterms,
		offices.name AS office
	FROM
		" . DB_SCHEMA_ERP . ".opportunities
	INNER JOIN
		" . DB_SCHEMA_INTERNAL . ".logins
		ON
		logins.login_id = opportunities.login_id
	LEFT JOIN
		" . DB_SCHEMA_INTERNAL . ".logins AS enteredby_logins
		ON
		enteredby_logins.login_id = opportunities.entered_login_id
	INNER JOIN
		" . DB_SCHEMA_ERP . ".opportunity_types
		ON
		opportunity_types.opportunity_type_id = opportunities.opportunity_type_id
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.custno = opportunities.custno
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".offices
		ON
		offices.terr = opportunities.terr
	WHERE
		opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
");
$opportunity = $grab_opportunity->fetch();

?>

<style type="text/css">
	#view-opportunity .section {
		display:inline-block;
		vertical-align:top;
	}
</style>

<div id="view-opportunity">
	<div class="tab-content-section">
		<h3>General Info</h3>
		<table>
			<tbody>
				<tr>
					<td class="title-width">Salesman</td>
					<td><?php print htmlentities($opportunity['initials']);?></td>
				</tr>
				<tr>
					<td class="title-width">Entered On</td>
					<td><?php print htmlentities($opportunity['entered_date']);?></td>
				</tr>
				<tr>
					<td class="title-width">Entered By</td>
					<td><?php print htmlentities($opportunity['enteredby_initials']);?></td>
				</tr>
				<tr>
					<td class="title-width">Office</td>
					<td><?php print htmlentities($opportunity['office']);?></td>
				</tr>
				<tr>
					<td class="title-width">Opportunity Name</td>
					<td><?php print htmlentities($opportunity['name']);?></td>
				</tr>
				<tr>
					<td class="title-width">Client Type</td>
					<td>
						<?php
						if(empty($opportunity['custno'])) {
							print 'Prospect';
						} else {
							print 'Client';
						}
						?>
					</td>
				</tr>
				
				<?php
				if(empty($opportunity['custno'])) {
					?>
					<tr>
						<td class="title-width">Prospect's Name</td>
						<td><?php print htmlentities($opportunity['client_name']);?></td>
					</tr>
					<?php
				} else {
					?>
					<tr>
						<td class="title-width">Client</td>
						<td><?php print htmlentities($opportunity['custno'] . ' - ' . $opportunity['client_name']);?></td>
					</tr>
					<?php
				}
				?>

				<tr>
					<td class="title-width">Opportunity Type</td>
					<td><?php print htmlentities($opportunity['opportunity_type']);?></td>
				</tr>
				<!--tr>
					<td class="title-width">Stage</td>
					<td><?php print htmlentities($opportunity['stage']);?></td>
				</tr-->
				
				<?php
				if($opportunity['stage'] == 'Closed Lost') {
					?>
					<tr>
						<td class="title-width">Lost To</td>
						<td><?php print htmlentities($opportunity['lost_to']);?></td>
					</tr>
					<tr>
						<td class="title-width">Reason For Losing</td>
						<td><?php print htmlentities($opportunity['lost_reason']);?></td>
					</tr>
					<?php
				}
				?>
				
				<!--tr>
					<td class="title-width">Next Step</td>
					<td><?php print htmlentities($opportunity['next_step']);?></td>
				</tr-->
				
				<?php
				/*if($opportunity['next_step'] == 'Other') {
					?>
					<tr>
						<td class="title-width">Next Step Memo Field</td>
						<td><?php print htmlentities($opportunity['next_step_memo']);?></td>
					</tr>
					<?php
				}*/
				?>
			</tbody>
		</table>
	</div>

	<div class="tab-content-section">
		<h3>Additional Info</h3>

		<table>
			<tbody>
				<!--tr>
					<td class="title-width">Due Date</td>
					<td>
						<?php
						$duedate_timestamp = strtotime($opportunity['due_date']);
						print date('Y-m-d\TH:i', $duedate_timestamp);
						?>
					</td>
				</tr-->

				<?php
				if($opportunity['close_date']) {
					?>
					<tr>
						<td class="title-width">Close Date</td>
						<td>
							<?php
							$closedate_timestamp = strtotime($opportunity['close_date']);
							print date('Y-m-d', $closedate_timestamp);
							?>
						</td>
					</tr>
					<?php
				}
				?>

				<?php
				if($opportunity['expires']) {
					?>
					<tr>
						<td class="title-width">Expires</td>
						<td>
							<?php
							$expires_timestamp = strtotime($opportunity['expires']);
							print date('Y-m-d', $expires_timestamp);
							?>
						</td>
					</tr>
					<?php
				}
				?>

				<tr>
					<td class="title-width">Projected Amount</td>
					<td>$<?php print number_format($opportunity['amount'], 2);?></td>
				</tr>
				
				<tr>
					<td class="title-width">Terms</td>
					<td><?php
						if(empty($opportunity['custno'])) {
							print 'TBD';
						} else {
							print htmlentities($opportunity['pterms']);
						}
					?>
				</tr>
				
				<?php
				if($opportunity['competitors']) {
					?>
					<tr>
						<td class="title-width">Competitors</td>
						<td>
							<?php
							foreach(explode('|', $opportunity['competitors']) as $competitor) {
								?>
								<label class="checkbox competitor">
									<?php print htmlentities($competitor);?>
								</label>
								<?php
							}
							?>
						</td>
					</tr>
					<?php
				}
				?>
					
				<tr>
					<td class="title-width">Vendor Lead</td>
					<td><?php print htmlentities($opportunity['vendor_lead']);?></td>
				</tr>
				<tr>
					<td class="title-width">Source</td>
					<td><?php print htmlentities($opportunity['source']);?></td>
				</tr>
				<!--tr>
					<td class="title-width">Notes</td>
					<td><?php print str_replace("\n", '<br />', str_replace("\r", '', htmlentities($opportunity['notes'])));?></td>
				</tr-->
			</tbody>
		</table>
	</div>
</div>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
