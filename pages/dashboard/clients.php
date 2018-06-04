<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */
ini_set('max_execution_time', 300);
$session->ensureLogin();
$session->ensureRole('Sales');

$args = array(
	'title' => 'Client',
	'breadcrumbs' => array(
		'Clients' => BASE_URI . '/dashboard/clients'
	)
);

Template::Render('header', $args, 'account');

// Create the WHERE clause array.
$where = array();
$current_year = date('Y', time());
$last_year = $current_year - 1;

if(isset($_REQUEST['search'])) {
	if(isset($_GET['letter']) && $_GET['letter'] != '*') {
		if($_GET['letter'] == '#') {
			// Starts with any non-letter character.
			$where[] = "LTRIM(RTRIM(arcust.company)) LIKE '[^aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ]%'";
		} else {
			// Normal letter.
			$where[] = "LTRIM(RTRIM(arcust.company)) LIKE " . $db->quote($_GET['letter'] . '%');
		}
	}

	// Credit Hold Status
	if(!empty($_GET['credit-hold'])) {
		$where_parts = [];
		foreach($_GET['credit-hold'] as $credit_hold) {
			$where_parts[] = "LTRIM(RTRIM(arcust.credstat)) = " . $db->quote($credit_hold);
		}
		$where[] = '(' . implode(' OR ', $where_parts) . ')';
	}

	// Client Status
	if(!empty($_GET['client-status'])) {
		$where_parts = [];
		foreach($_GET['client-status'] as $client_status) {
			$where_parts[] = "LTRIM(RTRIM(arcust.custstat)) = " . $db->quote($client_status);
		}
		$where[] = '(' . implode(' OR ', $where_parts) . ')';
	}

	// Client Code
	if(!empty($_GET['client-code'])) {
		$where[] = "LTRIM(RTRIM(arcust.custno)) LIKE " . $db->quote('%' . $_GET['client-code'] . '%');
	}

	// Ensure client list is constrained based on permissions.
	if(!$session->hasRole('Administration') && $session->hasRole('Sales')) {
		$client_permissions = $session->getPermissions('Sales', 'view-orders');
		if(!empty($client_permissions)) {
			$where[] = "arcust.salesmn IN ('" . implode("','", $client_permissions) . "')";
		} else {
			// If the user has not been granted any privs, then finish the query which will already return nothing.
			// TODO: There has to be a more elegant method of handling this...
			$where[] = "1 != 1";
		}
	}

	// Format the WHERE query to a string.
	if(!empty($where)) {
		$where_query = 'WHERE ' . implode(' AND ', $where);
	} else {
		$where_query = '';
	}

	// Grab the clients we're going to display on this page.
	// We use prepare / execute so we can actually return the rowCount().
	$grab_clients = $db->prepare("
		WITH one_year AS (
			SELECT
				arcust.custno,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
			FROM
				" . DB_SCHEMA_ERP . ".soslsm
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				soslsm.salesmn = arcust.salesmn
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('-1 year')
					)
				) . "
				AND
				artran.arstat != 'V'
			GROUP BY
				arcust.custno,
				arcust.company,
				arcust.onorder
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		),
		this_year AS (
			SELECT
				arcust.custno,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
			FROM
				" . DB_SCHEMA_ERP . ".soslsm
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				soslsm.salesmn = arcust.salesmn
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $current_year)
					)
				) . "
				AND
				artran.arstat != 'V'
			GROUP BY
				arcust.custno,
				arcust.company,
				arcust.onorder
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		),
		last_year AS (
			SELECT
				arcust.custno,
				SUM(
					ISNULL(artran.extprice, 0.00)
				) AS sales
			FROM
				" . DB_SCHEMA_ERP . ".soslsm
			INNER JOIN
				" . DB_SCHEMA_ERP . ".arcust
				ON
				soslsm.salesmn = arcust.salesmn
			INNER JOIN
				" . DB_SCHEMA_ERP . ".artran
				ON
				arcust.custno = artran.custno
			WHERE
				artran.invdte >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1, ' . $last_year)
					)
				) . "
				AND
				artran.invdte <= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('December 31, ' . $last_year)
					)
				) . "
				AND
				artran.arstat != 'V'
			GROUP BY
				arcust.custno,
				arcust.company,
				arcust.onorder
			HAVING
				SUM(
					ISNULL(artran.extprice, 0.00)
				) > 0
		)
		SELECT
			arcust.credstat AS credit_hold,
			arcust.custno AS code,
			arcust.company AS client,
			arcust.phone AS phone,
			arcust.salesmn AS sales_personnel,
			arcust.terr AS office,
			arcust.onorder AS open_orders,
			--arcust.ytdsls AS ytd_sales,
			one_year.sales AS oneyear_sales,
			this_year.sales AS ytd_sales,
			last_year.sales AS lastyear_sales,
			arcust.pterms AS terms,
			arcust.credlimit AS limit,
			arcust.balance AS balance,
			arcust.lpymt AS last_payment,
			arcust.tax AS tax_percentage,
			arcust.source AS source,
			CONVERT(varchar(10), arcust.adddate, 120) AS add_date,
			CONVERT(varchar(10), arcust.ldate, 120) AS last_sale_date,
			arcust.custstat AS status,
			arcust.vic
		FROM
			" . DB_SCHEMA_ERP . ".arcust
		LEFT JOIN
			this_year
			ON
			this_year.custno = arcust.custno
		LEFT JOIN
			last_year
			ON
			last_year.custno = arcust.custno
		LEFT JOIN
			one_year
			ON
			one_year.custno = arcust.custno
		" . $where_query . "
		ORDER BY
			arcust.company ASC
	", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL)); // Cursor args passed equired for retrieving rowCount.
	$grab_clients->execute();
}

