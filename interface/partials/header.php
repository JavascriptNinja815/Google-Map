<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Great Lakes Caster
 */

if($session->logged_in) {
	?>
	<div id="header">
		<div class="navigation">
			<?php Template::Partial('header/logo');?>
			<?php Template::Partial('header/navigation');?>
		</div>
		<?php Template::Partial('header/breadcrumbs');?>
	</div>
	<?php
}
