<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$response = array(
	'datetime' => date('n/j/Y \a\t g:i:sa', time())
);

/*
$today = date('Y-m-d', strtotime('today'));
$fifteen = date('Y-m-d', strtotime('today - 15 days'));
$thirty = date('Y-m-d', strtotime('today - 30 days'));
$forty_five = date('Y-m-d', strtotime('today - 45 days'));
$sixty = date('Y-m-d', strtotime('today - 60 days'));
$seventy_five = date('Y-m-d', strtotime('today - 75 days'));
$ninety = date('Y-m-d', strtotime('today - 90 days'));
$hundred_five = date('Y-m-d', strtotime('today - 105 days'));
$hundred_twenty = date('Y-m-d', strtotime('today - 120 days'));


$strSQL = "
	WITH
		fifteen AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
				AND
				armast.invdte >= '" . $fifteen . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "

		),
		thirty AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $fifteen . "'
				AND
				armast.invdte >= '" . $thirty . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		),
		forty_five AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $thirty . "'
				AND
				armast.invdte >= '" . $forty_five . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		),
		sixty AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $forty_five . "'
				AND
				armast.invdte >= '" . $sixty . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		),
		seventy_five AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $sixty . "'
				AND
				armast.invdte >= '" . $seventy_five . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		),
		ninety AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $seventy_five . "'
				AND
				armast.invdte >= '" . $ninety . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		),
		hundred_five AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $ninety . "'
				AND
				armast.invdte >= '" . $hundred_five . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		),
		hundred_twenty AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $hundred_five . "'
				AND
				armast.invdte >= '" . $hundred_twenty . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		),
		hundred_twenty_plus AS (
			SELECT
				SUM(armast.bbal) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".armast
			WHERE
				armast.arstat != 'V'
			AND
				armast.invdte < '" . $hundred_twenty . "'
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		)
	SELECT
		fifteen.amount AS fifteen,
		thirty.amount AS thirty,
		forty_five.amount AS forty_five,
		sixty.amount AS sixty,
		seventy_five.amount AS seventy_five,
		ninety.amount AS ninety,
		hundred_five.amount AS hundred_five,
		hundred_twenty.amount AS hundred_twenty,
		hundred_twenty_plus.amount AS hundred_twenty_plus
	FROM
		fifteen
	LEFT JOIN
		thirty
		ON
		1 = 1
	LEFT JOIN
		forty_five
		ON
		1 = 1
	LEFT JOIN
		sixty
		ON
		1 = 1
	LEFT JOIN
		seventy_five
		ON
		1 = 1
	LEFT JOIN
		ninety
		ON
		1 = 1
	LEFT JOIN
		hundred_five
		ON
		1 = 1
	LEFT JOIN
		hundred_twenty
		ON
		1 = 1
	LEFT JOIN
		hundred_twenty_plus
		ON
		1 = 1
";

//$response['SQL'] = $strSQL;
$results = $db->query($strSQL);


foreach($results as $result) {
	$response['openar'] = array(
		'fifteen' => number_format($result['fifteen'], 2),
		'thirty' => number_format($result['thirty'], 2),
		'forty_five' => number_format($result['forty_five'], 2),
		'sixty' => number_format($result['sixty'], 2),
		'seventy_five' => number_format($result['seventy_five'], 2),
		'ninety' => number_format($result['ninety'], 2),
		'hundred_five' => number_format($result['hundred_five'], 2),
		'hundred_twenty' => number_format($result['hundred_twenty'], 2),
		'hundred_twenty_plus' => number_format($result['hundred_twenty_plus'], 2),
		'total' => number_format($result['fifteen'] + $result['thirty'] + $result['forty_five'] + $result['sixty'] + $result['seventy_five'] + $result['ninety'] + $result['hundred_five'] + $result['hundred_twenty'] + $result['hundred_twenty_plus'], 2)
	);
}
$response['success'] = True;
*/

