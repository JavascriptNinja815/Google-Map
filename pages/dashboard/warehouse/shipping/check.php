<?php
// Check if the SO has already had a label printed.

function is_printed($sono){

	$db = DB::get();
	$q = $db->query("
		SELECT CASE WHEN EXISTS (
				SELECT 1
				FROM PRO01.dbo.shipments
				WHERE LTRIM(RTRIM(sono)) = ".$db->quote(trim($sono))."
			) THEN 1 ELSE 0
			END AS printed
	");

	return (bool)$q->fetch()['printed'];

}

if(isset($_POST['sono'])){
	$printed = is_printed($_POST['sono']);
}else{
	$printed = false;
}

print json_encode(array(
	'printed' => $printed
));

?>