<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */ 

$session->ensureLogin();

// Re-direct Sales roles to the Sales Dashboard.
// if(isset($_REQUEST['login']) && $session->hasRole('Sales') && !$session->hasRole('Supervisor') && !$session->hasRole('Administration')) {
// 	if(!$session->hasRole('Supervisor') && !$session->hasRole('Administration')) {
// 		header('Location: ' . BASE_URI . '/dashboard/sales');
// 		exit;
// 	}
// } else if(isset($_REQUEST['login']) && $session->hasRole('Production / Warehouse')) {
// 	header('Location: ' . BASE_URI . '/dashboard/account/timeoff');
// 	exit;
// }

$args = array(
	'title' => 'My Dashboard',
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
	$total = $r['past_due'];// + $r['at_risk']; // This should be JUST "past due".

	return $total;

}

function get_contacts_count(){

	// Get the number of customers that need to be contacted.

	// Constrain by the logged-in user.
	global $session;
	$login = $session->login;
	$initials = $login['initials'];

	$db = DB::get();
	$q = $db->query("
		DECLARE @months_back INTEGER;
		DECLARE @today DATE;
		DECLARE @months_ago DATE;
		DECLARE @days_between_count INTEGER;
		DECLARE @two_weeks_ago DATE;

		SET @months_back = 36;
		SET @today = GETDATE();
		SET @months_ago = DATEADD(MONTH, -@months_back, @today);
		SET @days_between_count = @months_back/12*365;
		SET @two_weeks_ago = DATEADD(DAY, -14, @today);

		WITH initial_counts AS (
			SELECT
				LTRIM(RTRIM(somast.custno)) AS custno,
				COUNT(*) AS count,
				CONVERT(varchar(10), MIN(somast.adddate), 120) AS first,
				CONVERT(varchar(10), MAX(somast.adddate), 120) AS last,
				CONVERT(varchar(10), DATEDIFF(d, MAX(somast.adddate), @today), 120) AS days_since_last_order,
				@days_between_count/COUNT(*) AS days_between_orders
			FROM PRO01.dbo.somast
			WHERE somast.adddate > @months_ago
				--AND LTRIM(RTRIM(somast.custno)) = 'AAS01'
				AND somast.salesmn = ".$db->quote($initials)."
			GROUP BY somast.custno
			HAVING COUNT(*) >= 3
		), processed AS (
			SELECT
				*,
				days_between_orders-days_since_last_order AS date_diff
			FROM initial_counts
		)
		SELECT COUNT(*) AS c
		FROM processed p
		LEFT JOIN PRO01.dbo.cust_contact_log l
			ON l.custno = p.custno COLLATE Latin1_General_BIN
		WHERE p.date_diff < 4
			AND (
				/* Call has never been logged. */
				l.added_on IS NULL
				OR
				/* Call was logged in the last two weeks. */
				l.added_on >= @two_weeks_ago
				)
				AND p.days_since_last_order > 10
	");

	return $q->fetch()['c'];

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
			AND RTRIM(LTRIM(somast.salesmn)) = ".$db->quote($initials)."
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
			AND RTRIM(LTRIM(soslsm.salesmn)) = ".$db->quote($initials)."
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
			AND RTRIM(LTRIM(somast.salesmn)) = ".$db->quote($initials)."
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

function get_todos(){

	// Ge the user's tasks.

	// Get the user's login ID.
	// global $session;
	// $login = $session->login;
	// $initials = $login['login_id'];

}

// Check for a POST.
if(isset($_POST['action'])){

	// Check for a POST to set background cookie.
	if($_POST['action'] == 'set-bg-cookie'){

		// Get values for cookies.
		$url = $_POST['url'];
		$title = $_POST['title'];
		$username = $_POST['username'];
		$realname = $_POST['realname'];

		// Set cookies.
		$midnight = strtotime('today 23:59');
		//$midnight = strtotime('today 8:45');
		setcookie('bg-url', $url, $midnight);
		setcookie('bg-title', $title, $midnight);
		setcookie('bg-username', $username, $midnight);
		setcookie('bg-realname', $realname, $midnight);

		// Give the client a response.
		echo json_encode(array(
			'success' => true
		));

		return;

	}

}

function get_bg_cookies(){

	// PHP is retarded.
	if(isset($_COOKIE['bg-url'])){

		return array(
			'bg-url' => $_COOKIE['bg-url'],
			'bg-title' => $_COOKIE['bg-title'],
			'bg-username' => $_COOKIE['bg-username'],
			'bg-realname' => $_COOKIE['bg-realname']
		);
	};

}

function get_greeting(){

	// Get the greeting for the dashboard.

	// Get the user's name.
	global $session;
	$name = $session->login['first_name'];

	// Get the hour of the day.
	$date = (int)date('H');

	// Get the greeting string based on time of day.
	if($date<12){
		$g = 'Good Morning';
	}elseif ($date>=12 and $date < 17) {
		$g = 'Good Afternoon';
	}else {
		$g = 'Good Evening';
	}
	$greeting = $g.', '.$name.'.';

	return $greeting;

}

function get_sales_pipeline(){

	// Get the sales pipeline values.

	// Get the user's login ID.
	global $session;
	$login = $session->login;
	$login_id = $login['login_id'];

	$db = DB::get();
	$q = $db->query("
		-- Declare locals variables.
		DECLARE @today date;
		DECLARE @week_ago date;
		DECLARE @month_ago date;

		-- Set local variables.
		SET @today = GETDATE();
		SET @week_ago = DATEADD(DAY, -7, @today);
		SET @month_ago = DATEADD(DAY, -30, @today);

		-- Begin actual query.
		WITH updates AS (
			SELECT
				opportunities.opportunity_id,
				CONVERT(varchar(10), opportunities.entered_date, 120) AS entered_on,
				(SELECT CONVERT(varchar(10), MAX(opportunity_logs.logged_on), 120)
					FROM ".DB_SCHEMA_ERP.".opportunity_logs
					WHERE opportunity_logs.opportunity_id = opportunities.opportunity_id
				) AS updated_on
			FROM ".DB_SCHEMA_ERP.".opportunities
		),active AS (
			SELECT
				o.opportunity_id,
				CASE WHEN u.updated_on >= @week_ago
					THEN 1
					ELSE 0
				END AS active
			FROM ".DB_SCHEMA_ERP.".opportunities o
			INNER JOIN updates u
				ON u.opportunity_id = o.opportunity_id
		)
		SELECT
			SUM(o.amount) AS amount,
			o.stage,
			SUM(a.active) AS active,
			COUNT(*) AS total
		FROM ".DB_SCHEMA_ERP.".opportunities o
		INNER JOIN active a
			ON a.opportunity_id = o.opportunity_id

		-- Constrain to opportunities entered within the last 30 days.
		WHERE --entered_date >= @month_ago AND entered_date <= @today
			--AND
			o.stage NOT IN ('Closed Won', 'Closed - Never Ordered', 'Closed Lost')
			AND o.login_id = ".$db->quote($login_id)."
		GROUP BY stage
		ORDER BY stage
	");

	// Fetch the results.
	return $q->fetchAll();

}

function get_time_off(){

	// Get the time-off requests.

	// Get the user's login ID.
	global $session;
	$login = $session->login;
	$login_id = $login['login_id'];

	// Get the total amount of time allowed.
	$db = DB::get();
	$q = $db->query("
		SELECT
			avail_sick_hours AS sick,
			avail_vacation_hours AS vacation
			--avail_sick_hours AS personal
		FROM Neuron.dbo.logins
		WHERE login_id = ".$db->quote($login_id)."
		--WHERE login_id = 3 -- TODO: Remove this -- Jason's login ID.
	");

	// Get allowed.
	$allowed = $q->fetch();
	$allowed_personal = $allowed['sick'];
	$allowed_vacation = $allowed['vacation'];

	// Get the amount of time off a user has already had approved in the
	// current calendar year.
	$q = $db->query("

		-- Declare date variables.
		DECLARE @today date;
		DECLARE @janfirst date;

		-- Set date variables.
		SET @today = GETDATE();
		SET @janfirst = DATEADD(YYYY, DATEDIFF(YYYY, 0, @today),0);

		-- Begin query.
		SELECT
			COALESCE(SUM(
				CASE WHEN timesheet_type_id IN (3, 5)
					THEN COALESCE(DATEDIFF(hour, from_datetime, to_datetime), 8)
				ELSE 0
			END),0) AS sick,
			COALESCE(SUM(
				CASE WHEN timesheet_type_id = 4
					THEN COALESCE(DATEDIFF(hour, from_datetime, to_datetime), 8)
				ELSE 0
			END
			),0) AS vacation
		FROM ".DB_SCHEMA_ERP.".timesheets
		WHERE from_datetime >= @janfirst
			AND login_id = ".$db->quote($login_id)."
			--AND login_id = 3 -- Jason's login ID
	");

	// Get used.
	$used = $q->fetch();
	$used_personal = $used['sick'];
	$used_vacation = $used['vacation'];

	// Check what is remaining.
	$remaining_personal = $allowed_personal - $used_personal;
	$remaining_vacation = $allowed_vacation - $used_vacation;

	// Don't allow negative values.
	if($remaining_personal<0){
		$remaining_personal = 0;
	}
	if($remaining_vacation<0){
		$remaining_vacation = 0;
	}

	return array(
		'personal' => array(
			'used' => $used_personal,
			'available' => $remaining_personal
		),
		'vacation' => array(
			'used' => $used_vacation,
			'available' => $remaining_vacation
		)
	);

}

function get_off_today(){

	// Query for employees that have today off.

	$db = DB::get();
	$q = $db->query("
		DECLARE @today date;
		SET @today = GETDATE();

		SELECT DISTINCT
			l.first_name,
			l.last_name,
			CONVERT(
				varchar(15),
				CAST(t.from_datetime AS time),
				100
			) AS from_time,
			CONVERT(
				varchar(15),
				CAST(t.to_datetime AS time),
				100
			) AS to_time
		FROM ".DB_SCHEMA_ERP.".timesheets t
		INNER JOIN Neuron.dbo.logins l
			ON l.login_id = t.login_id
		WHERE CAST(t.from_datetime AS date) = @today
			AND t.status = 1
			AND l.status = 1
		ORDER BY l.last_name, l.first_name DESC
	");

	return $q->fetchAll();

}

function get_hot(){

	// Query for hot orders.
	$db = DB::get();
	$q = $db->query("
		SELECT
			LTRIM(RTRIM(sono)) AS sono,
			custno,
			orderstat
		FROM ".DB_SCHEMA_ERP.".somast
		WHERE somast.hot = 1
			AND somast.sostat NOT IN ('V', 'C')
			AND somast.sotype NOT IN ('B', 'R')
			AND RTRIM(LTRIM(somast.defloc)) = 'DC'
			AND somast.ordate = CAST(GETDATE() AS date)
		ORDER BY sono, custno, orderstat
	");

	return $q->fetchAll();

}

function get_credit_holds(){

	// Query for customers with credit holds.

	$db = DB::get();
	$q = $db->query("
		WITH hold AS (
				SELECT
					c.custno,
					MAX(DATEDIFF(DAY, r.invdte, GETDATE())) AS age
				FROM ".DB_SCHEMA_ERP.".arcust c
				INNER JOIN ".DB_SCHEMA_ERP.".armast r
					ON r.custno = c.custno
				WHERE c.credstat = 'H'
					AND r.arstat != 'V'
					AND r.balance > 0
					--AND c.custno = 'FAC01'
				GROUP BY c.custno
		), complete AS (
				SELECT DISTINCT
					c.custno,
					0 AS age
				FROM ".DB_SCHEMA_ERP.".arcust c
				INNER JOIN Neuron.dbo.logins l
					ON l.initials = c.salesmn COLLATE Latin1_General_BIN
				LEFT JOIN ".DB_SCHEMA_ERP.".armast r
					ON r.custno = c.custno
				WHERE c.credstat = 'H'
				AND r.arstat != 'V'
		), agg AS (
			SELECT custno, age
			FROM hold
			UNION ALL
			SELECT custno, age
			FROM complete
		)

		SELECT
			custno,
			SUM(age) AS age
		FROM agg
		GROUP BY custno
	");

	return $q->fetchAll();

}

function get_quote(){

	// Query for a random quote.
	$db = DB::get();
	$q = $db->query("
		SELECT TOP 1 quote, author
		FROM ".DB_SCHEMA_INTERNAL.".quotes
		ORDER BY NEWID();
	");

	return $q->fetch();

}

function get_sales_today(){

	// Query for sales for today
	// ie: Sales Dashboard -> Sales by Territory -> "Today" column total.

	$db = DB::get();
	$q = $db->query("
		DECLARE @today date;
		SET @today = GETDATE();

		SELECT
			SUM(somast.ordamt + somast.shpamt) AS amount
		FROM ".DB_SCHEMA_ERP.".somast
		WHERE somast.sotype != 'B'
			AND somast.adddate = @today
	");

	return $q->fetch()['amount'];

}

function get_billing_month(){

	// Query for the billing for this month.
	// ie: Sales Dashboard -> Billing by Territory -> "Month to Date" column
	// total.

	$db = DB::get();
	$q = $db->query("
		DECLARE @today date;
		DECLARE @month date;

		SET @today = GETDATE();
		SET @month = DATEADD(month, DATEDIFF(month, 0, @today), 0);

		SELECT
			--arcust.terr AS territory,
			SUM(
				ISNULL(artran.extprice, 0.00)
			) AS total
		FROM ".DB_SCHEMA_ERP.".arcust
		INNER JOIN ".DB_SCHEMA_ERP.".artran
			ON arcust.custno = artran.custno
		WHERE
			artran.invdte >= @month
			AND RTRIM(LTRIM(artran.item)) NOT IN('FRT', 'Note-', 'SHIP')
		HAVING SUM(ISNULL(artran.extprice, 0.00)) > 0
	");

	return $q->fetch()['total'];

}

// Get a quote to display.
$quote = get_quote();

// Get credit holds.
$holds = get_credit_holds();

// Get hot orders.
$hot = get_hot();

// Get the people who have today off.
$off_today = get_off_today();

// Get time-off values.
$time_off = get_time_off();

// Get the sales-pipeline.
$pipeline = get_sales_pipeline();

// Get user's rocks.
$rocks = get_rocks();

// Get past-due and at-risk order count.
$pdar = get_past_due_at_risk();

// Get the number of contacts to contact.
$contacts_count = get_contacts_count();

// Get the sales/billing/backlog.
$sbb = get_sbb();

// Get the background image URL.
$cookies = get_bg_cookies();

// Get the greeting.
$greeting = get_greeting();

// Get today's sales.
$sales_today = get_sales_today();

// Get this month's billing.
$billing_month = get_billing_month();

Template::Render('header', $args, 'account');
?>

<!-- Include the bootstrap JavaScript -->
<script type="text/javascript" src="/interface/js/bootstrap.min.js"></script>

<style type="text/css">

	@font-face {
		font-family: 'noto';
		src: url('/interface/fonts/NotoSans-Regular.ttf');
	}

	#body {
		overflow: auto;
		overflow-x: hidden;
	}

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
	#contacts-count {
		font-size: 1000%;
	}
	#pdar-container {
		height:70%;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		color: #333;
	}
	#contacts-container {
		height:70%;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		color: #333;
	}
	#sbb-table {
		height: 100%;
	}
	#sbb-container {
		overflow: hidden;
	}
	.label-td {
		font-size: 75%;
	}
	.value-td {
		font-size: 200%;
	}
	.company-row {
		background-color: white;
	}
	#rocks-container {
		overflow-y: auto;
	}
	.db-box {
		border: 1px solid white;
		//border-radius: 25px;
		overflow-y: auto;
		background-color: #f1f2ea;
		background: rgba(255,255,255,0.75);
	}
	#photo-credits-container {
		position: fixed;
		left: 0;
		bottom: 0;
		padding-left: 15px;
	}
	#date-time-container {
		color: white;
		text-shadow:
			-1px -1px 0 #000,
			1px -1px 0 #000,
			-1px 1px 0 #000,
			1px 1px 0 #000; 
		/*
		position: fixed;
		right: 0;
		bottom: 0;
		padding-right: 35px;
		padding-bottom: 10px;
		*/
	}
	#photo-credits-table td {
		line-height: 5px;
	}
	#photo-credits-table {
		margin-bottom: 0px;
	}
	#bg-refresh-container {
		padding-left: 10px;
		padding-bottom: 3px;
	}
	#refresh-icon {
		cursor: pointer;
	}
	.borderless tr{
		border-top: hidden;
	}
	.font-readable {
		color: white;
		text-shadow:
			-1px -1px 0 #000,
			1px -1px 0 #000,
			-1px 1px 0 #000,
			1px 1px 0 #000; 
	}
	#photo-title {
		font-size: small;
	}
	#photo-username {
		font-size: x-small;
	}
	#photo-realname {
		font-size: x-small;
	}
	#greeting-header {
		color: #f1f2ea;
		text-shadow:
			-1px -1px 0 #000,
			1px -1px 0 #000,
			-1px 1px 0 #000,
			1px 1px 0 #000;
	}
	#time-container {
		font-size: 50px;
	}
	#banner-container {
		padding-bottom: 10px;
		padding-left: 15px;
	}
	.noto {
		font-family: noto;
	}
	#search-container {
		padding-right: 30px;
		padding-top: 10px;
	}
	.search-icon {
		padding-left: 7px;
		padding-right:9px;
		margin-top: 4px;
	}
	#search-input {
		margin-right: 5px;
		margin-left: 5px;
		text-transform: uppercase;
	}
	.fa {
		color: #333;
	}
	#search-type-container {
		padding-top: 5px;
	}
	#search-type-button {
		width: 70px;
	}
	#icon-container {
		background-color: white;
		margin-top: 5px;
		margin-right: 5px;
		height: 30px;
		border-radius: 5px;
		background-color: #f1f2ea;
		background: rgba(255,255,255,0.75);
	}
	#quote-container {
		padding-bottom: 30px;
		padding-top: 20px;
		/*
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		*/
		text-align: center;
		z-index: -1;
	}

	.quote {
		display: inline-block;
	}

	#quote-container .quote::before {
		content:'"';
		font-size:28px;
	}
	#quote-container .quote::after {
		content:'"';
		font-size:28px;
	}
	#quote-container .quote {
		font-size:24px;
	}
	#quote-container .author::before {
		content:"-";
		font-size:48px;
	}
	#quote-container .author {
		font-size:32px;
	}


	body {
		display:flex;
		flex-direction: column;
		min-height: 100vh;
	}
	#footer {
		//position: relative;
		//bottom: 0;
		//right: 0;
		//left: 0;
		margin-top: auto;
		width: 100%;
		flex: 0 0 100px;
	}

	#date-time-footer {
		padding-right: 55px;
	}

