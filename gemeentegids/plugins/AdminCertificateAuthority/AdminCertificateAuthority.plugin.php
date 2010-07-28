<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  SSL Certificate Authority PLUGIN for THE ADDRESS BOOK
 *************************************************************
* @package plugins
* @author Thomas Katzlberger
*/

if (!@include_once('plugins/AdminCertificateAuthority/config.php'))
    require_once('plugins/AdminCertificateAuthority/config.template.php');
    
require_once('DB.class.php');
require_once('Navigation.class.php');
require_once('StringHelper.class.php');
require_once('RightsManager.class.php');

class AdminCertificateAuthority {
    
    // CONSTRUCTOR FUNCTION - not needed

function isType($t) { return $t=='listMenu' || $t=='contactOutput' || $t=='editContactInterface' || $t=='changedContactRecord'; }
    
    /* There is not much to do here except to generate a link that will perform the actual work
     *
     *Useful globals:
     *        $_SESSION['user']  instance of User for current user
     */
function makeMenuLink(&$contact,&$nav)
    {
        if($_SESSION['user']->isAtLeast('admin'))
            $nav->addEntry('plugin.AdminCertificateAuthority','SSL-CA','../plugins/AdminCertificateAuthority/gencerts.php');
        
    }

    /* There is not much to do here except to generate a link that will perform the actual work
     *
     *Useful globals:
     *        $_SESSION['usertype'] (admin,manager,user)
     *        $_SESSION['username']
         *      $_GET, $_POST
         *      $contact the class Contact used in address.php
     */
function contactOutput(&$contact, $location)
    {
        global $CONFIG_CERT_PUBLIC_KEY_PATH, $CONFIG_CERT_PUBLIC_KEY_LINK;
                
        if($location != 'beforeNotes')
            return '';
        
        $cont = '';
        
        $cont .= '<div class="other-spacer"></div>';

        $cont .= '<div class="other"><span class="other-label">Organizational Unit</span><span class="other-info">';
        if (empty($contact->contact['organizationalUnit']))
            $cont .= "- blank -";
        else
            $cont .= $contact->contact['organizationalUnit'];
        $cont .= '</span></div>';
        
        if($contact->contact['certState'] == 'used')
        {
            $cont .= '<div class="other"><span class="other-label">Client Certificate</span><span class="other-info">in use</span></div>';

            $eml = $contact->getFirstEmail();
            $certFile = $CONFIG_CERT_PUBLIC_KEY_PATH .'/'. $eml .'.crt';
            $certLink = $CONFIG_CERT_PUBLIC_KEY_LINK .'/'. $eml .'.crt';
            if(file_exists($certFile))
                $cont .= '<div class="other"><span class="other-label">Public Key Certificate</span><span class="other-info"><a href="'.$certLink.'"><b>download</b></a> public key certificate for sending encrypted email to this person</span></div>';            
        }
        
        $rightsManager = RightsManager::getSingleton();
        if($rightsManager->mayViewPrivateInfo($contact))
        {
            $cont .= '<div class="other"><span class="other-label hidden">Certificate State</span><span class="other-info">';
            
            if($_SESSION['user']->isAtLeast('admin'))
            {
                if($contact->contact['certState']=='used')
                    $lastUsed = '('.$contact->contact['certLastUsed'].')';
                else
                    $lastUsed='';
                
                $cont .= '<a href="#" onclick="effect_1 = Effect.SlideDown(\'certChanger' . $contact->contact['id'] . '\',{duration:1.2}); return false;">' . $contact->contact['certState'] . '</a> '.$lastUsed;
                $cont .= '</span></div>';
                
                // Hidden changer div for scriptacuous magic:
                $id = $contact->contact['id'];
                $cont .= '<div class="other" id="certChanger' . $id . '" style="display:none;"><span class="other-info">Change: 
                    <a href="../admin/saveadmin.php?mode=cycleCertState&amp;id=' . $id . '&amp;newState=new">new</a>
                    <a href="../admin/saveadmin.php?mode=cycleCertState&amp;id=' . $id . '&amp;newState=revoke">revoke</a>
                    <a href="../admin/saveadmin.php?mode=cycleCertState&amp;id=' . $id . '&amp;newState=none">(none)</a>
                    <a href="../admin/saveadmin.php?mode=cycleCertState&amp;id=' . $id . '&amp;newState=issued">(issued)</a>
                    <a href="../admin/saveadmin.php?mode=cycleCertState&amp;id=' . $id . '&amp;newState=mailed">(mailed)</a>
                    <a href="../admin/saveadmin.php?mode=cycleCertState&amp;id=' . $id . '&amp;newState=used">(used)</a>
                    <a href="../admin/saveadmin.php?mode=cycleCertState&amp;id=' . $id . '&amp;newState=revoked">(revoked)</a></span></div>';
            }
            else
            {
                $cont .= $contact->contact['certState'];
                $cont .= '</span></div>';
            }
                        
            if($contact->contact['certState'] != 'none')
            {
                $cont .= '<div class="other"><span class="other-label hidden">Certificate Expires</span><span class="other-info">';
                $cont .= (empty($contact->contact['certExpires']) ? '- not set -' : $contact->contact['certExpires']) . '</span></div>';
                
                $x = $contact->contact['certPassword'];

                if(!empty($x))
                    $cont .= '<div class="other"><span class="other-label hidden">Certificate Password</span><span class="other-info" style="font-family: Courier">' . htmlentities($x,ENT_COMPAT,'UTF-8') . ' (0=zero, O=oscar, 1=one, l=lima)</span></div>';
            }
        } // end mayViewPrivateInfo()
        
        return $cont;
        
    }    
    
