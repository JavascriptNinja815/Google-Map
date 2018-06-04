<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

if($_GET['print'] == 'item') {

} else if($_GET['print'] == 'bin') {

}

$xml = '<?xml version="1.0" encoding="utf-8"?>
<XMLScript Version="2.0">';

$job_count = 0;
foreach($items as $item) {
	$job_count++;
	$xml .= '
	<Command Name="Job1">
		<Print>
			<Format>C:\BarTender\Labels\TestLabel.btw</Format>
			<PrintSetup>
				<Printer>\\glc-dc1\Godex EZPi-1300</Printer>
			</PrintSetup>
			<NamedSubString Name="Title">
				<Value>' . $item['desc'] . '</Value>
			</NamedSubString>
			<NamedSubString Name="BarCode">
				<Value>' . $item['itemno'] . '</Value>
			</NamedSubString>
		</Print>
	</Command>';
}

$xml .= '
</XMLScript>';

print $xml;
