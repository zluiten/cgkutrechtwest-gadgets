<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
 *
 *  Configuration file for certificate authority plugin
 *
 *  IF YOU CHANGE THIS FILE copy config.template.php to config.php
 *  to prevent uprades from overwriting it.
 */

// Label for user certificate request button
$GLOBALS['CONF_CERT_REQUEST_TEXT'] = '<span style="color: red;">REQUEST SSL CERTIFICATE WHEN SAVING</span>'; // 'Request intranet access';

// How many days before expiry should the certificate be re-issued?
$GLOBALS['CONF_CERT_DAYS_TILL_EXPIRE'] = 30;

// Array of valid organizational units to be chosen from, where the first is the default
// Other OUs than in this array can be specified by an admin, but currently not through ui, just through direct db access
// First value is DEFAULT value
$GLOBALS['CONFIG_CA_OU_CHOICES'] = array (
    'Extern','Accounting','Administration','Customer Service','Development',
    'Engineering','Finance','IT',
    'Orders','Procurement','Production','Research','Sales','Shipping','Service',
    'Local Management','Junior Management','Management','Senior Management');

// If you allow downloading of public key certificates from TABR put public key certificates 
// into this location and use the filename: email.crt ; for example: firstname.lastname@example.com.crt
// the path must be absolute to be able to verify that the file exists
$GLOBALS['CONFIG_CERT_PUBLIC_KEY_PATH'] = '/srv/www/tab-2/certificates/';
$GLOBALS['CONFIG_CERT_PUBLIC_KEY_LINK'] = 'certificates/'; // either http://... or relative

// After how many days will issued certificates expire?
$GLOBALS['CONF_CERT_EXPIRE_AFTER'] = 730;

// Password length for automatically generated passwords; Maximum is 30
$GLOBALS['CONF_CERT_PASSWORD_LEN'] = 10;

// Characters used in the password
$GLOBALS['CONF_CERT_PW_CHARS'] = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!?$%&()=+*#:';

?>
