<?php

$image = file_get_contents('https://dintelliship2.engagetechnology.com/print/label/8F88BW090YDJ6.jpg');

$labelPrinter = new LabelPrinter();

/*
$image_base64 = base64_encode($image);
$labelPrinter->printIntellishipLabelsByBase64(
	'\\\\glc-dc1\\Godex EZPi-1300', // Printer.
	[ // List of labels to print.
		$image_base64
	]
);*/

$labelPrinter->printIntellishipLabelsByFilename(
	'\\\\glc-dc1\\GodexEZPi-1300_GZPL', // Printer.
	[ // List of labels to print.
		'\\\\glcad1\\shipping-labels-dev\\1.jpg'
	]
);

print 'printing... i think?';
