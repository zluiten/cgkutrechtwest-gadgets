<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/*************************************************************
 *  THE ADDRESS BOOK RELOADED 3.0
 *************************************************************/

// Called from: [usrmgr/]admin/
chdir('..');
require_once('config.php');
require_once('lib/constants.inc');

// same as in init.php:
$path = 'lib/backEnd' . PATH_SEPARATOR . 'lib/frontEnd' . PATH_SEPARATOR . 'lib/utilities' . PATH_SEPARATOR . 'lib/custom' . PATH_SEPARATOR . 'lib/pdf' . PATH_SEPARATOR . ini_get('include_path');
ini_set('include_path',$path);

require_once('DB.class.php');
require_once('PageUpgrade.class.php');

session_name('TheAddressBookSID-'.$CONFIG_DB_NAME);

// Remove old session, if there was any to avoid errors in restoring it
setcookie(session_name(), '', time()-42000, '/');

if(isset($_GET['do'])) {

    $upgrades = array();
    
    $redo = TRUE;
    
    do {
        $db->query('SELECT TABversion FROM ' . TABLE_OPTIONS);
        $q = $db->next();
        
        $currentVersion = $q['TABversion'];
        
        $f = $CONFIG_INSTALL_SUBDIR . 'lib/upgrade/' . $currentVersion . '.php';
        if (file_exists($f))
        {
            require($f);
            // embedded configuration - load upgrades from superdirectory ../lib/upgrade/ if they exist
            if(file_exists('../' . $f))
                require('../' . $f);
            
            $redo = FALSE; // found at least one file to upgrade -- redo not necessary
        }
        else // the number was incremented but no new file was found ... try an automatic redo
        {
            if($redo)
            {
                $redo = FALSE;
                
                $f = $CONFIG_INSTALL_SUBDIR . 'lib/upgrade/' . DB_REDO_VERSION_NO . '.php';
                
                require($f);
                // embedded configuration - load upgrades from superdirectory ../lib/upgrade/ if they exist
                if(file_exists('../' . $f))
                    require('../' . $f);
            }
            
            break;
        }
        
    } while (true);

    $page = new PageUpgrade($upgrades,$currentVersion);
    
} 
else if(isset($_GET['redo'])) { 
    // hidden development option to redeploy a specific upgrade
    // This allows to upgrade the DB several times between versions, but causes errors to display
    // admin/upgrade.php?redo=3.0.3

    $upgrades = array();

    $f = $CONFIG_INSTALL_SUBDIR . 'lib/upgrade/' . $_GET['redo'] . '.php';
    if (file_exists($f))
    {
        require($f);
        // embedded configuration - load upgrades from superdirectory ../lib/upgrade/ if they exist
        if (file_exists('../' . $f))
            require('../' . $f);
    }
    
    $page = new PageUpgrade($upgrades,$_GET['redo']);
}
else
    $page = new PageUpgrade();


echo $page->create();

?>