</style>

<div id="dashboard-container">
	
	<div id="banner-container" class="row-fluid">
		<div class="pull-left">
			<h2 id="greeting-header" class="noto"><?php print htmlentities($greeting) ?></h2>
		</div>
		<div id="search-container" class="pull-right span4">
			<form id="search-form" class="navbar-form pull-right">
				<input id="search-input" type="text" placeholder="Enter Search Term">
				<div id="search-type-container" class="pull-left">
					
					<div class="btn-group">
						<a id="search-type-button" class="btn dropdown-toggle" data-toggle="dropdown" href="#">
							<span class="search-type-label">SO</span>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li class="search-type active"><a href="#">SO</a></li>
							<li class="search-type"><a href="#">Client</a></li>
							<li class="search-type"><a href="#">PO</a></li>
							<li class="search-type"><a href="#">Quote</a></li>
							<li class="search-type"><a href="#">Item</a></li>
						</ul>
					</div>

				</div>
				<div id="icon-container" class="pull-right">
					
					<div class="search-icon pull-right">
						<a href=""><i class="fa fa-fw fa-search"></i></a>
					</div>
				</div>
			</form>
		</div>
	</div>
	<div class="container-fluid">
		<div id="row-1" class="row-fluid">
			<div id="rocks-container" class="db-box row1 span3">
				<h5>Rocks</h5>
				<table id="rocks-table" class="table table-hover">
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
			<div class="db-box row1 span3">
				<h5>Sales Pipeline</h5>
				<div id="sales-pipeline-container">
					
					<table id="sales-pipeline-table" class="table table-hover">
						<thead>
							<th class="text-center">Amount</th>
							<th class="text-center">Stage</th>
							<th class="text-center">Active</th>
							<th class="text-center">Count</th>
						</thead>
						<tbody>
							<?php
								foreach($pipeline as $row){
								?>
								<tr class="pipeline-row">
									<td>$<?php print htmlentities(number_format($row['amount'])) ?></td>
									<td><?php print htmlentities($row['stage']) ?></td>
									<td><?php print htmlentities($row['active']) ?></td>
									<td><?php print htmlentities($row['total']) ?></td>
								</tr>
								<?php
								}
							?>
						</tbody>
					</table>

				</div>
			</div>
			<div class="db-box row1 span2">
				<h5>Past Due / At Risk</h5>
				<div id="pdar-container" class="overlayz-link noto" overlayz-url="<?php print BASE_URI;?>/dashboard/sales/orders-due" overlayz-data="<?php print htmlentities(json_encode(['block' => 'Past Due']), ENT_QUOTES);?>">
					<b id="pdar-content"><?php print htmlentities($pdar) ?></b>
				</div>
			</div>
			<div class="db-box row1 span2">
				<h5>Customer Contacts</h5>
				<div id="contacts-container" class="overlayz-link noto" overlayz-url="<?php print BASE_URI;?>/dashboard/contacts-list">
					<b id="contacts-count"><?php print htmlentities($contacts_count) ?></b>
				</div>
			</div>
			<div id="sbb-container" class="db-box row1 span2">
				<table id="sbb-table" class="table table-hover">
					<tr>
						<td class="span1 label-td" colspan="2"><b>Sales MTD</b></td>
						<td class="value-td"><b>$<?php print htmlentities($sbb['sales']) ?>K</b></td>
					</tr>
					<tr>
						<td class="span1 label-td" colspan="2"><b>Billing MTD</b></td>
						<td class="value-td"><b>$<?php print htmlentities($sbb['billing']) ?>K</b></td>
					</tr>
					<tr>
						<td class="span1 label-td" colspan="2"><b>Backlog</b></td>
						<td class="value-td"><b>$<?php print htmlentities($sbb['backlog']) ?>K</b></td>
					</tr>
					<tr class="company-row">
						<td class="span1 label-td" colspan="2"><b>Sales Today</b></td>
						<td class="value-td"><b>$<?php print number_format($sales_today/1000) ?>K</b></td>
					</tr>
					<tr class="company-row">
						<td class="span1 label-td" colspan="2"><b>Billing MTD</b></td>
						<td class="value-td"><b>$<?php print number_format($billing_month/1000) ?>K</b></td>
					</tr>
				</table>
			</div>
		</div>
		<div id="row-2" class="row-fluid">
		<!--
			<div class="db-box row2 span3">
				<h5>To Do's</h5>
			</div>
			<div class="row2 span3"></div>
		-->
			<div class="db-box row2 span3">

				<h5>Time Off</h5>
				<div id="time-off-container">
					<table id="time-off-table" class="table table-hover">
						<thead>
							<th></th>
							<th class="text-center">Used</th>
							<th class="text-center">Available</th>
						</thead>
						<tbody>
							<tr>
								<td>Pr/Sick:</td>
								<td><?php print htmlentities($time_off['personal']['used']) ?> hrs</td>
								<td><?php print htmlentities($time_off['personal']['available']) ?> hrs</td>
							</tr>
							<tr>
								<td>Vacation:</td>
								<td><?php print htmlentities($time_off['vacation']['used']) ?> hrs</td>
								<td><?php print htmlentities($time_off['vacation']['available']) ?> hrs</td>
							</tr>
						</tbody>
					</table>
				</div>

				<h5>Off Today</h5>
				<div id="off-today-container">
					<table id="people-off-table" class="table table-hover">
						<tbody>
							<?php foreach($off_today AS $off){

								// Get start and end times.
								$from = $off['from_time'];
								$to = $off['to_time'];

								// If someone is off all day, show no times.
								$offtime = $from .'-'. $to;
								if($from == '8:00AM'){
									if(!$to or $to == '5:00PM'){
										$offtime = '';
									}
								}

								?>
								<tr>
									<td><?php print htmlentities($off['first_name'].' '.$off['last_name']) ?></td>
									<td><?php print htmlentities($offtime) ?></td>
								</tr>
								<?php
							} ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="db-box row2 span3">

				<h5>Hot</h5>
				<div id="hot-container">
					<table id="hot-table" class="table table-hover">
						<thead>
							<th>SO#</th>
							<th>Client</th>
							<th>Status</th>
						</thead>
						<tbody>
							<?php foreach($hot AS $row){
							?>
								<tr>
									<?php $sono = json_encode(array('so-number' => $row['sono'])) ?>
									<td class="overlayz-link" overlayz-url="/dashboard/sales-order-status/so-details" overlayz-data="<?php print htmlentities($sono); ?>"><?php print htmlentities($row['sono']) ?></td>
									<td class="overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities(json_encode(['custno' => trim($row['custno'], ENT_QUOTES)]), ENT_QUOTES);?>"><?php print htmlentities($row['custno']) ?></td>
									<td><?php print htmlentities($row['orderstat']) ?></td>
								</tr>
							<?php
							} ?>
						</tbody>
					</table>
				</div>
				
			</div>
			<div class="db-box row2 span2">

				<h5>Credit Holds</h5>
				<div id="credit-holds-container">
					<table id="credit-holds-table" class="table table-hover">
						<thead>
							<th>Client</th>
							<th>Age</th>
						</thead>
						<tbody>
							<?php foreach($holds AS $hold){
							?>
								<tr>
									<td class="overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities(json_encode(['custno' => trim($hold['custno'], ENT_QUOTES)]), ENT_QUOTES);?>"><?php print htmlentities($hold['custno']) ?></td>
									<td><?php print htmlentities($hold['age']) ?></td>
								</tr>
							<?php
							} ?>
						</tbody>
					</table>
				</div>
				
			</div>
		</div>
	</div>

