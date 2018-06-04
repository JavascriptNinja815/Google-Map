<?php

$session->ensureLogin();

ob_start(); // Start loading output into buffer.

$grab_whereused = $db->query("
	SELECT
		LTRIM(RTRIM(pebmit.fitem)) AS fitem,
		LTRIM(RTRIM(icitem.itmdesc)) AS itmdesc
	FROM
		" . DB_SCHEMA_ERP . ".pebmdt
	INNER JOIN
		" . DB_SCHEMA_ERP . ".pebmit
		ON
		pebmit.bomno = pebmdt.bomno
	INNER JOIN
		" . DB_SCHEMA_ERP . ".icitem
		ON
		icitem.item = pebmit.fitem
	WHERE
		pebmdt.item = " . $db->quote($_REQUEST['item-number']) . "
");

?>
<div id="item-whereused-body">
	<table>
		<thead>
			<tr>
				<th>Item Code</th>
				<th>Part #</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($grab_whereused as $whereused) {
				?>
				<tr>
					<td class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/inventory/item-details" overlayz-data="<?php print htmlentities(json_encode(['item-number' => $whereused['fitem']], ENT_QUOTES));?>"><?php print htmlentities($whereused['fitem']);?></td>
					<td><?php print htmlentities($whereused['itmdesc']);?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
<?php

$html = ob_get_contents(); // Load buffer into accessible var.
ob_end_clean(); // Clear the buffer.

print json_encode([
	'success' => True,
	'html' => $html
]);