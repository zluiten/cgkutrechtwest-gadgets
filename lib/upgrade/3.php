<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

$upgrades[] = array(
    'from' => '3.0',
    'to' => '3.0.1',
    'notes' => array("Moved birthdays to general dates table.")
);

$db->query("DROP TABLE IF EXISTS `{$CONFIG_DB_PREFIX}dates`");
$db->query("CREATE TABLE `{$CONFIG_DB_PREFIX}dates` (
`id` INT NOT NULL ,
`value` DATE NOT NULL ,
`label` VARCHAR( 40 ) NOT NULL ,
`visibility` ENUM( 'visible', 'hidden', 'admin-hidden' ) NOT NULL DEFAULT 'visible'
) TYPE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci ");

$db->query("INSERT INTO {$CONFIG_DB_PREFIX}dates SELECT id, birthday as value,'Birthday' as label ,'visible' as visibility FROM {$CONFIG_DB_PREFIX}contact WHERE birthday IS NOT NULL AND birthday != '0000-00-00'");
$db->query("ALTER TABLE {$CONFIG_DB_PREFIX}contact DROP birthday");

$db->query('UPDATE ' . TABLE_OPTIONS . ' SET TABversion = ' . $db->escape('3.0.1'));
?>
