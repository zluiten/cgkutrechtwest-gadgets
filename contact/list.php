<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  Lists address book entries. This is the main page that is displazed as default after login.
 *
 */

    chdir('..');
    require_once('lib/init.php');
    require_once('PageList.class.php');
    require_once('StringHelper.class.php');
    require_once('HTMLBeautifier.class.php');
    require_once('ErrorHandler.class.php');
    
    // Is someone logged in? Terminate if not
    $rightsManager = RightsManager::getSingleton();
    
    // Allowed to view list
    if (!$rightsManager->currentUserIsAllowedTo('view-list'))
        $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
        
    if (!isset($_GET['group']) || $_GET['group'] == 'hidden' && !$_SESSION['user']->isAtLeast('admin'))
        $_GET['group'] = '';
    
    if (!isset($_GET['begin']))
        $_GET['begin'] = '';
    
    if (!isset($_GET['page']))
        $_GET['page'] = 0;
    
    if (!isset($_GET['expand']))
        $_GET['expand'] = 0;
    
    $page = Page::newPage('PageList',
        StringHelper::cleanGPC($_GET['group']),
        $_GET['expand'],
        StringHelper::cleanGPC($_GET['begin']),
        intval(StringHelper::cleanGPC($_GET['page']))
    );
    //echo HTMLBeautifier::beautify($page->create());
    echo $page->create();
    
    exit();
?>
