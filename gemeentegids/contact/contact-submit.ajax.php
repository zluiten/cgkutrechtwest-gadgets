<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* Script to deliver a contact as div for ajax scripts. Uses class PageContact to generate all content.
*
*  GET: id=123 ... id to be displayed
*       mode = notes ... change only notes of contact
*           xml = 1 ... parse POST array into XML
*
*  Output: normal feedback text
*
* @author Thomas Katzlberger
* @package default
*/

    chdir('..');
    require_once('lib/init.php');
    require_once("PageContact.class.php");
    require_once('XSLTUtility.class.php');
    
    // Is someone logged in?
    if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('guest'))
        $errorHandler->standardError('NOT_LOGGED_IN',basename($_SERVER['SCRIPT_NAME']));
        
    if(!isset($_GET['id']) || !isset($_GET['mode']))
        $errorHandler->standardError('PARAMETER_MISSING',basename($_SERVER['SCRIPT_NAME']));
    
    $contact = Contact::newContact(intval($_GET['id']));
    
    // SECURUITY: Is user allowed to edit that contact?
    $rightsManager = RightsManager::getSingleton();
    if (!$rightsManager->currentUserIsAllowedTo('edit',$contact))
        $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
        
    $save = FALSE;
    
    switch($_GET['mode'])
    {
        case 'contactXMLnotes_NoMandatoryEntries':
             if($_POST['duplicateContact']==1)
            {
                unset($contact->contact['id']);
                foreach($_POST['address'] as &$x)
                    unset($x['refid']);
            }
            
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['www'] as &$x)
                if(empty($x['value']))
                    $x['label']='';
                    
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['phone'] as &$x)
                if(empty($x['value']) && ($x['label']=='mobile' || $x['label']=='fax'))
                    $x['label']='';
                    
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['other'] as &$x)
                if(empty($x['value']))
                    $x['label']='';
                    
            $_POST['contact']['notes']=XSLTUtility::arrayToXMLraw($_POST['contact']['notes'],$h=false);
            
            $save = $contact->saveContactFromArray($_POST);
            break;
           
        case 'contact_NoMandatoryEntries':
             if($_POST['duplicateContact']==1)
            {
                unset($contact->contact['id']);
                foreach($_POST['address'] as &$x)
                    unset($x['refid']);
            }
            
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['www'] as &$x)
                if(empty($x['value']))
                    $x['label']='';
                    
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['phone'] as &$x)
                if(empty($x['value']) && ($x['label']=='mobile' || $x['label']=='fax'))
                    $x['label']='';
                    
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['other'] as &$x)
                if(empty($x['value']))
                    $x['label']='';
            
            $save = $contact->saveContactFromArray($_POST);
            break;
           
        case 'contact':
            if($_POST['duplicateContact']==1)
            {
                unset($contact->contact['id']);
                foreach($_POST['address'] as &$x)
                    unset($x['refid']);
            }
            
            $save = $contact->saveContactFromArray($_POST);
            break;
    }
    
    if($save)
        echo '<div style="background-color: lightGreen;">Successfully saved: '.$contact->generateFullName('text').'</div>';
    else
        echo '<div style="background-color: red;">Failed to save: '.$contact->generateFullName('text').'</div>' . $errorHandler->errorDIVs();
?>
