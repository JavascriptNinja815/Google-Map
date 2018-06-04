<?php

$session->ensureLogin();
ob_start(); // Start loading output into buffer.

function get_contacts(){

	// Get the contacts for the overlay.

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
		SELECT
			LTRIM(RTRIM(p.custno)) AS custno,
			p.count,
			p.first,
			p.last,
			p.days_since_last_order,
			p.days_between_orders,
			ABS(p.date_diff) AS date_diff,
			CASE WHEN p.date_diff > 0
				THEN 'Under'
				ELSE 'Over'
			END as direction,
			l.added_on as added_on
		FROM processed p
		LEFT JOIN PRO01.dbo.cust_contact_log l
			ON l.custno = p.custno COLLATE Latin1_General_BIN
		WHERE p.date_diff < 4
			AND (
				/* Call has never been logged. */
				l.added_on IS NULL
				OR
				/* Call was not logged in the last two weeks. */
				l.added_on < @two_weeks_ago
				)
				AND p.days_since_last_order > 10
		ORDER BY custno
	");

	return $q->fetchAll();

}

// Get the list of contacts.
$contacts = get_contacts();

?>

<style type="text/css">
	#contacts-table tr {
		display: table-row !important;
	}
	.text-center {
		text-align: center;
	}
	.log-call {
		color : white;
	}
</style>

<div id="contacts-list">
	<h3>Contacts List</h3>
	<table id="contacts-table" class="table table-small table-striped table-hover columns-sortable">
		<thead>
			<th class="sortable">Customer</th>
			<th class="sortable text-center">Order Count</th>
			<th class="sortable">First Order</th>
			<th class="sortable">Last Order</th>
			<th class="sortable text-center">Days Since Last Order</th>
			<th class="sortable text-center">Average Days Between Orders</th>
			<th class="sortable text-center">Difference</th>
			<th class="sortable text-center">Log Call</th>
		</thead>
		<tbody>
			<?php
				foreach($contacts AS $contact){
					?>
					<tr data-custno="<?php print htmlentities($contact['custno']) ?>">
						<?php
						// Overlay data.
						$cust = json_encode(array('custno'=>$contact['custno']));
						?>
						<td class="overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities($cust) ?>"><?php print htmlentities($contact['custno']) ?></td>
						<td class="text-center"><?php print htmlentities($contact['count']) ?></td>
						<td><?php print htmlentities($contact['first']) ?></td>
						<td><?php print htmlentities($contact['last']) ?></td>
						<td class="text-center"><?php print htmlentities($contact['days_since_last_order']) ?></td>
						<td class="text-center"><?php print htmlentities($contact['days_between_orders']) ?></td>
						<td class="text-center"><?php print htmlentities($contact['date_diff'].' '.$contact['direction']) ?></td>
						<td class="text-center">
							<?php
							// The data for the overlay.
							$data = json_encode(array('custno'=>$contact['custno']));
							?>

							<button class="btn btn-small btn-primary log-call overlayz-link" overlayz-url="/dashboard/clients/log-call" overlayz-data="<?php print htmlentities($data) ?>">Log Call</button>
						</td>
					</tr>
					<?php
				}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		// Make the table filterable/sortable.
		applyTableFeatures('#contacts-table')
	})
</script>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);