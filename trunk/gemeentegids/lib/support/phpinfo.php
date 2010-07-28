<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

chdir("../..");
require_once('lib/init.php');
require_once('RightsManager.class.php');

$rightsManager = RightsManager::getSingleton();

if (!$rightsManager->currentUserIsAllowedTo('phpinfo'))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
    
echo '<html><head><title> PHP INFO SCRIPT </title></head><body>';
phpinfo(INFO_ALL);
echo '</body></html>';
?>
