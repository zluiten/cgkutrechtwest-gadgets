<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  THE ADDRESS BOOK
 *     
 *****************************************************************
 *  save.php
 *  Modifies address book entries (admin only).
 *
 *    There are modes. save.php?id=123&mode=... ($_GET['mode']) can be equal to:
 *    1. 'imported'     reset lastModification
 * 
 *************************************************************/

chdir('..');
require_once('lib/init.php');
require_once('DB.class.php');
require_once('Contact.class.php');
require_once('User.class.php');
require_once('Navigation.class.php');

$rightsManager = RightsManager::getSingleton();
if(!$rightsManager->currentUserIsAllowedTo('administrate'))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));

// -- MODES --
if (!isset($_GET['mode']))
    $_GET['mode'] = '';

switch($_GET['mode'])
{
    case 'imported':
        if (isset($_GET['id']) && $_GET['id'])
            $db->query('UPDATE ' . TABLE_CONTACT . ' SET
                hidden = (lastModification != ' . $db->escape('deleted') . ' && hidden),
                lastModification = ' . $db->escape('imported') . '
                WHERE id = ' . $db->escape($_GET['id']));

        header('Location:'.$CONFIG_TAB_SERVER_ROOT.'contact/changedlist.php');
        exit(0);

    case 'chtype': // change user type
        if (isset($_GET['userid'],$_GET['type'])) {
            $eUser = new User(intval($_GET['userid']));
            $eUser->setType($_GET['type']);
        }
        
        $_GET['id'] = $eUser->contact['id'];

        header('Location:'.$CONFIG_TAB_SERVER_ROOT.'contact/contact.php?id='.$_GET['id']);
        exit(0);
        
    case 'cycleCertState':
        if (isset($_GET['id'],$_GET['newState']))
            $db->query('UPDATE ' . TABLE_CONTACT . ' SET certState = ' . $db->escape($_GET['newState']) . '
            WHERE id = ' . $db->escape($_GET['id']));
        
        header('Location:'.$CONFIG_TAB_SERVER_ROOT.'contact/contact.php?id='.$_GET['id']);
        exit(0);
}

header('Location:'.Navigation::mainPageUrl());
?>
