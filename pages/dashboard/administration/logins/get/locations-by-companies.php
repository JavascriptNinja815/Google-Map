<?php

$session->ensureLogin();
$session->ensureRole('Administration');

$response = [];

if(!empty($_POST['company_ids'])) {
	$company_ids = '';
	foreach($_POST['company_ids'] as $company_id) {
		if(!empty($company_ids)) {
			$company_ids .= ', ';
		}
		$company_ids .= $db->quote($company_id);
	}

	$grab_companies = $db->prepare("
		SELECT
			companies.company,
			companies.dbname,
			companies.company_id
		FROM
			" . DB_SCHEMA_INTERNAL . ".companies
		WHERE
			companies.company_id IN (" . $company_ids . ")
		ORDER BY
			companies.company
	");
	$grab_companies->execute();
	if($grab_companies->rowCount()) {
		foreach($grab_companies as $company) {
			$company_response = [];
			// Grab distinct list of locations for the companying being iterated over.
			$grab_locations = $db->query("
				SELECT
					somast.defloc
				FROM
					" . $company['dbname'] . ".somast
				GROUP BY
					somast.defloc
				ORDER BY
					somast.defloc
			");
			foreach($grab_locations as $location) {
				$company_response[] = trim($location['defloc']);
			}
			$response[$company['company']] = [
				'company_id' => $company['company_id'],
				'locations' => $company_response
			];
		}
	}
}

print json_encode([
	'success' => True,
	'locations' => $response
]);
