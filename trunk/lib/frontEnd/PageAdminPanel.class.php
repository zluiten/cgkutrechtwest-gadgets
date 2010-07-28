<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageAdminPanel}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');
require_once('Navigation.class.php');
require_once('TableEditor.class.php');
require_once('TableGenerator.class.php');
require_once('EmailHelper.class.php');
require_once('HTMLHelper.class.php');

/**
* the admin panel page
* 
* the admin panel allows administrators to manage users,
* install, activate, de-activate plugins and to
* adjust global options of TAB
* @package frontEnd
* @subpackage pages
*/
class PageAdminPanel extends Page {

    /**
    * @var Navigation the navigation of the admin panel
    */
    var $nav;
    
    var $htmlId; // private counter
    
    /**
    * Constructor
    * 
    * init superclass, create navigation
    */
function PageAdminPanel() 
{
        // MANDATORY SECURITY CHECK IN CONSTRUCTOR OF EACH PAGE
        $rightsManager = RightsManager::getSingleton();
        if(!$rightsManager->currentUserIsAllowedTo('administrate'))
            ErrorHandler::getSingleton()->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
        
        $this->Page('Admin Panel');
        
        $this->nav = new Navigation('admin-menu');
        $this->nav->addEntry('return','return',Navigation::mainPageUrl());
    }

    /**
    * create the content of admin panel
    * @return string html-content
    * @global ErrorHandler used for error handling
    * @uses createPluginForm()
    * @uses createUserManagementForm()
    * @uses createOptionsForm()
    * @uses createBackupDatabaseForm()
    */  
function innerCreate() {
                
        $cont = '<div class="admin-panel">';                         
        $cont .= $this->nav->create();

        $box = '<div class="admin-content">';                         
        $box .= '<div class="admin-title">Administration Options</div>';
        $box .= '<div class="admin-box">';
        $box .= $this->createUserManagementForm();
        $box .= $this->createPluginForm();
        $box .= $this->createBackupDatabaseForm();
        $box .= $this->createOptionsForm();
        $box .= $this->createGroupTableEditor();
        $box .= '</div></div>';
                      
        return $cont . HTMLHelper::createNestedDivBoxModel('admin-content',$box) .'</div>';
    }
    
    /**
    * create the content of the backup database panel
    * @return string html-content
    */
function createBackupDatabaseForm() {
        
        $cont = '<fieldset class="options-options">';
        
        $cont .= '<legend>Backup Database</legend>';
        
        $cont .= '<form action="backup.php" method="get">';
        
        $cont .= '<div>Page-ContextHelp-admin-db</div>';
        
        $cont .= HTMLHelper::createButton('Backup Database');
        
        $cont .= '</form>';
        
        $cont .= '</fieldset>';
        
        return $cont;
        
    }
    
    /**
    * create the form needed to manage the plugins
    * @return string html-content
    * @global DB used for database access
    */
function createPluginForm() {
        
        global $db;
        
        $cont = '<fieldset class="options-options">';
        
        $cont .= '<legend>Plugins</legend>';
        
        $db->query('SELECT * FROM ' . TABLE_PLUGINS . ' ORDER BY name ASC');
        
        $data = array();
        
        while ($r = $db->next()) {
            
            $classmethods = get_class_methods($r['name']);

            $version = '';
            if (in_array('version',$classmethods))
                $version = eval('return ' . $r['name'] . '::version();');
            
            $link = '';
            if ($r['state'] == 'not installed')
                $link = '<a href="../admin/adminPanel.php?mode=install&amp;plugin=' . $r['name'] . '">install</a>';
            else {
                if (intval(mb_substr(PHP_VERSION,0,1)) >= 5 && in_array('uninstallPlugin',$classmethods) || in_array('uninstallplugin',$classmethods))
                    $link = '<a href="javascript:uninstallPlugin(\'' . $r['name'] . '\')">uninstall</a>';
                
                if ($r['state'] == 'activated')
                    $link .= ' <a href="../admin/adminPanel.php?mode=deactivate&amp;plugin=' . $r['name'] . '">deactivate</a>';
                else
                    $link .= ' <a href="../admin/adminPanel.php?mode=activate&amp;plugin=' . $r['name'] . '">activate</a>';
                
                // only show upgrade option if class provides an upgrade method
                if (in_array('upgradeplugin',$classmethods) && $r['version'] < $version)
                    $link .= ' <a href="../admin/adminPanel.php?mode=upgrade&amp;plugin=' . $r['name'] . '">upgrade</a>';
            }
                            
            // the help method returns a link to help
            $help = '';
            if (in_array('help',$classmethods))
                $help .= eval('return ' . $r['name'] . '::help();');
            
            $data[] = array(
                $r['name'],
                $version,
                $r['state'],
                $link,
                $help
            );
            
        }
        
        $tGen = new TableGenerator('admin-plugins', array('Name','Version','Status','','Help'));
        $cont .= $tGen->generateTable($data);
        
        $cont .= '</fieldset>';
        
        return $cont;
        
    }
    
