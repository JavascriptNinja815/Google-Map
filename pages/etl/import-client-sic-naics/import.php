<?php

$filename = 'pages/etl/import-client-sic-naics/sic-naics.tsv';

$fp = fopen($filename, 'r');
$ct = 0;
print '<pre>';
while(($data = fgetcsv($fp, 10000, "\t")) !== False) {
	$ct++;
	if($ct === 1) {
		continue; // Skip heading row.
	}
	$row = [
		'Source' => $data[0],
		'custno' => $data[1],
		'company' => $data[2],
		'address1' => $data[3],
		'address2' => $data[4],
		'city' => $data[5],
		'addrstate' => $data[6],
		'zip' => $data[7],
		'seqnum1 +' => $data[8],
		'Source' => $data[9],
		'company_NAME' => $data[10],
		'address' => $data[11],
		'city' => $data[12],
		'state' => $data[13],
		'ZIP' => $data[14],
		'zip4' => $data[15],
		'seqnum1' => $data[16],
		'matchkey' => $data[17],
		'Company_Name' => $data[18],
		'First_Name' => $data[19],
		'Last_Name' => $data[20],
		'Standardized_Title' => $data[21],
		'Physical_Address_Standardized' => $data[22],
		'Physical_Address_City' => $data[23],
		'Physical_Address_State' => $data[24],
		'Physical_Address_Zip' => $data[25],
		'Physical_Address_Zip4' => $data[26],
		'Phone' => $data[27],
		'Fax' => $data[28],
		'Email' => $data[29],
		'URL' => $data[30],
		'Primary_SIC' => $data[31],
		'Primary_SIC_Description' => $data[32],
		'Location_Sales' => $data[33],
		'Location_Employee_Size' => $data[34],
		'Years_In_Business' => $data[35],
		'Year_Established' => $data[36],
		'CreditScore' => $data[37],
		'CreditCode' => $data[38],
		'Credit_Description' => $data[39],
		'County_Code' => $data[40],
		'County_Description' => $data[41],
		'CBSA_Code' => $data[42],
		'CBSA_Description' => $data[43],
		'Geo_Match_Level' => $data[44],
		'Latitude' => $data[45],
		'Longitude' => $data[46],
		'TimeZone' => $data[47],
		'PrimarySIC2' => $data[48],
		'Primary_2_Digit_SIC_Description' => $data[49],
		'PrimarySIC4' => $data[50],
		'Primary_4_Digit_SIC_Description' => $data[51],
		'SIC02' => $data[52],
		'SIC02_Description' => $data[53],
		'SIC03' => $data[54],
		'SIC03_Description' => $data[55],
		'NAICS01' => $data[56],
		'NAICS01_Description' => $data[57],
		'NAICS02' => $data[58],
		'NAICS02_Description' => $data[59],
		'NAICS03' => $data[60],
		'NAICS03_Description' => $data[61]
	];
	print $row['custno'] . "\r\n";

	$sics = [];
	if(trim($row['Primary_SIC'])) {
		$sics[] = trim($row['Primary_SIC']);
	}
	if(trim($row['SIC02'])) {
		$sics[] = trim($row['SIC02']);
	}
	if(trim($row['SIC03'])) {
		$sics[] = trim($row['SIC03']);
	}

	$naics = [];
	if(trim($row['NAICS01'])) {
		$naics[] = trim($row['NAICS01']);
	}
	if(trim($row['NAICS02'])) {
		$naics[] = trim($row['NAICS02']);
	}
	if(trim($row['NAICS03'])) {
		$naics[] = trim($row['NAICS03']);
	}

	if(!empty($sics) || !empty($naics)) {
		$sics_set = [];
		if(!empty($sics[0])) {
			$sics_set[] = 'sic = ' . $db->quote($sics[0]);
		}
		if(!empty($sics[1])) {
			$sics_set[] = 'sic2 = ' . $db->quote($sics[1]);
		}
		if(!empty($sics[2])) {
			$sics_set[] = 'sic3 = ' . $db->quote($sics[2]);
		}
		$sics_set = implode(', ', $sics_set);

		$naics_set = [];
		if(!empty($naics[0])) {
			$naics_set[] = 'naics = ' . $db->quote($naics[0]);
		}
		if(!empty($naics[1])) {
			$naics_set[] = 'naics2 = ' . $db->quote($naics[1]);
		}
		if(!empty($naics[2])) {
			$naics_set[] = 'naics3 = ' . $db->quote($naics[2]);
		}
		$naics_set = implode(', ', $naics_set);

		$db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".arcust
			SET
				" . (!empty($sics_set) ? $sics_set : Null) . "
				" . (!empty($sics_set) && !empty($naics_set) ? ',' : Null) . "
				" . (!empty($naics_set) ? $naics_set : Null) . "
			WHERE
				custno = " . $db->quote(trim($row['custno'])) . "
		");
	}
}
print 'DONE!';
print '</pre>';
