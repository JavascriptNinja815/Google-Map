<?php

$terms = [
	'today',
	'this week monday',
	'this week sunday',
	'first day of this month',
	'last day of this month',
	'first day of last month',
	'last day of last month'
];

foreach($terms as $term) {
	print $term . ': ' . date('m/d/Y', strtotime($term)) . '<br /><br />';
}
