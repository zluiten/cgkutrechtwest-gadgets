<?php
/**
 * Defines constants to be used within other scripts.
 * No configuration options in here.
 */

/*************************************************************/
// DO NOT MODIFY ANYTHING BELOW THIS LINE - NOT A CONFIG FILE
/*************************************************************/

// ERROR REPORTING set in init.php

/** DEFINE DB VERSION NUMBER (for upgrade.php). MAX: 9.99 ! */
define('DB_VERSION_NO','3.3');

/** DEFINE REDO DB VERSION NUMBER (for upgrade.php). This is the last public release version. Must have an upgrade file.
 * If DB_VERSION_NO changed *AND* x.y.php of DB_VERSION_NO is not found by upgrade.php it will re-run 
 * DB_REDO_VERSION_NO . '.php' to make a developer
 * upgrade. This may lead to a DB error or if the upgrade script is carefully crafted to a sucessful upgrade
 */
define('DB_REDO_VERSION_NO','3.3');

// DEFINE VERSION NUMBER
//define('VERSION_NO', DB_VERSION_NO);
define('VERSION_NO', '3.3.2');

// DEFINE TABLE NAMES
define('TABLE_ADDRESS', $CONFIG_DB_PREFIX . 'address');
define('TABLE_CONTACT', $CONFIG_DB_PREFIX . 'contact');
define('TABLE_GROUPS', $CONFIG_DB_PREFIX . 'groups');
define('TABLE_GROUPLIST', $CONFIG_DB_PREFIX . 'grouplist');
define('TABLE_OPTIONS', $CONFIG_DB_PREFIX . 'options');
define('TABLE_PROPERTIES', $CONFIG_DB_PREFIX . 'properties');
define('TABLE_USERS', $CONFIG_DB_PREFIX . 'users');
define('TABLE_PLUGINS', $CONFIG_DB_PREFIX . 'plugins');
define('TABLE_DATES', $CONFIG_DB_PREFIX . 'dates');

// DEFINE WEBSITES
define('URL_HOMEPAGE', 'http://tab-2.sourceforge.net/');
// http://www.corvalis.net/phpBB2/
define('URL_SOURCEFORGE', 'http://sourceforge.net/projects/tab-2/');

// END OF FILE
?>
