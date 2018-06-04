<?php

function check(){

	// Check if the SO is for a third-party order.

	$sono = trim($_POST['sono']);

	$db = DB::get();
	$q = $db->query("
		SELECT CASE WHEN EXISTS(
			SELECT 1
			FROM PRO01.dbo.somast
			WHERE LTRIM(RTRIM(sono)) = ".$sono."
				AND shipchg = 5
		) THEN 1 ELSE 0
		END AS third_party
	");

	return (bool)$q->fetch()['third_party'];

}

if(isset($_POST)){
	$third_party = check();
}else{
	$third_party = false;
}

print json_encode(array(
	'third_party' => $third_party
));