<?php

var_export(
	$session->hasPermission('Supervisor', 'timesheets', 109)
);

print '<br /><br />';

var_export(
	$session->hasPermission('Supervisor', 'timesheets', 108)
);
