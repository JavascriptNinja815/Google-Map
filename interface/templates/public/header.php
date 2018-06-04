<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

header('Content-Type: text/html; charset=utf-8');

if(empty($title)) {
	$title = 'CasterDepot: MAVEN';
} else {
	$title = $title . ' : Maven';
}
$social_url = BASE_URL . ($request->canonical ? $request->canonical : $request->page);

?><!DOCTYPE html>
<html lang="en">
	<?php Template::Partial('head');?>
	<body itemscope itemtype="http://schema.org/WebPage">
		<?php Template::Partial('header');?>
		<div id="body" class="<?php print $body_class;?>">
