<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
// Each upgrade sits in its own file and must be named after
// the previous version from which it upgrades

// HINT: change DB_VERSION_NO to newest version in constants.inc
$upgrades[] = array(
    'from' => '3.0.3',
    'to' => '3.0.4',
    'notes' => array('Added login tracking to users and versioning to plugins. Dropped display as popup.<br>Optional new configurations config.php: $CONFIG_USER_ACCOUNT_EXPIRED_INTERVAL, $CONFIG_USER_ACCOUNT_EXPIRED_MAIL, $CONFIG_LIST_BANNER<br>Dropped in config.php: $CONFIG_SEARCH_N_RESULTS, $CONFIG_STYLE, $CONFIG_DELETE_TRASH_MODE, $CONFIG_ADMINISTRATIVE_LOCK')
);

$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD adminEmailSubject VARCHAR(80) DEFAULT "The Address Book Reloaded"');
$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD adminEmailFooter VARCHAR(120) DEFAULT "Best regards, the administrators of The Address Book Reloaded."');

$db->query('ALTER TABLE ' . TABLE_CONTACT . ' ADD whoModified INT(11) DEFAULT NULL;');
$db->query('UPDATE ' . TABLE_CONTACT . ' SET whoModified=whoAdded;'); // jumpstart

$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD administrativeLock INT(1) DEFAULT 0');
$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD deleteTrashMode INT(1) DEFAULT 1');
$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD interfaceStyle VARCHAR(40) DEFAULT "default"');

$db->query('ALTER TABLE ' . TABLE_USERS . ' DROP displayAsPopup'); // no longer supported
$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' DROP displayAsPopup'); // no longer supported

$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD autocompleteLimit INT(2) DEFAULT 12');
$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD recentlyChangedLimit INT(4) DEFAULT 8');
$db->query('ALTER TABLE ' . TABLE_USERS . ' ADD `lastLogin` DATE');
$db->query('UPDATE ' . TABLE_USERS . ' SET `lastLogin` = NOW()');
$db->query('ALTER TABLE ' . TABLE_PLUGINS . ' ADD `version` VARCHAR(10)');

$db->query('UPDATE ' . TABLE_OPTIONS . ' SET TABversion = ' . $db->escape('3.0.4'));

?>
