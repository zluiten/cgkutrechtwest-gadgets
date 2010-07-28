<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  Adds check buttons to the contact that may request 
 *  specific things. Emails are sent to responsible persons 
 *  to request an action.
 *
 * @package plugins
 * @author Thomas Katzlberger
 */

if((@include_once('plugins/AdministrativeRequestMailer/config.php'))!=1)
    require_once('plugins/AdministrativeRequestMailer/config.template.php');     

class AdministrativeRequestMailer {
    
    // CONSTRUCTOR FUNCTION - not needed
    //function AdminInstantDelete() { }

function version() { return '1.0'; }
    
    function isType($t) { return  $t=='editContactInterface' || $t=='changedContactRecord'; }

function help()
    {
        return '<script type="text/javascript">
        function open_help_ARM() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>AdministrativeRequestMailer</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>AdministrativeRequestMailer</h3>");
                help_win.document.write("<p>The AdministrativeRequestMailer adds checkboxes to a section of the edit contact form. If a manager saves a contact and checks some requests the mailer will send an email to a preconfigured email requesting, for example a business card or an email account. The mail messages must be configured in the config.php of this plugin.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_ARM()">help</a>';
    }
    
    /* $mode is changed/added/deleted (before)/trashed and matches contact.lastModification in the DB
     *
     *Useful globals:
     *        $_SESSION['user'] 
     *
     * @global string used for the link in the e-mail
     */
function changedContactRecord(&$contact,$mode) 
    {
        global $CONFIG_ADMINRP_OPTIONS,$CONFIG_TAB_ROOT;

        if($mode != 'added' && $mode != 'changed')
            return;
            
        if(!$_SESSION['user']->isAtLeast('manager'))
            return;
        
        foreach($CONFIG_ADMINRP_OPTIONS as $k => $v)
            if($_POST[$k] == "1")
            {
                $message = $v[2] . 
                    "\n\n". $CONFIG_TAB_ROOT . 'contact/contact.php?id=' . $contact->contact['id'] .
                    "\n\n\nRequested by:". $CONFIG_TAB_ROOT . 'contact/contact.php?id=' . $_SESSION['user']->contact['id'];
                
                $this->sendNotificationEMail($v[1],$v[0],$message,$_SESSION['user']->getFirstEmail());
            }
    }
    
    // Insert Organizational Unit Interface after otherInfo
function editContactInterface(&$contact, $location)
    {
        global $CONFIG_ADMINRP_OPTIONS;
        
        if($location!='ownFieldset') 
            return "";

        if(!$_SESSION['user']->isAtLeast('manager'))
            return "";
                    
        $content = '<fieldset class="edit-names">';
        $content .= '<legend>Administrative Requests</legend>';
        foreach( $CONFIG_ADMINRP_OPTIONS as $k => $v)
            $content .= HTMLHelper::createCheckbox($k,$v[0] ." (".$v[1].")");
            
        return $content . '</fieldset>';
    }
    
    /**
    * sends a notification e-mail
    *
    * @param string $email address to send e-mail to
    * @param string $hash hash to send with e-mail
    * @global array used to configure phpmailer
    */
function sendNotificationEMail($email,$subject,$message,$cc='') {
    
        global $CONFIG_PHPMAILER, $errorHandler;
        
        require_once("lib/phpmailer/class.phpmailer.php");
        
        $mailer = new PHPMailer();
        
        if(isset($CONFIG_PHPMAILER))
        {
            $mailer->Mailer   = $CONFIG_PHPMAILER['Mailer']; 
            $mailer->Sendmail = $CONFIG_PHPMAILER['Sendmail'];
            $mailer->Host     = $CONFIG_PHPMAILER['Host'];
            $mailer->Port     = $CONFIG_PHPMAILER['Port'];
            $mailer->SMTPAuth = $CONFIG_PHPMAILER['SMTPAuth'];
            $mailer->Username = $CONFIG_PHPMAILER['Username'];         
            $mailer->Password = $CONFIG_PHPMAILER['Password'];
        }
        
        $mailer->From = 'noreply@' . $_SERVER['SERVER_NAME'];
        $mailer->FromName = 'noreply@' . $_SERVER['SERVER_NAME'];
        $mailer->AddAddress($email);
        $mailer->AddAddress($cc);
        
        $mailer->Subject = $subject;
        $mailer->Body    = $message;
        
        if(!$mailer->Send())
            $errorHandler->error('mail',$mail->ErrorInfo);
    }    
}
?>