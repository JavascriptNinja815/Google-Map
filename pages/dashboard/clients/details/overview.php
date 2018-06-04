<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_client = $db->query("
	SELECT
		arcust.custno,
		arcust.company,
		arcust.address1,
		arcust.address2,
		arcust.city,
		arcust.addrstate,
		arcust.zip,
		arcust.country,
		arcust.phone,
		arcust.faxno,
		arcust.email,
		arcust.onorder,
		arcust.balance,
		arcust.pterms,
		arcust.vic,
		arcust.sic,
		arcust.sic2,
		arcust.sic3,
		arcust.naics,
		arcust.naics2,
		arcust.naics3,
		arcust.omr,
		arcust.indtype,
		arcust.source,
		CONVERT(varchar(10), arcust.entered, 120) AS entered,
		arcust.terr,
		arcust.salesmn,
		arcust.arvic,
		arcust.tax,
		arcust.code,
		arcust.nlacno
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
");
$client = $grab_client->fetch();
unset($grab_client);

// Grab SIC descriptions...
$sic_descriptions = [];
foreach([$client['sic'], $client['sic2'], $client['sic3']] as $sic_code) {
	$sic_code = trim($sic_code);
	if(empty($sic_code)) { // Skip empty SIC codes.
		continue;
	}

	foreach([
		'industry_codes.code_long' => $sic_code,
		'industry_codes.code_6' => substr($sic_code, 0, 6),
		'industry_codes.code_5' => substr($sic_code, 0, 5),
		'industry_codes.code_4' => substr($sic_code, 0, 4)
	] as $sic_column => $sic_value) {
		$grab_sic_description = "
			SELECT TOP 1
				industry_codes.description
			FROM
				" . DB_SCHEMA_INTERNAL . ".industry_codes
			WHERE
				industry_codes.type = 'SIC'
				AND
				" . $sic_column . " = " . $db->quote($sic_value) . "
		";
		$grab_sic_description = $db->query($grab_sic_description);
		$sic_description = $grab_sic_description->fetch();
		if(!empty($sic_description)) {
			$sic_descriptions[$sic_code] = $sic_description['description'];
			break;
		}
	}
}

// Grab NAICS descriptions...
$naics_descriptions = [];
foreach([$client['naics'], $client['naics2'], $client['naics3']] as $naics_code) {
	$naics_code = trim($naics_code);
	if(empty($naics_code)) { // Skip empty NAICS codes.
		continue;
	}

	foreach([
		'industry_codes.code_long' => $naics_code,
		'industry_codes.code_8' => substr($naics_code, 0, 8),
		'industry_codes.code_7' => substr($naics_code, 0, 7),
		'industry_codes.code_6' => substr($naics_code, 0, 6)
	] as $naics_column => $naics_value) {
		$grab_naics_description = $db->query("
			SELECT TOP 1
				industry_codes.description
			FROM
				" . DB_SCHEMA_INTERNAL . ".industry_codes
			WHERE
				industry_codes.type = 'NAICS'
				AND
				" . $naics_column . " = " . $db->quote($naics_value) . "
		");
		$naics_description = $grab_naics_description->fetch();
		if(!empty($naics_description)) {
			$naics_descriptions[$naics_code] = $naics_description['description'];
			break;
		}
	}
}

$grab_margins = $db->query("
	SELECT
		invYear,
		yearlyPaid,
		yearlyCost,
		(yearlyPaid - yearlyCost) AS annProfit,
		(
			SELECT
				CASE WHEN yearlyPaid > 0 THEN
					(yearlyPaid - yearlyCost) / yearlyPaid
				ELSE
					0
				END
		) AS annMarginPercent
	FROM
		(
			SELECT
				SUM(extprice) AS yearlyPaid,
				YEAR(invdte) as invYear,
				SUM(qtyshp * cost) AS yearlyCost
			FROM
				(
					SELECT
						arytrn.invno,
						arytrn.invdte,
						arytrn.item,
						arytrn.qtyshp,
						arytrn.cost,
						arytrn.extprice,
						arytrn.custno,
						arytrn.terr
					FROM
						" . DB_SCHEMA_ERP . ".arytrn
					WHERE
						RTRIM(LTRIM(arytrn.custno)) = " . $db->quote(trim($_POST['custno'])) . "

					UNION ALL

					SELECT
						artran.invno,
						artran.invdte,
						artran.item,
						artran.qtyshp,
						artran.cost,
						artran.extprice,
						artran.custno,
						artran.terr
					FROM
						" . DB_SCHEMA_ERP . ".artran
					WHERE
						RTRIM(LTRIM(artran.custno)) = " . $db->quote(trim($_POST['custno'])) . "
				) as tbl
			GROUP BY
				YEAR(invdte)
		) as tbl2
	ORDER BY
		invYear DESC
");
$margins = $grab_margins->fetchAll();
unset($grab_margins);

$grab_notes = $db->query("
	SELECT
		arcust.cstmemo AS notes
	FROM
		" . DB_SCHEMA_ERP . ".arcust
	WHERE
		RTRIM(LTRIM(arcust.custno)) = " . $db->quote(trim($_POST['custno'])) . "
");
$notes = $grab_notes->fetch();
unset($grab_notes);

// Grab previous orders so we can determine average order rate (in days).
$order_months = 36;
$grab_prev_orders = $db->query("
	SELECT
		COUNT(*) AS count,
		CONVERT(varchar(10), MIN(somast.adddate), 120) AS first,
		CONVERT(varchar(10), MAX(somast.adddate), 120) AS last,
		CONVERT(varchar(10), DATEDIFF(d, MAX(somast.adddate), GETDATE()), 120) AS days_since_last_order
	FROM
		".DB_SCHEMA_ERP.".somast
	WHERE
		somast.adddate > DATEADD(month, -" . $order_months . ", GETDATE())
		AND
		somast.custno = " . $db->quote(trim($_POST['custno'])) . "
	HAVING
		COUNT(*) >= 3
");
$prev_orders = $grab_prev_orders->fetch();
if($prev_orders) {
	$days_between_orders = (($order_months / 12) * 365) / $prev_orders['count']; // Breaks down months into days.
	$days_since_last_order = $prev_orders['days_since_last_order'];
} else {
	$days_between_orders = '';
	$days_since_last_order = '';
}

function get_breakdown($custno, $year){

	// Query for a monthly breakdown for billing/margin/margin %.

	$db = DB::get();
	$q = $db->query("
		WITH client_values AS (
			SELECT
				YEAR(arytrn.invdte) AS iyear,
				--DATENAME(MONTH, MONTH(arytrn.invdte)) AS imonth,
				MONTH(arytrn.invdte) AS imonth,
				arytrn.qtyshp,
				arytrn.cost,
				arytrn.extprice
			FROM ".DB_SCHEMA_ERP.".arytrn
			WHERE RTRIM(LTRIM(arytrn.custno)) = ".$db->quote($custno)."
				AND YEAR(arytrn.invdte) = ".$db->quote($year)."

			UNION ALL

			SELECT
				YEAR(artran.invdte) AS iyear,
				--DATENAME(MONTH, MONTH(artran.invdte)) AS imonth,
				MONTH(artran.invdte) AS imonth,
				artran.qtyshp,
				artran.cost,
				artran.extprice
			FROM ".DB_SCHEMA_ERP.".artran
			WHERE RTRIM(LTRIM(artran.custno)) = ".$db->quote($custno)."
				AND YEAR(artran.invdte) = ".$db->quote($year)."
		), annual_values AS (
			SELECT
				SUM(extprice) AS paid,
				imonth,
				SUM(qtyshp * cost) AS cost
			FROM client_values
			GROUP BY imonth
		) SELECT
			imonth,
			paid,
			cost,
			(paid-cost) AS profit,
			CASE
				WHEN paid > 0
				THEN (paid-cost)/paid
				ELSE 0
			END AS margin_perc
		FROM annual_values
		ORDER BY imonth ASC
	");

	// Get query results.
	$rows = $q->fetchAll();

	// Build a table for the new rows.
	$html =  '<div class="row-fluid">';
	$html .=	'<table class="table table-striped table-hover">';
	$html .=		'<thead>';
	$html .=			'<th></th>';
	$html .=			'<th>Month</th>';
	$html .=			'<th>Billing</th>';
	$html .=			'<th>Margin</th>';
	$html .=			'<th>Margin %</th>';
	$html .=		'</thead>';
	$html .=		'<tbody>';
	foreach($rows as $row){

		// Do the translation.
		$dt = DateTime::createFromFormat('!m', $row['imonth']);
		$row['imonth'] = $dt->format('F');

		$html .= '<tr>';
		$html .=	'<td></td>';
		$html .= 	'<td>'.htmlentities($row['imonth']).'</td>';
		$html .= 	'<td>$'.number_format($row['paid'], 2).'</td>';
		$html .= 	'<td>$'.number_format($row['profit'], 2).'</td>';
		$html .= 	'<td>'.number_format($row['margin_perc']*100, 1).'%</td>';
		$html .= '</tr>';

	}
	$html .=		'</tbody>';
	$html .=	'</table>';
	$html .= '</div>';

	return $html;

}

function get_rolling_12(){

	// Get the table data for the last 12 months.

	$db = DB::get();
	$q = $db->query("
		DECLARE @days_ago INTEGER;
		DECLARE @today DATE;
		DECLARE @last_year DATE;

		SET @days_ago = 365;
		SET @today = GETDATE();
		SET @last_year = DATEADD(day, -@days_ago, @today);

		WITH client_values AS (
			SELECT
				arytrn.qtyshp,
				arytrn.cost,
				arytrn.extprice
			FROM ".DB_SCHEMA_ERP.".arytrn
			WHERE RTRIM(LTRIM(arytrn.custno)) = ".$db->quote($_POST['custno'])."
				AND invdte >= @last_year

			UNION ALL

			SELECT
				artran.qtyshp,
				artran.cost,
				artran.extprice
			FROM ".DB_SCHEMA_ERP.".artran
			WHERE RTRIM(LTRIM(artran.custno)) = ".$db->quote($_POST['custno'])."
				AND invdte >= @last_year
		), annual_values AS (
			SELECT
				SUM(extprice) AS paid,
				SUM(qtyshp * cost) AS cost
			FROM client_values
		) SELECT
			paid,
			cost,
			(paid-cost) AS profit,
			CASE
				WHEN paid > 0
				THEN (paid-cost)/paid
				ELSE 0
			END AS marg_perc
		FROM annual_values
	");

	return $q->fetch();

}

// Handle AJAX requests.
if(isset($_POST['action'])){

	// Get a monthly breakdown.
	if($_POST['action']=='get-breakdown'){

		// Get the customer number and year.
		$custno = $_POST['custno'];
		$year = $_POST['year'];

		// Get the actual breakdown figures.
		$breakdown = get_breakdown($custno, $year);

		print json_encode(array(
			'success' => true,
			'html' => $breakdown
		));

		return;

	}

}

// Get the last 12 months.
$rolling = get_rolling_12();

?>

<style type="text/css">
	.expand-row {
		cursor:pointer;
		width:5px;
	}
	.breakdown-row {
		display:none;
	}
</style>

<div class="tab-content-section">
	<h3>General Info</h3>
	<table>
		<tbody>
			<tr>
				<td class="title-width">Client Code:</td>
				<td><?php print htmlentities($client['custno']);?></td>
			</tr>
			<tr>
				<td class="title-width">Client Name:</td>
				<td><?php print htmlentities($client['company']);?></td>
			</tr>
			<tr>
				<td class="title-width">Address:</td>
				<td>
					<div><?php print htmlentities($client['address1']);?></div>
					<div><?php print htmlentities($client['address2']);?></div>
					<div><?php print htmlentities($client['city']);?>, <?php print htmlentities($client['addrstate']);?> <?php print htmlentities($client['zip']);?></div>
					<div><?php print htmlentities($client['country']);?></div>
				</td>
			</tr>
			<tr>
				<td class="title-width">Phone:</td>
				<td><?php print htmlentities($client['phone']);?></td>
			</tr>
			<tr>
				<td class="title-width">Fax:</td>
				<td><?php print htmlentities($client['faxno']);?></td>
			</tr>
			<tr>
				<td class="title-width">E-Mail:</td>
				<td><?php print htmlentities($client['email']);?></td>
			</tr>
		</tbody>
	</table>
</div>

<div class="tab-content-section">
	<h3>Company Info</h3>
	<table>
		<tbody>
			<tr>
				<td class="title-width">Priority Client:</td>
				<td><?php
					if($client['vic']) {
						print 'YES';
					} else {
						print 'No';
					}
				?></td>
			</tr>
			<tr>
				<td class="title-width">SIC Code(s):</td>
				<td><?php
				if(!empty($client['sic'])) {
					?><div><?php
						$client['sic'] = trim($client['sic']);
						print htmlentities($client['sic']);
						if(!empty($sic_descriptions[$client['sic']])) {
							print ' - ' . htmlentities($sic_descriptions[$client['sic']]);
						}
					?></div><?php
				}
				if(!empty($client['sic2'])) {
					?><div><?php
						$client['sic2'] = trim($client['sic2']);
						print htmlentities($client['sic2']);
						if(!empty($sic_descriptions[$client['sic2']])) {
							print ' - ' . htmlentities($sic_descriptions[$client['sic2']]);
						}
					?></div><?php
				}
				if(!empty($client['sic3'])) {
						$client['sic3'] = trim($client['sic3']);
					?><div><?php
						print htmlentities($client['sic3']);
						if(!empty($sic_descriptions[$client['sic3']])) {
							print ' - ' . htmlentities($sic_descriptions[$client['sic3']]);
						}
					?></div><?php
				}
				?></td>
			</tr>
			<tr>
				<td class="title-width">NAICS Code(s):</td>
				<td><?php
				if(!empty($client['naics'])) {
						$client['naics'] = trim($client['naics']);
					?><div><?php
						print htmlentities($client['naics']);
						if(!empty($naics_descriptions[$client['naics']])) {
							print ' - ' . htmlentities($naics_descriptions[$client['naics']]);
						}
					?></div><?php
				}
				if(!empty($client['naics2'])) {
						$client['naics2'] = trim($client['naics2']);
					?><div><?php
						print htmlentities($client['naics2']);
						if(!empty($naics_descriptions[$client['naics2']])) {
							print ' - ' . htmlentities($naics_descriptions[$client['naics2']]);
						}
					?></div><?php
				}
				if(!empty($client['naics3'])) {
						$client['naics3'] = trim($client['naics3']);
					?><div><?php
						print htmlentities($client['naics3']);
						if(!empty($naics_descriptions[$client['naics3']])) {
							print ' - ' . htmlentities($naics_descriptions[$client['naics3']]);
						}
					?></div><?php
				}
				?></td>
			</tr>
			<tr>
				<td class="title-width">OEM/MRO/Resale:</td>
				<td><?php print htmlentities($client['omr']);?></td>
			</tr>
			<tr>
				<td class="title-width">Industry Type:</td>
				<td><?php print htmlentities($client['indtype']);?></td>
			</tr>
			<tr>
				<td class="title-width">Source:</td>
				<td><?php print htmlentities($client['source']);?></td>
			</tr>
			<tr>
				<td class="title-width">Add Date:</td>
				<td><?php print htmlentities($client['entered']);?></td>
			</tr>
			<tr>
				<td class="title-width">Office:</td>
				<td><?php print htmlentities($client['terr']);?></td>
			</tr>
			<tr>
				<td class="title-width">Salesperson:</td>
				<td><?php print htmlentities($client['salesmn']);?></td>
			</tr>
		</tbody>
	</table>
</div>

<div class="tab-content-section">
	<h3>Client Account</h3>
	<table>
		<tbody>
			<tr>
				<td class="title-width">Priority AR Client:</td>
				<td><?php print htmlentities($client['vic']);?></td>
			</tr>
			<tr>
				<td class="title-width">Tax Rate:</td>
				<td><?php print htmlentities($client['sic']);?></td>
			</tr>
			<tr>
				<td class="title-width">Invoice Type:</td>
				<td><?php print htmlentities($client['naics']);?></td>
			</tr>
			<tr>
				<td class="title-width">National Account:</td>
				<td><?php print htmlentities($client['omr']);?></td>
			</tr>
			<tr>
				<td class="title-width">Average Days Between Orders</td>
				<td><?php print $days_between_orders ? number_format($days_between_orders, 0) . ' (' . $prev_orders['count'] . ' order in past ' . $order_months . ' months)' : Null;?></td>
			</tr>
			<tr>
				<td class="title-width">Projected Next Order Date</td>
				<td><?php
					if($days_since_last_order) {
						$order_date_diff = $days_between_orders - $days_since_last_order;
						if($order_date_diff > 0) {
							$order_date_diff = floor(abs($order_date_diff)) . ' Days Under';
						} else {
							$order_date_diff = floor(abs($order_date_diff)) . ' Days Over';
						}
						print $order_date_diff . ' (last order on ' . $prev_orders['last'] . ')';
					}
				?></td>
			</tr>
		</tbody>
	</table>
</div>

<div class="tab-content-section">
	<h3>Client Notes</h3>
	<p><?php print htmlentities($notes['notes']);?></p>
</div>

<div class="tab-content-section full-width">
	<h3>Historical Margins</h3>
	<table summary="historical margins">
		<thead>
			<tr>
				<th></th>
				<th>Year</th>
				<th class="right">Billing</th>
				<th class="right">Margin</th>
				<th>Margin %</th>
			</tr>
		</thead>
		<tbody>
			<tr class="rolling-12">
				<td></td>
				<td>Last 12</td>
				<td class="right">$<?php print number_format($rolling['paid'], 2) ?></td>
				<td class="right">$<?php print number_format($rolling['profit'], 2) ?></td>
				<td><?php print number_format($rolling['marg_perc']*100, 1) ?>%</td>
			</tr>
			<?php
			if(!empty($margins)) {
				foreach($margins as $margin) {
					?>
					<tr data-opened="0" data-custno="<?php print htmlentities(trim($_POST['custno'])) ?>" data-year="<?php print htmlentities($margin['invYear']) ?>">
						<td class="expand-row"><i class="fa fa-plus"></i></td>
						<td><?php print htmlentities($margin['invYear']);?></td>
						<td class="right">$<?php print number_format($margin['yearlyPaid'], 2);?></td>
						<td class="right">$<?php print number_format($margin['annProfit'], 2);?></td>
						<td><?php print number_format($margin['annMarginPercent'] * 100, 1);?>%</td>
					</tr>
					<tr class="breakdown-row"><td class="breakdown-body" colspan="5"></td></tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	$(document).ready(function(){

		function expand_row(){

			// Get the row and year.
			var $row = $(this).parents('tr')
			var custno = $row.attr('data-custno')
			var year = $row.attr('data-year')

			// Get the icon.
			var $icon = $row.find('i')

			// Get the breakdown div.
			var $bd_row = $row.next('.breakdown-row')
			var $bd_body = $bd_row.find('.breakdown-body')

			// Check whether the row has been expanded.
			var opened = $row.attr('data-opened')

			// If its open, close it.
			if(opened=='1'){
				$icon.replaceWith('<i class="fa fa-plus"></i>')
				$row.attr('data-opened', '0')
				$bd_row.hide()
				return
			}

			// The data to POST.
			var data = {
				'action' : 'get-breakdown',
				'custno' : custno,
				'year' : year
			}

			// Do the AJAX request.
			$.ajax({
				'url' : '/dashboard/clients/details/overview',
				'method' : 'POST',
				'dataType' : 'JSON',
				'data' : data,
				'success' : function(rsp){

					// Mark the row as opened.
					$row.attr('data-opened', '1')

					// Swap out the icons.
					$icon.replaceWith('<i class="fa fa-minus"></i>')

					// Display the HTML.
					$bd_body.html(rsp.html)
					$bd_row.show()
					$bd_row.css({'display':'table-row'})

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		$(document).off('click', '.expand-row')
		$(document).on('click', '.expand-row', expand_row)

	})
</script>