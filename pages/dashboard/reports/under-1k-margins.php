<?php

function get_margins($users){

	// Query for margins under $1k for the last 365 days.

	$db = DB::get();
	$query = "
		DECLARE @days_ago INTEGER;
		DECLARE @today DATE;
		DECLARE @last_year DATE;

		SET @days_ago = 365;
		SET @today = GETDATE();
		SET @last_year = DATEADD(day, -@days_ago, @today);

		WITH client_values AS (
			SELECT
				arytrn.custno,
				arytrn.qtyshp,
				arytrn.cost,
				arytrn.extprice,
				CAST(invdte AS date) AS invdte
			FROM PRO01.dbo.arytrn
			WHERE invdte >= @last_year

			UNION ALL

			SELECT
				artran.custno,
				artran.qtyshp,
				artran.cost,
				artran.extprice,
				CAST(invdte AS date) AS invdte
			FROM PRO01.dbo.artran
			WHERE invdte >= @last_year
		), annual_values AS (
			SELECT
				custno,
				SUM(extprice) AS paid,
				SUM(qtyshp * cost) AS cost,
				MAX(invdte) AS last_date
			FROM client_values
			GROUP BY custno
		), balances AS (
			SELECT
				armast.custno,
				SUM(armast.bbal) AS net
			FROM PRO01.dbo.armast
			WHERE armast.arstat NOT IN ('V')--, 'C')
				AND armast.invdte >= @last_year
			GROUP BY custno
		), entered AS (
			SELECT DISTINCT
				custno,
				CAST(entered AS date) AS entered,
				CASE WHEN entered > @last_year
					THEN 1
					ELSE 0
				END AS new,
				CASE WHEN entered > @last_year
					THEN DATEDIFF(DAY, entered, @today)
					ELSE NULL
				END AS days_to_mature
			FROM PRO01.dbo.arcust
		)

		SELECT
			a.custno,
			salesmn,
			terr,
			last_date,
			paid,
			cost,
			b.net,
			(paid-cost) AS margin,
			e.entered,
			e.new,
			e.days_to_mature
		FROM annual_values a
		INNER JOIN entered e
			ON e.custno = a.custno
		INNER JOIN PRO01.dbo.arcust c
			ON c.custno = a.custno
		LEFT JOIN balances b
			ON b.custno = a.custno
		WHERE paid-cost < 1000
		--ORDER BY a.custno, c.salesmn, c.terr
	";

	//AND salesmn IN (".$db->quote(implode($qusers, ',')).")
	if($users){
		$qusers = array();
		foreach($users as $user){
			array_push($qusers, $db->quote($user));
		}
		$query .= "AND salesmn IN (".implode($qusers, ',').")";
	}

	// Execute the query.
	$q = $db->query($query);

	return $q->fetchAll();

}

function get_available_users(){

	// Get the users the logged-in user has permission to view.

	global $session;
	$login = $session->login;
	$login_id = $login['login_id'];

	// Do not constrain admins or supervisors.
	if($session->hasRole('Administration') or $session->hasRole('Supervisor')){
		$login_id = null;
	}

	$db = DB::get();
	$query = "
		SELECT DISTINCT
			LTRIM(RTRIM(p.permission_value)) v
		FROM Neuron.dbo.logins l
		INNER JOIN Neuron.dbo.login_role_permissions p
			ON p.login_id = l.login_id
		WHERE permission_type = 'view-orders'
	";

	// Constrain to the login ID.
	if($login_id){
		$query .= " AND l.login_id = ".$db->quote($login_id);
	}

	// Execute the query.
	$q = $db->query($query);
	$r = $q->fetchAll();

	// Aggregate the permitted users.
	$users = array();
	foreach($r as $row){
		array_push($users, $row['v']);
	}

	return $users;

}

// Get the permitted users.
$users = get_available_users();

// Get >$1k margins.
$margins = get_margins($users);

ob_start(); // Start loading output into buffer.

?>

<style type="text/css">
	.new-client {
		color:green;
	}
</style>

<div class="container-fluid">
	<h2>Clients Under $1k in Margin (Last 12 months)</h2>
</div>

<div id="margins-report-container" class="container-fluid">
	<table id="margins-table" class="table table-striped table-hover table-small columns-filterable columns-sortable">
		<thead>
			<tr>
				<th class="filterable sortable">Days Until 12 Months</th>
				<th class="filterable sortable">Client</th>
				<th class="filterable sortable">Sales Person</th>
				<th class="filterable sortable">Territory</th>
				<th class="filterable sortable">Billing</th>
				<th class="filterable sortable">Margin</th>
				<th class="filterable sortable">Last Sale Date</th>
				<th class="filterable sortable">Open Orders</th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($margins as $margin){

					// If the row is new, highlight it.
					$new = '';
					if($margin['new']==1){
						$new = 'new-client';
					}

					?>
					<tr class="client-row <?php print $new ?>">

						<?php
						// Client overlay.

						$data = htmlentities(json_encode(array('custno'=>trim($margin['custno']))));
						?>

						<td><?php print htmlentities($margin['days_to_mature']) ?></td>
						<td class="overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print $data ?>"><?php print htmlentities($margin['custno']) ?></td>
						<td><?php print htmlentities($margin['salesmn']) ?></td>
						<td><?php print htmlentities($margin['terr']) ?></td>
						<td><?php print number_format($margin['paid'], 2) ?></td>
						<td><?php print number_format($margin['margin'], 2) ?></td>
						<td><?php print htmlentities($margin['last_date']) ?></td>
						<td><?php print number_format($margin['net'], 2) ?></td>
					</tr>
					<?php
				}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
$(document).ready(function(){

	function sortfilter_table(){

		// Make the table sortable/filterable.

		var c = 0
		var i = setInterval(function(){
			bindTableSorter($('#margins-table'))

			// Clearing the interval will not work without this
			// for some reason.
			c++
			if(c>=0){
				clearInterval(i)
			}
		},200)

	}

	// Make the table sortable/filterable.
	sortfilter_table()

})
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode(array(
	'success' => True,
	'html' => $html
));
?>