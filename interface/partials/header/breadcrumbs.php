<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Great Lakes Caster
 */

?>
<div class="breadcrumbs-container">
	<?php
	$breadcrumbs = array_merge(
		[
			'Dashboard' => BASE_URI . '/'
		],
		$breadcrumbs
	);
	foreach($breadcrumbs as $breadcrumb_text => $breadcrumb_url) {
		?>
		<div class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
			<a href="<?php print htmlentities($breadcrumb_url, ENT_QUOTES);?>" itemprop="url">
				<span itemprop="title"><?php print htmlentities($breadcrumb_text);?></span>
			</a>
		</div>
		<?php
	}
	?>
	<div class="collapse-navigation-container">
		<i class="fa fa-arrow-circle-up collapse-navigation" title="Toggle display of navigation"></i>
	</div>
	<div id="feedback-link" class="overlayz-link" overlayz-url="<?php print BASE_URI;?>/dashboard/feedback">
		<i class="fa fa-comment-o"></i> Feedback
	</div>
</div>
