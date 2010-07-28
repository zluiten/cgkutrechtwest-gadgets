<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
// Each upgrade sits in its own file and must be named after
// the previous version from which it upgrades

$upgrades[] = array(
    'from' => '3.0.2',
    'to' => '3.0.3',
    'notes' => array("Table grouplist.groupid changed to autoincrement.")
);

$db->query('ALTER TABLE ' . TABLE_GROUPLIST . ' CHANGE `groupid` `groupid` INT( 11 ) NOT NULL AUTO_INCREMENT ');
$db->query('ALTER TABLE ' . TABLE_GROUPS . ' ADD PRIMARY KEY ( `id` , `groupid` )');
$db->query('UPDATE ' . TABLE_OPTIONS . ' SET TABversion = ' . $db->escape('3.0.3'));

?>
