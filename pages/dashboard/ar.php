<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2015, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */
ini_set('max_execution_time', 300);
$session->ensureLogin();

$args = array(
	'title' => 'AR Dashboard',
	'breadcrumbs' => array(
		'AR Dashboard' => BASE_URI . '/dashboard/ar'
	)
);

$calllist = $db->prepare("
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
		--armast.arnotes AS client_notes,
		--armast.arnotes AS client_notes,
		CAST(arcust.arnotes AS character varying) AS client_notes,
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
		armast.balance != 0
	ORDER BY
		armast.invno
", array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$calllist->execute();

Template::Render('header', $args, 'account');
?>
<div class="padded dashboard">
	<h3>
		Open AR
		<span class="datetime" id="openar-datetime"></span>
	</h3>
	<div id="openar-container" class="sales-container">
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
	<br />

	<fieldset>
		<legend>
			<div class="padded-x padded-y calllist-title">
				Call List (<span id="order-count"><?php print number_format($calllist->rowCount());?></span>)
				<div class="additional-filters">
					<div class="filter">
						<label>
							<input type="radio" name="arvic" value="non-arvic" checked="checked" /> Non AR VIC
						</label>
					</div>
					<div class="filter">
						<label>
							<input type="radio" name="arvic" value="arvic" /> AR VIC
						</label>
					</div>
					<div class="filter">
						<label>
							<input type="radio" name="arvic" value="both" /> Show All
						</label>
					</div>
				</div>
			</div>
		</legend>
	</fieldset>
	<div id="calllist-container" class="sales-container">
		<table id="calllist-table" class="table table-small table-striped table-hover columns-sortable columns-filterable headers-sticky columns-draggable" filterable-count-container="#order-count">
			<thead>
				<tr>
					<th class="sortable filterable">Credit Hold</th>
					<th class="sortable filterable">AR VIC</th>
					<th class="sortable filterable">Age</th>
					<th class="sortable filterable">Client</th>
					<th class="sortable filterable">Inv. #</th>
					<th class="sortable filterable">Inv. Date</th>
					<th class="sortable filterable">PO Number</th>
					<th class="sortable filterable">Terms</th>
					<th class="sortable filterable">Balance</th>
					<th class="sortable filterable">Pd. Amt</th>
					<th class="sortable filterable">Orig. Amt</th>
					<th class="sortable filterable">Pay Date</th>
					<th class="">Follow Up</th>
					<th class="sortable filterable">Client Notes</th>
					<th class="sortable filterable">Invoice Notes</th>
					<th class="sortable filterable">Invoice Type</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($calllist as $entry) {
					?>
					<tr class="stripe <?php print $entry['arvic'] ? 'arvic' : 'non-arvic';?> <?php $entry['credit_hold'] == 'H' ? print 'on-credit-hold' : Null;?>" <?php print $entry['arvic'] ? 'style="display:none;"' : '';?> invno="<?php print $entry['invoice_number'];?>">
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
						<td class="right content-arvic"><?php print $entry['arvic'] ? 'Yes' : 'No';?></td>
						<td class="right content-ag"><?php print number_format($entry['age'], 0);?></td>
						<td class="right content-client overlayz-link" overlayz-url="/dashboard/clients/details" overlayz-data="<?php print htmlentities(json_encode(['custno' => $entry['custno']]), ENT_QUOTES);?>"><?php print trim(htmlentities($entry['custno']));?></td>
						<td class="right content-invoicenumber"><?php print trim(htmlentities($entry['invoice_number']));?></td>
						<td class="right content-invoicedate"><?php print trim(htmlentities($entry['invoice_date']));?></td>
						<td class="right content-ponumber"><?php print trim(htmlentities($entry['po_number']));?></td>
						<td class="right content-paymentterms"><?php print trim(htmlentities($entry['payment_terms']));?></td>
						<td class="right content-balance">$<?php print number_format($entry['balance'], 2);?></td>
						<td class="right content-amountpaid">$<?php print number_format($entry['amount_paid'], 2);?></td>
						<td class="right content-originalamount">$<?php print number_format($entry['original_amount'], 2);?></td>
						<td class="right content-paydate">
							<div class="edit-container">
								<i class="edit-icon fa fa-pencil fa-fw"></i>
							</div>
							<div class="paydate-container"><?php print trim(htmlentities($entry['pay_date'] === '1900-01-01' ? '' : $entry['pay_date']));?></div>
						</td>
						<td class="center content-followup"><input type="checkbox" name="followup" value="1" <?php print $entry['follow_up'] ? 'checked="checked"' : '';?> /></td>
						<td class="center content-clientnotes">
							<i class="fa fa-file-text notes-icon overlayz-link <?php print !empty(trim($entry['client_notes'])) ? 'highlight' : 'no-highlight';?>" overlayz-url="/dashboard/clients/notes" overlayz-data="<?php print htmlentities(json_encode([ 'type' => 'client-notes', 'custno' => $entry['custno'] ]), ENT_QUOTES);?>"></i>
						</td>
						<td class="center content-invoicenotes">
							<i class="fa fa-file-text notes-icon overlayz-link <?php print !empty(trim($entry['invoice_notes'])) ? 'highlight' : 'no-highlight';?>" overlayz-url="/dashboard/clients/notes" overlayz-data="<?php print htmlentities(json_encode([ 'type' => 'invoice-notes', 'custno' => $entry['custno'], 'invno' => $entry['invoice_number'] ]), ENT_QUOTES);?>"></i>
						</td>
						<td class="right content-invoicetype"><?php print trim($entry['invoice_type']);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
</div>
<?php Template::Render('footer', 'account');
