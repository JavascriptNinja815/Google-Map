<?php

$session->ensureLogin();
$session->ensureRole('Sales');

$response = array(
	'datetime' => date('n/j/Y \a\t g:ia', time()),
	'territories' => array()
);

$grab_territory_sales = $db->query("
	SELECT
		somast_terr.terr AS territory,
		(
			SELECT
				SUM(somast.ordamt + somast.shpamt) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".somast
			WHERE
				somast.sotype != 'B'
				AND
				somast.adddate = " . $db->quote(
					date(
						'Y-m-d',
						strtotime('today')
					)
				) . "
				AND
				somast.terr = somast_terr.terr
		) AS today,
		(
			SELECT
				SUM(somast.ordamt + somast.shpamt) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".somast
			WHERE
				somast.sotype != 'B'
				AND
				somast.adddate >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('last Sunday')
					)
				) . "
				AND
				somast.terr = somast_terr.terr
		) AS week,
		(
			SELECT
				SUM(somast.ordamt + somast.shpamt) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".somast
			WHERE
				somast.sotype != 'B'
				AND
				somast.adddate >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('first day of this month')
					)
				) . "
				AND
				somast.terr = somast_terr.terr
		) AS month,
		(
			SELECT
				SUM(somast.ordamt + somast.shpamt) AS amount
			FROM
				" . DB_SCHEMA_ERP . ".somast
			WHERE
				somast.sotype != 'B'
				AND
				somast.adddate >= " . $db->quote(
					date(
						'Y-m-d',
						strtotime('January 1st')
					)
				) . "
				AND
				somast.terr = somast_terr.terr
		) AS year
	FROM
		" . DB_SCHEMA_ERP . ".somast AS somast_terr
	WHERE
		somast_terr.terr != ''
		AND
		somast_terr.sotype != 'B'
	GROUP BY
		somast_terr.terr
	ORDER BY
		somast_terr.terr
");

$totals = array(
	'sales-today' => 0,
	'sales-thisweek' => 0,
	'sales-thismonth' => 0,
	'sales-thisyear' => 0,
	'territories' => array()
);
foreach($grab_territory_sales as $territory_sales) {
	if(!$territory_sales['year']) {
		continue;
	}
	$totals['sales-today'] += $territory_sales['today'];
	$totals['sales-thisweek'] += $territory_sales['week'];
	$totals['sales-thismonth'] += $territory_sales['month'];
	$totals['sales-thisyear'] += $territory_sales['year'];
	$totals['territories'][$territory_sales['territory']] = array(
		'sales-today' => number_format($territory_sales['today']),
		'sales-thisweek' => number_format($territory_sales['week']),
		'sales-thismonth' => number_format($territory_sales['month']),
		'sales-thisyear' => number_format($territory_sales['year'])
	);
	$response['territories'][] = array(
		'territory' => $territory_sales['territory'],
		'sales-today' => number_format($territory_sales['today'], 2),
		'sales-thisweek' => number_format($territory_sales['week'], 2),
		'sales-thismonth' => number_format($territory_sales['month'], 2),
		'sales-thisyear' => number_format($territory_sales['year'], 2)
	);
}

$response['total'] = array(
	'sales-today' => number_format($totals['sales-today'], 2),
	'sales-thisweek' => number_format($totals['sales-thisweek'], 2),
	'sales-thismonth' => number_format($totals['sales-thismonth'], 2),
	'sales-thisyear' => number_format($totals['sales-thisyear'], 2),
);

$data = array(
	'today' => array(),
	'week' => array(),
	'month' => array(),
	'year' => array(),
);
$ct = 0;
$color = 111111;
foreach($totals['territories'] AS $territory_name => $territory_data) {
	$ct++;
	$data['sals-today'][] = array(
		'label' => $territory_name,
		'value' => (float) $territory_data['sales-today'],
		'color' => '#' . $color * $ct
	);
	$data['sales-thisweek'][] = array(
		'label' => $territory_name,
		'value' => (float) $territory_data['sales-thisweek'],
		'color' => '#' . $color * $ct
	);
	$data['sales-thismonth'][] = array(
		'label' => $territory_name,
		'value' => (float) $territory_data['sales-thismonth'],
		'color' => '#' . $color * $ct
	);
	$data['sales-thisyear'][] = array(
		'label' => $territory_name,
		'value' => (float) $territory_data['sales-thisyear'],
		'color' => '#' . $color * $ct
	);
}
$response['data'] = $data;

print json_encode($response);
