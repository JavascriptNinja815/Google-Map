<?php

/*
This API will send a pdf statement for a client with an open AR balance.
*/

// function get_statement($statement_id){

// 	// Query for the statement details.

// 	$db = DB::get();
// 	$q = $db->query("
// 		SELECT filename, recipient
// 		FROM Neuron.dbo.ar_statements
// 		WHERE statement_id = ".$db->quote($statement_id)."
// 	");

// 	return $q->fetch();

// }

function send_statement($recipient, $filename, $company_id){

	// Send the statement to the recipient.

	// The URL to POST to.
	$url = 'http://10.1.247.195/ar-statements/send';

	try{

		// Get the path to the file.
		$statement_dir = "\\\\GLC-SQL1\\SAGEPRO\\CR\\CR Messages\Statements\\";
		$path = $statement_dir.$filename;

		// print($path);

		// Get the file contents and base-64 encode it.
		$file = file_get_contents($path);
		$data = base64_encode($file);

		// The data to POST.
		$post = array(
			'recipient' => $recipient,
			'statement' => $data,
			'company_id' => $company_id
		);

		// Prepare POST with cURL.
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

		// POST the data.
		$response = curl_exec($ch);
		curl_close($ch);

		if($response){
			$r = json_decode($response, true);
			return $r;
		}else{
			return array(
				'success' => false,
				'message' => 'Error sending statement.',
			);
		}

	} catch (Exception $e){
		return array(
			'success' => false,
			'message' => $e->getMessage()
		);
	}

}

// Handle POSTs.
if(isset($_POST['statement_id'])){

	// Get the statement.
	$statement_id = $_POST['statement_id'];
	// $statement = get_statement($statement_id);

	$recipient = $_POST['recipient'];
	$filename = $_POST['filename'];
	$company_id = $_POST['company_id'];

	// Send the statement.
	// $recipient = $statement['recipient'];
	// $filename = $statement['filename'];
	$response = send_statement($recipient, $filename, $company_id);
	//$response = array('success'=>false);

	// Give the caller a meaninful response.
	print json_encode($response);

	return;
}

print json_encode(array(
	'success' => false,
	'message' => 'Only POST supported'
));

?>