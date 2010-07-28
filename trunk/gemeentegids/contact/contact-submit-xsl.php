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
    require_once("PageContactEdit.class.php");
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
            
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['date'] as &$x)
                if(empty($x['value1']) && empty($x['value2']))
                    $x['label']='';
                    
            if(!empty($_POST['contact']['notes']))
                $_POST['contact']['notes']=XSLTUtility::arrayToXMLraw($_POST['contact']['notes'],$h=false);
            
            $_POST['URLtoMugshot'] = isset($_FILES['contact']['tmp_name']['pictureData']['file']) ? $_FILES['contact']['tmp_name']['pictureData']['file'] : null;
            // pic upload error!!
            if(!empty($_FILES['contact']['name']['pictureData']['file']) && empty($_FILES['contact']['tmp_name']['pictureData']['file']))
                $errorHandler->warning('File upload failed! Error code (6 means tmp directory not writeable): ' . $_FILES['contact']['error']['pictureData']['file'],basename($_SERVER['SCRIPT_NAME']));
            
            $save = $contact->saveContactFromArray(StringHelper::cleanGPC($_POST));
            break;
           
        case 'contact_NoMandatoryEntries':
             if($_POST['duplicateContact']==1)
            {
                unset($contact->contact['id']);
                foreach($_POST['address'] as &$x)
                    unset($x['refid']);
            }
            
            // delete labels of empty entries to make mandatory entries optional
            if(isset($_POST['www']))
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
                    
            // delete labels of empty entries to make mandatory entries optional
            foreach($_POST['date'] as &$x)
                if(empty($x['value1']) && empty($x['value2']))
                    $x['label']='';
            
            $_POST['URLtoMugshot'] = isset($_FILES['contact']['tmp_name']['pictureData']['file']) ? $_FILES['contact']['tmp_name']['pictureData']['file'] : null;
            // pic upload error!!
            if(!empty($_FILES['contact']['name']['pictureData']['file']) && empty($_FILES['contact']['tmp_name']['pictureData']['file']))
                $errorHandler->warning('File upload failed! Error code (6 means tmp directory not writeable): ' . $_FILES['contact']['error']['pictureData']['file'],basename($_SERVER['SCRIPT_NAME']));
                
            $save = $contact->saveContactFromArray($_POST);
            break;
           
        case 'contact':
            if($_POST['duplicateContact']==1)
            {
                unset($contact->contact['id']);
                foreach($_POST['address'] as &$x)
                    unset($x['refid']);
            }
            
            $_POST['URLtoMugshot'] = isset($_FILES['contact']['tmp_name']['pictureData']['file']) ? $_FILES['contact']['tmp_name']['pictureData']['file'] : null;
            // pic upload error!!
            if(!empty($_FILES['contact']['name']['pictureData']['file']) && empty($_FILES['contact']['tmp_name']['pictureData']['file']))
                $errorHandler->warning('File upload failed! Error code (6 means tmp directory not writeable): ' . $_FILES['contact']['error']['pictureData']['file'],basename($_SERVER['SCRIPT_NAME']));
                
            $save = $contact->saveContactFromArray($_POST);
            break;
    }
    
    if($save)
    {
        $errorHandler->success('Successfully saved: '.$contact->generateFullName('text'));
        $p = Page::newPage('PageContact',$contact->contact['id'],isset($_GET['noxslt']));
        echo $p->create();
    }
    else
    {
        $errorHandler->success('Failed to save: '.$contact->generateFullName('text'));
        $p = Page::newPage('PageContactEdit',$contact->contact['id'],isset($_GET['noxslt']));
        echo $p->create();
    }
?>
