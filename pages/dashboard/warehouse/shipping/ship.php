<?php

// let this page run for more than 30 seconds.
set_time_limit(90);

// Find how many days have gone by without a file being edited.
function get_file_days($path){

	// Get the age of a file in days.
	$age = time()-filemtime($path);
	return $age/60/60/24;

};

function remove_old_logs(){

	// Get the files in the log dierctory.
	$path = dirname(__FILE__).DIRECTORY_SEPARATOR."logs".DIRECTORY_SEPARATOR;
	$contents = scandir($path);

	// Remove the old files.
	foreach($contents as $name){

		$full_path = $path.$name;
		$days = get_file_days($full_path);

		if($days>30 && is_file($full_path)){
			// Actually remove the file.
			unlink($full_path);
		};
	};

};

// Get the path to the log file.
function get_logpath(){

	// Create the filename with today's date.
	$d = date("j.n.Y");
	$p = dirname(__FILE__).DIRECTORY_SEPARATOR;
	$f = $p."logs".DIRECTORY_SEPARATOR."log_".$d.".txt";

	return $f;
};

// Enable logging.
function logdata($data){

	// Log `$data` to file - name file with today's date.
	$logpath = get_logpath();
	file_put_contents($logpath, $data, FILE_APPEND);

	// Remove any old logs.
	remove_old_logs();

};