    // Insert Organizational Unit Interface after otherInfo (admins and managers only)
function editContactInterface(&$contact, $location)
    {
        global $CONFIG_CA_OU_CHOICES,$CONF_CERT_REQUEST_TEXT;

        if($location!='otherInfo' || false == $_SESSION['user']->isAtLeast('manager')) 
            return "";
        
        if(!isset($contact->contact['organizationalUnit'])) // add new entry menu ... prepare default
            $contact->contact['organizationalUnit'] = $CONFIG_CA_OU_CHOICES[0];
                        
        if (in_array($contact->contact['organizationalUnit'],$CONFIG_CA_OU_CHOICES))
            $cont = HTMLHelper::createDropdownValuesAreKeys('contact[organizationalUnit]','Organizational Unit',$CONFIG_CA_OU_CHOICES,$contact->contact['organizationalUnit'],'edit-input');
        else
            $cont = HTMLHelper::createDropdownValuesAreKeys('contact[organizationalUnit]','Organizational Unit',
                array_merge($CONFIG_CA_OU_CHOICES,array($contact->contact['organizationalUnit'])),
                $contact->contact['organizationalUnit'],'edit-input');
        
        $cont .= HTMLHelper::createCheckbox('AdminCertificateAuthority_new_cert_request', ( empty($CONF_CERT_REQUEST_TEXT) ? "<span style='color: red;'>REQUEST SSL CERTIFICATE WHEN SAVING</span>" : $CONF_CERT_REQUEST_TEXT ),false,'edit-input-checkbox');
        
        return $cont;
    }

function changedContactRecord(&$contact,$mode) {
        
        global $CONFIG_CA_OU_CHOICES, $db, $CONF_CERT_REQUEST_TEXT, $errorHandler;
        
        if ($mode != 'will_change' && $mode != 'will_add')
            return;

        if($mode=='will_change' && $contact->contact['certState'] != 'none' && $contact->contact['certState'] != 'revoked')
        {
            // load the old contact from the DB
            $verify = new Contact($contact->contact['id']);
            if($contact->contact['lastname'] != $verify->contact['lastname'] || 
               $contact->contact['firstname'] != $verify->contact['firstname'])
                $errorHandler->error('invArg','SSL_CA: Namechange not possible when a certificate is issued. Please contact the administrator to make the change and reissue the certificate. Thank you!');
        }
        
        // Certificate request
        if ($_SESSION['user']->isAtLeast('manager') && isset($_POST['AdminCertificateAuthority_new_cert_request']) && $_POST['AdminCertificateAuthority_new_cert_request'])
        {
            if($mode == 'will_add' || $contact->contact['certState'] == 'none')
            {
                $contact->contact['certState'] = 'new';
                $errorHandler->error('ok','SSL_CA: ' . (empty($CONF_CERT_REQUEST_TEXT) ? "<span style='color: red;'>REQUEST SSL CERTIFICATE WHEN SAVING</span>" : $CONF_CERT_REQUEST_TEXT) . ' processed.' );
            }
            else
                $errorHandler->warning('SSL_CA: ' . (empty($CONF_CERT_REQUEST_TEXT) ? "<span style='color: red;'>REQUEST SSL CERTIFICATE WHEN SAVING</span>" : $CONF_CERT_REQUEST_TEXT) . ' failed: User has already a (valid/expired/revoked) certificate or a pending request. Please contact an administrator.' );
        }
            
        // Check, if value of organizational unit is allowed
        
        // Admin and manager may do anything
        if ($_SESSION['user']->isAtLeast('manager'))
            return;
        
        // users cannot change the value (fetch or reset):
        
        // no custom, because new contact, set default
        if ($mode == 'will_add') {
            $contact->contact['organizationalUnit'] = $CONFIG_CA_OU_CHOICES[0];
            return;
        }
        
        // Retrieve old value (to reset, or because it is the custom one)
        // the query here is somewhat sloppy, because actually the contact class
        // should do that... but there is no rollback implemented (yet)
        $db->query('SELECT * FROM ' . TABLE_CONTACT . ' WHERE id = '. $db->escape($contact->contact['id']),'ca');
        $r = $db->next('ca');
        $contact->contact['organizationalUnit'] = $r['organizationalUnit'];
        
    }
    
function installPlugin()
    {
        global $db,$CONF_CERT_DAYS_TILL_EXPIRE;

        // Certificate Authority DB extensions
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " ADD certLastUsed DATE DEFAULT NULL"); 
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " ADD organizationalUnit VARCHAR(25) DEFAULT NULL");
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " ADD certPassword VARCHAR(30) DEFAULT NULL");
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " ADD certExpires DATE DEFAULT NULL"); 
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " ADD certState ENUM( 'none', 'new', 'revoke', 'issued', 'mailed', 'used', 'expired' ,'revoked' ) NOT NULL DEFAULT 'none'");
            // none = has no cert; new = will issue next time; revoke = revoke next time; issued = have generated issuing request        
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " ADD certModifiedAt DATE NULL DEFAULT NULL");
        $db->query('UPDATE ' . TABLE_CONTACT . ' SET certModifiedAt = SUBDATE(certExpires,' . $db->escape($CONF_CERT_DAYS_TILL_EXPIRE) . ') WHERE ( certState="issued" OR certState="used" ) AND certModifiedAt IS NULL');
    }
    
