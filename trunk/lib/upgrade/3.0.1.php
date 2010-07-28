<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
// Each upgrade sits in its own file and must be named after
// the previous version from which it upgrades

$upgrades[] = array(
    'from' => '3.0.1',
    'to' => '3.0.2',
    'notes' => array('Added an admin panel option to change the display of the recently changed list.<br>Dropped in config.php: $CONFIG_DISPLAY_CHANGED_LIST')
);

$db->query('ALTER TABLE ' . TABLE_OPTIONS . ' ADD recentlyChangedDisplay INT(1) DEFAULT 0');
$db->query('UPDATE ' . TABLE_OPTIONS . ' SET TABversion = ' . $db->escape('3.0.2'));

?>
