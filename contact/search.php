<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* Searches for contacts 
* Currently supported modes:
* GOTO: used by the ajax-goto in contact/list.php
*/

chdir('..');
require_once('lib/init.php');
require_once('ContactList.class.php');
require_once('ErrorHandler.class.php');
require_once('StringHelper.class.php');
require_once('PageSearchResult.class.php');
require_once('PageContact.class.php');

// Is a user logged in?
if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('guest'))
    $errorHandler->standardError('NOT_LOGGED_IN',basename($_SERVER['SCRIPT_NAME']));

// Do we have something from the text field??
if (isset($_POST['goTo'])) {
    
    if ($_POST['goTo'] == 'whoami' && isset($_SESSION['user']->contact['id'])) {
        header("Location: " . $CONFIG_TAB_ROOT.'contact/contact.php?id=' . $_SESSION['user']->contact['id']);
        exit();
    }
    
    // Remove single quotes which come from $db->escape
    $goTo = mb_substr($db->escape(StringHelper::cleanGPC($_POST['goTo'])),1,-1);
    
    // Search the database
    $cList = new ContactList('SELECT *
        FROM ' . TABLE_CONTACT . ' AS contact
        WHERE 
        (
            CONCAT(firstname,\' \', lastname) LIKE \'%' . $goTo . '%\' OR
            CONCAT(firstname,\' \', middlename,\' \', lastname) LIKE \'%' . $goTo . '%\' OR
            nickname LIKE \'%' . $goTo . '%\' OR
            CONCAT(lastname,\', \',firstname) LIKE \'%' . $goTo . '%\'
        )
        AND (hidden = 0 OR ' . $db->escape($_SESSION['user']->isAtLeast('admin')) . ')
        ORDER BY lastname ASC, firstname ASC');
    
    // if theres only one contact, show it
    if (count($cList->getContacts()) == 1) { // redirect to the page to have a valid URL in the window
        $conts = $cList->getContacts();
        header("Location: " . $CONFIG_TAB_ROOT.'contact/contact.php?id=' . $conts[0]->contact['id']);
        //$page = Page::newPage('PageContact',$conts[0],isset($_GET['noxslt']));
        //echo $page->create();
        exit();
    }
    
    // else: show the page with a list of the contacts
    $page = Page::newPage('PageSearchResult',$cList);
    echo $page->create();    
    
}

exit();

?>
