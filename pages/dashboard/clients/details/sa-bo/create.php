<?php

$session->ensureLogin();
$session->ensureRole('Sales');

// Insert the SA/BO into the DB.
$grab_sabo = $db->query("
	SET NOCOUNT ON;
	INSERT INTO
		" . DB_SCHEMA_ERP . ".sabo
	(
		custno,
		type,
		addate,
		startdate,
		enddate,
		notes,
		cdab1,
		cdab2,
		custsig,
		custsigtit,
		adduser
	) VALUES (
		" . $db->quote($_POST['custno']) . ",
		" . $db->quote($_POST['type']) . ",
		GETDATE(),
		" . $db->quote($_POST['startdate']) . ",
		" . $db->quote($_POST['enddate']) . ",
		" . $db->quote($_POST['notes']) . ",
		" . $db->quote($_POST['cdab1']) . ",
		" . $db->quote($_POST['cdab2']) . ",
		" . $db->quote($_POST['custsig']) . ",
		" . $db->quote($_POST['custsigtit']) . ",
		" . $db->quote($session->login['initials']) . "
	);
	SELECT SCOPE_IDENTITY() AS saboid;
");
$sabo = $grab_sabo->fetch();

// Insert SA/BO item entries.
if(!empty($_POST['items'])) {
	$items = [];
	foreach($_POST['items']['item'] as $offset => $value) {
		// Skip prototype hidden item fields.
		if($offset === 0) {
			continue;
		}
		$items[] = [
			'ponum' => $_POST['items']['ponum'][$offset],
			'item' => $_POST['items']['item'][$offset],
			'vendno' => $_POST['items']['vendno'][$offset],
			'vpartno' => $_POST['items']['vpartno'][$offset],
			'stkumid' => $_POST['items']['stkumid'][$offset],
			'qty' => $_POST['items']['qty'][$offset],
			'min' => $_POST['items']['min'][$offset],
			'max' => $_POST['items']['max'][$offset],
			'dayss' => $_POST['items']['dayss'][$offset],
			'price' => $_POST['items']['price'][$offset],
			'monthly' => $_POST['items']['monthly'][$offset],
			'annual' => $_POST['items']['annual'][$offset],
			'loctid' => $_POST['items']['loctid'][$offset],
			'notes' => $_POST['items']['notes'][$offset]
		];
	}
	foreach($items as $item) {
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".saboitm
			(
				saboid,
				item,
				vendno,
				vpartno,
				stkumid,
				qty,
				min,
				max,
				dayss,
				notes,
				price,
				monthly,
				annual,
				ponum,
				loctid,
				adduser,
				addate
			) VALUES (
				" . $db->quote($sabo['saboid']) . ",
				" . $db->quote($item['item']) . ",
				" . $db->quote($item['vendno']) . ",
				" . $db->quote($item['vpartno']) . ",
				" . $db->quote($item['stkumid']) . ",
				" . $db->quote($item['qty'] ? $item['qty'] : 0) . ",
				" . $db->quote($item['min'] ? $item['min'] : 0) . ",
				" . $db->quote($item['max'] ? $item['max'] : 0) . ",
				" . $db->quote($item['dayss'] ? $item['dayss'] : 0) . ",
				" . $db->quote($item['notes']) . ",
				" . $db->quote($item['price'] ? $item['price'] : 0) . ",
				" . $db->quote($item['monthly'] ? $item['monthly'] : 0) . ",
				" . $db->quote($item['annual'] ? $item['annual'] : 0) . ",
				" . $db->quote($item['ponum']) . ",
				" . $db->quote($item['loctid']) . ",
				" . $db->quote($session->login['initials']) . ",
				GETDATE()
			)
		");
	}
}

print json_encode([
	'success' => True,
	'saboid' => $sabo['saboid']
]);
