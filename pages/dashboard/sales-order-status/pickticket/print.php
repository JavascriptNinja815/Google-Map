<?php

$pickticket = new PickingTicket($_POST['sono']);
$print_result = $pickticket->sendToPrint($_POST['printer']);

print json_encode([
	'success' => True,
	'debug' => $print_result
]);