    /**
    * create the form needed for user management
    * @return string html-content
    * @global DB used for database access
    * @global CONFIG_USER_ACCOUNT_EXPIRED_MAIL email subject and content (mailto:) for expired account message
    * @global CONFIG_USER_ACCOUNT_EXPIRED_INTERVAL SQL interval for DATE_ADD e.g. "1 MONTH" to check account expiration
    */
function createUserManagementForm() {
        
        global $db, $CONFIG_USER_ACCOUNT_EXPIRED_MAIL, $CONFIG_USER_ACCOUNT_EXPIRED_INTERVAL;
        
        $cont = '<fieldset class="options-options">';
        $cont .= '<legend>User list</legend>';
        
        if(empty($CONFIG_USER_ACCOUNT_EXPIRED_INTERVAL)) $CONFIG_USER_ACCOUNT_EXPIRED_INTERVAL='1 MONTH';
        $db->query("SELECT *, DATE_ADD(lastLogin, INTERVAL $CONFIG_USER_ACCOUNT_EXPIRED_INTERVAL)<NOW() AS expiredLogin FROM " . TABLE_USERS . ' AS users LEFT JOIN ' . TABLE_CONTACT . ' AS contact ON users.id = contact.id ORDER BY usertype');
        
        $data = array();
        
        // collect user types in array for mailto link
        $users = array();
        $managers = array();
        $admins = array();
        
        while ($r = $db->next()) {
            $email = $r['firstname'] . ' ' . $r['lastname'] . ' <' . $r['reg_email'] . '>';
            
            $baseHref = '../contact/contact.php?id=' . $r['id'] . '&noxslt=1'; // no stylesheet display
            $editHref = $baseHref . '&mode=edit';
            
            if (isset($r['lastname']))
                $name = '<a href="' . $baseHref . '">' . $r['lastname'] . ', ' . $r['firstname'] . '</a>&nbsp;(<a href="' . $editHref . '">edit</a>, ' . EmailHelper::sendEmailLink($email,$r['reg_email']) . ')';
            else
                $name = EmailHelper::sendEmailLink($email,$email);
            
            $data[] = array(
                'name' => $name,
                'lastLogin' => $r['lastLogin'],
                'loginExpired' => $r['expiredLogin'] ? "<a href='mailto:$email$CONFIG_USER_ACCOUNT_EXPIRED_MAIL'>[!]</a>" : '',
                'type' => $r['usertype'],
                'delete' => '<a href="javascript:deleteUser(' . $r['userid'] . ')">delete</a>'
            );
            
            switch($r['usertype'])
            {
                case 'admin'  : $admins[]   = $email; break;
                case 'manager': $managers[] = $email; break;
                case 'user'   : $users[]    = $email; break;
            }
        }
        
        $tableGen = new TableGenerator('admin-users');
        $cont .= $tableGen->generateTable($data,null,'','type',false); //group by type
        $cont .= '<div><br> Send email to: ' . EmailHelper::sendEmailLink($admins,'administrators') . '&nbsp;&nbsp;&nbsp;&nbsp;';
        $cont .= EmailHelper::sendEmailLink($managers,'managers') . '&nbsp;&nbsp;&nbsp;&nbsp;';
        $cont .= EmailHelper::sendEmailLink($users,'users') . '&nbsp;&nbsp;&nbsp;&nbsp;';
        $cont .= EmailHelper::sendEmailLink(array_merge($managers,$users),'users and managers') . '&nbsp;&nbsp;&nbsp;&nbsp;';
        $cont .= EmailHelper::sendEmailLink(array_merge($admins,$managers,$users),'all') . '&nbsp;&nbsp;&nbsp;&nbsp; </div>';
        $cont .= '</fieldset>';
                
        return $cont;
        
    }
    
