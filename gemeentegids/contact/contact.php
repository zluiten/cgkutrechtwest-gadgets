<?php // jEdit :folding=indent: :collapseFolds=3: :noTabs=true:
/**
* Script to view|edit|save|delete|trash|create a contact.
*
*  GET:    id=123 ...                              id to be displayed
*          [mode=view|edit|save|delete|trash|new]  what to do, default is view
*
*  Output: Full webpage with header and footer
*
*  Embed:  Uses classes PageContact and PageContactEditNew to
*          display respective web-pages
*
* @author Tobias Schlatter, Thomas Katzlberger
* @package default
*/

/** */

    chdir('..');
    require_once('lib/init.php');
    require_once('ErrorHandler.class.php');
    require_once('Options.class.php');
    require_once('Contact.class.php');
    require_once("PageContact.class.php");
    require_once("PageContactEdit.class.php");
    require_once("RightsManager.class.php");
    
    // Is someone logged in? RightsManager will exit if not.
    $rightsManager = RightsManager::getSingleton();
    
    // Set default mode, if necessary
    if (!isset($_GET['mode']))
        $_GET['mode'] = 'view';
    
    // Does user want to edit contact, but is admin-lock set?
    if ($_GET['mode'] != 'view' && $options->getOption('administrativeLock'))
        $errorHandler->error('adminLock');

    $adminsave = FALSE;
    $enableXSLTProcessing = !isset($_GET['noxslt']);
    
    if(isset($_COOKIE["save"]) && $_COOKIE["save"]=='adminsave' && $_GET['mode']=='save')
        $adminsave=true;
        
    switch ($_GET['mode']) {
        // delete the contact
        case 'delete': 
            // is trash mode turned on??
            // if user is admin --> delete anyway
            if (!$options->getOption('deleteTrashMode') || $_SESSION['user']->isAtLeast('admin')) {
                // do we have an id??
                if (isset($_GET['id'])) {
                    $contact = Contact::newContact(intval($_GET['id']));
                    // Is user really allowed to delete contact??
                    // Has effect only, if trash mode is turned off
                    $rightsManager = RightsManager::getSingleton();
                    if (!$rightsManager->currentUserIsAllowedTo('delete',$contact))
                        $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
                    $contact->delete();
                    $errorHandler->error('ok','Contact permanently deleted.',basename($_SERVER['SCRIPT_NAME']));
                    header('Location:'.Navigation::mainPageUrl());
                } else
                    $errorHandler->error('invArg','No id is given');
            }
            // trash mode is turned on
        // do not add break
        // trash the contact
        case 'trash':
            if (isset($_GET['id'])) {
                // Do we have an id?
                $contact = Contact::newContact(intval($_GET['id']));
                // May user trash contact??
                $rightsManager = RightsManager::getSingleton();
                if (!$rightsManager->currentUserIsAllowedTo('delete',$contact))
                    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
                $contact->trash($_SESSION['user']);
                
                $errorHandler->error('ok','Contact marked for deletion. An administrator can edit, unhide and save the contact to restore it.',basename($_SERVER['SCRIPT_NAME']));
                if ($_SESSION['user']->isAtLeast('admin'))
                    $page = Page::newPage('PageContact',$contact,isset($_GET['noxslt']));
                else
                    header('Location:'.Navigation::mainPageUrl());
            } else
                $errorHandler->error('invArg','No id is given');
        break;
        // Save contact
        case 'save':
            // Is id given?
            if (isset($_POST['contact']['id'])) { // YES it is a contact to edit
                // Load contact
                $contact = Contact::newContact(intval($_POST['contact']['id']));
                // Is user allowed to edit that contact?
                $rightsManager = RightsManager::getSingleton();
                if (!$rightsManager->currentUserIsAllowedTo('edit',$contact))
                    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
            } else { // NO it is a new contact
                // Is user allowed to add new contact?
                if (!$_SESSION['user']->isAtLeast('user'))
                    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
                // create new contact
                $contact = Contact::newContact();
            }
            
            $page = Page::newPage('PageContactEdit',$contact,!isset($_POST['contact']['id'])); // display this in case of error
            $picfile = isset($_FILES['contact']['tmp_name']['pictureData']['file']) ? $_FILES['contact']['tmp_name']['pictureData']['file'] : null;
            if($page->saveContactFromPost($contact,$_POST,$picfile,$adminsave))
            {
                // pic upload error!!
                if(!empty($_FILES['contact']['name']['pictureData']['file']) && empty($_FILES['contact']['tmp_name']['pictureData']['file']))
                    $errorHandler->warning('File upload failed! Error code (6 means tmp directory not writeable): ' . $_FILES['contact']['error']['pictureData']['file'],basename($_SERVER['SCRIPT_NAME']));
                
                $errorHandler->success('Changes successfully saved.',basename($_SERVER['SCRIPT_NAME']));
                $page = Page::newPage('PageContact',$contact->contact['id'],isset($_GET['noxslt'])); // force reload from mysql tables                    
            }

        break;
        case 'edit':
            if (isset($_GET['id'])) {
                $contact = Contact::newContact(intval($_GET['id']));
                $rightsManager = RightsManager::getSingleton();
                if (!$rightsManager->currentUserIsAllowedTo('edit',$contact))
                    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
                $page = Page::newPage('PageContactEdit',$contact,FALSE,$enableXSLTProcessing);
            } else
                $errorHandler->error('invArg','No id is given');
        break;
        case 'new':
            if (!$_SESSION['user']->isAtLeast('user'))
                $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
            if (isset($_GET['id']))
            {
                $errorHandler->error('ok','Edit duplicate: The contact will be written to the database if you save. Make sure to change at least the name and verify other information to avoid duplicate entries in the database.');
                $page = Page::newPage('PageContactEdit',intval($_GET['id']),true);
            }
            else
                $page = Page::newPage('PageContactEdit',null,true);
        break;
        case 'incorrect': // report contact as incorrect
            if(isset($_GET['id']))
                $contact = Contact::newContact(intval($_GET['id']));
                
            if(!empty($contact->contact['whoModified']))
            {
                if($_SESSION['user']->isAtLeast('user'))
                    $sender = $CONFIG_TAB_ROOT . 'contact/contact.php?id=' . $_SESSION['user']->contact['id'];
                else
                    $sender = 'guest login';
                    
                $who = new User(intval($contact->contact['whoModified']));
                $who->sendEMail('Incorrect Entry',"A user ($sender) reported this contact as incorrect.\nPlease carefully verify the following contact:\n" .
                    $CONFIG_TAB_ROOT . 'contact/contact.php?id=' . $contact->contact['id']);
            }
            else
                $errorHandler->warning('No user has ever edited the contact. Please contact an administrator for help.');
            
            $page = Page::newPage('PageContact',intval($_GET['id']),isset($_GET['noxslt']));
        break;
        default: // view contact
            if(isset($_GET['id']))
            {
                $contact = Contact::newContact(intval($_GET['id']));
                
                $rightsManager = RightsManager::getSingleton();
                if (!$rightsManager->currentUserIsAllowedTo('view',$contact))
                    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
                
                $page = Page::newPage('PageContact',intval($_GET['id']),$enableXSLTProcessing);
            }
            else
                $errorHandler->error('invArg','No id is given'); // fatal error - will exit
                
        break;
    }

    echo $page->create();
    
    exit();
    
?>
