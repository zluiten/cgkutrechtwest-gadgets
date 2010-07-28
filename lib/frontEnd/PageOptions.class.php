<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link PageOptions}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */                
require_once('Page.class.php');
require_once('Navigation.class.php');
require_once('DB.class.php');
require_once('ErrorHandler.class.php');
require_once('TableEditor.class.php');
require_once('HTMLHelper.class.php');

/**
* the options
* 
* the options page allows users to change their personal options
* it also allows administrators to change and view options of other users
* @package frontEnd
* @subpackage pages
*/
class PageOptions extends Page {
    
    /**
    * @var Navigation the navigation of the option panel
    */
    var $nav;
    
    /**
    * @var User user for which to display the panel
    */
    var $user;
    
    /**
    * Constructor
    * 
    * init superclass, create navigation, init {@link $user}
    */
function PageOptions($user) {
        
        $this->Page('Options');
        $this->nav = new Navigation('options-menu');
        
        $this->nav->addEntry('return','return',Navigation::previousPageUrl());
        
        $this->user = $user;
        
    }
    
    /**
    * create the content of the options page
    * @return string html-content
    * @global ErrorHandler used for error handling
    * @uses createPasswordForm()
    * @uses createEmailForm()
    * @uses createOptionsForm()
    */
function innerCreate() {
        
        global $errorHandler;
        
        $cont = '<div class="options">';
        
        $cont .= $this->nav->create();

        $box = '';
        
        $rightsManager = RightsManager::getSingleton();
        if(!$rightsManager->isSelf($this->user))
            $box .= '<div class="options-title">Edit the options of ' . $this->user->contact['lastname'] . ', ' . $this->user->contact['firstname'] . '</div>';
        else
            $box .= '<div class="options-title">Edit your options</div>';
        
        $box .= '<div class="options-box">';

        if($rightsManager->currentUserIsAllowedTo('edit-options-password',$this->user))
            $box .= $this->createPasswordForm();
        
        if($rightsManager->currentUserIsAllowedTo('edit-options-regemail',$this->user))
            $box .= $this->createEmailForm();
        
        if($rightsManager->currentUserIsAllowedTo('edit-options-useroptions',$this->user))
            $box .= $this->createOptionsForm();
        
        $box .= '</div>';
        
        return $cont . HTMLHelper::createNestedDivBoxModel('options-content',$box) . '</div>';
        
    }
    
    /**
    * create the form needed to change password
    * @return string html-content
    */
function createPasswordForm() {
    
        $cont = '<form action="'.$_SERVER['PHP_SELF'].'?mode=options-password' . ($this->user!==null?'&amp;userid=' . $this->user->id:'') . '" method="post">';
        $cont .= '<fieldset class="options-password">';
        $cont .= '<legend>Change password</legend>';
        
        $cont .= '<div><label for="oldPassword">Old password</label><input type="password" name="oldPassword" id="oldPassword" /></div>';
        $cont .= '<div><label for="newPassword1">New password</label><input type="password" name="newPassword1" id="newPassword1" /></div>';
        $cont .= '<div><label for="newPassword2">Repeat</label><input type="password" name="newPassword2" id="newPassword2" /></div>';
        $cont .= '<div><button type="submit">change password</button></div>';
        
        $cont .= '</fieldset>';
        $cont .= '</form>';
        
        return $cont;
    
    }
    
    /**
    * create the form needed to change email
    * @return string html-content
    */
function createEmailForm() {
    
        $cont = '<form action="'.$_SERVER['PHP_SELF'].'?mode=options-email' . ($this->user!==null?'&amp;userid=' . $this->user->id:'') . '" method="post">';
        $cont .= '<fieldset class="options-email">';
        $cont .= '<legend>Change e-mail address</legend>';
        
        $cont .= '<div><label for="email">New e-mail address</label><input type="text" name="email" id="email" size="50"/></div>';
        $cont .= '<div><button type="submit">change email</button></div>';
        
        $cont .= '</fieldset>';
        $cont .= '</form>';
        
        return $cont;
    
    }
    
