<?php

set_time_limit(0);

// Imports NAICS codes.
$code_type = 'NAICS';
$filename = 'pages/etl/import-naics/naics.tsv';

function code_exists($db, $code_type, $code_long) {
	$grab_exist_ct = $db->query("
		SELECT
			COUNT(*) AS existing
		FROM
			" . DB_SCHEMA_INTERNAL . ".industry_codes
		WHERE
			industry_codes.type = " . $db->quote($code_type) . "
			AND
			industry_codes.code_long = " . $db->quote($code_long) . "
	");
	$exist_ct = $grab_exist_ct->fetch();
	return $exist_ct['existing'] > 0;
}

function insert_code($db, $code_type, $code, $code_long, $description) {
	$db->query("
		INSERT INTO
			" . DB_SCHEMA_INTERNAL . ".industry_codes
		(
			type,
			code,
			code_long,
			description
		) VALUES (
			" . $db->quote($code_type) . ",
			" . $db->quote($code) . ",
			" . $db->quote($code_long) . ",
			" . $db->quote($description) . "
		)
	");
}

$fp = fopen($filename, 'r');
$ct = 0;
print '<pre>';
while(($data = fgetcsv($fp, 10000, "\t")) !== False) {
	$ct++;
	if($ct === 1) {
		continue; // Skip heading row.
	}
	$row = [
		'code' => substr($data[0], 0, 6),
		'code_long' => $data[0],
		'description' => $data[1]
	];
	print '#' . $ct . ' -- ' . $row['code'] . "\r\n";
	if(!code_exists($db, $code_type, $row['code_long'])) {
		print "\tNew code, inserting.\r\n";
		insert_code($db, $code_type, $row['code'], $row['code_long'], $row['description']);
	} else {
		print "\tExisting code, skipping.\r\n";
	}
}
print '</pre>';
