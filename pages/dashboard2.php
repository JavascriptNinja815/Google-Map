<?php

/*
This is a temporary file to fascilitate developingthe enhancements to the dashboard that Jason requested. Josh is gonig to move the time-off requests features to a new page - so the original dashboard file should be replaced with this one eventually. - Jake - 2017-12-08
*/

$session->ensureLogin();

// Re-direct Sales roles to the Sales Dashboard.
if(isset($_REQUEST['login']) && $session->hasRole('Sales') && !$session->hasRole('Supervisor') && !$session->hasRole('Administration')) {
	if(!$session->hasRole('Supervisor') && !$session->hasRole('Administration')) {
		header('Location: ' . BASE_URI . '/dashboard/sales');
		exit;
	}
} else if(isset($_REQUEST['login']) && $session->hasRole('Production / Warehouse')) {
	header('Location: ' . BASE_URI . '/dashboard/account/timeoff');
	exit;
}

$args = array(
	'title' => 'My Dashboard 2',
	'breadcrumbs' => [],
	'body-class' => 'padded'
);

function days_until($date){

	// Get the number of days until the due date.
	$now = time();
	$dif = strtotime($date) - $now;

	$days = ceil($dif / (60 * 60 * 24));

	if ($days<=0){
		return;
	};

	return '('.$days.')';

}