?>

<div class="padded" id="client-filters">
	<fieldset>
		<legend>Filter Clients</legend>

		<div class="filters">
			<form method="get" class="form-horizontal">
				<input type="hidden" name="search" value="1" />
				<input type="hidden" name="letter" value="<?php print !empty($_GET['letter']) ? htmlentities($_GET['letter'], ENT_QUOTES) : Null;?>" />

				<div class="letters">
					<?php
					$search_args = [];
					parse_str($_SERVER['QUERY_STRING'], $search_args);
					unset($search_args['page']);
					$search_args = http_build_query($search_args);
					if(!empty($search_args)) {
						$search_args = '&';
					}
					?>
					<span href="<?php print BASE_URI;?>/dashboard/clients?search=1&<?php print $search_args;?>letter=*" class="letter">All</span>
					<span href="<?php print BASE_URI;?>/dashboard/clients?search=1&<?php print $search_args;?>letter=<?php print urlencode('#');?>" class="letter" letter="#">#</span>
					<?php
					foreach(range('A', 'Z') as $letter) {
						?><span href="<?php print BASE_URI;?>/dashboard/clients?search=1&<?php print $search_args;?>letter=<?php print htmlentities($letter, ENT_QUOTES);?>" class="letter <?php print !empty($_GET['letter']) && $_GET['letter'] == $letter ? 'selected' : Null;?>" letter="<?php print htmlentities($letter, ENT_QUOTES);?>"><?php print htmlentities($letter);?></span><?php
					}
					?>
				</div>
				<br />

				<label>
					<input type="checkbox" name="credit-hold[]" value="H" <?php print !empty($_GET['credit-hold']) && in_array('H', $_GET['credit-hold']) ? 'checked' : Null;?> /> Credit Hold
				</label>

				<label>
					<input type="checkbox" name="client-status[]" value="A" <?php print empty($_GET['search']) || (!empty($_GET['client-status']) && in_array('A', $_GET['client-status'])) ? 'checked' : Null;?> /> Active
				</label>

				<label>
					<input type="checkbox" name="client-status[]" value="I" <?php print !empty($_GET['client-status']) && in_array('I', $_GET['client-status']) ? 'checked' : Null;?> /> Inactive
				</label>
				
				<label>
					<input type="text" name="client-code" value="<?php print !empty($_GET['client-code']) ? $_GET['client-code'] : Null;?>" /> Client Code
				</label>
			</form>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
	// Bind to clicks on letters.
	$(document).off('click', '#client-filters .letters .letter');
	$(document).on('click', '#client-filters .letters .letter', function(event) {
		var $letter = $(this);
		var letter = $letter.attr('letter');
		var $form = $letter.closest('form');
		var $letter_input = $form.find('input[name="letter"]');
		$letter_input.val(letter);
		$form.submit();
	});

	// Bind to clicks on checkboxes.
	$(document).off('change', 'input[name="credit-hold[]"], input[name="client-status[]"], input[name="client-code"]');
	$(document).on('change', 'input[name="credit-hold[]"], input[name="client-status[]"], input[name="client-code"]', function(event) {
		var $input = $(this);
		var $form = $input.closest('form');
		
		// Ensure Client Code is uppercase before submitting.
		var $client_code = $form.find('input[name="client-code"]');
		$client_code.val($client_code.val().toUpperCase());

		$form.submit();
	});
