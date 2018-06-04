<?php

// Get the proper URL.
$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$url = str_replace('/live', '', $url);
$url = str_replace('10.1.247.17', 'maven.local', $url);

// Redirect to Maven dev.
header('Location: '.$url);
exit();
?>