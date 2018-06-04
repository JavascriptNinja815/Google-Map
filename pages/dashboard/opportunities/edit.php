<?php

$session->ensureLogin();
$session->ensureRole('Sales');

ob_start(); // Start loading output into buffer.

$grab_opportunity_types = $db->query("
	SELECT
		opportunity_types.opportunity_type_id,
		opportunity_types.name
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_types
	ORDER BY
		opportunity_types.name
");

$grab_vendors = $db->query("
	SELECT
		opportunity_vendors.opportunity_vendor_id,
		opportunity_vendors.name
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_vendors
	ORDER BY
		opportunity_vendors.name
");

$grab_competitors = $db->query("
	SELECT
		opportunity_competitors.opportunity_competitor_id,
		opportunity_competitors.name
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_competitors
	ORDER BY
		opportunity_competitors.name
");
$competitors = $grab_competitors->fetchall();

$grab_quote_templates = $db->query("
	SELECT
		opportunity_quotetemplates.quotetemplate_id,
		opportunity_quotetemplates.name,
		opportunity_quotetemplates.default_selection
	FROM
		" . DB_SCHEMA_ERP . ".opportunity_quotetemplates
	ORDER BY
		opportunity_quotetemplates.sort_order,
		opportunity_quotetemplates.name
");
$quote_templates = [];
$quote_template_default = Null;
foreach($grab_quote_templates as $quotetemplate_data) {
	$quotetemplate = [];
	foreach($quotetemplate_data as $key => $value) {
		if(ctype_digit((string)$key)) {
			continue;
		}
		$quotetemplate[$key] = $value;
	}
	if($quotetemplate['default_selection']) {
		$quote_template_default = $quotetemplate['quotetemplate_id'];
	}
	$quote_templates[] = $quotetemplate;
}

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
		$permission_constraint = "arcust.salesmn IN (" . implode(',', $permissions) . ")";
	} else {
		// If the user has not been granted any privs, then finish the query which will already return nothing.
		// TODO: There has to be a more elegant method of handling this...
		$permission_constraint = "1 != 1";
	}
} else {
	$permission_constraint = "1 != 1"; // Don't show any.
}

$grab_clients = $db->query("
	SELECT
		arcust.custno,
		arcust.company,
		arcust.salesmn
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		" . $permission_constraint . "
	ORDER BY
		arcust.custno
");
$clients = [];
foreach($grab_clients as $client) {
	$clients[trim($client['custno'])] = strtoupper(
		trim($client['custno']) . ' - ' . trim($client['company'])
	);
}

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

$grab_offices = $db->query("
	SELECT
		offices.office_id,
		offices.name,
		offices.terr
	FROM
		" . DB_SCHEMA_ERP . ".offices
	ORDER BY
		offices.name
");

if(!empty($_POST['opportunity_id'])) {
	$grab_opportunity = $db->query("
		SELECT
			opportunities.opportunity_id,
			logins.initials,
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
			--opportunities.contacts,
			opportunities.terr,
			opportunities.vendor_ref,
			opportunities.quotetemplate_id
		FROM
			" . DB_SCHEMA_ERP . ".opportunities
		INNER JOIN
			" . DB_SCHEMA_INTERNAL . ".logins
			ON
			logins.login_id = opportunities.login_id
		LEFT JOIN
			" . DB_SCHEMA_ERP . ".arcust
			ON
			arcust.custno = opportunities.custno
		WHERE
			opportunities.opportunity_id = " . $db->quote($_POST['opportunity_id']) . "
	");
	$opportunity = $grab_opportunity->fetch();
	//$opportunity['contacts'] = json_decode($opportunity['contacts'], True);

	/*$contacts = new ArrayObject();
	if(!empty($opportunity['custno'])) {
		$custno = explode('-', $opportunity['custno']);
		$custno = trim($custno[0]);
		$grab_contacts = $db->query("
			SELECT
				sf_contacts.sf_contact_id,
				sf_contacts.FirstName,
				sf_contacts.LastName,
				sf_contacts.Title
			FROM
				" . DB_SCHEMA_ERP . ".sf_contacts
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				arcust.sfid LIKE (sf_contacts.AccountId + '%')
			WHERE
				arcust.custno = " . $db->quote($custno) . "
			ORDER BY
				sf_contacts.FirstName,
				sf_contacts.LastName
		");
		foreach($grab_contacts as $contact) {
			$contacts[$contact['sf_contact_id']] = $contact['FirstName'] . ' ' . $contact['LastName'] . (!empty($contact['Title']) ? ' - ' . $contact['Title'] : Null);
		}
	}*/
}
?>

<style type="text/css">
	#new-opportunity-container {
		position:absolute;
		top:0px;
		left:0px;
		right:0px;
		bottom:48px;
		overflow:auto;
	}
	#new-opportunity .section {
		display:inline-block;
		vertical-align:top;
	}
	#new-opportunity .competitor {
		width:220px;
		float:left;
	}
	#new-opportunity .floatable {
		position:absolute;
		bottom:0px;
		left:12px;
		padding-bottom:6px;
		height:32px;
	}
	#new-opportunity .add-contact {
		cursor:pointer;
	}
	#new-opportunity .error {
		color:#f00;
	}
	/*#new-opportunity .contacts-container .contact {
		display:inline-block;
		margin:12px;
	}
	#new-opportunity .contacts-container.is-prospect .contact .sf_contact {
		display:none;
	}
	#new-opportunity .contacts-container.is-client .contact .name,
	#new-opportunity .contacts-container.is-client .contact .title,
	#new-opportunity .contacts-container.is-client .contact .phone,
	#new-opportunity .contacts-container.is-client .contact .email {
		display:none;
	}*/
