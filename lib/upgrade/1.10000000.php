<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

$delUsers = array();
$noLoginUsers = array();


$db->query("UPDATE `{$CONFIG_DB_PREFIX}contact` AS contact LEFT JOIN `{$CONFIG_DB_PREFIX}users` AS users ON contact.whoAdded = users.username SET contact.whoAdded = users.id;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}contact` CHANGE `whoAdded` `whoAdded` INT NULL DEFAULT NULL;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}options` ADD `optID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}options` DROP `defaultLetter`;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` CHANGE `usertype` `usertype` ENUM( 'admin', 'manager', 'user', 'guest', 'register' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'register';");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` CHANGE `confirm_hash` `confirm_hash` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;");
$db->query("UPDATE `{$CONFIG_DB_PREFIX}users` SET `confirm_hash` = NULL WHERE is_confirmed = 1;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` DROP `is_confirmed`;");
//Kill duplicate e-mail users
$db->query("SELECT COUNT(id) AS c, email FROM `{$CONFIG_DB_PREFIX}users` GROUP BY email HAVING c >= 2");
while ($r = $db->next()) {
    if ($r['email'] == '')
        continue;
    $db->query("SELECT * FROM `{$CONFIG_DB_PREFIX}users` WHERE email = " . $db->escape($r['email']) . " ORDER BY id ASC LIMIT " . $db->escape(intval($r['c']) - 1),'rm');
    while ($q = $db->next('rm')) {
        $delUsers[] = $q['username'];
        $db->query("DELETE FROM `{$CONFIG_DB_PREFIX}users` WHERE id = " . $q['id'] . ' LIMIT 1');
    }
}

$db->query("UPDATE `{$CONFIG_DB_PREFIX}users` SET email = CONCAT(username," . $db->escape('@example.com') . ") WHERE email = '';");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` DROP `username`;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` DROP `defaultLetter`;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` CHANGE `email` `reg_email` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` CHANGE `id` `userid` INT NOT NULL AUTO_INCREMENT;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` ADD `id` INT NULL DEFAULT NULL AFTER `userid`;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}users` ADD UNIQUE (`id`);");