function get_rocks(){

	// Get Employee rocks.
	global $session;
	$user = $session->login;
	$company_id = $user['company_id'];
	$login_id = $user['login_id'];

	$db = DB::get();
	return $db->query("
		SELECT
			r.rock_id,
			name,
			due_on,
			COALESCE(r.percent_complete, 0) AS percent_complete
		FROM Neuron.dbo.employee_rocks r
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = r.login_id
		WHERE company_id = ".$db->quote($company_id)."
			AND r.login_id = ".$db->quote($login_id)."
			AND r.status = 1 -- Constrain to enabled rocks
	");

}

function get_past_due_at_risk(){

	// Get the total number of At Risk / Past Due orders.

	// Get date values.
	$date_format = 'Y-m-d';
	$yesterday = date($date_format, strtotime('yesterday'));
	$now = time();
	$today = date($date_format, $now);
	$tomorrow = date($date_format, strtotime('tomorrow'));

	// Get the user's initials.
	global $session;
	$initials = $session->login['initials'];

	// Get the counts
	$db = DB::get();
	$q = $db->query("
		WITH past_due AS (
			SELECT
				1 AS bsid,
				COUNT(*) AS pd
			FROM " . DB_SCHEMA_ERP . ".somast
			WHERE somast.ordate <= ".$db->quote($yesterday)."
				AND RTRIM(LTRIM(somast.defloc)) = 'DC'
				AND RTRIM(LTRIM(somast.orderstat)) NOT IN ('SHIPPED', 'SHIPPING', 'PICKUP')
				AND RTRIM(LTRIM(somast.salesmn)) = ".$db->quote($initials)."
				--AND RTRIM(LTRIM(somast.salesmn)) = 'BRG'

				-- BELOW SHOULD ALWAYS BE PRESENT
				AND RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
				AND RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		),
		at_risk AS (
			SELECT
				1 AS bsid,
				COUNT(*) AS ar
			FROM " . DB_SCHEMA_ERP . ".somast
			WHERE somast.ordate BETWEEN ".$db->quote($today)." AND ".$db->quote($tomorrow)."
				AND RTRIM(LTRIM(somast.defloc)) = 'DC'
				AND RTRIM(LTRIM(somast.orderstat)) IN ('NSP', 'SSP', 'ON HOLD', 'OTHER', 'PURCHASING', 'STAGED', 'TRANSFER', 'VENDOR')
				AND RTRIM(LTRIM(somast.salesmn)) = ".$db->quote($initials)."
				--AND RTRIM(LTRIM(somast.salesmn)) = 'BRG'

				-- BELOW SHOULD ALWAYS BE PRESENT
				AND RTRIM(LTRIM(somast.sostat)) NOT IN ('C', 'V', 'X')
				AND RTRIM(LTRIM(somast.sotype)) NOT IN ('B', 'D')
		)
		SELECT
			p.pd AS past_due,
			r.ar AS at_risk
		FROM past_due p
		INNER JOIN at_risk r
			ON r.bsid = p.bsid
	");

	// Feth results.
	$r = $q->fetch();
	
	// Sum past-due and at-risk orders.
	$total = $r['past_due'] + $r['at_risk'];

	return $total;

}

function get_sales(){

	// Get the sales for the user.

	// Get the users's initials.
	global $session;
	$login = $session->login;
	$initials = $login['initials'];

	// Query for sales.
	$db = DB::get();
	$q = $db->query("
		SELECT SUM(somast.ordamt + somast.shpamt) AS amount
		FROM " . DB_SCHEMA_ERP . ".somast
		WHERE
			somast.sotype != 'B'
			AND somast.adddate >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime('first day of this month')
				)
			) . "
			--AND RTRIM(LTRIM(somast.salesmn)) = ".$db->quote($initials)."
			AND RTRIM(LTRIM(somast.salesmn)) = 'BRG'
		GROUP BY
			LTRIM(RTRIM(somast.salesmn))
		HAVING
			SUM(
				ISNULL(somast.ordamt, 0.00) + ISNULL(somast.shpamt, 0.00)
			) > 0
	");

	// Fetch results.
	$r = $q->fetch();
	return $r['amount'];

}

function get_billing(){

	// Get the billing for the user.

	// Get some date values.
	$current_year = date('Y', time());
	$current_month = date('F', time());

	// Get the user's initials.
	global $session;
	$login = $session->login;
	$initials = $login['initials'];

	// Query for the billing.
	$db = DB::get();
	$q = $db->query("
		SELECT
			SUM(
				ISNULL(artran.extprice, 0.00)
			) AS sales
		FROM " . DB_SCHEMA_ERP . ".soslsm
		INNER JOIN " . DB_SCHEMA_ERP . ".arcust
			ON soslsm.salesmn = arcust.salesmn
		INNER JOIN " . DB_SCHEMA_ERP . ".artran
			ON arcust.custno = artran.custno
		WHERE artran.invdte >= " . $db->quote(
				date(
					'Y-m-d',
					strtotime($current_month . ' 1, ' . $current_year) // Ex: February 1, 2015
				)
			) . "
			AND RTRIM(LTRIM(artran.item)) NOT IN('FRT', 'Note-', 'SHIP')
			--AND RTRIM(LTRIM(soslsm.salesmn)) = ".$db->quote($initials)."
			AND RTRIM(LTRIM(soslsm.salesmn)) = 'BRG'
		GROUP BY soslsm.salesmn
		HAVING SUM(
				ISNULL(artran.extprice, 0.00)
			) > 0
	");

	// Fetch results.
	$r = $q->fetch();
	return $r['sales'];

}

function get_backlog(){

	// Get backlog valeus for the user.

	// Get some date values.
	$current_year = date('Y', time());
	$current_month = date('F', time());
	$current_monthdate = date('F j', time());
	$today = date(
		'Y-m-d',
		strtotime($current_monthdate)
	);
	$this_month_end = date(
		'Y-m-d',
		strtotime('last day of '  . $current_month . ' ' . $current_year)
	);

	// Get the users's initials.
	global $session;
	$login = $session->login;
	$initials = $login['initials'];

	// Get the backlog.
	$db = DB::get();
	$q = $db->query("
		SELECT
			SUM(
				(
					COALESCE(sotran.tqtyord, 0) - COALESCE(sotran.tqtyshp, 0)
				) * COALESCE(sotran.tprice, 0)
			) AS amount
		FROM " . DB_SCHEMA_ERP . ".sotran
		INNER JOIN " . DB_SCHEMA_ERP . ".somast
			ON sotran.sono = somast.sono
		WHERE sotran.sostat NOT IN ('C', 'V', 'X')
			AND somast.sotype IN ('', 'O', 'R')
			AND sotran.rqdate >= " . $db->quote($today) . "
			AND sotran.rqdate <= " . $db->quote($this_month_end) . "
			AND (sotran.tqtyord - sotran.tqtyshp) * sotran.tprice != 0
			AND sotran.tqtyord != 0.00
			--AND RTRIM(LTRIM(somast.salesmn)) = ".$db->quote($initials)."
			AND RTRIM(LTRIM(somast.salesmn)) = 'BRG'
	");

	// Fetch results.
	$r = $q->fetch();
	return $r['amount'];

}

function get_sbb(){

	// Get the values for the sales/billing/backlog area of the dashboard.

	$sales = get_sales();
	$billing = get_billing();
	$backlog = get_backlog();

	return array(
		'sales' => round($sales/1000, 2),
		'billing' => round($billing/1000, 2),
		'backlog' => round($backlog/1000, 2)
	);

}

// Get user's rocks.
$rocks = get_rocks();

// Get past-due and at-risk order count.
$pdar = get_past_due_at_risk();

// Get the sales/billing/backlog.
$sbb = get_sbb();

Template::Render('header', $args, 'account');
?>

<style type="text/css">
	#row-2 {
		padding-top: 25px;
	}
	.row1 {
		height:250px;
		//border: 1px solid black;
	}
	.row2 {
		height:300px;
		//border: 1px solid black;
	}
	h5 {
		padding-left: 5px;
	}
	#pdar-content {
		font-size: 1000%;
	}
	#pdar-container {
		height:70%;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	#sbb-table {
		height: 100%;
	}
	.label-td {
		font-size: 75%;
	}
	.value-td {
		font-size: 200%;
	}
	#rocks-container {
		overflow: auto;
	}
