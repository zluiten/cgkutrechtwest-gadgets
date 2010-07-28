<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link RightsManager}
* @package utilities
* @author Tobias Schlatter, Thomas Katzlberger
*/

/** */
require_once('User.class.php');
require_once('Contact.class.php');
require_once('ErrorHandler.class.php');

/**
* Singleton class to manage the rights of a user. 
* Usage: $rightsManager = RightsManager::getSingleton(); $rightsManager->methodXYZ(...)
* @package utilities
*/
class RightsManager {
  
    /**
    * @var User currentUser ... mostly $_SESSION['user']
    */
    var $currentUser;
    
    /**
    * @var array of 'right' => 'return true or false; for currentUserIsAllowedTo()'
    * each value contains a function that returns true or false.
    * the function must be global or static
    * $this is RightsManager, $this->currentUser the User object, 
    * $target whatever was passed to currentUserIsAllowedTo()
    * Example: 'administrate' => 'return $this->currentUser->isAtLeast('admin');',
    */
    var $rights;

    /**
    * Static method to get the only instance of this object!
    * Calls: 1. RightsManager($_SESSION['user']) 2. setUser($_SESSION['user']) 3. Guarantees that $currentUser is set or throws standardError('NOT_LOGGED_IN'
    * @static
    */
function getSingleton()
    {
        static $rightsManager,$errorHandler;
        
        if(!isset($rightsManager))
            $rightsManager = new RightsManager(isset($_SESSION['user']) ? $_SESSION['user'] : null);
            
        return $rightsManager;
    }
    
    /** Calls setUser($_SESSION['user']); Installs $CONFIG_RIGHTS_MANAGER rights from config file.
    * @global array $CONFIG_RIGHTS_MANAGER
    * @param User $currentUser user that wants to do something. Called by getSingleton(): RightsManager($_SESSION['user'])
    */
function RightsManager($currentUser)
    {
        $this->setUser($currentUser);
        
        $this->rights = array( 
            // user
            'view' => 'return $this->mayViewContact($target);',
            'view-list' => 'return $this->currentUser->isAtLeast("guest");',
            'view-private' => 'return $this->mayViewPrivateInfo($target);',
            'create' => 'return $this->currentUser->isAtLeast("user");',
            'edit' => 'return $this->mayEditContact($target);',
            'delete' => 'return $this->mayDeleteContact($target);',
            'edit-options' => 'return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);', // target is a User object (inherits Contact)
            // allow/disallow parts of the user-options panel (will be hidden if not allowed)
            'edit-options-password' => 'return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);',
            'edit-options-regemail' => 'return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);',
            'edit-options-useroptions' => 'return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);',
            
            'edit-self'    => 'return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);',
            // manager
            'manage' => 'return $this->currentUser->isAtLeast("manager");',
            // admin
            'administrate' => 'return $this->currentUser->isAtLeast("admin");',
            'phpinfo' => 'return $this->currentUser->isAtLeast("admin");',
            'backup' => 'return $this->currentUser->isAtLeast("admin");'
        );
        
        // copy preconfigured rights if applicable
        global $CONFIG_RIGHTS_MANAGER;
        if(isset($CONFIG_RIGHTS_MANAGER))
            foreach($CONFIG_RIGHTS_MANAGER as $k => $v)
                $this->addRight($k,$v);
    }
    
    /**
    * Set a user for all future checks (called by RightsManager() & getSingleton() may be used to reset user in special circumstances)
    * @param User $currentUser user that wants to do something
    */
function setUser($currentUser) {
        global $errorHandler;
        
        if(!isset($currentUser) || $currentUser == NULL) // die miserably
        {
            if($errorHandler)
                $errorHandler->standardError('NOT_LOGGED_IN',get_class($this)); // redirects to login.php
            else
                echo "<h1>PERMISSION DENIED: NOT LOGGED IN</h1>";
            
            exit(100);
        }
        
        $this->currentUser = $currentUser;
    }

    /**
    * Static method to get only instance of this object!
    * @return User the current user (mostly $_SESSION['user'])
    */
function getUser()
    {
        return $this->currentUser;
    }
    
    /**
    * Adds a new right or <b>replaces</b> an existing right. No verification. To change rights in the config file use: $CONFIG_RIGHTS_MANAGER.
    * Predefined names: 'view','view-list','view-private','create','edit','delete','options','manage','administrate','phpinfo','backup'
    * Example reconfiguration: A normal user may only view and edit the own contact and not see the list:
        $rightsManager->addRight('view','return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);');
        $rightsManager->addRight('edit','return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);');
        $rightsManager->addRight('view-list','return $this->currentUser->isAtLeast("manager");');
    * Example reconfiguration from config file: A normal user may only view and edit the own contact and not see the list:
        $CONFIG_RIGHTS_MANAGER['view'] = 'return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);';
        $CONFIG_RIGHTS_MANAGER['edit'] = 'return $this->currentUser->isAtLeast("admin") || $this->isSelf($target);';
        $CONFIG_RIGHTS_MANAGER['view-list'] = 'return $this->currentUser->isAtLeast("manager");';
    * @param string name of right or name of right to replace
    * @param string php-code to verify the right (eg: 'return $this->currentUser->isAtLeast("admin");)
    */
function addRight($name, $right)
    {
        $this->rights[$name] = $right;
    }
    
