<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* Shows the admin panel, which contains a user list and the global options
* Currently supported modes:
* delete: removes a user
* also uses TableEditor to edit global options
*/

chdir('..');
require_once('lib/init.php');
require_once('PageAdminPanel.class.php');
require_once('PluginManager.class.php');
require_once('DB.class.php');

// Is someone logged in?
if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('guest'))
    $errorHandler->standardError('NOT_LOGGED_IN',basename($_SERVER['SCRIPT_NAME']));

// Is logged in user an admin??
if (!$_SESSION['user']->isAtLeast('admin'))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
    
if (!isset($_GET['mode']))
    $_GET['mode'] = '';

$pluginManager->checkForNewPlugins();

switch ($_GET['mode']) {
    case 'delete':
        if (isset($_GET['userid']))            
            $user = new User(intval($_GET['userid']));
            $user->delete();
        break;
    case 'deactivate':
        if (isset($_GET['plugin']))
            $db->query('UPDATE ' . TABLE_PLUGINS . ' SET state = ' . $db->escape('deactivated') . '
                WHERE name = ' . $db->escape(StringHelper::cleanGPC($_GET['plugin'])) . ' AND state = ' . $db->escape('activated'));
        break;
    case 'activate':
        if (isset($_GET['plugin']))
            $db->query('UPDATE ' . TABLE_PLUGINS . ' SET state = ' . $db->escape('activated') . '
                WHERE name = ' . $db->escape(StringHelper::cleanGPC($_GET['plugin'])) . ' AND state = ' . $db->escape('deactivated'));
        break;
    case 'install':
        if (isset($_GET['plugin'])) {
            $classname = StringHelper::cleanGPC($_GET['plugin']);
            $plugin = new $classname;
            $plugin->installPlugin();
            $db->query('UPDATE ' . TABLE_PLUGINS . ' SET state = ' . $db->escape('activated') . '
                WHERE name = ' . $db->escape(StringHelper::cleanGPC($_GET['plugin'])));
        }
        break;
    case 'upgrade':
        if (isset($_GET['plugin'])) {
            $classname = StringHelper::cleanGPC($_GET['plugin']);
            $db->query('SELECT version FROM ' . TABLE_PLUGINS . ' WHERE name="' . $classname .'"');  // retrieve old version
            $r = $db->next();
            $plugin = new $classname;
            $plugin->upgradePlugin($r['version']);
        }
        break;
    case 'uninstall':
        if (isset($_GET['plugin'])) {
            $classname = StringHelper::cleanGPC($_GET['plugin']);
            $plugin = new $classname;
            $plugin->uninstallPlugin();
            $db->query('UPDATE ' . TABLE_PLUGINS . ' SET state = ' . $db->escape('not installed') . '
                WHERE name = ' . $db->escape(StringHelper::cleanGPC($_GET['plugin'])));
        }
        break;
}
    
// show admin panel
$page = new PageAdminPanel();
echo $page->create();

exit();

?>