</style>

<div id="dashboard-container">
	
	<div class="navbar">
		<div class="navbar-inner"></div>
	</div>

	<div class="container-fluid">
		<div id="row-1" class="row-fluid">
			<div id="rocks-container" class="row1 span3">
				<h5>Rocks</h5>
				<table id="rocks-table" class="table table-striped table-hover">
					<?php
						foreach($rocks as $rock){
						?>
						<tr class="rock-row">
							<td><?php print htmlentities($rock['name']) ?></td>
							<td><?php print htmlentities($rock['percent_complete']) ?>%</td>
							<td><?php print htmlentities(days_until($rock['due_on'])) ?></td>
						</tr>
						<?php
						}
					?>
				</table>
			</div>
			<div class="row1 span3"></div>
			<div class="row1 span2">
				<h5>Past Due / At Risk</h5>
				<div id="pdar-container">
					<b id="pdar-content"><?php print htmlentities($pdar) ?></b>
				</div>
			</div>
			<div class="row1 span2">
				<h5><!-- % of Plan --></h5>
			</div>
			<div class="row1 span2">
				<table id="sbb-table" class="table table-striped table-hover">
					<tr>
						<td class="span1 label-td"><b>Sales</b></td>
						<td class="value-td"><b>$<?php print htmlentities($sbb['sales']) ?>K</b></td>
					</tr>
					<tr>
						<td class="span1 label-td"><b>Billing</b></td>
						<td class="value-td"><b>$<?php print htmlentities($sbb['billing']) ?>K</b></td>
					</tr>
					<tr>
						<td class="span1 label-td"><b>Backlog</b></td>
						<td class="value-td"><b>$<?php print htmlentities($sbb['backlog']) ?>K</b></td>
					</tr>
				</table>
			</div>
		</div>
		<div id="row-2" class="row-fluid">
			<div class="row2 span3">
				<h5><!-- To Do's --></h5>
			</div>
			<div class="row2 span3"></div>
			<div class="row2 span2"></div>
			<div class="row2 span2"></div>
			<div class="row2 span2"></div>
		</div>
	</div>

</div>

<script type="text/javascript">
$(document).ready(function(){

	//

})
</script>

<?php Template::Render('footer', 'account');