</script>

<?php
if(isset($_REQUEST['search'])) {
	?>
	<fieldset>
		<legend>
			<div class="padded-x">
				Clients Matching Criteria:
				<span id="order-count"><?php print number_format($grab_clients->rowCount());?></span>
			</div>
		</legend>
		<table id="clients-container" class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable" filterable-count-container="#order-count">
			<thead>
				<tr>
					<th class="filterable sortable">Credit Hold</th>
					<th class="filterable sortable" title="Very Important Client">VIC</th>
					<th class="filterable sortable">Code</th>
					<th class="filterable sortable">Client</th>
					<th class="filterable sortable">Phone</th>
					<th class="filterable sortable">Sales Person</th>
					<th class="filterable sortable">Office</th>
					<th class="filterable sortable">Open Orders</th>
					<th class="filterable sortable">YTD Sales</th>
					<th class="filterable sortable">12mo Sales</th>
					<th class="filterable sortable">Last Year</th>
					<th class="filterable sortable">Terms</th>
					<th class="filterable sortable">Limit</th>
					<th class="filterable sortable">Balance</th>
					<th class="filterable sortable">Last Payment</th>
					<th class="filterable sortable">Tax %</th>
					<th class="filterable sortable">Source</th>
					<th class="filterable sortable">Add Date</th>
					<th class="filterable sortable">Last Sale</th>
					<th class="filterable sortable">Status</th>
				</tr>
			<tbody>
				<?php
				while($client = $grab_clients->fetch()) {
					$lastSaleDiff = floor((time()-intval(strtotime($client['last_sale_date'])))/(60*60*24));
					if ($lastSaleDiff>16000){
						$lastSaleDiff="never";
					}
					?>
					<tr class="stripe<?php $client['credit_hold'] == 'H' ? print 'on-credit-hold' : Null;?>" client="<?php print trim(htmlentities($client['client'], ENT_QUOTES));?>" code="<?php print trim(htmlentities($client['code'], ENT_QUOTES));?>">
						<td class="content content-credit-hold">
							<?php
							if($client['credit_hold'] == 'H') {
								?>
								<i class="fa fa-fw fa-lock"></i>
								<span class="text">Lock</span>
								<?php
							}
							?>
						</td>
						<td class="content content-add-date"><input type="checkbox" name="vic" value="1" <?php print $client['vic'] ? 'checked="checked"' : Null;?> /></td>
						<td class="content content-code overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities(json_encode(['custno' => trim($client['code'], ENT_QUOTES)]), ENT_QUOTES);?>"><?php print htmlentities($client['code']);?></td>
						<td class="content content-client overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities(json_encode(['custno' => trim($client['code'], ENT_QUOTES)]), ENT_QUOTES);?>"><?php print htmlentities($client['client']);?></td>
						<td class="content content-phone"><?php print htmlentities($client['phone']);?></td>
						<td class="content content-sales-personnel"><?php print htmlentities($client['sales_personnel']);?></td>
						<td class="content content-office"><?php print htmlentities($client['office']);?></td>
						<td class="content right content-open-orders"><?php
							print number_format($client['open_orders']);
						?></td>
						<td class="content right content-ytd-sales">$<?php
							print number_format($client['ytd_sales'], 2);
						?></td>
						<td class="content right content-oneyear-sales">$<?php
							print number_format($client['oneyear_sales'], 2);
						?></td>
						<td class="content right content-lastyear-sales">$<?php
							print number_format($client['lastyear_sales'], 2);
						?></td>
						<td class="content content-terms"><?php print htmlentities($client['terms']);?></td>
						<td class="content right content-limit">$<?php
							print number_format($client['limit'], 2);
						?></td>
						<td class="content right content-balance">$<?php
							print number_format($client['balance'], 2);
						?></td>
						<td class="content right content-last-payment">$<?php
							print number_format($client['last_payment'], 2);
						?></td>
						<td class="content right content-tax-percentage"><?php
							print number_format($client['tax_percentage']);
						?>%</td>
						<td class="content content-source"><?php print htmlentities($client['source']);?></td>
						<td class="content content-add-date"><?php print htmlentities($client['add_date']);?></td>
						<td class="content content-add-date"><?php print htmlentities($lastSaleDiff);?></td>
						<td class="content content-status">
							<?php
							if($client['status'] == 'A') {
								print 'Active';
							} else if($client['status'] == 'I') {
								print 'Inactive';
							} else {
								print '??? (' . $client['status'] . ')';
							}
							?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</fieldset>
	<?php
}

Template::Render('footer', 'account');
