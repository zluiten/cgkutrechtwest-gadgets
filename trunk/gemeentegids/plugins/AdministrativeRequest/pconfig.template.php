<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  The Address Book Reloaded 3.0 - Administrative Requests Plugin Configuration
 *
 *  @author Thomas Katzlberger
 *
 */

/* changing the config file requires manual update of the DB or Reinstall with loss of past data
 * DB field name  => array (
        interface => checkbox | textfield | html (only if fieldname = html)
        label     => string with HTML
        default   => string default value (no HTML)
        dbType    => SQL data type e.g VARCHAR(30), INT(3), ...
    )
 * htmlXXX and submit are special values and ignored when creating the DB */
$GLOBALS['CONFIG_ADMIN_REQUEST_INTERFACE'] = array(
        'startOfEmployment' => array('interface'=>'textfield','label'=>'Start of employment (YYYY-MM-DD)','default'=>'','dbType'=>'VARCHAR(30)'),
        'requestedEmail' => array('interface'=>'textfield','label'=>'Requested new company email (if applicable)','default'=>'','dbType'=>'VARCHAR(30)'), 
        'forwardEmail' => array('interface'=>'textfield','label'=>'Forwarding to other email<br>(if applicable)','default'=>'','dbType'=>'VARCHAR(30)'), 
        'html1' => array('interface'=>'html','html'=>"</fieldset>\n<fieldset>"),
        'sapClient' => array('interface'=>'textfield','label'=>'SAP client','default'=>'','dbType'=>'VARCHAR(30)'), 
        'sapUsername' => array('interface'=>'textfield','label'=>'SAP user name','default'=>'','dbType'=>'VARCHAR(30)'), 
        'sapAuthorization' => array('interface'=>'textfield','label'=>'SAP authorization','default'=>'','dbType'=>'VARCHAR(30)'), 
        'sapTemplate' => array('interface'=>'textfield','label'=>'SAP template of user<br>(if applicable)','default'=>'','dbType'=>'VARCHAR(30)'), 
        'otherAuthorization' => array('interface'=>'textfield','label'=>'Other authorization<br>(if applicable)','default'=>'','dbType'=>'VARCHAR(30)'), 
        'html2' => array('interface'=>'html','html'=>"</fieldset>\n<fieldset>"),
        'vpnUsername' => array('interface'=>'textfield','label'=>'VPN user name<br>(if needed)','default'=>'','dbType'=>'VARCHAR(30)'),
        'html3' => array('interface'=>'html','html'=>"</fieldset>\n<fieldset>"),
        'signedITPolicy' => array('interface'=>'checkbox','label'=>'Has the user signed the IT User Policies?','default'=>false,'dbType'=>'INT(1)'),
        'submit' => array('interface'=>'checkbox','label'=>'<span style="color: red;">SUBMIT REQUEST WHEN SAVING</span>','default'=>false)
        );
        
// set report "line brakes" after x entries
$GLOBALS['CONFIG_ADMIN_REQUEST_BREAKS'] = array(5,8,13);

// User IDs of users who can mark requests as 'processed' (used in updateRequest.php)
$GLOBALS['CONFIG_ADMIN_REQUEST_AUTHORIZED_USERS'] = array(1);

?>
