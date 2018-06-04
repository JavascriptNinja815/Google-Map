<?php

$session->ensureLogin();
$session->ensureRole('Sales');

// Define the list of salesmen user has permission to view Opportunities for.
$permissions = [
	$session->login['initials']
];

// Grab all order viewing permissions.
$view_permissions = [ $session->login['initials'] ];
$view_permissions_fromdb = $session->getPermissions('Sales', 'view-orders');
if($view_permissions_fromdb) {
	$view_permissions = array_merge(
		$view_permissions,
		$view_permissions_fromdb
	);
}
$view_permissions_sanitized = [];
foreach($view_permissions as $permission) {
	$view_permissions_sanitized[] = $db->quote($permission);
}

// Grab all order editing permissions.
$edit_permissions = [ $session->login['initials'] ];
$edit_permissions_fromdb = $session->getPermissions('Sales', 'edit-orders');
if($edit_permissions_fromdb) {
	$edit_permissions = array_merge(
		$edit_permissions,
		$edit_permissions_fromdb
	);
}
$edit_permissions_sanitized = [];
foreach($edit_permissions as $permission) {
	$edit_permissions_sanitized[] = $db->quote($permission);
}

// Combine viewing and editing order permissions.
$all_permissions_sanitized = array_unique(
	array_merge($view_permissions_sanitized, $edit_permissions_sanitized)
);

$where = [];
// Me or My Team
if(isset($_REQUEST['me-vs-team']) && $_REQUEST['me-vs-team']) {
	if(!$session->hasRole('Administration')) {
		// My team
		$where[] = "logins.initials IN (" . implode(', ', $all_permissions_sanitized) . ")";
	} else {
		// Me
		$where[] = "1 = 1";
	}
} else {
	$where[] = "logins.initials = " . $db->quote($session->login['initials']);
}

// All or Open
if(isset($_REQUEST['open-vs-all']) && $_REQUEST['open-vs-all']) {
	// All
	$where[] = "1 = 1";
} else {
	// Open only.
	$where[] = "opportunities.stage NOT IN ('Closed Won', 'Closed - Never Ordered', 'Closed Lost')";
}

if(!empty($_POST['salesman'])) {
	$where[] = "opportunities.login_id = " . $db->quote($_POST['salesman']);
}

if(!empty($_POST['date-filter'])) {
	$datefilter_parts = explode('-', $_POST['date-filter'], 2);
	$datefilter_field = $datefilter_parts[0];
	$date_filter = $datefilter_parts[1];
	if($datefilter_field == 'created') {
		$field = 'entered_date';
	} else if($datefilter_field == 'updated') {
		$field = 'update_date';
	}

	if($date_filter == 'today') {
		$from_date = date('Y-m-d', strtotime('today'));
		$to_date = date('Y-m-d', strtotime('today'));
	} else if($date_filter == 'this-week') {
		$from_date = date('Y-m-d', strtotime('this week monday'));
		$to_date = date('Y-m-d', strtotime('this week sunday'));
	} else if($date_filter == 'this-month') {
		$from_date = date('Y-m-d', strtotime('first day of this month'));
		$to_date = date('Y-m-d', strtotime('last day of this month'));
	} else if($date_filter == 'last-month') {
		$from_date = date('Y-m-d', strtotime('first day of last month'));
		$to_date = date('Y-m-d', strtotime('last day of last month'));
	}
	$where[] = "(" . $field . " >= " . $db->quote($from_date) . " AND " . $field . " <= " . $db->quote($to_date) . ")";
}