function uninstallPlugin()
    {
        global $db;

        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " DROP certState");
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " DROP certExpires"); 
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " DROP certPassword");
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " DROP organizationalUnit");
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " DROP certLastUsed");
        $db->queryNoError("ALTER TABLE " . TABLE_CONTACT . " DROP certModifiedAt"); 
    }
    
    // Upgrades the plugin; called from admin/adminPanel.php
function upgradePlugin($oldVersion)
    {
        global $db;

        switch($oldVersion)
        {
            case null: // no version ... added 'revoke' to mark a cert to be revoked next time SSL-CA runs
                $db->query("ALTER TABLE " . TABLE_CONTACT . " CHANGE certState certState ENUM( 'none', 'new', 'revoke', 'issued', 'mailed', 'used', 'expired' ,'revoked' ) NOT NULL DEFAULT 'none';");
            case '0.8':
                // added reissue (revoke, then issue again)
                $db->query("ALTER TABLE " . TABLE_CONTACT . " CHANGE certState certState ENUM( 'none', 'new', 'revoke', 'issued', 'mailed', 'used', 'expired' ,'revoked', 'reissue' ) NOT NULL DEFAULT 'none';");
                $db->query("ALTER TABLE " . TABLE_CONTACT . " ADD certEmail VARCHAR(40) DEFAULT '';");
        }
        
        $db->query('UPDATE ' . TABLE_PLUGINS . ' SET version = "' . $this->version() . '" WHERE name = "AdminCertificateAuthority"');
    }
    
function version() {
        return '0.9';
    }
}
?>