</style>

<form class="form-horizontal" id="new-opportunity" action="<?php print BASE_URI;?>/dashboard/opportunities/<?php print isset($opportunity) ? 'update' : 'create';?>" method="POST">
	<div id="new-opportunity-container" class="padded">
		<h3><?php print isset($opportunity) ? 'Edit' : 'New';?> Opportunity</h3>

		<?php
		if(isset($opportunity)) {
			?><input type="hidden" name="opportunity_id" value="<?php print htmlentities($opportunity['opportunity_id'], ENT_QUOTES);?>" /><?php
		}
		?>

		<div class="section">
			<div class="control-group">
				<label class="control-label" for="opportunity-login_id">Salesman</label>
				<div class="controls">
					<select name="login_id" id="opportunity-login_id" required>
						<option value="">-- Required --</option>
						<?php
						foreach($grab_logins as $login) {
							$selected = '';
							if(isset($opportunity) && $login['login_id'] == $opportunity['login_id']) {
								$selected = 'selected';
							} else if(!isset($opportunity) && $session->login['login_id'] == $login['login_id']) {
								$selected = 'selected';
							}
							?><option value="<?php print htmlentities($login['login_id'], ENT_QUOTES);?>" <?php print $selected;?>><?php print htmlentities($login['initials'] . ' - ' . $login['first_name'] . ' ' . $login['last_name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="opportunity-name">Opportunity Name</label>
				<div class="controls">
					<input type="text" name="name" id="opportunity-name" value="<?php print isset($opportunity) ? htmlentities($opportunity['name'], ENT_QUOTES) : Null;?>" required />
				</div>
			</div>

			<div class="control-group">
				<label class="control-label">Client Type</label>
				<div class="controls">
					<label class="radio">
						<input type="radio" name="clienttype" value="prospect" <?php print !isset($opportunity) || isset($opportunity) && empty($opportunity['custno']) ? 'checked' : Null;?> required />
						Prospect
					</label>
					<label class="radio">
						<input type="radio" name="clienttype" value="client" <?php print isset($opportunity) && !empty($opportunity['custno']) ? 'checked' : Null;?> />
						Client
					</label>
				</div>
			</div>

			<div class="control-group client_name-container <?php print isset($opportunity) && !empty($opportunity['custno']) ? 'hidden' : Null;?>">
				<label class="control-label" for="opportunity-client_name">Prospect's Name</label>
				<div class="controls">
					<input type="text" name="client_name" id="opportunity-client_name" value="<?php print isset($opportunity) && empty($opportunity['custno']) ? htmlentities($opportunity['client_name'], ENT_QUOTES) : Null;?>" <?php print !isset($opportunity) || isset($opportunity) && empty($opportunity['custno']) ? 'required' : Null;?> />
				</div>
			</div>

			<div class="control-group custno-container <?php print !isset($opportunity) || (isset($opportunity) && empty($opportunity['custno'])) ? 'hidden' : Null;?>">
				<label class="control-label" for="opportunity-custno">Client</label>
				<div class="controls">
					<input type="text" name="custno" id="opportunity-custno" value="<?php print isset($opportunity) && !empty($opportunity['custno']) ? htmlentities($opportunity['custno'] . ' - ' . $opportunity['client_name'], ENT_QUOTES) : Null;?>" <?php print isset($opportunity) && !empty($opportunity['custno']) ? 'required readonly' : Null;?> />
				</div>
			</div>

			<div class="control-group office-container">
				<label class="control-label" for="opportunity-office">Office</label>
				<div class="controls">
					<select name="office" id="opportunity-office">
						<option value=""></option>
						<?php
						foreach($grab_offices as $office) {
							?><option value="<?php print htmlentities($office['terr'], ENT_QUOTES);?>" <?php print isset($opportunity) && !empty($opportunity['terr']) && $opportunity['terr'] == $office['terr'] ? 'selected' : Null;?>><?php print htmlentities($office['name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="opportunity-type">Opportunity Type</label>
				<div class="controls">
					<select name="opportunity_type_id" id="opportunity-type" required>
						<option value="">-- Required --</option>
						<?php
						foreach($grab_opportunity_types as $opportunity_type) {
							?><option value="<?php print htmlentities($opportunity_type['opportunity_type_id'], ENT_QUOTES);?>" <?php print isset($opportunity) && $opportunity['opportunity_type_id'] == $opportunity_type['opportunity_type_id'] ? 'selected' : Null;?>><?php print htmlentities($opportunity_type['name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="opportunity-stage">Stage</label>
				<div class="controls">
					<select name="stage" id="opportunity-stage" required>
						<option value="">-- Required --</option>
						<!--option value="Prospecting" <?php print isset($opportunity) && $opportunity['stage'] == 'Prospecting' ? 'selected' : Null;?>>1. Prospecting</option-->
						<optgroup label="1. Discovery">
							<option value="Discovery - Site Survey" <?php print isset($opportunity) && $opportunity['stage'] == 'Discovery - Site Survey' ? 'selected' : Null;?>>Site Survey</option>
							<option value="Discovery - Gather Info" <?php print (isset($opportunity) && $opportunity['stage'] == 'Discovery - Gather Info') || !isset($opportunity) ? 'selected' : Null;?>>Gather Info</option>
							<option value="Discovery - Other" <?php print isset($opportunity) && ($opportunity['stage'] == 'Discovery - Other' || $opportunity['stage'] == 'Needs Discovery') ? 'selected' : Null;?>>Other</option>
						</optgroup>
						<option value="Quoted" <?php print isset($opportunity) && ($opportunity['stage'] == 'Quoted' || $opportunity['stage'] == 'Proposal Delivered/Quoted') ? 'selected' : Null;?>>2. Quoted</option>
						<optgroup label="3. Negotiation">
							<option value="Negotiation - Samples" <?php print isset($opportunity) && $opportunity['stage'] == 'Negotiation - Samples' ? 'selected' : Null;?>>Samples</option>
							<option value="Negotiation - Price" <?php print isset($opportunity) && $opportunity['stage'] == 'Negotiation - Price' ? 'selected' : Null;?>>Price</option>
							<option value="Negotiation - Delivery" <?php print isset($opportunity) && $opportunity['stage'] == 'Negotiation - Delivery' ? 'selected' : Null;?>>Delivery</option>
							<option value="Negotiation - Other" <?php print isset($opportunity) && ($opportunity['stage'] == 'Negotiation - Other' || $opportunity['stage'] == 'Price Negotiation') ? 'selected' : Null;?>>Other</option>
						</optgroup>
						<!--option value="Stalled" <?php print isset($opportunity) && $opportunity['stage'] == 'Stalled' ? 'selected' : Null;?>>5. Stalled</option-->
						<option value="Verbal Commitment" <?php print isset($opportunity) && $opportunity['stage'] == 'Verbal Commitment' ? 'selected' : Null;?>>4. Verbal Commitment</option>
						<optgroup label="5. Won/Lost">
							<option value="Closed Won" <?php print isset($opportunity) && $opportunity['stage'] == 'Closed Won' ? 'selected' : Null;?>>Closed Won</option>
							<option value="Closed - Never Ordered" <?php print isset($opportunity) && $opportunity['stage'] == 'Closed - Never Ordered' ? 'selected' : Null;?>>Closed - Never Ordered</option>
							<option value="Closed Lost" <?php print isset($opportunity) && $opportunity['stage'] == 'Closed Lost' ? 'selected' : Null;?>>Closed Lost</option>
						</optgroup>
					</select>
				</div>
			</div>

			<div class="control-group lostto-container <?php print !isset($opportunity) || (isset($opportunity) && $opportunity['stage'] != 'Closed Lost') ? 'hidden' : Null;?>">
				<label class="control-label" for="opportunity-lostto">Lost To</label>
				<div class="controls">
					<select name="lost_to" id="opportunity-lostto" <?php print isset($opportunity) && $opportunity['stage'] == 'Closed Lost' ? 'required' : Null;?>>
						<option value="">-- Required --</option>
						<?php
						foreach($competitors as $competitor) {
							?><option value="<?php print htmlentities($competitor['name'], ENT_QUOTES);?>" <?php print isset($opportunity) && $opportunity['lost_to'] == $competitor['name'] ? 'selected' : Null;?>><?php print htmlentities($competitor['name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>

			<div class="control-group lostreason-container <?php print !isset($opportunity) || (isset($opportunity) && $opportunity['stage'] != 'Closed Lost') ? 'hidden' : Null;?>">
				<label class="control-label" for="opportunity-lostreason">Reason For Losing</label>
				<div class="controls">
					<select name="lost_reason" id="opportunity-lostreason" <?php print isset($opportunity) && $opportunity['stage'] == 'Closed Lost' ? 'required' : Null;?>>
						<option value="">-- Required --</option>
						<option value="Cost" <?php print isset($opportunity) && $opportunity['lost_reason'] == 'Cost' ? 'selected' : Null;?>>Cost</option>
						<option value="Delivery" <?php print isset($opportunity) && $opportunity['lost_reason'] == 'Delivery' ? 'selected' : Null;?>>Delivery</option>
						<option value="No Idea" <?php print isset($opportunity) && $opportunity['lost_reason'] == 'No Idea' ? 'selected' : Null;?>>No Idea</option>
					</select>
				</div>
			</div>

			<!--div class="control-group">
				<label class="control-label" for="opportunity-nextstep">Next Step</label>
				<div class="controls">
					<select name="next_step" id="opportunity-nextstep" required>
						<option value="">-- Required --</option>
						<option value="Site Survey" <?php print isset($opportunity) && $opportunity['next_step'] == 'Site Survey' ? 'selected' : Null;?>>Site Survey</option>
						<option value="Team Feasibility" <?php print isset($opportunity) && $opportunity['next_step'] == 'Team Feasibility' ? 'selected' : Null;?>>Team Feasibility</option>
						<option value="Samples" <?php print isset($opportunity) && $opportunity['next_step'] == 'Samples' ? 'selected' : Null;?>>Samples</option>
						<option value="Follow Up" <?php print isset($opportunity) && $opportunity['next_step'] == 'Follow Up' ? 'selected' : Null;?>>Follow Up</option>
						<option value="Proposal/Quote" <?php print isset($opportunity) && $opportunity['next_step'] == 'Proposal/Quote' ? 'selected' : Null;?>>Proposal/Quote</option>
						<option value="Gather Info" <?php print isset($opportunity) && $opportunity['next_step'] == 'Gather Info' ? 'selected' : Null;?>>Gather Info</option>
						<option value="Other" <?php print isset($opportunity) && $opportunity['next_step'] == 'Other' ? 'selected' : Null;?>>Other</option>
					</select>
				</div>
			</div>

			<div class="control-group nextstepmemo-container <?php print !isset($opportunity) || $opportunity['next_step'] != 'Other' ? 'hidden' : Null;?>">
				<label class="control-label" for="opportunity-nextstepmemo">Next Step Memo Field</label>
				<div class="controls">
					<input type="text" name="next_step_memo" id="opportunity-nextstepmemo" value="<?php print isset($opportunity) ? htmlentities($opportunity['next_step_memo'], ENT_QUOTES) : Null;?>" <?php print isset($opportunity) && $opportunity['next_step'] == 'Other' ? 'required' : Null;?> />
				</div>
			</div-->
		</div>

		<div class="section">
			<!--div class="control-group ">
				<label class="control-label" for="opportunity-duedate">Due Date</label>
				<div class="controls">
					<?php
					if(isset($opportunity)) {
						$duedate_timestamp = strtotime($opportunity['due_date']);
					} else {
						$one_hour = 60*60;
						$four_hours = $one_hour * 4;
						$duedate_timestamp = time() + $four_hours;
					}
					?>
					<input type="datetime-local" name="due_date" id="opportunity-duedate" value="<?php print date('Y-m-d\TH:i', $duedate_timestamp);?>" required />
				</div>
			</div-->

			<div class="control-group">
				<label class="control-label" for="opportunity-closedate">Close Date</label>
				<div class="controls">
					<?php
					if(isset($opportunity)) {
						$closedate_timestamp = strtotime($opportunity['close_date']);
					} else {
						$closedate_timestamp = Null;
					}
					?>
					<input type="date" name="close_date" id="opportunity-closedate" value="<?php print !empty($closedate_timestamp) ? date('Y-m-d', $closedate_timestamp) : Null;?>" />
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="opportunity-expires">Expires</label>
				<div class="controls">
					<?php
					if(isset($opportunity)) {
						if(!empty($opportunity['expires'])) {
							$expires_timestamp = strtotime($opportunity['expires']);
						} else {
							$expires_timestamp = Null;
						}
					} else {
						$expires_timestamp = time() + (60 * 60 * 24 * 30); // 30 days on the future.
					}
					?>
					<input type="date" name="expires" id="opportunity-expires" value="<?php print !empty($expires_timestamp) ? date('Y-m-d', $expires_timestamp) : Null;?>" />
				</div>
			</div>

			<div class="control-group ">
				<label class="control-label" for="opportunity-amount">Amount</label>
				<div class="controls">
					<div class="input-prepend input-append">
						<span class="add-on">$</span>
						<input type="number" name="amount" precision="0" id="opportunity-amount" value="<?php print isset($opportunity) ? htmlentities($opportunity['amount'], ENT_QUOTES) : Null;?>" class="span2" required />
						<span class="add-on">.00</span>
					</div>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="opportunity-vendorlead">Vendor Lead</label>
				<div class="controls">
					<select name="vendor_lead" id="opportunity-vendorlead">
						<option value="">-- Optional --</option>
						<?php
						foreach($grab_vendors as $vendor) {
							?><option value="<?php print htmlentities($vendor['name'], ENT_QUOTES);?>" <?php print isset($opportunity) && $opportunity['vendor_lead'] == $vendor['name'] ? 'selected' : Null;?>><?php print htmlentities($vendor['name']);?></option><?php
						}
						?>
					</select>
				</div>
			</div>

			<div class="control-group vendor-ref-container <?php print !isset($opportunity) || (isset($opportunity) && !$opportunity['vendor_lead']) ? 'hidden' : Null;?>">
				<label class="control-label" for="opportunity-vendorlead">Vendor Ref</label>
				<div class="controls">
					<input type="text" name="vendor_ref" id="opportunity-vendorref" value="<?php print isset($opportunity) ? htmlentities($opportunity['vendor_ref'], ENT_QUOTES) : Null;?>" />
				</div>
			</div>

			<div class="control-group">
				<label class="control-label" for="opportunity-source">Source</label>
				<div class="controls">
					<select name="source" id="opportunity-source">
						<option value="">-- Optional --</option>
						<option value="Web">Web</option>
					</select>
				</div>
			</div>

			<?php
			if(count($quote_templates) === 1) {
				?><input type="hidden" name="quotetemplate_id" value="<?php print htmlentities($quote_templates[0]['quotetemplate_id'], ENT_QUOTES);?>" /><?php
			} else {
				?>
				<div class="control-group">
					<label class="control-label" for="opportunity-quotetemplate">Quote Template</label>
					<div class="controls">
						<select name="quotetemplate_id" id="opportunity-quotetemplate">
							<?php
							if(!$quote_template_default && !isset($opportunity)) {
								?><option value="">-- Optional --</option><?php
							}
							foreach($quote_templates as $quotetemplate) {
								?><option value="<?php print htmlentities($quotetemplate['quotetemplate_id'], ENT_QUOTES);?>" <?php print (isset($opportunity) && $opportunity['quotetemplate_id'] == $quotetemplate['quotetemplate_id']) || (!isset($opportunity) && $quotetemplate['quotetemplate_id'] == $quote_template_default) ? 'selected' : Null;?>><?php print htmlentities($quotetemplate['name']);?></option><?php
							}
							?>
						</select>
					</div>
				</div>
				<?php
			}
			?>

			<!--
			<div class="control-group">
				<label class="control-label" for="opportunity-source">Source</label>
				<div class="controls">
					<input type="text" name="source" value="<?php print isset($opportunity) ? htmlentities($opportunity['source'], ENT_QUOTES) : Null;?>" />
				</div>
			</div>
			-->

			<!--div class="control-group">
				<label class="control-label" for="opportunity-notes">Notes</label>
				<div class="controls">
					<textarea name="notes" cols="50" rows="4" id="opportunity-notes"><?php print isset($opportunity) ? htmlentities($opportunity['notes']) : Null;?></textarea>
				</div>
			</div-->
		</div>

		<!--br style="clear:both;" />
		<h4>Contacts</h4>
		<span class="add-contact"><i class="fa fa-plus action action-add"></i> New Contact</span>
		<div class="contacts-container <?php print !isset($opportunity) || isset($opportunity) && empty($opportunity['custno']) ? 'is-prospect' : 'is-client';?>">
			<?php
			if(isset($opportunity) && !empty($opportunity['contacts'])) {
				foreach($opportunity['contacts'] as $offset => $contact) {
					if(!isset($contact['title'])) {
						$contact['title'] = ''; // Backward compatibility, prevent errors from showing
					}
					?>
					<div class="contact">
						<table>
							<thead>
								<tr>
									<th colspan="2" style="text-align:center;"><i class="fa fa-minus action action-remove"></i></th>
								</tr>
							</thead>
							<tbody class="contact-tbody">
								<tr class="name">
									<th>Name</th>
									<td><input type="text" name="contacts[name][]" value="<?php print htmlentities($contact['name'], ENT_QUOTES);?>" /></td>
								</tr>
								<tr class="sf_contact">
									<th>SF Contact</th>
									<td>
										<select name="contacts[sf_contact_id][]">
											<option value="">-- Select --</option>
											<?php
											foreach($contacts as $sf_contact_id => $contact_name) {
												?><option value="<?php print htmlentities($sf_contact_id, ENT_QUOTES);?>" <?php print $contact['sf_contact_id'] == $sf_contact_id ? 'selected' : Null;?>><?php print htmlentities($contact_name);?></option><?php
											}
											?>
										</select>
									</td>
								</tr>
								<tr class="title">
									<th>Title</th>
									<td><input type="text" name="contacts[title][]" value="<?php print htmlentities($contact['title'], ENT_QUOTES);?>" /></td>
								</tr>
								<tr class="phone">
									<th>Phone</th>
									<td><input type="text" name="contacts[phone][]" value="<?php print htmlentities($contact['phone'], ENT_QUOTES);?>" /></td>
								</tr>
								<tr class="email">
									<th>E-Mail</th>
									<td><input type="text" name="contacts[email][]" value="<?php print htmlentities($contact['email'], ENT_QUOTES);?>" /></td>
								</tr>
								<tr class="memo">
									<th>Memo</th>
									<td><input type="text" name="contacts[memo][]" value="<?php print htmlentities($contact['memo'], ENT_QUOTES);?>" /></td>
								</tr>
							</tbody>
						</table>
					</div>
					<?php
				}
			}
			?>
		</div-->

		<br style="clear:both;" />
		<h4>Competitors</h4>
		<div>
			<?php
			$existing_competitors = [];
			if(isset($opportunity) && !empty($opportunity['competitors'])) {
				$existing_competitors = explode('|', $opportunity['competitors']);
			}
			foreach($competitors as $competitor) {
				?>
				<label class="checkbox competitor">
					<input type="checkbox" name="competitors[]" value="<?php print htmlentities($competitor['name'], ENT_QUOTES);?>" <?php print in_array($competitor['name'], $existing_competitors) ? 'checked' : Null;?> /> <?php print htmlentities($competitor['name']);?>
				</label>
				<?php
			}
			?>
		</div>
	</div>

	<div class="floatable">
		<button type="submit" class="btn btn-primary"><?php print isset($opportunity) ? 'Save Changes' : 'Create';?></button>
	</div>
</form>

<script type="text/javascript">
	// Populate list of clients for later reference.
	var clients = <?php print json_encode($clients);?>;

	<?php
	if(!empty($contacts)) {
		?>var contacts = <?php print json_encode($contacts);?>;<?php
	} else {
		?>var contacts = {};<?php
	}
	?>

	// Implement autocomplete
	$('#opportunity-custno').autoComplete({
		'minChars': 1,
		'source': function(term, suggestions) {
			term = term.toUpperCase();
			var matches = [];
			$.each(clients, function(i, client) {
				if(client.indexOf(term) > -1) {
					matches.push(client);
				}
			});
			suggestions(matches);
		}
	});

	/**
	 * Bind to blur on Opportunity Cust No, to load contacts relating to that customer.
	 */
	$(document).off('change', '#opportunity-custno');
	$(document).on('change', '#opportunity-custno', function(event) {
		var $custno = $(this);
		// Populate contacts global for populating SF Contact dropdown in Contacts.
		$.ajax({
			'url': BASE_URI + '/dashboard/opportunities/contacts',
			'data': {
				'custno': $custno.val().split('-')[0].trim()
			},
			'dataType': 'json',
			'method': 'POST',
			'success': function(response) {
				contacts = response.contacts;

				$.each($('#new-opportunity .contacts-container .contact [name="contacts[sf_contact_id][]"]'), function(offset, contact_select) {
					var $contact_select = $(contact_select);
					$contact_select.empty();
					$contact_select.append(
						$('<option>').val('').text('-- Select --')
					);
					$.each(contacts, function(sf_contact_id, contact) {
						$contact_select.append(
							$('<option>').val(sf_contact_id).text(contact)
						);
					});
				});
			}
		});
	});

	$(document).off('change', '#new-opportunity :input[name="clienttype"]');
	$(document).on('change', '#new-opportunity :input[name="clienttype"]', function(event) {
		var $radio = $(this);
		var type = $radio.val();

		var $clientname_container = $('#new-opportunity .client_name-container');
		var $office_container = $('#new-opportunity .office-container');
		var $custno_container = $('#new-opportunity .custno-container');
		var $contacts_container = $('#new-opportunity-container .contacts-container');

		//$office_container.find(':input[name="office"]').prop('required', true).show();
		if(type === 'prospect') {
			$custno_container.slideUp('fast');
			$clientname_container.slideDown('fast');
			//$office_container.slideDown('fast');
			$custno_container.find(':input[name="custno"]').prop('required', false);
			$clientname_container.find(':input[name="client_name"]').prop('required', true).focus();

			$contacts_container.removeClass('is-client').addClass('is-prospect');
		} else if(type === 'client') {
			$clientname_container.slideUp('fast');
			//$office_container.slideUp('fast');
			$custno_container.slideDown('fast');
			$clientname_container.find(':input[name="client_name"]').prop('required', false);
			$custno_container.find(':input[name="custno"]').prop('required', true).focus();

			$contacts_container.removeClass('is-prospect').addClass('is-client');
		}
		// Immediately validate the custno. Or if switching from Prospect to Client, re-enable submit button.
		$('#new-opportunity :input[name="custno"]').trigger('change');
	});

	$(document).off('change', '#new-opportunity :input[name="stage"]');
	$(document).on('change', '#new-opportunity :input[name="stage"]', function(event) {
		var $stage = $(this);
		var stage = $stage.val();

		var $lostto = $('#new-opportunity .lostto-container');
		var $lostreason = $('#new-opportunity .lostreason-container');

		if(stage === 'Closed Lost') {
			$lostto.slideDown('fast');
			$lostto.find(':input[name="lost_to"]').prop('required', true).focus();
			$lostreason.slideDown('fast');
			$lostreason.find(':input[name="lost_reason"]').prop('required', true);
		} else {
			$lostto.slideUp('fast');
			$lostto.find(':input[name="lost_to"]').prop('required', false);
			$lostreason.slideUp('fast');
			$lostreason.find(':input[name="lost_reason"]').prop('required', false);
		}
	});

	/*$(document).off('change', '#new-opportunity :input[name="next_step"]');
	$(document).on('change', '#new-opportunity :input[name="next_step"]', function(event) {
		var $next_step = $(this);
		var next_step = $next_step.val();

		var $nextstepmemo_container = $('#new-opportunity .nextstepmemo-container');

		if(next_step === 'Other') {
			$nextstepmemo_container.slideDown('fast');
			$nextstepmemo_container.find(':input[name="next_step_memo"]').prop('required', true).focus();
		} else {
			$nextstepmemo_container.slideUp('fast');
			$nextstepmemo_container.find(':input[name="next_step_memo"]').prop('required', false);
		}
	});*/

	/**
	 * BIND TO FORM SUBMISSIONS
	 */
	$(document).off('submit', '#new-opportunity');
	$(document).on('submit', '#new-opportunity', function(event) {
		var $form = $(this);
		var data = new FormData(this);

		var $saving_overlayz = $.overlayz({
			'html': $ajax_loading_prototype.clone(),
			'css': {
				'body': {
					'width': 300,
					'height': 300,
					'border-radius': 150,
					'border': 0,
					'padding': 0,
					'line-height': '300px'
				}
			},
			'close-actions': false // Prevent the user from being able to close the overlay on demand.
		}).hide();
		$saving_overlayz.fadeIn();

		$.ajax({
			'url': $form.attr('action'),
			'method': $form.attr('method'),
			'data': data,
			'dataType': 'json',
			'processData': false,
			'contentType': false,
			'enctype': 'multipart/form-data',
			'success': function(response) {
				if(!response.success) {
					if(response.message) {
						alert(response.message);
					} else {
						alert('Something didn\'t go right');
					}
					return;
				}

				if($form.attr('action').endsWith('create')) { // Create
					var $link = $('<div class="overlayz-link">').attr('overlayz-url', BASE_URI + '/dashboard/opportunities/details').attr('overlayz-data', JSON.stringify({'opportunity_id': response.opportunity_id, 'tab': 'lineitems'}));
					$link.appendTo($form);
					$link.click();
				} else { // Update
					// Nothing else to do right now. In the future, perhaps it should add entry to global Opportunities data object and re-render the page?
				}

				// Close "New" overlay.
				$form.closest('.overlayz').fadeOut('fast', function() {
					$(this).remove();
				});
				$saving_overlayz.remove();

			}
		});
		return false;
	});

	/**
	 * Bind to clicks on "Add Contact" icon.
	 */
	$(document).off('click', '#new-opportunity .add-contact');
	$(document).on('click', '#new-opportunity .add-contact', function(event) {
		var $contacts = $('#new-opportunity .contacts-container');
		var $contact = $('<div class="contact">').append(
			$('<table>').append(
				$('<thead>').append(
					$('<tr>').append(
						$('<th colspan="2" style="text-align:center;"><i class="fa fa-minus action action-remove"></i></th>')
					)
				),
				$('<tbody class="contact-tbody">').append(
					$('<tr class="name">').append(
						$('<th>Name</th>'),
						$('<td><input type="text" name="contacts[name][]" /></td>')
					),
					$('<tr class="title">').append(
						$('<th>Title</th>'),
						$('<td><input type="text" name="contacts[title][]" /></td>')
					),
					$('<tr class="sf_contact">').append(
						$('<th>SF Contact</th>'),
						$('<td>').append(
							$('<select name="contacts[sf_contact_id][]" />')
						)
					),
					$('<tr class="phone">').append(
						$('<th>Phone</th>'),
						$('<td><input type="text" name="contacts[phone][]" /></td>')
					),
					$('<tr class="email">').append(
						$('<th>E-Mail</th>'),
						$('<td><input type="text" name="contacts[email][]" /></td>')
					),
					$('<tr class="memo">').append(
						$('<th>Memo</th>'),
						$('<td><input type="text" name="contacts[memo][]" /></td>')
					)
				)
			)
		).hide().appendTo($contacts).slideDown('fast');

		// Populate SF Contact drop-down.
		if(contacts) {
			var $sf_contact_id = $contact.find('[name="contacts[sf_contact_id][]"]');
			$sf_contact_id.append(
				$('<option>').val('').text('-- Select --')
			);
			console.log('CONTACTS:', contacts);
			$.each(contacts, function(sf_contact_id, contact) {
				var $contact = $('<option>').val(sf_contact_id).text(contact);
				$contact.appendTo($sf_contact_id);
			});
		}
	});

	/**
	 * Bind to clicks on "Remove Contact" icon.
	 */
	$(document).off('click', '#new-opportunity .contact .action-remove');
	$(document).on('click', '#new-opportunity .contact .action-remove', function(event) {
		var $contact = $(this).closest('.contact');
		$contact.slideUp('fast', function() {
			$contact.remove();
		});
	});

	/**
	 * Bind to changes on "Client" input.
	 */
	$(document).off('change', '#new-opportunity #opportunity-custno');
	$(document).on('change', '#new-opportunity #opportunity-custno', function(event) {
		var $custno = $(this);
		var $clienttype = $('#new-opportunity :input[name="clienttype"][value="client"]');
		var $submit_button = $('#new-opportunity button[type="submit"]');
		
		// Remove any existing errors.
		$custno.parent().find('.error').slideUp('fast', function() {
			$(this).remove();
		});
		
		// Only validate if Client Type is set to "Client".
		if($clienttype.is(':checked')) {
			var custno = $custno.val();
			if(!custno) {
				// Blank custno, disable submission.
				$submit_button.prop('disabled', true);
				$custno.parent().append(
					' ',
					$('<span class="error">').text('Invalid')
				);
			} else {
				var $verify_overlayz = $.overlayz({
					'html': $ajax_loading_prototype.clone(),
					'css': {
						'body': {
							'width': 300,
							'height': 300,
							'border-radius': 150,
							'border': 0,
							'padding': 0,
							'line-height': '300px'
						}
					},
					'close-actions': false // Prevent the user from being able to close the overlay on demand.
				}).hide();
				$verify_overlayz.fadeIn('fast');
				$.ajax({
					'url': BASE_URI + '/dashboard/opportunities/client/validate',
					'method': 'POST',
					'data': {
						'custno': custno
					},
					'dataType': 'json',
					'success': function(response) {
						$verify_overlayz.fadeOut('fast', function() {
							$verify_overlayz.remove();
						});
						if(!response.success) {
							$submit_button.prop('disabled', true);
							$custno.parent().append(
								' ',
								$('<span class="error">').text('Invalid')
							);
							return;
						}
						$submit_button.prop('disabled', false);
					}
				});
			}
		} else {
			$submit_button.prop('disabled', false);
		}
	});

	/**
	* Bind to changes on "Vendor Lead" input.
	*/
	$(document).off('change', '#new-opportunity :input[name="vendor_lead"]');
	$(document).on('change', '#new-opportunity :input[name="vendor_lead"]', function(event) {
		var $vendor_lead = $(this);
		var $vendor_ref_container = $('#new-opportunity .vendor-ref-container');
		var $vendor_ref = $vendor_ref_container.find(':input[name="vendor_ref"]');
		if($vendor_lead.val()) {
			$vendor_ref_container.slideDown('fast');
		} else {
			$vendor_ref_container.slideUp('fast');
			$vendor_ref.val('');
		}
	});
</script>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
