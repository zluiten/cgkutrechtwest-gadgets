<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
    /** Certificate Authority queries and updates. **/

    chdir('../../'); // goto main directory
    
    if((@include_once('plugins/AdminCertificateAuthority/config.php'))!=1)
        require_once('plugins/AdminCertificateAuthority/config.template.php');     

// ** GET CONFIGURATION DATA **
    require_once('lib/init.php');
    
    require_once('DB.class.php');
    require_once('plugins/AdminCertificateAuthority/PageCA.class.php');
    require_once('plugins/AdminCertificateAuthority/PageExpiredList.class.php');
    require_once('ContactList.class.php');
    require_once('ErrorHandler.class.php');
    require_once('StringHelper.class.php');
    
    if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('admin'))
        $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
    
    if (!isset($_GET['mode']))
        $_GET['mode'] = 'default';

    if (isset($_GET['print']) && $_GET['mode']=='pwlist')
        $_GET['mode'] .= '-print';

    // ?performUpdates=1 changes the DB
    if (isset($_GET['performUpdates']))
        $performUpdates = $_GET['performUpdates'];
    else
        $performUpdates = false;
        
    if (isset($_GET['days']))
        $idays = intval($_GET['days']); // How many days to include in the query (pwlist, imported)
    
    if(empty($idays))
        $idays=2;

    define('VALID_CERT','(certState = "issued" OR certState = "mailed" OR certState = "used")');
    
    // query used to issue new certificates per default
    // define(NEWCERT_QUERY,"( ( lastModification='added' AND certState='none' ) OR certState='new' )"); // issue automatically when added
    define('NEWCERT_QUERY',"certState='new'");
    define('EXPIRED_QUERY',"( TO_DAYS(certExpires) - TO_DAYS(CURRENT_DATE) < " . $CONF_CERT_DAYS_TILL_EXPIRE . " AND certState!='revoked' AND certState!='none')");
    define('IMPORT_QUERY',"( lastModification='imported' AND certState='none' AND TO_DAYS(CURRENT_DATE) - TO_DAYS(lastUpdate) < $idays)");
    define('REVOKE_QUERY',"(lastModification = 'deleted' AND certState!='expired' AND certState!='revoked' AND certState!='none' AND certState!='new') || certState='revoke'");
    
    $modeInfo = '';
    $issueContacts = null;
    $revokeContacts = null;
    $mode = 'exec'; // internal mode:
                    /* exec: any command which creates an output to pass to the shell
                     * stat: any command which creates statistics showed in a table
                     */
                     
    switch($_GET['mode'])
    {
        case 'expired': // Reissue expired:
            $modeInfo = "Reissue cerificates that will expire in " . $CONF_CERT_DAYS_TILL_EXPIRE . " days.";
            $issueContacts = new ContactList('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE ' . EXPIRED_QUERY);
            break;
        case 'imported': // Issue newly imported
            $modeInfo = "Records imported in the last $idays days.";
            $issueContacts = new ContactList('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE ' . IMPORT_QUERY);
            break;
        case 'added':// Issue added
            $modeInfo = "Issue certificates to added records.";
            $issueContacts = new ContactList('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE ' . NEWCERT_QUERY);
            break;
        case 'deleted':
            $modeInfo = "Revoke certificates of deleted records.";
            $revokeContacts = new ContactList('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE ' . REVOKE_QUERY);
            break;
        case 'crl':
            $performUpdates = ''; // useless
            $modeInfo = "Certificate revokation list (revokations for all valid but revoked certificates).";
            $revokeContacts = new ContactList('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE certState = ' . $db->escape('revoked'));
            break;
        case 'relist': // relist commands for a specific date
            $performUpdates = ''; // not only useless, even dangerous
            $date = !isset($_GET['date']) ? 'CURRENT_DATE' : $db->escape($_GET['date']);
            $modeInfo = "Relist commands executed on " . $date . " (only if contact is still here).";
            $issueContacts = new ContactList('SELECT * 
                                                FROM ' . TABLE_CONTACT . ' AS contact
                                                WHERE TO_DAYS(certModifiedAt) = TO_DAYS(' . $date . ')
                                                AND certState != ' . $db->escape('revoked'));
            $revokeContacts = new ContactList('SELECT * 
                                                FROM ' . TABLE_CONTACT . ' AS contact
                                                WHERE TO_DAYS(certModifiedAt) = TO_DAYS(' . $date . ')
                                                AND certState = ' . $db->escape('revoked'));
            break;
            
        case 'expired-list': // Generate a page that list passwords by group/company
            $page = new PageExpiredList();
            echo $page->create();
            exit;
            
        case 'utrack':
        
            if (!isset($_POST['mails']))
                break;
                
            $lines = explode("\n",StringHelper::cleanGPC($_POST['mails']));
            
            $undone = '';
            
            foreach ($lines as $l) {
                
                $l = trim($l);
                
                if (!$l)
                    continue;
                
                $sql = 'UPDATE ' . TABLE_CONTACT . ' AS contact, ' . TABLE_PROPERTIES . ' AS properties
                    SET certLastUsed = NOW(), certState = "used" 
                    WHERE contact.id = properties.id 
                    AND properties.type = "email" 
                    AND properties.value = ' . $db->escape($l) . '
                    AND '.VALID_CERT;
                $db->query($sql);

                if ($db->rowsAffected() <= 0)
                    $undone .= $l . ',<br>';
                    
            }
            
            if ($undone)
                $errorHandler->error('formVal','Was not able to update following addresses: ' . $undone);
        
            break;
        case 'default':
        default:
            $modeInfo = "Reissue cerificates that will expire in " . $CONF_CERT_DAYS_TILL_EXPIRE . " days, issue added and revoke deleted records";
            $issueContacts = new ContactList('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE ' . NEWCERT_QUERY .' OR '. EXPIRED_QUERY);
            $revokeContacts = new ContactList('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE ' . REVOKE_QUERY);
            break;
    }
    
    if ($mode == 'exec') {
        
        $data = "# $modeInfo \n\n";
        
        // issue certificates
        if ($issueContacts !== null)
            foreach ($issueContacts->getContacts() as $c) {
                
                $id = $c->contact['id'];
                
                $name = escapeshellarg($c->contact['firstname'] . ' ' . $c->contact['lastname']);
                $ou = $c->contact['organizationalUnit'];
                if(empty($ou)) $ou=$CONFIG_CA_OU_CHOICES[0];
                $ou = escapeshellarg($ou);
                
                $email = $c->getValueGroup('email');
                $email = escapeshellarg($email[0]['value']);
                
                $company = $c->getValueGroup('groups');
                $company = escapeshellarg($company[0]['groupname']);
                
                $add = $c->getValueGroup('addresses');
                $add = $add[0];
                
                $city = escapeshellarg($add['city']);
                $state = $add['state'];
                if(empty($state)) $state='blank';
                $state = escapeshellarg($state);
                $country = escapeshellarg($add['country']);
                
                // generate password
                $pw = '';
                for ($i=0;$i<$CONF_CERT_PASSWORD_LEN;$i++) {
                    $pw .= $randval = mb_substr($CONF_CERT_PW_CHARS,mt_rand(0,mb_strlen($CONF_CERT_PW_CHARS)-1),1);
                }
                                
                // make generation command
                $cmd = "genusercert -C $name -e $email -o $company -u $ou -l $city -s $state -c $country -d " . $CONF_CERT_EXPIRE_AFTER . " -x " . escapeshellarg($pw) . " -p \$CA_PASSWORD\n";
                if(empty($name) || empty($email) || empty($company) || empty($city) || empty($state) || empty($country))
                    $data .= "# ERROR($id): " .$cmd;
                elseif(!$performUpdates) // show only
                    $data .= '# ' . $cmd;
                else {
                    $data .= $cmd;
                    // certLastUsed is issuing date to suppress premature usage tracking
                    $db->query("UPDATE " . TABLE_CONTACT . " SET certState = 'issued', certLastUsed = NOW(), certPassword = " . $db->escape($pw) . ", certExpires = DATE_ADD(CURDATE(),INTERVAL " . $CONF_CERT_EXPIRE_AFTER . " DAY), certModifiedAt = CURDATE() WHERE id=" . $db->escape($id));
                }
                
            }
            
        // revoke certificates
        if ($revokeContacts !== null) {
            foreach ($revokeContacts->getContacts() as $c) {
                
                $id = $c->contact['id'];
                
                $email = $c->getValueGroup('email');
                $email = escapeshellarg($email[0]['value']);
                
                // generate command
                $cmd = "revokecert -e $email -p \$CA_PASSWORD\n";
                if(empty($email))
                    $data .= "# ERROR($id): " . $cmd;
                elseif ($_GET['mode'] == 'crl')
                    $data .= $cmd;
                elseif (!$performUpdates)
                    $data .= '# ' . $cmd;
                else {
                    $data .= $cmd;
                    $db->query("UPDATE " . TABLE_CONTACT . " SET certState = 'revoked', certModifiedAt = CURDATE() WHERE id=$id");
                }
            }
            $data .= "\n# reissue & install the revokation list on the server\n";
            $data .= "revokecert -p \$CA_PASSWORD\n";
        } else
            $data .= "# Revokations not processed.\n";

        $data .= '# EOF';
           
    }
    
    $page = new PageCA($_GET['mode'],$data,$performUpdates);
    
    echo $page->create();

?>