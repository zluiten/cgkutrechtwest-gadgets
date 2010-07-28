<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
    /********************************************************************
     *
     * The Address Book Reloaded 3.0 - SSL-CA
     *
     * Certificate Authority password list viewable for managers only 
     *
     ********************************************************************/

    chdir('../../'); // goto main directory
    
    if((@include_once('plugins/AdminCertificateAuthority/config.php'))!=1)
        require_once('plugins/AdminCertificateAuthority/config.template.php');     
    
// ** GET CONFIGURATION DATA **
    require_once('lib/init.php');
    
    require_once('DB.class.php');
    require_once('plugins/AdminCertificateAuthority/PageCA.class.php');
    require_once('plugins/AdminCertificateAuthority/PageExpiredList.class.php');
    require_once('ContactList.class.php');
    require_once('ErrorHandler.class.php');
    require_once('StringHelper.class.php');
    
    if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('manager'))
        $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
    
    if (!isset($_GET['groupname']))
        $_GET['groupname'] = '-undefined-';

    if (!isset($_GET['mode']))
        $_GET['mode'] = 'pwlist';

    if (isset($_GET['print']) && $_GET['mode']=='pwlist')
        $_GET['mode'] .= '-print';

    // one could fetch the manager's group here and fix it ...

    define('VALID_CERT','(certState = "issued" OR certState = "mailed" OR certState = "used")');
    
    $mode = 'stats';
    $data = new ContactList('SELECT *, contact.id AS id FROM ' . TABLE_CONTACT . ' AS contact, ' . TABLE_GROUPS . ' AS groups, ' . TABLE_GROUPLIST . ' AS grouplist
        WHERE contact.id = groups.id
        AND '.VALID_CERT.' 
        AND groups.groupid = grouplist.groupid
        AND grouplist.groupname = ' . $db->escape($_GET['groupname']) . '
        GROUP BY contact.id
        ORDER BY grouplist.groupname, lastname, firstname');
    $page = new PageCA($_GET['mode'],$data,false);
    echo $page->create();
?>