function get_openar(){

	// Get the "Open AR" table values.


	$db = DB::get();
	$invoice_numbers = [];
	if(!empty($_POST['invoice_numbers'])) {
		foreach($_POST['invoice_numbers'] as $invoice_number) {
			$invoice_numbers[] = $db->quote($invoice_number);
		}
	}

	$sql = "
		/* Declare locals */
		DECLARE @today date;
		DECLARE @fifteen date;
		DECLARE @thirty date;
		DECLARE @forty_five date;
		DECLARE @sixty date;
		DECLARE @seventy_five date;
		DECLARE @ninety date;
		DECLARE @hundred_five date;
		DECLARE @hundred_twenty date;

		/* Set locals */
		SET @today = GETDATE();
		SET @fifteen = DATEADD(DAY, -15, @today);
		SET @thirty = DATEADD(DAY, -30, @today);
		SET @forty_five = DATEADD(DAY, -45, @today);
		SET @sixty = DATEADD(DAY, -60, @today);
		SET @seventy_five = DATEADD(DAY, -75, @today);
		SET @ninety = DATEADD(DAY, -90, @today);
		SET @hundred_five = DATEADD(DAY, -105, @today);
		SET @hundred_twenty = DATEADD(DAY, -120, @today);

		WITH fifteen AS (
			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte >= @fifteen
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "

		), thirty AS (
			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @fifteen
				AND armast.invdte >= @thirty
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		), forty_five AS (

			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @thirty
				AND armast.invdte >= @forty_five
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		), sixty AS (

			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @forty_five
				AND armast.invdte >= @sixty
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		), seventy_five AS (

			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @sixty
				AND armast.invdte >= @seventy_five
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		), ninety AS (

			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @seventy_five
				AND armast.invdte >= @ninety
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		), hundred_five AS (

			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @ninety
				AND armast.invdte >= @hundred_five
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		), hundred_twenty AS (

			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @hundred_five
				AND armast.invdte >= @hundred_twenty
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		), hundred_twenty_plus AS (
			SELECT
				SUM(CASE WHEN bbal > 0 THEN bbal ELSE 0 END) AS invoice,
				SUM(CASE WHEN bbal < 0 THEN bbal ELSE 0 END) AS credit,
				SUM(armast.bbal) AS net
			FROM " . DB_SCHEMA_ERP . ".armast
			WHERE armast.arstat != 'V'
				AND armast.invdte < @hundred_twenty
				" . (!empty($invoice_numbers) ? "AND LTRIM(armast.invno) IN (" . implode(', ', $invoice_numbers) . ")" : Null) . "
		)
		SELECT
			-- 15
			fifteen.invoice AS fifteen_invoice,
			fifteen.credit AS fifteen_credit,
			fifteen.net AS fifteen_net,

			-- 30
			thirty.invoice AS thirty_invoice,
			thirty.credit AS thirty_credit,
			thirty.net AS thirty_net,

			-- 45
			forty_five.invoice AS forty_five_invoice,
			forty_five.credit AS forty_five_credit,
			forty_five.net AS forty_five_net,
			
			-- 60
			sixty.invoice AS sixty_invoice,
			sixty.credit AS sixty_credit,
			sixty.net AS sixty_net,
			
			-- 75
			seventy_five.invoice AS seventy_five_invoice,
			seventy_five.credit AS seventy_five_credit,
			seventy_five.net AS seventy_five_net,
			
			-- 90
			ninety.invoice AS ninety_invoice,
			ninety.credit AS ninety_credit,
			ninety.net AS ninety_net,
			
			-- 105
			hundred_five.invoice AS hundred_five_invoice,
			hundred_five.credit AS hundred_five_credit,
			hundred_five.net AS hundred_five_net,
			
			-- 120
			hundred_twenty.invoice AS hundred_twenty_invoice,
			hundred_twenty.credit AS hundred_twenty_credit,
			hundred_twenty.net AS hundred_twenty_net,
			
			-- 120+
			hundred_twenty_plus.invoice AS hundred_twenty_plus_invoice,
			hundred_twenty_plus.credit AS hundred_twenty_plus_credit,
			hundred_twenty_plus.net AS hundred_twenty_plus_net

		FROM fifteen,
			thirty,
			forty_five,
			sixty,
			seventy_five,
			ninety,
			hundred_five,
			hundred_twenty,
			hundred_twenty_plus

	";
	$r = $db->query($sql);

	// Restructure the results.
	$result = $r->fetch();
	$processed = array(
		'invoice' => array(
			'fifteen' => number_format($result['fifteen_invoice'], 2),
			'thirty' => number_format($result['thirty_invoice'], 2),
			'forty_five' => number_format($result['forty_five_invoice'], 2),
			'sixty' => number_format($result['sixty_invoice'], 2),
			'seventy_five' => number_format($result['seventy_five_invoice'], 2),
			'ninety' => number_format($result['ninety_invoice'], 2),
			'hundred_five' => number_format($result['hundred_five_invoice'], 2),
			'hundred_twenty' => number_format($result['hundred_twenty_invoice'], 2),
			'hundred_twenty_plus' => number_format($result['hundred_twenty_plus_invoice'], 2)
		),
		'credit' => array(
			'fifteen' => number_format($result['fifteen_credit'], 2),
			'thirty' => number_format($result['thirty_credit'], 2),
			'forty_five' => number_format($result['forty_five_credit'], 2),
			'sixty' => number_format($result['sixty_credit'], 2),
			'seventy_five' => number_format($result['seventy_five_credit'], 2),
			'ninety' => number_format($result['ninety_credit'], 2),
			'hundred_five' => number_format($result['hundred_five_credit'], 2),
			'hundred_twenty' => number_format($result['hundred_twenty_credit'], 2),
			'hundred_twenty_plus' => number_format($result['hundred_twenty_plus_credit'], 2)
		),
		'net' => array(
			'fifteen' => number_format($result['fifteen_net'], 2),
			'thirty' => number_format($result['thirty_net'], 2),
			'forty_five' => number_format($result['forty_five_net'], 2),
			'sixty' => number_format($result['sixty_net'], 2),
			'seventy_five' => number_format($result['seventy_five_net'], 2),
			'ninety' => number_format($result['ninety_net'], 2),
			'hundred_five' => number_format($result['hundred_five_net'], 2),
			'hundred_twenty' => number_format($result['hundred_twenty_net'], 2),
			'hundred_twenty_plus' => number_format($result['hundred_twenty_plus_net'], 2)
		)
	);

	// Get totals.
	$invoice_total = 0;
	$credit_total = 0;
	$net_total = 0;

	// Invoice total.
	foreach($processed['invoice'] as $group => $value){
		$invoice_total += floatval(str_replace(',','',$value));
	}

	// Credit total.
	foreach($processed['credit'] as $group => $value){
		$credit_total += floatval(str_replace(',','',$value));
	}

	// Net total.
	foreach($processed['net'] as $group => $value){
		$net_total += floatval(str_replace(',','',$value));
	}

	// Add the totals back into the processed array.
	$processed['totals'] = array(
		'invoice' => number_format($invoice_total),
		'credit' => number_format($credit_total),
		'net' => number_format($net_total)
	);

	return $processed;

}

$response['openar'] = get_openar();

print json_encode($response);