    /**
    * Check whether the currentUser may do something. 
    * This will allow a more fine grained access control in future.
    * @param string | array $rights one of or array of: administrate, manage, view, view-private, edit, delete
    * @param Contact | User $target (for view, edit, delete, options);  default null
    */
function currentUserIsAllowedTo($requestedPermissions,$target=null)
    {
        if(is_array($requestedPermissions))
        {
            foreach($requestedPermissions as $r)
                if($this->currentUserIsAllowedTo($r,$target)==false) // RECURSION!
                    return false;
                    
            return true;
        }
                
        if(!isset($this->rights[$requestedPermissions]))
        {
            global $errorHandler;
            $errorHandler->error('invArg','Invalid argument passed to RightsManager::currentUserIsAllowedTo: '.$right,get_class($this));        
        }
        
        $ok = eval($this->rights[$requestedPermissions]);
        
        /* DEBUG & TESTING:
        if($ok)
            echo 'RightsManager::currentUserIsAllowedTo: ' . $requestedPermissions . ' ' . $this->rights[$requestedPermissions] . ', '; */
        
        return $ok;
    }

    /**
    * check whether the currentUser may view a contact
    * If a user's contact is hidden a user can login but not view own data.
    * @param Contact $contact contact that should be shown
    * @deprecated use currentUserIsAllowedTo('view',$contact)
    */
function mayViewContact(&$contact) {
        return $this->currentUser->isAtLeast('admin') || (isset($contact->contact['hidden']) && false == $contact->contact['hidden']);
    }
    
    /**
    * check whether a user may edit a contact
    * @param Contact $contact contact that should be edited
    * @deprecated use currentUserIsAllowedTo('edit',$contact)
    */
function mayEditContact(&$contact) {
        return
            ($this->currentUser->isAtLeast('manager') ||
            $this->currentUser->id == $contact->isUser() ||
            $this->currentUser->id == $contact->contact['whoAdded']) &&
            $this->currentUser->isAtLeast('user') &&
            $this->mayViewContact($contact);
    }
    
    /**
    * check whether a user may delete a contact
    * @param Contact $contact contact that should be deleted
    * @deprecated use currentUserIsAllowedTo('delete',$contact)
    */
function mayDeleteContact(&$contact) {
        return $this->mayEditContact($contact);
    }

    /**
    * check whether $contact == $currentUser OR $_SERVER['SSL_CLIENT_S_DN_Email'] matches the current contact if $CONFIG_SSL_CLIENT_AUTHENTICATION==true
    * @param Contact $contact contact of which the private info should be showed
    */
function isSelf(&$contact) {
        global $CONFIG_SSL_CLIENT_AUTHENTICATION;        
        return (isset($contact->contact['id']) && $this->currentUser->contact['id'] == $contact->contact['id'] && !$contact->contact['hidden']) ||
               (isset($_SERVER['SSL_CLIENT_S_DN_Email']) && isset($CONFIG_SSL_CLIENT_AUTHENTICATION) && $CONFIG_SSL_CLIENT_AUTHENTICATION==true && $contact->hasEmail($_SERVER['SSL_CLIENT_S_DN_Email']));
    }
    
    /**
    * check whether a user may view private info of a contact includes check for $_SERVER['SSL_CLIENT_S_DN_Email'] if $CONFIG_SSL_CLIENT_AUTHENTICATION==true
    * @param Contact $contact contact of which the private info should be shown
    * @deprecated use currentUserIsAllowedTo('view-private',$contact)
    */
function mayViewPrivateInfo(&$contact) {
        global $CONFIG_SSL_CLIENT_AUTHENTICATION;        
        return $this->currentUser->isAtLeast('admin') ||
            (isset($contact->contact['id']) && $this->currentUser->contact['id'] == $contact->contact['id'] && !$contact->contact['hidden']) ||
            (isset($_SERVER['SSL_CLIENT_S_DN_Email']) && isset($CONFIG_SSL_CLIENT_AUTHENTICATION) && $CONFIG_SSL_CLIENT_AUTHENTICATION==true && $contact->hasEmail($_SERVER['SSL_CLIENT_S_DN_Email']));
    }
    
}

// defend against register_globals
$rightsManager = NULL;

?>
