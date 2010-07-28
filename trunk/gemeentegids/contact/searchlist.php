<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  THE ADDRESS BOOK RELOADED
 *   
 *************************************************************
 *
 *  contact/searchlist.php
 *  Lists address book entries from a query in the same format as the main list.
 *  Has a mailing-list function.
 *
 *************************************************************/


    chdir('..');
    require_once('lib/init.php');
    require_once('PageSearchList.class.php');
    require_once('StringHelper.class.php');
    require_once('HTMLBeautifier.class.php');
    require_once('ErrorHandler.class.php');
    
    // Is someone logged in? Terminate if not
    if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('guest'))
        $errorHandler->standardError('NOT_LOGGED_IN',basename($_SERVER['SCRIPT_NAME']));
    
    if (!isset($_GET['group']) || $_GET['group'] == 'hidden' && !$_SESSION['user']->isAtLeast('admin'))
        $_GET['group'] = '';
        
    if (!isset($_GET['search'])) $_GET['search'] = '';
    if (!isset($_GET['type'])) $_GET['type'] = '';
    if (!isset($_GET['expand'])) $_GET['expand'] = 0;
    
    // contact/searchlist.php?search=string&type=[name|www|chat|...]
    $page = Page::newPage('PageSearchList',StringHelper::cleanGPC($_GET['search']),StringHelper::cleanGPC($_GET['type']),StringHelper::cleanGPC($_GET['expand']));
    
    echo $page->create();

    exit();
?>
