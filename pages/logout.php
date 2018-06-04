<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

// setcookie('account', '', 1);
// setcookie('session', '', 1);

if($session->alias){$session->logOutAlias();}
else{$session->LogOut();}

header('Location: /' . BASE_URI);
?><script type="text/javascript">window.location = '<?php print BASE_URI;?>';</script>