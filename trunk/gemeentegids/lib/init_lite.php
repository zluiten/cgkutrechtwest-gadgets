<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/** 
    Init Lite - Create *NO* session/cookie for scripts that deliver *PUBLIC* information
    1. loads config.php
    2. path setup
    3. basic includes
    
    INIT LITE PROVIDES NO SECURITY WHATSOEVER.
*/
    // On some servers PHP does not display anything if config.php is missing
    ini_set('display_errors',true);
    // E_STRICT cannot be used because PHP5 has too many warnings
    error_reporting(E_ALL);
        
    if((@include_once('config.php'))!=1)
    {
        echo '<h1>Please copy config.template.php to config.php and make the necessary changes.</h1>';
        exit;
    }
    
    // admin/upgrade.php and install.php do not call this script: has identical code!
    $path = 'lib/backEnd' . PATH_SEPARATOR . 'lib/frontEnd' . PATH_SEPARATOR . 'lib/utilities' . PATH_SEPARATOR . 'lib/custom' . PATH_SEPARATOR . 'lib/pdf' . PATH_SEPARATOR . ini_get('include_path');
    ini_set('include_path',$path);
    
    // Start page creation timer. This includes now most of the substantial (65% of total) file loading time
    require_once('Timer.class.php');
    $PAGE_TIMER = new Timer();
    
    require_once('lib/constants.inc');

    require_once('ErrorHandler.class.php');
    require_once('Page.class.php');
    require_once('DB.class.php');
    require_once('Options.class.php');
    require_once('StringHelper.class.php');
    
    // $db->setCharacterSet('utf8'); is the default of the DB class
    mb_internal_encoding('utf8');
    
    // Load standard options to check DB before session is started
    $options = new Options();
?>
