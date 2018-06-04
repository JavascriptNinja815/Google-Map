<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$invoices = $db->prepare("
	SELECT DISTINCT
		DATEDIFF(DAY, armast.invdte, GETDATE()) AS age,
		armast.custno,
		armast.invno AS invoice_number,
		CONVERT(varchar(10), armast.invdte, 120) AS invoice_date,
		armast.ponum AS po_number,
		armast.pterms AS payment_terms,
		armast.balance AS balance,
		armast.paidamt AS amount_paid,
		armast.invamt AS original_amount,
		CONVERT(varchar(10), artran.paydate, 120) AS pay_date,
		armast.followup AS follow_up,
		armast.arnotes AS client_notes,
		artran.arinvnts AS invoice_notes,
		arcust.code AS invoice_type,
		arcust.arvic,
		arcust.credstat AS credit_hold
	FROM
		" . DB_SCHEMA_ERP . ".armast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".artran
		ON
		artran.invno = armast.invno
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcust
		ON
		arcust.custno = armast.custno
	WHERE
		armast.arstat != 'V'
		AND
		armast.balance > 0
		AND
		RTRIM(LTRIM(armast.custno)) = " . $db->quote(trim($_POST['custno'])) . "
	ORDER BY
		armast.invno
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$invoices->execute();

$past_thirty = date('Y-m-d', strtotime('now - 30 days'));
$past_ninety = date('Y-m-d', strtotime('now - 90 days'));
$past_sixmos = date('Y-m-d', strtotime('now - 182 days'));
$past_oneyear = date('Y-m-d', strtotime('now - 365 days'));
$past_twoyears = date('Y-m-d', strtotime('now - 730 days'));

$grab_daystopay = $db->query("
	WITH thirty AS (
		SELECT
			AVG(DATEDIFF(DAY, arcash.invdte, arcash.dtepaid)) AS days_to_pay
		FROM
			" . DB_SCHEMA_ERP . ".arcash
		WHERE
			LTRIM(RTRIM(arcash.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			arcash.dtepaid >= " . $db->quote($past_thirty) . "
	),
	ninety AS (
		SELECT
			AVG(DATEDIFF(DAY, arcash.invdte, arcash.dtepaid)) AS days_to_pay
		FROM
			" . DB_SCHEMA_ERP . ".arcash
		WHERE
			LTRIM(RTRIM(arcash.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			arcash.dtepaid >= " . $db->quote($past_ninety) . "
			--AND
			--arcash.dtepaid <  " . $db->quote($past_thirty) . "
	),
	sixmos AS (
		SELECT
			AVG(DATEDIFF(DAY, arcash.invdte, arcash.dtepaid)) AS days_to_pay
		FROM
			" . DB_SCHEMA_ERP . ".arcash
		WHERE
			LTRIM(RTRIM(arcash.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			arcash.dtepaid >= " . $db->quote($past_sixmos) . "
			--AND
			--arcash.dtepaid <  " . $db->quote($past_ninety) . "
	),
	oneyear AS (
		SELECT
			AVG(DATEDIFF(DAY, arcash.invdte, arcash.dtepaid)) AS days_to_pay
		FROM
			" . DB_SCHEMA_ERP . ".arcash
		WHERE
			LTRIM(RTRIM(arcash.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			arcash.dtepaid >= " . $db->quote($past_oneyear) . "
			--AND
			--arcash.dtepaid <  " . $db->quote($past_sixmos) . "
	),
	twoyears AS (
		SELECT
			AVG(DATEDIFF(DAY, arcash.invdte, arcash.dtepaid)) AS days_to_pay
		FROM
			" . DB_SCHEMA_ERP . ".arcash
		WHERE
			LTRIM(RTRIM(arcash.custno)) = " . $db->quote(trim($_POST['custno'])) . "
			AND
			arcash.dtepaid >= " . $db->quote($past_twoyears) . "
			--AND
			--arcash.dtepaid <  " . $db->quote($past_oneyear) . "
	)
	SELECT
		thirty.days_to_pay AS thirty,
		ninety.days_to_pay AS ninety,
		sixmos.days_to_pay AS sixmos,
		oneyear.days_to_pay AS oneyear,
		twoyears.days_to_pay AS twoyears
	FROM
		thirty
	INNER JOIN
		ninety ON 1=1
	INNER JOIN
		sixmos ON 1=1
	INNER JOIN
		oneyear ON 1=1
	INNER JOIN
		twoyears ON 1=1
");
$days_to_pay = $grab_daystopay->fetch();

?>

<h2>Open Accounts Receivable</h2>

<div id="client-invoices-daystopay">
	<h3>Average Days To Pay</h3>
	<table>
		<thead>
			<tr>
				<th>30 Days</th>
				<th>90 Days</th>
				<th>6 Months</th>
				<th>1 Year</th>
				<th>2 Years</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php print $days_to_pay['thirty'] ? $days_to_pay['thirty'] : 'N/A';?></td>
				<td><?php print $days_to_pay['ninety'] ? $days_to_pay['ninety'] : 'N/A';?></td>
				<td><?php print $days_to_pay['sixmos'] ? $days_to_pay['sixmos'] : 'N/A';?></td>
				<td><?php print $days_to_pay['oneyear'] ? $days_to_pay['oneyear'] : 'N/A';?></td>
				<td><?php print $days_to_pay['twoyears'] ? $days_to_pay['twoyears'] : 'N/A';?></td>
			</tr>
		</tbody>
	</table>
</div>

<div id="openar-container" class="sales-container">
	<h3>Totals</h3>
	<table id="openar-table">
		<thead>
			<tr>
				<th></th>
				<th class="right">&lt; 15 Days</th>
				<th class="right">16-30 Days</th>
				<th class="right">31-45 Days</th>
				<th class="right">46-60 Days</th>
				<th class="right">61-75 Days</th>
				<th class="right">76-90 Days</th>
				<th class="right">91-105 Days</th>
				<th class="right">106-120 Days</th>
				<th class="right">121+ Days</th>
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="right">Invoices:</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
			</tr>
			<tr>
				<td class="right">Credit Memos:</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
			</tr>
			<tr>
				<td class="right">Net Amount:</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
				<td class="right">...</td>
			</tr>
		</tbody>
	</table>
</div>

<div id="client-invoices-container" class="sales-container">
	<h3>Open AR</h3>
	<table id="client-invoices-table" class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable" filterable-count-container="#order-count">
		<thead>
			<tr>
				<th class="sortable filterable">Credit Hold</th>
				<th class="sortable filterable">AR VIC</th>
				<th class="sortable filterable">Age</th>
				<th class="sortable filterable">Inv. #</th>
				<th class="sortable filterable">Inv. Date</th>
				<th class="sortable filterable">PO Number</th>
				<th class="sortable filterable">Terms</th>
				<th class="sortable filterable">Balance</th>
				<th class="sortable filterable">Pd. Amt</th>
				<th class="sortable filterable">Orig. Amt</th>
				<th class="sortable filterable">Pay Date</th>
				<!--th class="">Follow Up</th-->
				<th class="sortable filterable">Client Notes</th>
				<th class="sortable filterable">Invoice Notes</th>
				<th class="sortable filterable">Invoice Type</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($invoices as $entry) {
				?>
				<tr class="stripe" invno="<?php print $entry['invoice_number'];?>">
					<td class="content-credit-hold">
						<?php
						if($entry['credit_hold'] == 'H') {
							?>
							<i class="fa fa-fw fa-lock"></i>
							<span class="text">Lock</span>
							<?php
						}
						?>
					</td>
					<td class="content-arvic"><?php print $entry['arvic'] ? 'Yes' : 'No';?></td>
					<td class="content-ag"><?php print number_format($entry['age'], 0);?></td>
					<td class="content-invoicenumber"><?php print trim(htmlentities($entry['invoice_number']));?></td>
					<td class="content-invoicedate"><?php print trim(htmlentities($entry['invoice_date']));?></td>
					<td class="content-ponumber"><?php print trim(htmlentities($entry['po_number']));?></td>
					<td class="content-paymentterms"><?php print trim(htmlentities($entry['payment_terms']));?></td>
					<td class="right content-balance">$<?php print number_format($entry['balance'], 2);?></td>
					<td class="right content-amountpaid">$<?php print number_format($entry['amount_paid'], 2);?></td>
					<td class="right content-originalamount">$<?php print number_format($entry['original_amount'], 2);?></td>
					<td class="content-paydate">
						<!--div class="edit-container">
							<i class="edit-icon fa fa-pencil fa-fw"></i>
						</div-->
						<div class="paydate-container"><?php print trim(htmlentities($entry['pay_date'] === '1900-01-01' ? '' : $entry['pay_date']));?></div>
					</td>
					<!--td class="content-followup"><input type="checkbox" name="followup" value="1" <?php print $entry['follow_up'] ? 'checked="checked"' : '';?> /></td-->
					<td class="center content-clientnotes">
						<i class="fa fa-file-text notes-icon overlayz-link <?php print !empty(trim($entry['client_notes'])) ? 'highlight' : 'no-highlight';?>" overlayz-url="/dashboard/calllist/client-notes" overlayz-data="<?php print htmlentities(json_encode([ 'custno' => $entry['custno'] ]), ENT_QUOTES);?>"></i>
					</td>
					<td class="center content-invoicenotes">
						<i class="fa fa-file-text notes-icon overlayz-link <?php print !empty(trim($entry['invoice_notes'])) ? 'highlight' : 'no-highlight';?>" overlayz-url="/dashboard/calllist/invoice-notes" overlayz-data="<?php print htmlentities(json_encode([ 'custno' => $entry['custno'], 'invno' => $entry['invoice_number'] ]), ENT_QUOTES);?>"></i>
					</td>
					<td class="content-invoicetype"><?php print trim($entry['invoice_type']);?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	
	$(document).ready(function(){

		function get_totals_open_ar(){

			// Populate the "Totals" table.

			// Get all invoice numbers.
			var invoice_numbers = []
			var $tds = $('.content-invoicenumber')
			$.each($tds, function(idx, val){
				var invoice_number = $(val).text()
				invoice_numbers.push(invoice_number)
			})

			// The table containers
			var $container = $('#openar-container');
			var $table = $('#openar-table');
			var $tbody = $table.find('tbody');

			// The data to POST.
			var data = {
				'invoice_numbers' : invoice_numbers
			}

			// Populate the table.
			$.ajax({
				'url' : '/dashboard/ar-widgets/openar',
				'method' : 'POST',
				'dataType' : 'JSON',
				'data' : data,
				'beforeSend' : function(){
					// Make it obvious the table is refreshing.
					$tbody.find('td').text('...');
				},
				'success' : function(rsp){
					console.log('success')
					console.log(rsp)

					// Prevent the table from freaking out and empty it.
					var container_height = $container.outerHeight(true);
					$container.css('min-height', container_height);
					$tbody.empty();

					// Populate the table.
					// Add invioce row.
					var $tr = $('<tr>').appendTo($tbody);
					$('<td>').addClass('right').text('Invoices:').appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.fifteen).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.thirty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.forty_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.sixty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.seventy_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.ninety).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.hundred_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.hundred_twenty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.invoice.hundred_twenty_plus).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.totals.invoice).appendTo($tr);

					// Add credit row.
					var $tr = $('<tr>').appendTo($tbody);
					$('<td>').addClass('right').text('Credit Memos:').appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.fifteen).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.thirty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.forty_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.sixty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.seventy_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.ninety).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.hundred_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.hundred_twenty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.credit.hundred_twenty_plus).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.totals.credit).appendTo($tr);

					// Add net row.
					var $tr = $('<tr>').appendTo($tbody);
					$('<td>').addClass('right').text('Net Amount:').appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.fifteen).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.thirty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.forty_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.sixty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.seventy_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.ninety).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.hundred_five).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.hundred_twenty).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.net.hundred_twenty_plus).appendTo($tr);
					$('<td>').addClass('right').text('$' + rsp.openar.totals.net).appendTo($tr);

				},
				'error' : function(rsp){
					console.log('error')
					console.log(rsp)
				}
			})

		}

		// Populate the totals table.
		get_totals_open_ar()

	})

</script>