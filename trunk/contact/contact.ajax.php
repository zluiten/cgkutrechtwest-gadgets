<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* Script to deliver a contact as div for ajax scripts. Uses class PageContact to generate all content.
*
*  GET:    id=123 ... id to be displayed
*
*  Output: div WITHOUT header and footer
*
* @author Thomas Katzlberger
* @package default
*/

    chdir('..');
    require_once('lib/init.php');
    require_once("PageContact.class.php");
    
    if(!isset($_GET['id']))
    {
        $errorHandler->standardError('PARAMETER_MISSING',basename($_SERVER['SCRIPT_NAME']));
        exit(0);
    }
    
    // SECURITY
    $contact = Contact::newContact(intval($_GET['id']));
    $rightsManager = RightsManager::getSingleton();
    if (!$rightsManager->currentUserIsAllowedTo('view',$contact))
        $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
        
    $p = Page::newPage('PageContact',$contact,isset($_GET['noxslt'])); // checks view permissions
    echo $p->innerCreate();
?>