</div>

<div id="footer" class="container-fluid">
	<div class="row-fluid">
		<div class="footer-box span3">
			
			<table id="photo-credits-table" class="table table-small borderless noto">
				<tbody>
					<tr><td id="photo-title" class="font-readable"></td></tr>
					<tr><td id="photo-username" class="font-readable"></td></tr>
					<tr><td id="photo-realname" class="font-readable"></td></tr>
				</tbody>
			</table>
			<div id="bg-refresh-container" style="width:5px,height:5px,border:1px solid black;">
				<i id="refresh-icon" class="fa fa-refresh"></i>
			</div>

		</div>
		<div class="footer-box span6">
			
			<div id="quote-container" class="text-center noto font-readable">
				<div class="quote"><?php print htmlentities($quote['quote']) ?></div>
				<div class="author"><?php print htmlentities($quote['author']) ?></div>
			</div>

		</div>
		<div id="date-time-footer" class="footer-box span2 pull-right">
			
			<div id="date-time-container" class="pull-right noto">
				<h1 id="time-container"></h1>
				<div class="text-center"><h5 id="date-container"></h5></div>
			</div>

		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function(){


	function set_background(url, title, username, realname){

		// Set the background image.
		var $body = $('body')
		$body.css('background-image', 'url('+url+')')
		$body.css('background-repeat', 'no-repeat')
		$body.css('background-position', 'center center')
		$body.css('background-size', '100%')

		// Get photo credit containers.
		var $photo_title = $('#photo-title')
		var $photo_username = $('#photo-username')
		var $photo_realname = $('#photo-realname')

		// Set photo credits.
		$photo_title.text(title)
		$photo_username.text(username)
		$photo_realname.text(realname)

	}

	function get_background(refresh=false){

		// Check for an existing URL.
		if(!refresh){
			var cookies_set = "<?php print isset($cookies['bg-url']) ?>"
			if(cookies_set){
				var url = "<?php print $cookies['bg-url']; ?>"
				var title = "<?php print $cookies['bg-title']; ?>"
				var username = "<?php print $cookies['bg-username']; ?>"
				var realname = "<?php print $cookies['bg-realname']; ?>"

				set_background(url, title, username, realname)
				return
			}
		}

		// Get a new URL.
		$.ajax({
			'url' : 'http://10.1.247.195/get-flickr-url?callback=jsonp',
			'method' : 'GET',
			'async' : false,
			'dataType' : 'jsonp'
		}).error(function(){
			console.log('error')
		}).success(function(rsp){

			// Get the background image details.
			var url = rsp.url
			var title = rsp.title

			// Get the photo owner details.
			owner = rsp.owner
			username = owner.username
			realname = owner.realname
			profile_url = owner.profile_url

			// Set the background.
			set_background(url, title, username, realname)

			// The data to POST for setting a cookie.
			var data = {
				'action' : 'set-bg-cookie',
				'url' : url,
				'title' : title,
				'username' : username,
				'realname' : realname
			}

			// Set a cookie for the URL.
			$.ajax({
				'url' : '',
				'method' : 'POST',
				'async' : true,
				'data' : data
			}).error(function(){
				console.log('error')
			}).success(function(rsp){
				//console.log(rsp)
			})

		})

	}

	function set_time(){

		// Display the current date and time on the page.

		// Get date values.
		var date = new Date();
		var month = date.getMonth()+1
		var day = date.getDate();
		var year = date.getFullYear()

		// Make sure the day and month are displayed correctly.
		if(day<10){
			day = '0'+day
		}
		if(month<10){
			month = '0'+month
		}

		// Get the date string to use on the page.
		var datestring = month + '-' + day + '-' + year

		// Get the time values.
		var hour = date.getHours()
		var minute = date.getMinutes()

		// Make sure time is displayed properly.
		if(minute<10){
			minute = '0'+minute
		}
		if(hour>12){
			hour -= 12
		}

		// Get the time string to use on the page.
		var timestring = hour + ':' + minute

		// Get the date and time containers.
		var $date = $('#date-container')
		var $time = $('#time-container')

		// Set the date and time.
		$date.text(datestring)
		$time.text(timestring)

	}

	function search(e){

		// Support dashboard searching.

		// Prevent navigating away.
		e.preventDefault()

		// Get the search type.
		// var $div = $(this)
		// var search_type  = $div.attr('data-search-type')

		// Get the search value.
		var $input = $('#search-input')
		var value = $input.val().trim().toUpperCase()

		// Get the search-type.
		$selected = $('.search-type.active')
		search_type = $selected.text()

		// The data used to search.
		if(search_type=='SO'){
			var data = {'so-number':value}
			var uri = '/dashboard/sales-order-status/so-details'
		} else if(search_type=='Item') {
			var data = {'item-number':value}
			var uri = '/dashboard/inventory/item-details'
		} else if(search_type=='Client'){
			var data = {'custno':value}
			var uri = '/dashboard/clients/details'
		} else if (search_type=='Quote'){
			var data = {'opportunity_id':value}
			var uri = 'dashboard/opportunities/details'
		} else if (search_type== 'PO'){
			var data = {'purno':value}
			var uri = '/dashboard/purchaseorders/details'
			activateOverlayZ(BASE_URI+uri, data, undefined, undefined, undefined, 'html')
			return
		}

		// The URL for overlayz
		var url = BASE_URI+uri

		// Create the overlay.
		activateOverlayZ(url, data)

		return false

	}

	function refresh_background(){

		// Refresh the background image.
		get_background(refresh=true)

	}

	function hack_overlay(){

		// Temporary hack to fix the overlay-loading issue.


		// Get and remove the details container.
		var $container = $('#client-details-container')
		$container.remove()

	}

	function position_footer(){

		// Position the footer at the bottom of the page with proper padding.

		// Get the window and main dashboard height.
		var win_height = $('#body').outerHeight()
		var db_height = $('#dashboard-container').outerHeight()

		// Get the footer and its height.
		var $footer = $('#footer')
		var ft_height = $footer.outerHeight()

		// Get the remaining space between the dashboard content and the bottom
		// of the page.
		var remainder = win_height - db_height

		// Get the difference between the remaining height and the footer height
		// to know how much to pad the footer.
		var pad_height = remainder - ft_height

		// Pad the top of the footer.
		$footer.css('padding-top', pad_height+'px')

	}

	function handle_search_type(){

		// Update the search type.

		// Get all search types.
		$types = $('.search-type')

		// Remove any active seletction.
		$types.removeClass('active')

		// Make the section active.
		$selected = $(this)
		$selected.addClass('active')

		// Update the dropdown text.
		$dropdown = $('.search-type-label')
		newtext = $selected.text()
		$dropdown.text(newtext)

	}

	function maybe_search(e){

		// Check for <Enter> being pressed and submit the search.

		if(e.keyCode == 13){
			search(e)
		}

	}

	// Start cycling background images.
	get_background()

	// Set the date and time and update it every second.
	set_time()
	setInterval(set_time, 1000)

	// Set the footer position.
	position_footer()

	// Enable dashboard searching.
	$(document).off('click', '.search-icon')
	$(document).on('click', '.search-icon', search)

	$(document).off('click', '.overlayz-close')
	$(document).on('click', '.overlayz-close', hack_overlay)

	// Support refreshing the background.
	$(document).off('click', '#refresh-icon')
	$(document).on('click', '#refresh-icon', refresh_background)

	// Enable search-type selection.
	$(document).off('click', '.search-type')
	$(document).on('click', '.search-type', handle_search_type)

	// Support submitting search by pressing <Enter>.
	$(document).off('keypress', '#search-input')
	$(document).on('keypress', '#search-input', maybe_search)

	// Adjust the footer position on resize.
	$(window).resize(position_footer)

})
</script>

<?php Template::Render('footer', 'account');