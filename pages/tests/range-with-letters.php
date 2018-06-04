<?php

function generateAlphabeticalList($lower, $upper) {
    ++$upper;
    for($i = $lower; $i !== $upper; ++$i) {
        yield $i;
    }
}

$opportunity = [
	'opportunity_id' => '100135'
];

$base_dir = '\\\\GLC-SQL1\\SAGEPRO\\CR\\CR Messages\\Quotes';
foreach(generateAlphabeticalList('A', 'ZZ') as $value) {
	$filename = $opportunity['opportunity_id'] . '-' . $value . '.pdf';
	if(!file_exists($base_dir . '\\' . $filename)) {
		break;
	}
}

print $filename;