$db->query("SELECT DISTINCT userid, reg_email
            FROM `{$CONFIG_DB_PREFIX}email` AS email, `{$CONFIG_DB_PREFIX}email` AS search, `{$CONFIG_DB_PREFIX}users` AS users
            WHERE email.id = search.id AND users.reg_email = search.email GROUP BY email.email HAVING COUNT(email.email) >= 2;");
while ($r = $db->next()) {
    $db->query("UPDATE `{$CONFIG_DB_PREFIX}users` SET id = NULL WHERE userid = " . $r['userid'],'up');
    $noLoginUsers[] = $q['reg_email'];
}

$db->query("UPDATE `{$CONFIG_DB_PREFIX}users` AS users, `{$CONFIG_DB_PREFIX}email` AS email SET users.id = email.id WHERE users.id IS NULL AND users.reg_email = email.email;");
$db->query("UPDATE `{$CONFIG_DB_PREFIX}users` AS users SET users.id = NULL WHERE users.id = -1;");
$db->query("DELETE FROM `{$CONFIG_DB_PREFIX}grouplist` WHERE groupname = '(all entries)' OR groupname = '(ungrouped entries)' OR groupname = '(hidden entries)';");
$db->query("DROP TABLE IF EXISTS {$CONFIG_DB_PREFIX}properties;");
$db->query("CREATE TABLE `{$CONFIG_DB_PREFIX}properties` (id INT(11) NOT NULL DEFAULT '0', value TEXT DEFAULT NULL, label VARCHAR(40), type ENUM ('other','phone','email','www','chat') NOT NULL DEFAULT 'other') TYPE=MyISAM;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}properties` ADD INDEX ( `type` );");
$db->query("INSERT INTO {$CONFIG_DB_PREFIX}properties SELECT id, value AS value, type as label, 'other' as type  FROM {$CONFIG_DB_PREFIX}additionaldata;");
$db->query("INSERT INTO {$CONFIG_DB_PREFIX}properties SELECT id, handle AS value, type as label, 'chat' as type FROM {$CONFIG_DB_PREFIX}messaging;");
$db->query("INSERT INTO {$CONFIG_DB_PREFIX}properties SELECT id, email AS value, type as label, 'email' as type FROM {$CONFIG_DB_PREFIX}email;");
$db->query("INSERT INTO {$CONFIG_DB_PREFIX}properties SELECT id, phone AS value, type as label, 'phone' as type FROM {$CONFIG_DB_PREFIX}otherphone;");
$db->query("INSERT INTO {$CONFIG_DB_PREFIX}properties SELECT id, webpageURL AS value, webpageName as label, 'www' as type FROM {$CONFIG_DB_PREFIX}websites;");
$db->query("DROP TABLE {$CONFIG_DB_PREFIX}additionaldata;");
$db->query("DROP TABLE {$CONFIG_DB_PREFIX}messaging;");
$db->query("DROP TABLE {$CONFIG_DB_PREFIX}email;");
$db->query("DROP TABLE {$CONFIG_DB_PREFIX}otherphone;");
$db->query("DROP TABLE {$CONFIG_DB_PREFIX}websites;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}properties` ADD `visibility` ENUM( 'visible', 'hidden' , 'admin-hidden' ) NOT NULL DEFAULT 'visible';");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}contact` ADD `pictureData` MEDIUMBLOB NULL DEFAULT NULL AFTER `pictureURL`;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}options` DROP `picDupeMode`;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}options` ADD `picCrop` INT( 1 ) NOT NULL DEFAULT '0' AFTER `picAllowUpload`;");
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}options` DROP `modifyTime`;");
$db->query("DROP TABLE IF EXISTS `{$CONFIG_DB_PREFIX}plugins`");
$db->query("CREATE TABLE `{$CONFIG_DB_PREFIX}plugins` (
`name` VARCHAR( 50 ) NOT NULL ,
`state` ENUM( 'not installed', 'activated', 'deactivated' ) NOT NULL ,
PRIMARY KEY ( `name` )
) TYPE = MYISAM ;");
$db->query("SELECT allowUserReg FROM `{$CONFIG_DB_PREFIX}options`");
$r = $db->next();
$db->query("ALTER TABLE `{$CONFIG_DB_PREFIX}options` CHANGE `allowUserReg` `allowUserReg` ENUM( 'no', 'everyone', 'contactOnly', 'contactOnlyNoConfirm' ) NOT NULL DEFAULT 'no';");
$db->query("UPDATE `{$CONFIG_DB_PREFIX}options` SET allowUserReg = " . $db->escape($r['allowUserReg']?'everyone':'no'));
$db->query("INSERT INTO `{$CONFIG_DB_PREFIX}plugins` (name,state) VALUES ('Map','activated');");
$db->query("UPDATE {$CONFIG_DB_PREFIX}contact SET pictureURL = CONCAT('mugshots/' , pictureURL) WHERE pictureURL != '' AND SUBSTRING(pictureURL,1,7) != 'http://' AND SUBSTRING(pictureURL,1,8) != 'https://' AND SUBSTRING(pictureURL,1,1) != '/';");

$db->query("DROP TABLE IF EXISTS `{$CONFIG_DB_PREFIX}scratchpad`");

$tmp = array(
    'from' => '1.1',
    'to' => '3.0',
    'notes' => array(
        'Table scratchpad was dropped, it is no longer used.',
        'Plugins now have to be activated. If you have CertificateAuthority running, please change the entry in the table to activated or deactivated.',
        'Users now have to login with their e-mail-address, if they didn\'t have an e-mail address defined, it is now &lt;username&gt;@example.com.'
    )
);
    
if (count($delUsers) > 0)
    $tmp['notes'][] = 'Following users were duplicate and therefore deleted: ' . implode(', ',$delUsers);
    
if (count($noLoginUsers) > 0)
    $tmp['notes'][] = 'The users with the following e-mail addresses will not be able to log in after the upgrade: ' . implode(', ', $noLoginUsers);

$upgrades[] = $tmp;

$db->query('UPDATE ' . TABLE_OPTIONS . ' SET TABversion = ' . $db->escape('3'));

session_name('TheAddressBookSID-'.$CONFIG_DB_NAME);

// Remove old session, if there was any
setcookie(session_name(), '', time()-42000, '/');

?>
