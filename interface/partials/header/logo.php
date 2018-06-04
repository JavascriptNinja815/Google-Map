<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

?>
<div class="logo-container">
	<a class="logo" href="<?php print BASE_URI;?>/dashboard?login">
		<?php
		if(COMPANY === '1') {
			?><img src="<?php print BASE_URI;?>/interface/images/casterdepot-logo.png" /><?php
		} else if(COMPANY === '2') {
			?><img src="<?php print BASE_URI;?>/interface/images/dorodo-logo.png" /><?php
		} else {
			?><img src="<?php print BASE_URI;?>/interface/images/casterdepot-logo.png" /><?php
		}
		?>
	</a>
</div>