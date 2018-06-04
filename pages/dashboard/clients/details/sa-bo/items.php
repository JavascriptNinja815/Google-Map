<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$grab_sabo_items = $db->query("
	SELECT
		saboitm.entryid,
		saboitm.item,
		saboitm.vendno,
		saboitm.vpartno,
		saboitm.stkumid,
		saboitm.qty,
		saboitm.min,
		saboitm.max,
		saboitm.dayss,
		saboitm.notes,
		saboitm.price,
		saboitm.monthly,
		saboitm.annual,
		saboitm.ponum,
		saboitm.loctid,
		saboitm.adduser,
		saboitm.addate
	FROM
		" . DB_SCHEMA_ERP . ".saboitm
	WHERE
		saboitm.saboid = " . $db->quote($_POST['saboid']) . "
	ORDER BY
		saboitm.item
");

ob_start(); // Start loading output into buffer.
?>
<table>
	<thead>
		<tr>
			<th>Item</th>
			<th>Vendor</th>
			<th>Vendor P/N</th>
			<th>?stkumid?</th>
			<th>Qty</th>
			<th>Min</th>
			<th>Max</th>
			<th>?dayss?</th>
			<th>Notes</th>
			<th>Price</th>
			<th>Monthly</th>
			<th>Annual</th>
			<th>Customer PO</th>
			<th>Location</th>
			<th>Added By</th>
			<th>Added On</th>
		</tr>
	</thead>
	<tbody class="sabo-items">
		<tr>
			<?php
			foreach($grab_sabo_items as $sabo_item) {
				?>
				<tr class="sabo-item">
					<th><?php print htmlentities($sabo_item['item']);?></th>
					<th><?php print htmlentities($sabo_item['vendno']);?></th>
					<th><?php print htmlentities($sabo_item['vpartno']);?></th>
					<th><?php print htmlentities($sabo_item['stkumid']);?></th>
					<th><?php print htmlentities($sabo_item['qty']);?></th>
					<th><?php print htmlentities($sabo_item['min']);?></th>
					<th><?php print htmlentities($sabo_item['max']);?></th>
					<th><?php print htmlentities($sabo_item['dayss']);?></th>
					<th><?php print htmlentities($sabo_item['notes']);?></th>
					<th><?php print htmlentities($sabo_item['price']);?></th>
					<th><?php print htmlentities($sabo_item['monthly']);?></th>
					<th><?php print htmlentities($sabo_item['annual']);?></th>
					<th><?php print htmlentities($sabo_item['ponum']);?></th>
					<th><?php print htmlentities($sabo_item['loctid']);?></th>
					<th><?php print htmlentities($sabo_item['addate']);?></th>
					<th><?php print htmlentities($sabo_item['adduser']);?></th>
				</tr>
				<?php
			}
			?>
		</tr>
	</tbody>
</table>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);