    /**
    * create the options form, using the {@link TableEditor}
    * 
    * {@link TableEditor} is able to handle saving of the data himself
    * @uses TableEditor
    * @return string html-content
    * @global array country acronyms and names
    * @global DB used for database access
    */
function createOptionsForm() {
        
        global $country,$db,$CONFIG_INSTALL_SUBDIR;
        
        $x = PageAdminPanel::scanSubdirectories($CONFIG_INSTALL_SUBDIR.'styles');
        
        $styles = array();
        foreach($x as $q)
            $styles[$q] = $q;

        $cont = '<fieldset class="options-options">';
        $cont .= '<legend>Change Global Options</legend>';
        
        $tEdit = new TableEditor($db,TABLE_OPTIONS,'optID',
                array(
                'optID' => 'hidden',
                'interfaceStyle' => $styles,
                'requireLogin' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'msgLogin' => 'textarea-4-30',
                'allowUserReg' => array(
                    'no' => 'no',
                    'everyone' => 'allow for everyone',
                    'contactOnly' => 'only allow for contacts',
                    'contactOnlyNoConfirm' => 'only allow for contacts, no confirmation'
                ),
                'msgWelcome' => 'textarea-4-30',
                'defaultGroup' => 'text-30',
                'limitEntries' => 'text-5',
                'autocompleteLimit' => 'text-3',
                'bdayDisplay' => array (
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'recentlyChangedDisplay' => array (
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'recentlyChangedLimit' => 'text-3',
                'bdayInterval' => 'text-3',
                'picAlwaysDisplay' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'picWidth' => 'text-5',
                'picHeight' => 'text-5',
                'deleteTrashMode' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'picAllowUpload' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'picCrop' => array(
                    '0' => 'Resize',
                    '1' => 'Resize and Crop',
                    '2' => 'Resize and Fit'
                ),
                'picForceWidth' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'picForceHeight' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'countryDefault' => $country,
                'language' => array_merge(
                                          array('NULL' => 'autodetect'),
                                          Localizer::getSingleton()->availableLanguages()
                                          ),
                'eMailAdmin' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'adminEmailSubject' => 'text-40',
                'adminEmailFooter' => 'textarea-4-30',
                'administrativeLock' => array(
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'telURI' => 'hidden',  // is only a user option
                'faxURI' => 'hidden',  // is only a user option
                'TABversion' => 'hidden',
                'useMailScript' => array(
                    '0' => 'no',
                    '1' => 'yes'
                )
            ),
            array(
                'interfaceStyle' => 'User interface: CSS style',
                'bdayDisplay' => 'Main list: display dates',
                'recentlyChangedDisplay' => 'Main list: display recently changed contacts',
                'recentlyChangedLimit' => 'Main list: maximum number for recently changed contacts and dates',
                'bdayInterval' => 'Main list: display dates/recently changed contacts n days forward/backward',
                'limitEntries' => 'Main list: limit entries per page',
                'autocompleteLimit' => 'Main list: maximum number of autocomplete search results',
                'msgWelcome' => 'Main list: welcome message',
                'defaultGroup' => 'Main list: default group',
                'picAlwaysDisplay' => 'Contact: always display picture',
                'picWidth' => 'Contact: picture width',
                'picHeight' => 'Contact: picture height',
                'picForceWidth' => 'Contact: force picture width by html',
                'picForceHeight' => 'Contact: force picture height by html',
                'deleteTrashMode' => 'Edit: trash mode (only admin deletes contacts permanently)',
                'countryDefault' => 'Edit: default country',
                'picAllowUpload' => 'Edit: allow picture upload',
                'picCrop' => 'Edit: picture clip mode',
                'picDupeMode' => 'Edit: duplicate picture mode',
                'msgLogin' => 'Login: login message',
                'allowUserReg' => 'Login: allow self registration',
                'requireLogin' => 'Login: require login to access contacts',
                'eMailAdmin' => 'Login: BCC email to all admins upon self registration (admin can confirm)',
                'useMailScript' => 'Users can send email with a web interface from the server (feature will be dropped if not in demand - post to discussion forums if you need this)',
                'language' => 'User interface language',
                'adminEmailSubject' => 'ADMIN: email subject',
                'adminEmailFooter' => 'ADMIN: email signature',
                'administrativeLock' => 'ADMIN: lock modifications (edit and delete)'
            ),
            null,
            'text',
            true
        );
                
        $cont .= $tEdit->create('','');
        
        $cont .= '</fieldset>';
        
        return $cont;
    
    }
    
    /**
    * create the groups editor to change group names {@link TableEditor}
    * 
    * {@link TableEditor} is able to handle saving of the data himself
    * @uses TableEditor
    * @return string html-content
    * @global array country acronyms and names
    * @global DB used for database access
    */
function createGroupTableEditor() {
        
        global $country,$db;
        
        $cont = '<fieldset class="options-options">';
        $cont .= '<legend>Change Group Names</legend>';
        
        $cont .= $this->contextHelp('admin-groups');        

        $cont .= '<div class="options-clear">';
        $tEdit = new TableEditor($db,TABLE_GROUPLIST,'groupid',
                    array('groupname' => 'text-40','acronym' => 'text-5','logoURL' => 'text-20','groupid' => 'hidden'),
                    array('groupname' => 'Group Name', 'acronym' => 'Acronym', 'logoURL' => 'Logo URL (future use)')
                );
                
        $cont .= $tEdit->create('','');
        
        $cont .= '</div></fieldset>';
        
        return $cont;
    
    }

    /**
    * scans a directory for subdirectories (used to find available styles)
    *
    * @param string $dir the directory to scan
    * @param string $ext the extension of the files wanted
    * @return array list of subdirectories without path
    * @static
    */
function scanSubdirectories($dir)
    {
        $dh  = opendir($dir);
        while (false !== ($filename = readdir($dh))) 
            if($filename!='.' && $filename!='..' && is_dir($dir . '/' . $filename))
                $files[] = $filename;
                
        return $files;
    }
}


?>
