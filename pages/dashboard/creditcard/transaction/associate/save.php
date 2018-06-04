<?php

function calculateChanges($db, $subject) {
	//print($subject);
	//print "\r\n<br>\r\n";
	// Grab the IDs to ensure exist.
	$ids_present = [];
	if(!empty($_POST[$subject . 's'])) {
		foreach($_POST[$subject . 's'] as $offset => $id) {
			$id = trim($id);
			if(strlen($id)) {
				$ids_present[(int)$id] = (float)($_POST[$subject . 's_amounts'][(int)$offset]);
			}
		}
	}

	// Grab existing IDs in teh database.
	$ids_indb = [];
	$grab_ids_indb = $db->query("
		SELECT
			authorize_transaction_relations.relation_type_id AS id
		FROM
			" . DB_SCHEMA_ERP . ".authorize_transaction_relations
		WHERE
			authorize_transaction_relations.transaction_id = " . $db->quote($_POST['transaction_id']) . "
			AND
			authorize_transaction_relations.relation_type = " . $db->quote($subject) . "
	");
	foreach($grab_ids_indb as $id_indb) {
		$id_indb = trim($id_indb['id']);
		if(strlen($id_indb)) {
			$ids_indb[] = $id_indb;
		}
	}

	$ids_toadd = array_diff(
		array_keys($ids_present),
		$ids_indb
	);
	$ids_todelete = array_diff(
		$ids_indb,
		array_keys($ids_present)
	);
	$ids_toupdate = array_diff(
		array_keys($ids_present),
		$ids_toadd,
		$ids_todelete
	);

	//print 'PRESENT: ';
	//print_r($ids_present);
	//print "\r\n<br>\r\n";
	//print 'IN DB: ';
	//print_r($ids_indb);
	//print "\r\n<br>\r\n";
	//print 'TO ADD: ';
	//print_r($ids_toadd);
	//print "\r\n<br>\r\n";
	//print 'TO DELETE: ';
	//print_r($ids_todelete);
	//print "\r\n<br>\r\n";
	//print 'TO UPDATE: ';
	//print_r($ids_toupdate);

	// Insert IDs into DB.
	foreach($ids_toadd as $id_toadd) {
		$amount = $ids_present[$id_toadd];
		//print "\r\n";
		//print 'INSERT: ' . $id_toadd . ' :: ' . $amount;
		//print "\r\n";
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".authorize_transaction_relations
			(
				transaction_id,
				relation_type,
				relation_type_id,
				amount
			) VALUES (
				" . $db->quote($_POST['transaction_id']) . ",
				" . $db->quote($subject) . ",
				" . $db->quote($id_toadd) . ",
				" . $db->quote($amount) . "
			)
		");
	}

	// Delete IDs from DB.
	foreach($ids_todelete as $id_todelete) {
		$db->query("
			DELETE FROM
				" . DB_SCHEMA_ERP . ".authorize_transaction_relations
			WHERE
				transaction_id = " . $db->quote($_POST['transaction_id']) . "
				AND
				relation_type = " . $db->quote($subject) . "
				AND
				relation_type_id = " . $db->quote($id_todelete) . "
		");
	}

	// Update IDs in DB.
	foreach($ids_toupdate as $id_toupdate) {
		$amount = $ids_present[$id_toupdate];
		//print "\r\n";
		//print 'UPDATE: ' . $id_toupdate . ' :: ' . $amount;
		//print "\r\n";
		$db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".authorize_transaction_relations
			SET
				amount = " . $db->quote($amount) . "
			WHERE
				transaction_id = " . $db->quote($_POST['transaction_id']) . "
				AND
				relation_type = " . $db->quote($subject) . "
				AND
				relation_type_id = " . $db->quote($id_toupdate) . "
		");
	}
	return $ids_present;
}

$invnos = calculateChanges($db, 'invno');
$sonos =  calculateChanges($db, 'sono');

print json_encode([
	'success' => True,
	'sonos' => $sonos,
	'invnos' => $invnos
]);
