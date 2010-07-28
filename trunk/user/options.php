<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* THE ADDRESS BOOK RELOADED: user options manager
* @author Tobias Schlatter
* @package frontEnd
*/

/** */

chdir('..');
require_once('lib/init.php');
require_once('PageOptions.class.php');
require_once('ErrorHandler.class.php');
require_once('StringHelper.class.php');
require_once('User.class.php');

// ============ SECUTRITY ============

// retrieve a target user to edit: ?userid=123
if (isset($_GET['userid']) && $_SESSION['user']->isAtLeast('admin'))
    $eUser = new User(intval($_GET['userid'])); // admin only -- checked in currentUserIsAllowedTo
else
    $eUser = $_SESSION['user'];

// check if allowed to edit: currentUser->isAtLeast("admin") || isSelf($target);
$rightsManager = RightsManager::getSingleton();
if(!$rightsManager->currentUserIsAllowedTo('edit-options',$eUser))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));

// ===================================

$page = new PageOptions($eUser);

if (isset($_GET['mode']))
    switch ($_GET['mode']) {
        case 'options-password': $page->postPassword($eUser); break;
        case 'options-email': $page->postEmail($eUser); break;
    }
    
echo $page->create();
    
?>