    /**
    * create the personal options options form, using the {@link TableEditor}
    * 
    * {@link TableEditor} is able to handle saving of the data himself
    * @uses TableEditor
    * @return string html-content
    * @global array country acronyms and names
    * @global DB used for database access
    */
function createOptionsForm() {
    
        global $country,$db;
        
        $cont = '<fieldset class="options-options">';
        $cont .= '<legend>Change your options</legend>';
        
        $tEdit = new TableEditor($db,TABLE_USERS,'userid',
            array(
                'id' => 'hidden',
                'usertype' => 'hidden',
                'password' => 'hidden',
                'reg_email' => 'hidden',
                'confirm_hash' => 'hidden',
                'lastLogin' => 'hidden',
                'limitEntries' => 'text-5',
                'bdayDisplay' => array (
                    'NULL' => 'default',
                    '0' => 'no',
                    '1' => 'yes'
                ),
                'bdayInterval' => 'text-3',
                'telURI' => 'text-30',  // NEEDS DB CHANGE 3.0.5 to appear
                'faxURI' => 'text-30',
                'language' => array_merge(
                                          array('NULL' => 'autodetect'),
                                          Localizer::getSingleton()->availableLanguages()
                                          ),
                'useMailScript' => 'hidden', // not supported in 3.0 (see mailto.php) == Users can select this even if admin says no!
                'failedLogins' => 'hidden', //
                'lastRemoteIP' => 'hidden' //
                /*'useMailScript' => array(
                    'NULL' => 'default',
                    '0' => 'no',
                    '1' => 'yes'
                )*/
            ),
            array(
                'limitEntries' => 'Main list: limit entries per page',
                'bdayInterval' => 'Main list: display dates and recently changed contacts n days back',
                'bdayDisplay' => 'Main list: display dates',
                'telURI' => 'URI: Replace tel: (e.g. skype:$?call)',
                'faxURI' => 'URI: Replace fax: (e.g. sip:$@sip.com:5060)',
                'language' => 'User interface language',
                'useMailScript' => 'Users can send email with a web interface from the server (not supported in 3.0)'
            ),
            'SELECT * FROM ' . TABLE_USERS . ' WHERE userid = ' . ($this->user!==null?$db->escape($this->user->id):$_SESSION['user']->id),
            'text',
            true,
            ($this->user!==null?'userid=' . $this->user->id:'')
        );
        
        $cont .= $tEdit->create('','');
        
        $cont .= '</fieldset>';
        
        return $cont;
    }

function postPassword($eUser)
    {
        global $errorHandler;
        
        if (!isset($_POST['oldPassword'],$_POST['newPassword1'],$_POST['newPassword2']) || $_POST['newPassword1'] != $_POST['newPassword2'])
        {
            $errorHandler->error('formVal','New passwords do not match.',basename($_SERVER['SCRIPT_NAME']));
            return;
        }
        
        if (($_SESSION['user']->isAtLeast('admin') && $eUser->id != $_SESSION['user']->id) || $eUser->login($_POST['oldPassword']))
        {
            $eUser->setPassword(StringHelper::cleanGPC($_POST['newPassword1']));
            $errorHandler->error('ok',"Password successfully changed.");
        }
        else 
        {
            $errorHandler->clear('login');
            $errorHandler->error('formVal','Incorrect old password.',basename($_SERVER['SCRIPT_NAME']));
        }
    }
    
function postEmail($eUser)
    {
        global $errorHandler;
        
        if (isset($_POST['email'])) {
            $eUser->setEmail(StringHelper::cleanGPC($_POST['email']));
            if (($err = $errorHandler->getLastError('register')) || ($err = $errorHandler->getLastError('mail')))
                break;

            if ($eUser->id == $_SESSION['user']->id) {
                $_SESSION['user'] = null;
                header('Location:'.Navigation::mainPageUrl());
            }
        }
    }
}
    
?>
