<?php

$db->query("
	UPDATE
		". DB_SCHEMA_ERP .".somast
	SET
		printed = '" . date("Y-m-d") . "'
	WHERE
		RTRIM(LTRIM(sono)) = " . $db->quote($_GET['so']) . "
");

header('Location: ' . BASE_URI . '/dashboard/sales-orders/not-printed');
