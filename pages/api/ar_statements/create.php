<?php

/*
This API will create a pdf statement for a client with an open AR balance.
*/

function create_statement($custno, $filename){

	// Create the .pdf statement.

	try {
		// Create the shell command for VisualCut.
		$template = '"\\\\GLC-SQL1\SAGEPRO\CR\CR CD\Customer Reports\Statementv2018.rpt"';
		$parm1 = escapeshellarg('Parm1:'.$custno);
		$parm2 = escapeshellarg('Parm2:'.$filename);
		$command = '"C:\\\\Program Files (x86)\\\\Visual CUT 11\\\\Visual CUT.exe" -E';
		$command .= ' '.$template;
		$command .= ' '.$parm1;
		$command .= ' '.$parm2;

		// Execute the command to create the pdf.
		$o = '';
		system($command, $o);

		return true;
	} catch (Exception $e) {
		return false;
	}

}

// Handle POSTs.
if(isset($_POST['custno'])){

	// Create the statement.
	$custno = $_POST['custno'];
	$filename = $_POST['filename'];
	$success = create_statement($custno, $filename);

	// Give the caller a meaninful response.
	print json_encode(array(
		'success' => true,
		'response' => $success
	));

	return;
}

// If a request method other than POST was used or there was
// an error generating the pdf, let the caller know.
print json_encode(array(
	'success' => false
));

?>