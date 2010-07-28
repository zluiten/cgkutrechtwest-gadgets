<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  The Address Book Reloaded 3.0 - SingleSignOn
 *
 *  @author Thomas Katzlberger
 *
 * SSO can currently only copy a useraccount eventually also a MD5 password to another application in a SINGLE table by an administrator.
 * It CANNOT yet capture the foreign-key USERID or create sessions for the other application.
 * 
 * This plugin is not even ALPHA and very experimental.
 */
 
/** application name           ... Application name displayed to User (signup button)
    MySQL host name            ... usually 'localhost' or empty
    DB name                    ... $CONFIG_DB_NAME (if other application resides in same DB use $CONFIG_DB_...)
    DB username                ... $CONFIG_DB_USER (if same DB)
    DB password                ... $CONFIG_DB_PASSWORD (if same DB)
    DB users table (other app) ... depends
    DB users table primary key ... curretly unused - needed to capture foreignDbUserid for updates of the SSO login
*/
// create SSO client #1
$sso1 = new SSOClient('Create/reset account on Newsboard','',$GLOBALS['CONFIG_DB_NAME'],$GLOBALS['CONFIG_DB_USER'],$GLOBALS['CONFIG_DB_PASSWORD'],'users-table-name','UserId-PrimaryKey-Field',
    // this will fill appropriate entries in the user table, 
    // but assumes that there is only one table without relations
    // $c is Contact.class.php (currently displayed contact)
    // $u is the db data of the user $u["password"], if $c is not a user SSOClient does nothing
    array(
        'Name' => 'return $c->contact["firstname"] . " " . $c->contact["lastname"];',
        'RegEmail' => 'return $c->getFirstEmail();',
        'Email' => 'return $c->getFirstEmail();',
        'Password' => 'return $u["password"];'
        //'Jabber' => '$c->getProperty("jabber")',
        //'ICQ' => '$c->getProperty("icq")',
        //'AIM' => '$c->getProperty("aim")',
        //'YIM' => '$c->getProperty("yim")',
        //'MSN' => '$c->getProperty("msn")',
        //'Photo' => '$c->getPhoto()',
        //'Avatar' => '$c->getMugshot()',
        //'Location' => 'somewhere in space'
        )
    );
 
// add all clients
$GLOBALS['CONFIG_SSO_CLIENTS'] = array($sso1);

?>
