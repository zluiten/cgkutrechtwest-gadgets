<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
// Each upgrade sits in its own file and must be named after
// the previous version from which it upgrades

// HINT: change DB_VERSION_NO to newest version in constants.inc
$upgrades[] = array(
    'from' => '3.0.4',
    'to' => '3.0.5',
    'notes' => array('New in config.php: $CONFIG_LIST_NAME_SPEC; Dropped in config.php: $CONFIG_TEL_URI, $COFIG_FAX_URI')
);
//Optional new configurations config.php: <br>

// RUN: /tab-2/admin/upgrade.php?redo=_3.0.4
// ADD new DB MODS BEFORE the first query so that redo works until the upgrade is finished:

// private means the contact is private to its creator and the admin and does not show in standard searches (same as hidden)
$db->query('ALTER TABLE ' . TABLE_CONTACT . " ADD private INT(1) NOT NULL DEFAULT 0 AFTER hidden");

$db->query('ALTER TABLE ' . TABLE_CONTACT . " ADD namePrefix VARCHAR(40) NOT NULL DEFAULT '' AFTER middleName");
$db->query('ALTER TABLE ' . TABLE_CONTACT . " ADD nameSuffix VARCHAR(40) NOT NULL DEFAULT '' AFTER namePrefix");
$db->query('ALTER TABLE ' . TABLE_CONTACT . " ADD sex enum('blank','female','male','shemale') NOT NULL DEFAULT 'blank' AFTER nameSuffix"); // ;-)

$db->query('ALTER TABLE ' . TABLE_GROUPLIST . ' ADD logoURL VARCHAR(255) NOT NULL DEFAULT ""'); // future group logo
$db->query('ALTER TABLE ' . TABLE_GROUPLIST . ' ADD acronym VARCHAR(6) NOT NULL DEFAULT ""');

// must also add to USER OPTIONS PANEL!!! $ replaced by phonenumber.
$db->query('ALTER TABLE ' . TABLE_USERS . ' ADD telURI VARCHAR(40) DEFAULT "sip:$@example.com"');
$db->query('ALTER TABLE ' . TABLE_USERS . ' ADD faxURI VARCHAR(40) DEFAULT "fax:$"');
$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' CHANGE msgWelcome msgWelcome TEXT');

$db->query('ALTER TABLE ' . TABLE_DATES . " CHANGE value value1 DATE NULL DEFAULT '0000-00-00'");
$db->query('ALTER TABLE ' . TABLE_DATES . " ADD value2 DATE NULL DEFAULT '0000-00-00' AFTER value1");
$db->query('ALTER TABLE ' . TABLE_DATES . " ADD type ENUM( 'yearly', 'monthly', 'weekly', 'once', 'autoremove' ) NOT NULL DEFAULT 'yearly' AFTER id");

$db->query('UPDATE ' . TABLE_OPTIONS . ' SET TABversion = ' . $db->escape('3.0.5'));

?>
