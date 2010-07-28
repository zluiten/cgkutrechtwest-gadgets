<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

chdir('..');
require_once('lib/init.php');

require_once('DB.class.php');
require_once('User.class.php');
require_once('ErrorHandler.class.php');

$rightsManager = RightsManager::getSingleton();
if(!$rightsManager->currentUserIsAllowedTo('backup'))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));

$db->backup();

?>
