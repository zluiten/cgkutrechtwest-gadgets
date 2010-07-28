<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

chdir('..');
require_once('config.php');
require_once('lib/constants.inc');

// same as in init.php:
$path = 'lib/backEnd' . PATH_SEPARATOR . 'lib/frontEnd' . PATH_SEPARATOR . 'lib/utilities' . PATH_SEPARATOR . 'lib/custom' . PATH_SEPARATOR . 'lib/pdf' . PATH_SEPARATOR . ini_get('include_path');
ini_set('include_path',$path);

require_once('ErrorHandler.class.php');

session_name('TheAddressBookSID-'.$CONFIG_DB_NAME);

if (!isset($_GET['do']) || !$_GET['do']) {
    
    require_once('PageInstall.class.php');
    
    // Remove old session, if there was any
    setcookie(session_name(), '', time()-42000, '/');
    
    $page = new PageInstall();
    echo $page->create();

    exit();
}
    

$warnings = array();

if (!function_exists('mb_substr'))
    $errorHandler->error('install','Multibyte-String library seems not to be installed');

if (!function_exists('imagejpeg') || !function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled'))
    $warnings[] = 'GD-library >= 2.1.0 seems not to be installed; auto-resize of pictures won\'t work';

mysql_connect($CONFIG_DB_HOSTNAME,$CONFIG_DB_USER,$CONFIG_DB_PASSWORD);

if (!@mysql_query('CREATE DATABASE IF NOT EXISTS ' . $CONFIG_DB_NAME))
    $errorHandler->error('install','Database ' . $CONFIG_DB_NAME . ' could not be created');

mysql_close();

require_once('DB.class.php');

$db->query('SHOW TABLES');

$tables = array(
    TABLE_ADDRESS,
    TABLE_CONTACT,
    TABLE_GROUPLIST,
    TABLE_GROUPS,
    TABLE_OPTIONS,
    TABLE_PLUGINS,
    TABLE_PROPERTIES,
    TABLE_USERS
);

while ($r = $db->next())
    if (in_array($r[0],$tables))
        $errorHandler->error('install','Table ' . $r[0] . ' already exists. Previous installation of TAB? Try to <a href="upgrade.php">upgrade</a>');


$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}address` (
  `refid` int(11) NOT NULL auto_increment,
  `id` int(11) NOT NULL default '0',
  `type` varchar(20) NOT NULL default '',
  `line1` varchar(100) default NULL,
  `line2` varchar(100) default NULL,
  `city` varchar(50) default NULL,
  `state` varchar(10) default NULL,
  `zip` varchar(20) default NULL,
  `country` varchar(3) default NULL,
  `phone1` varchar(20) default NULL,
  `phone2` varchar(20) default NULL,
  `latitude` decimal(15,12) default NULL,
  `longitude` decimal(15,12) default NULL,
  PRIMARY KEY  (`refid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}contact` (
  `id` int(11) NOT NULL auto_increment,
  `firstname` varchar(40) NOT NULL default '',
  `lastname` varchar(80) NOT NULL default '',
  `middlename` varchar(40) default NULL,
  `primaryAddress` int(11) default NULL,
  `birthday` date default NULL,
  `nickname` varchar(40) default NULL,
  `pictureURL` varchar(255) default NULL,
  `pictureData` mediumblob,
  `notes` text,
  `lastUpdate` datetime default NULL,
  `hidden` int(1) NOT NULL default '0',
  `whoAdded` int(11) default NULL,
  `lastModification` enum('imported','added','changed','deleted') NOT NULL default 'imported',
  `certExpires` date NOT NULL default '0000-00-00',
  `certLastUsed` date default NULL,
  `organizationalUnit` varchar(25) default NULL,
  `certPassword` varchar(30) default NULL,
  `certState` enum('none','new','issued','mailed','used','expired','revoked') NOT NULL default 'none',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}grouplist` (
  `groupid` int(11) NOT NULL default '0',
  `groupname` varchar(60) default NULL,
  PRIMARY KEY  (`groupid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}groups` (
  `id` int(11) NOT NULL default '0',
  `groupid` tinyint(4) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}options` (
  `optID` int(11) NOT NULL auto_increment,
  `bdayInterval` int(3) NOT NULL default '21',
  `bdayDisplay` int(1) NOT NULL default '1',
  `displayAsPopup` int(1) NOT NULL default '0',
  `useMailScript` int(1) NOT NULL default '1',
  `picAlwaysDisplay` int(1) NOT NULL default '0',
  `picWidth` int(1) NOT NULL default '140',
  `picHeight` int(1) NOT NULL default '140',
  `picAllowUpload` int(1) NOT NULL default '1',
  `picCrop` int(1) NOT NULL default '0',
  `msgLogin` TEXT,
  `msgWelcome` TEXT,
  `countryDefault` varchar(3) default '0',
  `allowUserReg` ENUM( 'no', 'everyone', 'contactOnly', 'contactOnlyNoConfirm' ) NOT NULL DEFAULT 'no',
  `eMailAdmin` int(1) NOT NULL default '0',
  `requireLogin` int(1) NOT NULL default '1',
  `language` varchar(25) default NULL,
  `limitEntries` smallint(3) NOT NULL default '0',
  `TABversion` varchar(10) NOT NULL default '3',
  PRIMARY KEY  (`optID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}plugins` (
  `name` varchar(50) NOT NULL default '',
  `state` enum('not installed','activated','deactivated') NOT NULL default 'not installed',
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}properties` (
  `id` int(11) NOT NULL default '0',
  `value` text,
  `label` varchar(40) default NULL,
  `type` enum('other','phone','email','www','chat') NOT NULL default 'other',
  `visibility` enum('visible','hidden','admin-hidden') NOT NULL default 'visible',
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("
CREATE TABLE `{$CONFIG_DB_PREFIX}users` (
  `userid` int(11) NOT NULL auto_increment,
  `id` int(11) default NULL,
  `usertype` enum('admin','manager','user','guest','register') NOT NULL default 'register',
  `password` varchar(32) NOT NULL default '',
  `reg_email` varchar(50) NOT NULL default '',
  `confirm_hash` varchar(50) default NULL,
  `bdayInterval` int(3) default NULL,
  `bdayDisplay` int(1) default NULL,
  `displayAsPopup` int(1) default NULL,
  `useMailScript` int(1) default NULL,
  `language` varchar(25) default NULL,
  `limitEntries` smallint(3) NOT NULL default '0',
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

$db->query("INSERT INTO {$CONFIG_DB_PREFIX}options (msgLogin,msgWelcome)
    VALUES (" . $db->escape('Please log in to access the Address Book Reloaded') . ',' . $db->escape('<strong>Welcome to the Address Book Reloaded</strong>') . ');');

// embedded configuration
if(file_exists('../lib/upgrade/install.php'))
    require('../lib/upgrade/install.php');
    
require_once('User.class.php');
require_once('GuestUser.class.php');

$db->query("INSERT INTO {$CONFIG_DB_PREFIX}users (reg_email,confirm_hash,password,usertype) VALUES ('admin@example.com', NULL, MD5('admin'), 'admin')");

require_once('PageInstall.class.php');

$page = new PageInstall($warnings);
echo $page->create();

exit();

?>