$grab_opportunities_sql = "
	SELECT
		opportunities.opportunity_id,
		logins.initials,
		enteredby_logins.initials AS entered_by,
		CONVERT(varchar(10), opportunities.entered_date, 120) AS entered_on,
		(
			SELECT
				CONVERT(varchar(10), MAX(opportunity_logs.logged_on), 120)
			FROM
				" . DB_SCHEMA_ERP . ".opportunity_logs
			WHERE
				opportunity_logs.opportunity_id = opportunities.opportunity_id
		) AS updated_on,
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
		opportunities.vendor_ref,
		opportunity_types.name AS opportunity_type,
		offices.name AS office,
		(
			SELECT
				SUM(opportunity_lineitem_prices.priceea * opportunity_lineitem_prices.quantity)
			FROM
				" . DB_SCHEMA_ERP . ".opportunity_groups
			INNER JOIN
				" . DB_SCHEMA_ERP . ".opportunity_lineitems
				ON
				opportunity_lineitems.opportunity_group_id = opportunity_groups.opportunity_group_id
			INNER JOIN
				" . DB_SCHEMA_ERP . ".opportunity_lineitem_prices
				ON
				opportunity_lineitem_prices.lineitem_id = opportunity_lineitems.opportunity_lineitem_id
			WHERE
				opportunity_groups.opportunity_id = opportunities.opportunity_id
		) AS lineitem_sum
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
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".offices
		ON
		offices.terr = opportunities.terr
	WHERE
		" . implode(' AND ', $where) . "
	ORDER BY
		opportunities.due_date
";

$grab_opportunities = $db->query($grab_opportunities_sql);

$opportunities = [];
foreach($grab_opportunities as $opportunity) {
	$current_date = new DateTime();

	$due_date = new DateTime($opportunity['due_date']);
	$due_date = $due_date->format('Y-m-d h:i A');

	$close_date = '';
	if($opportunity['close_date']) {
		$close_date = new DateTime($opportunity['close_date']);
		$close_date = $close_date->format('Y-m-d');
	}
	$expires = '';
	if($opportunity['expires']) {
		$expires = new DateTime($opportunity['expires']);
		$expires = $expires->format('Y-m-d');
	}

	$age_diff = new DateTime($opportunity['entered_on']);
	$age_diff = $age_diff->diff($current_date);
	$age = $age_diff->days;

	if($opportunity['updated_on']) {
		$edit_age_diff = new DateTime($opportunity['updated_on']);
	} else {
		$edit_age_diff = new DateTime($opportunity['entered_on']);
	}
	$edit_age_diff = $edit_age_diff->diff($current_date);
	$edit_age = $edit_age_diff->days;

	$competitors = explode('|', $opportunity['competitors']);

	if(in_array($opportunity['initials'], $edit_permissions)) {
		$editable = True;
	} else {
		$editable = False;
	}

	$opportunities[] = [
		'opportunity_id' => $opportunity['opportunity_id'],
		'initials' => $opportunity['initials'],
		'entered_by' => $opportunity['entered_by'],
		'entered_on' => $opportunity['entered_on'],
		'custno' => $opportunity['custno'],
		'client_name' => $opportunity['client_name'],
		'name' => $opportunity['name'],
		'opportunity_type_id' => $opportunity['opportunity_type_id'],
		'stage' => $opportunity['stage'],
		'lost_reason' => $opportunity['lost_reason'],
		'lost_to' => $opportunity['lost_to'],
		'next_step' => $opportunity['next_step'],
		'next_step_memo' => $opportunity['next_step_memo'],
		'amount' => number_format($opportunity['amount'], 0),
		'lineitem_sum' => number_format($opportunity['lineitem_sum'], 0),
		'due_date' => $due_date,
		'close_date' => $close_date,
		'expires' => $expires,
		'competitors' => $competitors,
		'vendor_lead' => $opportunity['vendor_lead'],
		'source' => $opportunity['source'],
		'notes' => $opportunity['notes'],
		'opportunity_type' => $opportunity['opportunity_type'],
		'office' => $opportunity['office'],
		'editable' => $editable,
		'age' => $age,
		'edit_age' => $edit_age,
		'vendor_ref' => $opportunity['vendor_ref']
	];
}

print json_encode([
	'success' => True,
	'opportunities' => $opportunities
]);
