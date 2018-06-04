<?php

$session->ensureLogin();

ob_start(); // Start loading output into buffer.

$grab_pricing = $db->query("
	SELECT
		price_items.brand,
		price_items.part_number,
		price_items.description,
		price_quantities.price,
		price_quantities.quantity,
		price_quantities.effective_date,
		price_quantities.expires_date
	FROM
		" . DB_SCHEMA_ERP . ".price_items
	INNER JOIN
		" . DB_SCHEMA_ERP . ".price_quantities
		ON
		price_quantities.price_item_id = price_items.price_item_id
	WHERE
		price_items.item = " . $db->quote($_POST['item-number']) . "
	ORDER BY
		price_items.part_number ASC,
		price_quantities.quantity ASC,
		price_quantities.price ASC
");

?>
<table>
	<thead>
		<tr>
			<th>Brand</th>
			<th>Part Number</th>
			<th>Description</th>
			<th>Price</th>
			<th>Quantity</th>
			<th>Effective Date</th>
			<th>Expires Date</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($grab_pricing as $price) {
			?>
			<tr>
				<td><?php print htmlentities($price['brand']);?></td>
				<td><?php print htmlentities($price['part_number']);?></td>
				<td><?php print htmlentities($price['description']);?></td>
				<td>$<?php print number_format($price['price'], 2);?></td>
				<td><?php print number_format($price['quantity'], 0);?></td>
				<td><?php print htmlentities($price['effective_date']);?></td>
				<td><?php print htmlentities($price['expires_date']);?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);