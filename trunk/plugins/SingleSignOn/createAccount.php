<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  SiNgLeSiGnOn PLUGIN for THE ADDRESS BOOK
 *************************************************************
* @package plugins
* @author Thomas Katzlberger
*/

/** */
    chdir('../..');
    require_once('lib/init.php');
    
    if(!@include_once('plugins/SingleSignOn/pconfig.php'))
        require_once('plugins/SingleSignOn/pconfig.template.php');   
        
    $rightsManager = RightsManager::getSingleton();
    if(!$rightsManager->currentUserIsAllowedTo('administrate'))
        $errorHandler->standardError('PERMISSION_DENIED', basename($_SERVER['SCRIPT_NAME']));

    if(!isset($_GET['id']) || !isset($_GET['mode']))
       $errorHandler->standardError('PARAMETER_MISSING', basename($_SERVER['SCRIPT_NAME']));

    $contact = new Contact(intval($_GET['id']));
    
    foreach($CONFIG_SSO_CLIENTS as $cli) // Check which SSOClient object can handle the request
        if($cli->mode == $_GET['mode'])
        {
            $msg = $cli->createAccount($contact);
            $errorHandler->success('Created: '.$msg, basename($_SERVER['SCRIPT_NAME']));
            //redirect
        }
        else
            echo $cli->mode;
?>