function get_location_id($printer_id){

	$db = DB::get();
	$q = $db->query("
		SELECT location_id
		FROM Neuron.dbo.printers
		WHERE printer_id = ".$db->quote($printer_id)."
	");

	$r = $q->fetch();
	return $r['location_id'];

}

// Grab Intelliship Credentials from DB.
if(substr($_SERVER['HTTP_HOST'], 0, strlen('dev.')) == 'dev.') {
	// DEV
	$live = 0;
	$api_name = 'Intelliship Dev';
	$label_network_path = '\\\\glcad1\\shipping-labels-dev';
} else {
	// LIVE
	$live = 1;
	$api_name = 'Intelliship Live';
	$label_network_path = '\\\\glcad1\\shipping-labels-live';
}

function get_credentials($api_name, $location_id){

	$db = DB::get();

	// Always get dev credentials for dev.
	if($api_name == 'Intelliship Dev'){
		$r = $db->query("
			SELECT
				apis.credentials
			FROM
				" . DB_SCHEMA_INTERNAL . ".apis
			WHERE apis.name = " . $db->quote($api_name) . "
		");
	}else {
		$r = $db->query("
			SELECT
				apis.credentials
			FROM
				" . DB_SCHEMA_INTERNAL . ".apis
			WHERE apis.name = " . $db->quote($api_name) . "
				AND CAST(apis.location_id AS varchar) = ".$db->quote($location_id)."
		");
	}

	return $r->fetch();

}

function get_shiptment_total($shipment_id){

	// Get the total shipping cost.

	$db = DB::get();
	// $q = $db->query("
	// 	SELECT DISTINCT
	// 		'S-H' AS item,
	// 		t.sono,
	// 		SUM(s.cost) AS price,
	// 		CASE WHEN MAX(t.transeq) < 9000
	// 			THEN 9000
	// 		ELSE MAX(t.transeq)+1
	// 		END AS transeq
	// 	FROM ".DB_SCHEMA_ERP.".shipments s
	// 	INNER JOIN ".DB_SCHEMA_ERP.".shipment_packages p
	// 		ON p.shipment_id = s.shipment_id
	// 	INNER JOIN ".DB_SCHEMA_ERP.".sotran t
	// 		ON LTRIM(RTRIM(t.sono)) = LTRIM(RTRIM(s.sono))
	// 	WHERE s.shipment_id = ".$db->quote($shipment_id)."
	// 	GROUP BY t.sono
	// ");

	$q = $db->query("

		DECLARE @now DATETIME
		DECLARE @h VARCHAR(2)
		DECLARE @m VARCHAR(2)
		DECLARE @s VARCHAR(2)
		DECLARE @t VARCHAR(8)

		SET @now = GETDATE()
		SET @h = DATEPART(HOUR, @now)
		SET @m = DATEPART(MINUTE, @now)
		SET @s = DATEPART(SECOND, @now)
		SET @t = @h+':'+@m+':'+@s

		SELECT DISTINCT
			'S-H' AS item,
			t.sono,
			1000 AS transeq,
			SUM(s.cost) AS price,
			/*
			CASE WHEN MAX(t.transeq) < 9000
				THEN 9000
			ELSE MAX(t.transeq)+1
			END AS transeq,
			--*/
			
			t.custno,
			1 AS qtyord,
			t.terr,
			t.salesmn,
			t.loctid,
			1 AS umfact,
			'EA' AS umeasur,
			'MAVE' AS adduser,
			CAST(GETDATE() AS DATE) AS adddate,
			@t addtime,
			'USD' AS currid,

			t.ordate,
			t.rqdate
			
		FROM ".DB_SCHEMA_ERP.".shipments s
		INNER JOIN ".DB_SCHEMA_ERP.".shipment_packages p
			ON p.shipment_id = s.shipment_id
		INNER JOIN ".DB_SCHEMA_ERP.".sotran t
			ON LTRIM(RTRIM(t.sono)) = LTRIM(RTRIM(s.sono))
		WHERE s.shipment_id = ".$db->quote($shipment_id)."
		GROUP BY t.sono,
			t.custno,
			qtyord,
			t.terr,
			t.salesmn,
			t.loctid,
			umfact,
			umeasur,
			adduser,
			adddate,
			addtime,
			currid,
			t.ordate,
			t.rqdate
	");

	return $q->fetch();

}

function get_shipment_tracking($shipment_id){

	// Get the tracking numbers for the shipment's packages.

	$db = DB::get();
	$q = $db->query("
		SELECT
			'PKG'+ CAST(
				ROW_NUMBER() OVER (ORDER BY tracking)
				AS VARCHAR(2)
			) AS item,
			tracking,
			1000 + ROW_NUMBER() OVER (ORDER BY tracking) AS transeq
		FROM ".DB_SCHEMA_ERP.".shipment_packages
		WHERE shipment_id = ".$db->quote($shipment_id).";
	");

	return $q->fetchAll();

}

function set_shipment_total($sono, $price, $transeq, $terr, $salesmn, $loctid, $umfact, $umeasur, $adduser, $adddate, $addtime, $currid, $custno, $qtyord, $ordate, $rqdate){

	// Upsert the total shipping.

	$db = DB::get();
	$q = $db->query("
		INSERT INTO ".DB_SCHEMA_ERP.".sotran (
			sono,
			custno,
			item,
			price,
			transeq,
			terr,
			salesmn,
			loctid,
			umfact,
			umeasur,
			adduser,
			adddate,
			addtime,
			currid,
			qtyord,

			ordate,
			rqdate,

			exchrat
		)
		SELECT ".$db->quote($sono).",
			".$db->quote($custno).",
			'S-H',
			".$db->quote($price).",
			".$db->quote($transeq).",
			".$db->quote($terr).",
			".$db->quote($salesmn).",
			".$db->quote($loctid).",
			".$db->quote($umfact).",
			".$db->quote($umeasur).",
			".$db->quote($adduser).",
			".$db->quote($adddate).",
			".$db->quote($addtime).",
			".$db->quote($currid).",
			".$db->quote($qtyord).",

			".$db->quote($ordate).",
			".$db->quote($rqdate).",
			1
		WHERE NOT EXISTS (
			SELECT 1
			FROM ".DB_SCHEMA_ERP.".sotran
			WHERE LTRIM(RTRIM(sono)) = ".$db->quote($sono)."
				AND item = 'S-H'
				AND transeq = ".$db->quote($transeq)."
		);
		UPDATE ".DB_SCHEMA_ERP.".sotran
		SET price = ".$db->quote($price)."
		WHERE LTRIM(RTRIM(sono)) = ".$db->quote($sono)."
			AND item = 'S-H'
	");

}

function do_set_shipment_tracking($sono, $item, $tracking, $terr, $salesmn, $loctid, $umfact, $umeasur, $adduser, $adddate, $addtime, $currid, $custno, $qtyord, $ordate, $rqdate, $transeq){

	// Do the actual tracking upsert.

	$db = DB::get();
	$q = $db->query("
		INSERT INTO ".DB_SCHEMA_ERP.".sotran (
			sono,
			custno,
			item,
			descrip,
			terr,
			salesmn,
			loctid,
			umfact,
			umeasur,
			adduser,
			adddate,
			addtime,
			currid,
			qtyord,

			ordate,
			rqdate,
			exchrat,

			transeq
		)
		SELECT
			".$db->quote($sono).",
			".$db->quote($custno).",
			".$db->quote($item).",
			".$db->quote($tracking).",
			".$db->quote($terr).",
			".$db->quote($salesmn).",
			".$db->quote($loctid).",
			".$db->quote($umfact).",
			".$db->quote($umeasur).",
			".$db->quote($adduser).",
			".$db->quote($adddate).",
			".$db->quote($addtime).",
			".$db->quote($currid).",
			".$db->quote($qtyord).",

			".$db->quote($ordate).",
			".$db->quote($rqdate).",
			1,
			".$db->quote($transeq)."
		WHERE NOT EXISTS (
			SELECT 1
			FROM ".DB_SCHEMA_ERP.".sotran
			WHERE sono = ".$db->quote($sono)."
				AND item = ".$db->quote($item)."
		)
	");

}

function set_shipment_tracking($sono, $tracking, $terr, $salesmn, $loctid, $umfact, $umeasur, $adduser, $adddate, $addtime, $currid, $custno, $qtyord, $ordate, $rqdate){

	// Set the tracking info for the shipment.

	foreach($tracking AS $row){

		$item = $row['item'];
		$trck = $row['tracking'];
		$transeq = $row['transeq'];

		// Do the upsert.
		do_set_shipment_tracking($sono, $item, $trck, $terr, $salesmn, $loctid, $umfact, $umeasur, $adduser, $adddate, $addtime, $currid, $custno, $qtyord, $ordate, $rqdate, $transeq);

	}

}

function record_in_pro($shipment_id){

	// Create the shipping-and-handling and package rows in PRO for the
	// shipment.


	// Get the tracking and total.
	$total = get_shiptment_total($shipment_id);
	$tracking = get_shipment_tracking($shipment_id);

	// Get the required values.
	$item = $total['item'];
	$sono = $total['sono'];
	$price = $total['price'];
	$transeq = $total['transeq'];

	$custno = $total['custno'];
	$qtyord = $total['qtyord'];
	$terr = $total['terr'];
	$salesmn = $total['salesmn'];
	$loctid = $total['loctid'];
	$umfact = $total['umfact'];
	$umeasur = $total['umeasur'];
	$adduser = $total['adduser'];
	$adddate = $total['adddate'];
	$addtime = $total['addtime'];
	$currid = $total['currid'];

	$ordate = $total['ordate'];
	$rqdate = $total['rqdate'];

	// Reset the price for any charge method other than 1.
	global $charge_method;
	if($charge_method!=1){
		$price = 0.00;
	}

	// Record the total and tracking info.
	set_shipment_total($sono, $price, $transeq, $terr, $salesmn, $loctid, $umfact, $umeasur, $adduser, $adddate, $addtime, $currid, $custno, $qtyord, $ordate, $rqdate);
	set_shipment_tracking($sono, $tracking, $terr, $salesmn, $loctid, $umfact, $umeasur, $adduser, $adddate, $addtime, $currid, $custno, $qtyord, $ordate, $rqdate);

}

// $grab_credentials = $db->query("
// 	SELECT
// 		apis.credentials
// 	FROM
// 		" . DB_SCHEMA_INTERNAL . ".apis
// 	WHERE
// 		apis.name = " . $db->quote($api_name) . "
// ");

// Get the location ID.
$location_id = get_location_id($_POST['printer_id']);

//$credentials = $grab_credentials->fetch();
//$credentials = get_credentials($api_name);
$credentials = get_credentials($api_name, $location_id);
$credentials = json_decode($credentials['credentials'], True);

// Retrieve Customer Number from database.
$grab_orderinfo = $db->query("
	SELECT
		LTRIM(RTRIM(somast.custno)) AS customer_number
	FROM
		" . DB_SCHEMA_ERP . ".somast
	INNER JOIN
		" . DB_SCHEMA_ERP . ".sotran
		ON
		LTRIM(RTRIM(somast.sono)) = LTRIM(RTRIM(sotran.sono))
	WHERE
		LTRIM(RTRIM(somast.sono)) = " . $db->quote(trim($_POST['sono'])) . "
");
$order_info = $grab_orderinfo->fetch();

// Retrieve "From" address and contact info specific to Warehouse selected from DB.
$grab_warehouse = $db->query("
	SELECT
		warehouses.contact_name,
		warehouses.contact_phone,
		warehouses.contact_email,
		warehouses.contact_department,
		warehouses.address_name,
		warehouses.address_line1,
		warehouses.address_line2,
		warehouses.address_city,
		warehouses.address_state,
		warehouses.address_zip,
		warehouses.address_country
	FROM
		" . DB_SCHEMA_INTERNAL . ".warehouses
	WHERE
		warehouses.company_id = " . $db->quote(COMPANY) . "
		AND
		warehouses.warehouse_id = " . $db->quote($_POST['warehouse_id']) . "
");
$warehouse = $grab_warehouse->fetch();

// Transform packages submitted into more manageable data structures
$packages = [];
foreach($_POST['packages']['weight'] as $offset => $weight) {
	$curr_ct = 1;
	while($curr_ct <= $_POST['packages']['quantity'][$offset]) {
		$package = [
			'weight'    => $_POST['packages']['weight'][$offset],
			//'length'    => $_POST['packages']['length'][$offset],
			//'width'     => $_POST['packages']['width'][$offset],
			//'height'    => $_POST['packages']['height'][$offset],
			'type'      => $_POST['packages']['type'][$offset],
			'insurance' => $_POST['packages']['insurance'][$offset],
			'class'     => $_POST['packages']['class'][$offset]
		];

		if($_POST['packages']['package_id'][$offset] != 'other') {
			$grab_package_info = $db->query("
				SELECT
					shipping_packages.length,
					shipping_packages.width,
					shipping_packages.height
				FROM
					" . DB_SCHEMA_INTERNAL . ".shipping_packages
				WHERE
					shipping_packages.company_id = " . $db->quote(COMPANY) . "
					AND
					shipping_packages.package_id = " . $db->quote($_POST['packages']['package_id'][$offset]) . "
			");
			$package_info = $grab_package_info->fetch();
			$package['length'] = $package_info['length'];
			$package['width'] = $package_info['width'];
			$package['height'] = $package_info['height'];
		} else {
			$package['length'] = $_POST['packages']['length'][$offset];
			$package['width'] = $_POST['packages']['width'][$offset];
			$package['height'] = $_POST['packages']['height'][$offset];
		}

		$packages[] = $package;
		$curr_ct++;
	}
	/*
	$packages[] = [
		'weight'    => $_POST['packages']['weight'][$offset],
		'length'    => $_POST['packages']['length'][$offset],
		'width'     => $_POST['packages']['width'][$offset],
		'height'    => $_POST['packages']['height'][$offset],
		'type'      => $_POST['packages']['type'][$offset],
		'insurance' => $_POST['packages']['insurance'][$offset],
		'class'     => $_POST['packages']['class'][$offset],
		'quantity'  => $_POST['packages']['quantity'][$offset]
	];
	*/
}

$request = [
	// Credentials
	'username' => $credentials['username'],
	'password' => $credentials['password'],

	'ordernumber' => $_POST['sono'],
	'datetoship' => date('m/d/Y', time()),
	'dateneeded' => date('m/d/Y', time() + 1209600), /// Two weeks out.

	'fromname' => $warehouse['address_name'],
	'fromaddress1' => $warehouse['address_line1'],
	'fromaddress2' => $warehouse['address_line2'] ? $warehouse['address_line2'] : '',
	'fromcity' => $warehouse['address_city'],
	'fromstate' => $warehouse['address_state'],
	'fromzip' => $warehouse['address_zip'],
	'fromcountry' => $warehouse['address_country'],

	'fromcontact' => $warehouse['contact_name'],
	'fromphone' => $warehouse['contact_phone'],
	'fromemail' => $warehouse['contact_email'],
	'fromdepartment' => $warehouse['contact_department'],

	'toname' => $_POST['address']['company'],
	'toaddress1' => $_POST['address']['address1'],
	'toaddress2' => $_POST['address']['address2'],
	'tocity' => $_POST['address']['city'],
	'tostate' => $_POST['address']['state'],
	'tozip' => $_POST['address']['zip'],
	'tocountry' => $_POST['address']['country'],

	'tocustomernumber' => $order_info['customer_number'],
	'pkg_detail_row_count' => count($packages), // Defines the number of packages to be shipped.
	'weighttype' => 'LBS'
];
if(!empty($_POST['contact']['name'])) {
	$request['tocontact'] = $_POST['contact']['name'];
}
if(!empty($_POST['contact']['phone'])) {
	$request['tophone'] = $_POST['contact']['phone'];
}
if(!empty($_POST['contact']['email'])) {
	$request['toemail'] = $_POST['contact']['email'];
}
if($_POST['action'] == 'print') {
	// Set Carrier and Method/Service selected.
	$request['carrier'] = $_REQUEST['carrier'];
	$request['service'] = $_REQUEST['method'];
}

// Determine if we need to add info for "Collect" or "3rd Party" shipments.
$grab_chargemethod = $db->query("
	SELECT
		LTRIM(RTRIM(arcadr.upsshpact)) AS upsshpact,
		LTRIM(RTRIM(somast.shipchg)) AS shipchg
	FROM
		" . DB_SCHEMA_ERP . ".soaddr
	INNER JOIN
		" . DB_SCHEMA_ERP . ".somast
		ON
		LTRIM(RTRIM(somast.sono)) = LTRIM(RTRIM(soaddr.sono))
	INNER JOIN
		" . DB_SCHEMA_ERP . ".arcadr
		ON
		LTRIM(RTRIM(arcadr.cshipno)) = LTRIM(RTRIM(soaddr.cshipno))
		AND
		arcadr.custno = soaddr.custno
	WHERE
		LTRIM(RTRIM(soaddr.sono)) = " . $db->quote(trim($_REQUEST['sono'])) . "
");
$charge_method = $grab_chargemethod->fetch();
if($charge_method['shipchg'] == 3) { // Collect.
	$request['freightcharges'] = '1';

	/*
	Set third-party values
	*/
	$request['tpname'] = $_POST['address']['company'];
	$request['tpaddress1'] = $_POST['address']['address1'];
	$request['tpcity'] = $_POST['address']['city'];
	$request['tpstate'] = $_POST['address']['state'];
	$request['tpzip'] = $_POST['address']['zip'];
	$request['tpcountry'] = $_POST['address']['country'];

	// Get the UPS account #
	if(isset($_POST['ups_account_no'])){$accno = $_POST['ups_account_no'];
	}else{	$accno = '';}
	if($accno=='MISSING ACCOUNT NUMBER'){$accno='';}
	if(!$accno){$accno=$charge_method['upsshpact'];}

	$request['tpacctnumber'] = $charge_method['upsshpact'];
} else if($charge_method['shipchg'] == 5) { // 3rd Party.
	$request['freightcharges'] = '2';
	$request['tpacctnumber'] = $charge_method['upsshpact'];
}

$ct = 0;
$total_value = 0.00;
foreach($packages as $package) {
	$ct++;
	$request['rownum_id_'.$ct]     = $ct;
	$request['type_'.$ct]          = 'package';
	$request['unittype_'.$ct]      = $package['type']; // Envelope vs Box vs Pallet
	$request['weight_'.$ct]        = $package['weight']; // Pounds
	$request['dimlength_'.$ct]     = $package['length']; // Inches
	$request['dimwidth_'.$ct]      = $package['width']; // Inches
	$request['dimheight_'.$ct]     = $package['height']; // Inches
	if($package['insurance']) { // Insurance.
		$request['decval_'.$ct] = $package['insurance'];
		$total_value += $package['insurance'];
	}
	$request['description_'.$ct] = 'Test Product 1';
	//$request['quantity_'.$ct]    = $package['quantity'];
	$request['nmfc_'.$ct]          = '1';
	if($package['type'] == '1') {
		$request['class_'.$ct]     = $package['class'];
	}
}
if($total_value) {
	$request['insurance'] = $total_value;
}
//print_r($request);
//exit; //?

if($_POST['action'] == 'print') {
	$api_url = $credentials['url'] . '/book_shipment';
	$grab_shipment = $db->query("
		INSERT INTO
			" . DB_SCHEMA_ERP . ".shipments
		(
			sono,
			live,
			carrier,
			method,
			notes,
			address_name,
			address_line1,
			address_line2,
			address_city,
			address_state,
			address_zip,
			address_country,
			contact_name,
			contact_phone,
			contact_email,
			debug_request
		)
		OUTPUT Inserted.shipment_id
		VALUES (
			" . $db->quote($_POST['sono']) . ",
			" . $db->quote($live) . ",
			" . $db->quote($_POST['carrier']) . ",
			" . $db->quote($_POST['method']) . ",
			" . $db->quote($_POST['notes']) . ",
			" . $db->quote($_POST['address']['company']) . ",
			" . $db->quote($_POST['address']['address1']) . ",
			" . $db->quote($_POST['address']['address2']) . ",
			" . $db->quote($_POST['address']['city']) . ",
			" . $db->quote($_POST['address']['state']) . ",
			" . $db->quote($_POST['address']['zip']) . ",
			" . $db->quote($_POST['address']['country']) . ",
			" . $db->quote($_POST['contact']['name']) . ",
			" . $db->quote($_POST['contact']['phone']) . ",
			" . $db->quote($_POST['contact']['email']) . ",
			" . $db->quote(json_encode($request)) . "
		);
		SELECT
			shipments.shipment_id
		FROM
			" . DB_SCHEMA_ERP . ".shipments
		WHERE
			shipments.shipment_id = SCOPE_IDENTITY();
	");
	$shipment = $grab_shipment->fetch();
	$shipment_id = $shipment['shipment_id'];

} else {
	$api_url = $credentials['url'] . '/quote_all';
}

// Encode request into a JSON string.
$request_json = json_encode($request);

// Log the request.
$j = json_encode($request, JSON_UNESCAPED_SLASHES);
$l = "Request:".PHP_EOL.$j.PHP_EOL."-----".PHP_EOL;//
logdata($l);

// Set up the request.
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url); // Set the URL.
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, False);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, False);
//curl_setopt($ch, CURLOPT_HTTPHEADER, [
//	'Content-Type' => 'application/x-www-form-urlencoded',
//	'Origin' => $credentials['url'],
//	'Referer' => $api_url
//]);
curl_setopt($ch, CURLOPT_POST, 1); // Ensure we're set up to POST the data.
curl_setopt($ch, CURLOPT_POSTFIELDS, [
	'api_params' => $request_json,
	'submit' => 'get response']
); // This is our raw payload.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, True); // Return response as a variable instead of printing.

// Perform the request.
$response_json = curl_exec($ch); // Do it.

// Check for lack of response.
if($response_json == False){
	// Get the request info of the failed response.
	$ch_info = curl_getinfo($ch);
	$ch_json = json_encode($ch_info);

	// Log the json string to file.
	$l = "Failure:".PHP_EOL.$ch_json.PHP_EOL."-----".PHP_EOL;
	logdata($l);
};

curl_close($ch);

// Log response.
$l = "Response:".PHP_EOL.$response_json.PHP_EOL."-----".PHP_EOL;
logdata($l);

$response = json_decode($response_json, True);

if($_POST['action'] == 'print') {
	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".shipments
		SET
			debug_response = " . $db->quote($response_json) . "
		WHERE
			shipments.shipment_id = " . $db->quote($shipment_id) . "
	");

	if(!$response) {

		// // This is probably redundant.
		// logdata("Response: NULL".PHP_EOL."-----".PHP_EOL);

		print json_encode([
			'success' => False,
			'message' => 'Intelliship did not return any data, pleae try again',
			'response' => $response_json,
			'api' => $api_name
		]);
		$db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".shipments
			SET
				status = -2
			WHERE
				shipments.shipment_id = " . $db->quote($shipment_id) . "
		");
		exit;
	} else if(!empty($response['SUCCESS']) && isset($response['SUCCESS'][0]) && $response['SUCCESS'][0] == 0) {
		if(!empty($response['Error']) && !empty($response['Error'][0])) {

			$r = json_encode([
				'success' => False,
				'message' => $response['Error'][0],
				'response' => $response_json,
				'api' => $api_name
			]);
			// $l = "Response:".PHP_EOL.$r.PHP_EOL."-----".PHP_EOL;
			// logdata($l);
			print $r;
		} else {

			$r = json_encode([
				'success' => False,
				'message' => 'Intelliship responded stating there was no success, please try again',
				'response' => $response_json,
				'api' => $api_name
			]);
			// $l = "Response:".PHP_EOL.$r.PHP_EOL."-----".PHP_EOL;
			// logdata($l);
			print $r;
		}
		$db->query("
			UPDATE
				" . DB_SCHEMA_ERP . ".shipments
			SET
				status = -2
			WHERE
				shipments.shipment_id = " . $db->quote($shipment_id) . "
		");
		exit;
	}

	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".shipments
		SET
			transitdays = " . $db->quote($_POST['transitdays']) . ",
			cost = " . $db->quote($_POST['cost']) . "
		WHERE
			shipments.shipment_id = " . $db->quote($shipment_id) . "
	");

	//print 'JSON RESPONSE: ' . "\r\n";
	//print $response_json;

	$image_paths = [];
	$intelliship_packages = $response['Response'][0];
	//print "\r\n\r\nPackages:\r\n";
	//print_r($intelliship_packages);
	//print "\r\n\r\n";
	foreach($intelliship_packages as $offset => $package) {
		$tracking = $package['TrackingNumber'];
		$intelliship_label_url = $package['Label'];
		$intelliship_shipment_id = $package['ShipmentId'];
		$intelliship_cost = $package['Cost'];

		$image_filename = $shipment_id . '-' . $offset . '.jpg';
		$image_url_fp = fopen($intelliship_label_url, 'r');
		$image_path = str_replace('\\', '/', BASE_PATH) . '/interface/images/shipping-labels/' . $image_filename;
		file_put_contents(
			$image_path, // Filename to be written to.
			$image_url_fp // Data to be written.
		);

		$image_paths[] = $label_network_path . '\\' . $image_filename;

		// Insert entry into the DB.
		$db->query("
			INSERT INTO
				" . DB_SCHEMA_ERP . ".shipment_packages
			(
				shipment_id,
				tracking,
				weight,
				length,
				width,
				height,
				label_filename,
				intelliship_label_url,
				intelliship_shipment_id,
				intelliship_cost
			) VALUES (
				" . $db->quote($shipment_id) . ",
				" . $db->quote($tracking) . ",
				" . $db->quote($packages[$offset]['weight']) . ",
				" . $db->quote($packages[$offset]['length']) . ",
				" . $db->quote($packages[$offset]['width']) . ",
				" . $db->quote($packages[$offset]['height']) . ",
				" . $db->quote($image_filename) . ",
				" . $db->quote($intelliship_label_url) . ",
				" . $db->quote($intelliship_shipment_id) . ",
				" . $db->quote($intelliship_cost) . "
			)
		");
	}

	// Record the shipment in PRO.
	// TODO: Uncomment this.
	//record_in_pro($shipment_id);

	// Get selected printer.
	$printers = $db->query("
			SELECT real_printer_name AS printer_name
			FROM " . DB_SCHEMA_INTERNAL . ".printers
			WHERE printer_id = " . $db->quote($_POST['printer_id']) . "
		");
	$printers = $printers->fetch();
	$printer = $printers['printer_name'];

	// Print to BarTender.
	$labelPrinter = new LabelPrinter();
	$labelPrinter->printIntellishipLabelsByFilename(
		//'\\\\glc-dc1\\DC WH1 Label Printer (Zebra GX430t)',
		$printer,
		//'\\\\glc-dc1\\GodexEZPi-1300_GZPL', // Printer.
		$image_paths // List of labels to print.
	);

	$db->query("
		UPDATE
			" . DB_SCHEMA_ERP . ".somast
		SET
			orderstat = " . $db->quote($_POST['orderstatus']) . "
		WHERE
			LTRIM(RTRIM(somast.sono)) = " . $db->quote($_POST['sono']) . "
	");

	print json_encode([
		'success' => True,
		'valid' => True,
		'images' => $image_paths,
		'request' => $request,
		'response_json' => $response_json,
		'response' => $response,
		'api' => $api_name
	]);
} else {
	$services = [];
	foreach(json_decode($response['Alllist'][0], True) as $service) {
		$service_extracted = [];
		foreach($service as $service_part) {
			foreach($service_part as $service_key => $service_value) {
				$service_extracted[$service_key] = $service_value;
			}
		}
		$services[] = [
			'carrier' => $service_extracted['Carrier'],
			'method' => $service_extracted['Service'],
			'cost' => $service_extracted['Cost'],
			'eta' => $service_extracted['ETA'],
			'days_in_transit' => $service_extracted['TransitDays'],
			'scac' => $service_extracted['SCAC'],
			'service_code' => $service_extracted['ServiceCode'],
			'mode' => $service_extracted['Mode']
		];
	}

	print json_encode([
		'success' => True,
		'valid' => True,
		'services' => $services,
		'packages' => $packages,
		'api' => $api_name
	]);
}
