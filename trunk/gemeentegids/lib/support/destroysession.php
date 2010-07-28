<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

require('../../config.php');

session_name('TheAddressBookSID-'.$CONFIG_DB_NAME);

// Remove old session, if there was any
setcookie(session_name(), '', time()-42000, '/');

echo "Session destroyed. Use your back button and use a link on the error page to proceed.";

?>
