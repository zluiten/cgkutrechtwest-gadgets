<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
// Each upgrade sits in its own file and must be named after
// the previous version from which it upgrades

// NEW DEVELOPER UPGRADES:
// 1. INCREMENT define('DB_VERSION_NO','3.n'); in constants.inc
// 2. INSERT DB changes AT BOTTOM OF FILE
$upgrades[] = array(
    'from' => '3.0.5',
    'to' => '3.3',
    'notes' => array('Address associated phonenumbers were moved to properties with the label address-type (or city, if type is empty). A complete reconfiguration of the file structure took place please delete old PHP files if installing over the old installation. ADDED: Fully configurable list content. AJAX Features. XSLT processing. IMPORTANT CHANGES in config.php: $CONFIG_INSTALL_SUBDIR, $CONFIG_TAB_SERVER_ROOT, $CONFIG_TAB_ROOT, $CONFIG_MAIN_PAGE, $CONFIG_LOGOUT_PAGE.')
);

$oldVersion = $currentVersion;

/** @todo Fix phone1 phone2 labels to be meaningful: address-type or city (see bug @ sf) */
if($currentVersion <= '3.2')
{
    // Change phones data-scheme
    $db->query('ALTER TABLE ' . TABLE_PROPERTIES . ' ADD refid INT NULL DEFAULT NULL');
    $db->query('INSERT INTO ' . TABLE_PROPERTIES . ' (id,label,value,type,refid)
                SELECT id,IF(type!="",type,city),phone1,\'phone\',refid FROM ' . TABLE_ADDRESS . ' WHERE phone1 != \'\'');
    $db->query('INSERT INTO ' . TABLE_PROPERTIES . ' (id,label,value,type,refid)
                SELECT id,\'phone2\',phone2,\'phone\',refid FROM ' . TABLE_ADDRESS . ' WHERE phone2 != \'\'');
    $db->query('ALTER TABLE ' . TABLE_ADDRESS . ' DROP phone1, DROP phone2');
    
    // Developer Upgrade 3.3
    $currentVersion = '3.3';
    $db->query('UPDATE ' . TABLE_OPTIONS . " SET TABversion = '$currentVersion'");
}

// NEW DEVELOPER UPGRADES:
// 1. INCREMENT define('DB_VERSION_NO','3.n'); in constants.inc
// 2. INSERT DB changes HERE
/* if($currentVersion <= '3.(n-1)')
{
    //
    $db->query('');
    
    // Developer Upgrade to 3.n
    $currentVersion = '3.n';    
    $db->query('UPDATE ' . TABLE_OPTIONS . " SET TABversion = '$currentVersion'");
} */

// OLD DEPRECATED UPGRADE SCHEME --- DISCONTINUED
if($oldVersion <= '3.2')
{
    // Add option to set a default group
    $db->queryNoError('ALTER TABLE ' . TABLE_OPTIONS . ' ADD defaultGroup VARCHAR(60) NOT NULL DEFAULT "" AFTER limitEntries');
    $db->queryNoError('ALTER TABLE ' . TABLE_OPTIONS . ' DROP useMailScript');
    
    // Add options to force height and width of pictures (by html)
    $db->queryNoError('ALTER TABLE ' . TABLE_OPTIONS . ' ADD picForceWidth INT(1) NOT NULL DEFAULT 0 AFTER picCrop');
    $db->queryNoError('ALTER TABLE ' . TABLE_OPTIONS . ' ADD picForceHeight INT(1) NOT NULL DEFAULT 0 AFTER picForceWidth');
    
    // Add telURI and faxURI for sip also to global options
    $db->queryNoError('ALTER TABLE ' . TABLE_OPTIONS . ' ADD telURI VARCHAR(40) NOT NULL DEFAULT "sip:$"');
    $db->queryNoError('ALTER TABLE ' . TABLE_OPTIONS . ' ADD faxURI VARCHAR(40) NOT NULL DEFAULT "fax:$"');
    
    // XML/XSL -> HTML display of contacts Naming: xsltDisplayType='sample' ==> styles: sample.xsl, sample-edit.xsl
    // styles must be stored in lib/xslt
    $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . " ADD xsltDisplayType VARCHAR(20) DEFAULT ''");
    
    // add some security to prevent brute force cracking
    $db->queryNoError('ALTER TABLE ' . TABLE_USERS . " ADD failedLogins INT(2) DEFAULT 0");
    $db->queryNoError('ALTER TABLE ' . TABLE_USERS . " ADD lastRemoteIP VARCHAR(40) DEFAULT ''"); // can store IPv6
}

?>
