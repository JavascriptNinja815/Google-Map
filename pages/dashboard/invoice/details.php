<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_invoice = $db->query("
	SELECT
		armast.notes
	FROM
		" . DB_SCHEMA_ERP . ".armast
	WHERE
		LTRIM(RTRIM(armast.invno)) = " . $db->quote($_POST['invno']) . "
");
$invoice = $grab_invoice->fetch();

$grab_lineitems = $db->query("
	SELECT
		artran.tranlineno AS line_number,
		icitem.comcode AS type,
		artran.item,
		icitem.itmdesc AS part_number,
		artran.cpartno AS client_part_number,
		artran.price,
		artran.extprice,
		artran.cost,
		artran.cost * artran.qtyshp AS extcost,
		artran.qtyord AS ordered_qty,
		artran.qtyshp AS shipped_qty,
		artran.qtyord - artran.qtyshp AS open_qty,
		artran.loctid AS location,
		(
			CASE WHEN (artran.qtyshp * artran.price) > 0.00 THEN
				(
					(artran.qtyshp * artran.price) - (artran.qtyshp  * artran.cost)
				) / (artran.qtyshp * artran.price)
			ELSE
				0.00
			END
		) AS margin
	FROM
		" . DB_SCHEMA_ERP . ".armast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".artran
		ON
		artran.invno = armast.invno
	LEFT JOIN
		" . DB_SCHEMA_ERP . ".icitem
		ON
		icitem.item = artran.item
	WHERE
		LTRIM(RTRIM(armast.invno)) = " . $db->quote($_POST['invno']) . "
	ORDER BY
		artran.tranlineno
");

ob_start(); // Start loading output into buffer.
?>

<style type="text/css">
	#overall-margin-container {
		position: absolute;
		top: 15px;
		right: 80px;
		font-size: 60px;
		font-weight: bold;
		line-height: 60px;
	}
</style>

<h2 class="padded">Invoice #<?php print htmlentities($_POST['invno']);?></h2>

<i class="bad">Notice: Due to freight calculations and potentially other small parts, these numbers may not perfectly reflect actual margins. This is a work in progress.</i>

<h3>Line Items</h3>
<table>
	<thead>
		<tr>
			<th>Line Number</th>
			<th>Type</th>
			<th>Item</th>
			<th>Part Number</th>
			<th>Client Number</th>
			<th>Price</th>
			<th>Ext Price</th>
			<th>Cost</th>
			<th>Ext Cost</th>
			<th>Margin</th>
			<th>Ordered Qty</th>
			<th>Shipped Qty</th>
			<th>Open Qty</th>
			<th>Location</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$total_price_withoutshipping = 0.00;
		$total_cost_withoutshipping = 0.00;
		$total_price_withshipping = 0.00;
		$total_cost_withshipping = 0.00;
		foreach($grab_lineitems as $lineitem) {
			$margin = $lineitem['margin'] * 100;
			$total_price_withshipping += (float)$lineitem['extprice'];
			$total_cost_withshipping += (float)$lineitem['extcost'];
			if(!in_array(trim($lineitem['item']), ['FRT', 'SHIP'])) {
				$total_price_withoutshipping += (float)$lineitem['extprice'];
				$total_cost_withoutshipping += (float)$lineitem['extcost'];
			}
			?>
			<tr>
				<td><?php print htmlentities(trim($lineitem['line_number']));?></td>
				<td><?php print htmlentities(trim($lineitem['type']));?></td>
				<td><?php print htmlentities(trim($lineitem['item']));?></td>
				<td><?php print htmlentities(trim($lineitem['part_number']));?></td>
				<td><?php print htmlentities(trim($lineitem['client_part_number']));?></td>
				<td>$<?php print number_format($lineitem['price'], 2);?></td>
				<td>$<?php print number_format($lineitem['extprice'], 2);?></td>
				<td>$<?php print number_format($lineitem['cost'], 2);?></td>
				<td>$<?php print number_format($lineitem['extcost'], 2);?></td>
				<td><?php print number_format($margin, 1);?>%</td>
				<td><?php print number_format($lineitem['ordered_qty'], 0);?></td>
				<td><?php print number_format($lineitem['shipped_qty'], 0);?></td>
				<td><?php print number_format($lineitem['open_qty'], 0);?></td>
				<td><?php print htmlentities(trim($lineitem['location']));?></td>
			</tr>
			<?php
		}
		$overall_margin = 0.00;
		if($total_price_withoutshipping > 0.00) {
			$overall_margin = 100 - ((100 / $total_price_withoutshipping) * $total_cost_withoutshipping);
		}
		$overall_margin_withshipping = 0.00;
		if($total_price_withshipping > 0.00) {
			$overall_margin_withshipping = 100 - ((100 / $total_price_withshipping) * $total_cost_withshipping);
		}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td class="right" colspan="9">Margin not including shipping:</td>
			<td colspan="5"><?php print number_format($overall_margin, 1);?>%</td>
		</tr>
		<tr>
			<td class="right" colspan="9">Margin including shipping:</td>
			<td colspan="5"><?php print number_format($overall_margin_withshipping, 1);?>%</td>
		</tr>
	</tfoot>
</table>

<div id="overall-margin-container"><?php print number_format($overall_margin_withshipping, 1);?>%</div>

<div class="invoice-notes-wrapper">
	<h3>Invoice Notes</h3>
	<div class="notes invoice-notes-container">
		<?php
		$order_notes = trim($invoice['notes']);
		if($order_notes) {
			foreach(explode("\n", $order_notes) as $note_part) {
				$note_part = trim(str_replace("\r", '', $note_part));
				if(!empty($note_part)) {
					?><div class="order-notes-row"><?php print htmlentities($note_part);?></div><?php
				}
			}
		} else {
			?>None<?php
		}
		?>
	</div>
	<!--div class="order-notes-new-container input-append input-block-level">
		<input type="text" class="order-notes-new-input">
		<button type="button" class="order-notes-new-button btn">Add</button>
	</div-->
</div>

<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
