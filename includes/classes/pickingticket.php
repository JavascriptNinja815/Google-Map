<?php

class PickingTicket {
	public function __construct($sono) {
		$length = trim($sono);
		while($length < 10) {
			$sono = ' ' . $sono;
			$length = trim($sono);
		}

		$this->sono = $sono;
	}

	public function sendToPrint($printer) {
		$output = '';
		$command = '"C:\\Program Files (x86)\\Visual CUT 11\\Visual CUT.exe" -E "\\\\GLC-SQL1\\SAGEPRO\\CR\\CR CD\\Picking Tickets\\Maven_Picking_Ticket_with_Packing_Slipv2.rpt" ' . escapeshellarg('Parm1:' . $this->sono) . ' ' . escapeshellarg('Printer_Only:' . $printer);
		system($command, $output);

		return [
			'command' => $command,
			'output' => $output
		];
	}
}
