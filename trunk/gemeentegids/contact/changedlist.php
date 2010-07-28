<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* Shows the expanded recently changed list
*/

chdir('..');
require_once('lib/init.php');
require_once('PageChangedList.class.php');

// Is someone logged in?
if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('guest'))
    $errorHandler->standardError('NOT_LOGGED_IN',basename($_SERVER['SCRIPT_NAME']));

$page = Page::newPage('PageChangedList',true);
echo $page->create();

exit();

?>
