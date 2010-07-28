<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  The Address Book Reloaded 3.0 - Administrative Requests Plugin
 *
 *  This plugin adds a new table to the DB to store admin requests.   
 *  This could be for example IT, SAP accounts, or requests for a 
 *  company email. Each request is initiated when creating (or 
 *  eventually modifying) a contact by a manager. The DB stores 
 *  creation date, completion date, originating manager, target 
 *  contact and ll custom fields from the confuguration. 
 *
 * @package plugins
 * @author Thomas Katzlberger
 */

if (!@include_once('plugins/AdministrativeRequest/pconfig.php'))
    require_once('plugins/AdministrativeRequest/pconfig.template.php');   

class AdministrativeRequest {
    
    // CONSTRUCTOR FUNCTION - not needed
    //function AdminInstantDelete() { }

function version() { return '0.4'; }
    
    function isType($t) { return  $t=='editContactInterface' || $t=='changedContactRecord' || $t=='listMenu'; }

function help()
    {
        return '<script type="text/javascript">
        function open_help_AR() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Administrative Requests</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Administrative Requests</h3>");
                help_win.document.write("<p>This plugin adds a new table to the DB to store admin requests. This could be for example IT, SAP accounts, or requests for a company email. Each request is initiated when creating (or eventually modifying) a contact by a manager. The DB stores creation date, completion date, originating manager, target contact and all custom fields from the configuration.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_AR()">help</a>';
    }
    
    function makeMenuLink(&$contactList,&$nav)
    {
        global $db,$CONFIG_DB_PREFIX;
        
        if(!$_SESSION['user']->isAtLeast('manager'))
            return;
        
        // anything pending? then add a menu
        $db->query("SELECT COUNT(*) as n FROM `{$CONFIG_DB_PREFIX}AdministrativeRequests` AS request WHERE dateProcessed IS NULL");
        $r = $db->next();

        if($r['n']>0)
            $nav->addEntry('plugin.AdministrativeRequest','<span style="color: red;">admin requests pending</span>','../plugins/AdministrativeRequest/report.php');
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
        global $CONFIG_ADMIN_REQUEST_INTERFACE,$CONFIG_TAB_ROOT,$errorHandler;

        if($mode != 'added' && $mode != 'changed')
            return;
            
        if(!$_SESSION['user']->isAtLeast('manager'))
            return;
            
        if(isset($_POST['AdminRequest']) && $_POST['AdminRequest']['submit'] == '1')
        {
            $_POST['AdminRequest']['contactId'] = $contact->contact['id'];
            $_POST['AdminRequest']['requesterId'] = $_SESSION['user']->contact['id'];
            
            // sanity checks?
            
            $this->save($_POST['AdminRequest']);
            $errorHandler->success('Administrative Request submitted.',get_class($this));
        }
    }
    
    // Insert Organizational Unit Interface after otherInfo
function editContactInterface(&$contact, $location)
    {
        global $CONFIG_ADMIN_REQUEST_INTERFACE;
        
        if($location!='ownFieldset') 
            return "";

        if(!$_SESSION['user']->isAtLeast('manager'))
            return "";
                    
        $content = '<fieldset class="edit-names">';
        $content .= '<legend>Administrative Requests</legend>';
        foreach( $CONFIG_ADMIN_REQUEST_INTERFACE as $k => $v)
            switch($v['interface'])
            {
                case 'textfield':
                    $content .= HTMLHelper::createTextField('AdminRequest['.$k.']',$v['label'],$v['default'],'edit-input');
                    break;
                case 'checkbox':
                    $content .= HTMLHelper::createCheckbox('AdminRequest['.$k.']',$v['label'],$v['default'],'edit-input-checkbox');
                    break;
                case 'html':
                    $content .= $v['html'];
                    break;
            }
            
        return $content . '</fieldset>';
    }
    
function installPlugin()
    {
        global $db,$CONFIG_DB_PREFIX,$CONFIG_ADMIN_REQUEST_INTERFACE;
        
        $fields = '';
        foreach( $CONFIG_ADMIN_REQUEST_INTERFACE as $k => $v)
            if(substr($k,0,4)!='html' && $k!='submit') // not for DB!
                $fields .= "`$k` " . $v['dbType'] .',';
        
        // DB extensions
        $db->query("CREATE TABLE IF NOT EXISTS `{$CONFIG_DB_PREFIX}AdministrativeRequests` ( `requestId` int(11) NOT NULL auto_increment, 
                    `contactId` int(11) NOT NULL default '0', 
                    `requesterId` int(11) NOT NULL default '0',
                    `dateAdded` DATE DEFAULT NULL,
                    `dateProcessed`  DATE DEFAULT NULL, 
                    `whoProcessedId` int(11) DEFAULT NULL, " . $fields . 
                     " PRIMARY KEY  (`requestId`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
    }
    
function uninstallPlugin()
    {
        global $db,$CONFIG_DB_PREFIX;
        
        // DB extensions
        $db->query("DROP TABLE `{$CONFIG_DB_PREFIX}AdministrativeRequests`");
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

function save($v)
    {
        global $db,$CONFIG_DB_PREFIX,$CONFIG_ADMIN_REQUEST_INTERFACE;
                        
        $tbl = $CONFIG_DB_PREFIX . 'AdministrativeRequests';
        
        // Remove old entries ... not needed
        /*$db->query('DELETE FROM ' . $tbl . '
            WHERE type = ' . $db->escape($k) . '
            AND (visibility = ' . $db->escape('visible') . '
                OR visibility = ' . $db->escape('hidden') . ' AND ' . $db->escape($privateOK) . '
                OR visibility = ' . $db->escape('admin-hidden') . ' AND ' . $db->escape($currentUser->isAtLeast('admin')) . ')
            AND id = ' . $db->escape($this->contact['id']));
         */  
        
        if (count($v) <= 0)
            continue;
            
        $db->query('SHOW COLUMNS FROM ' . $tbl);
        
        $cols = array();
        
        while ($r = $db->next())
            $cols[] = $r['Field'];
        
        $queryContent = 'VALUES ';

        $db->free();
        
        $queryContent .= '(';
        foreach ($cols as $c) {
            if($c=='dateAdded')
                $queryContent .= 'NOW(),';
            else if($c=='dateProcessed' || $c=='requestId' || $c=='whoProcessedId')
                $queryContent .= 'NULL,';
            else
                $queryContent .= $db->escape($v[$c]) . ',';
        }
        $queryContent = mb_substr($queryContent,0,-1) . ')';
                
        $sql = 'INSERT INTO ' . $tbl . ' (' . implode(',',$cols) . ') ' . $queryContent;
        $db->query($sql);

        // Remove overhead
        $db->query('OPTIMIZE TABLE ' . $tbl);
    }
}
?>