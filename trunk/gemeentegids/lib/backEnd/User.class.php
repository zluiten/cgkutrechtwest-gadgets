<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link User}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
//require_once('StringHelper.class.php');
//require_once('DB.class.php');
//require_once('ErrorHandler.class.php');
//require_once('PluginManager.class.php');
require_once('Contact.class.php');

/**
* Represents a user
*
* Handles all database-communication, in order to retrieve and save user-data
* @package backEnd
*/
class User extends Contact {
    
    /**
    * @var integer id of the user. This is the id of the user record, not the contact record $this->contact['id']!
    */
    var $id;
    
    /**
    * one of: admin, manager, user, guest, register
    * @var string type of the user
    */
    var $type;
    
    /**
    * @var boolean is user logged in
    */
    var $loggedIn = false;
    
    /**
    * Constructor
    *
    * If {@link $pw} is not given, this loads a user from database
    * if {@link $pw} is given, this creates a new user
    * @param string $email e-mail of user to load/create OR id (numeric)
    * @param string $password of user to create
    * @param boolean $sendEmail should a confirmation e-mail be sent?
    * @global DB used for database connection
    * @global ErrorHandler used for error handling
    * @global PluginManager used for plugin hooks
    */
function User($email,$pw='',$sendEmail=true) {
        
        global $db, $options, $errorHandler, $pluginManager;
        
        $this->Contact(array());
        
        $this->loggedIn = false;
        
        if (is_numeric ($email) || is_array($email)) {
            
            if (is_numeric ($email)) {
                $db->query('SELECT *, users.id AS id, contact.id AS has
                    FROM ' . TABLE_USERS . ' AS users LEFT JOIN ' . TABLE_CONTACT . ' AS contact
                    ON users.id = contact.id
                    WHERE userid = ' . $db->escape($email));
                $r = $db->next();
                
                if (!$r)
                {
                    $errorHandler->error('argVal','There is no such user id.',get_class($this));
                    return;
                }
                
            } else {
                $r = $email;
            }
            
            $this->id = $r['userid'];
            $this->type = $r['usertype'];
            
            if ($r['id'] !== NULL && is_numeric($r['id']))
                $this->load($r['id']);
            
            return;
        }
        
        if (!StringHelper::isEmail($email)) {
            $errorHandler->error('login','This is not an e-mail address.',get_class($this));
            return false;
        }
        
        if ($pw) {
            
            if (User::getUserFromEmail($email)) {
                $errorHandler->error('register','A user with this e-mail address already exists.',get_class($this));
                return false;
            }
            
            if (Contact::getContactFromEmail($email,$__) > 1) {
                $errorHandler->error('register','Multiple contacts with this e-mail address exist.',get_class($this));
                return false;
            }
            
            $hash = md5(time() . $email);
            
            $db->query('INSERT INTO ' . TABLE_USERS . ' (reg_email,confirm_hash,password,usertype,lastLogin)
                VALUES (' . $db->escape($email) . ',
                    ' . $db->escape($hash) . ',
                    MD5(' . $db->escape($pw) . '),
                    ' . $db->escape('register') . ',NOW())');
            
            $this->id = $db->insertID();
            
            $this->type = 'register';
            $this->loggedIn = true;
            
            if ($sendEmail)
                $this->sendConfirmationEMail($email,$hash,$options->getOption('eMailAdmin'));
            
            $pluginManager->changedUserRecord($this, 'added');
            
            return;
            
        }
        
        $r = User::getUserFromEmail($email);
        
        if (!$r) {
            $errorHandler->error('login','<span>The user does not exist:</span> '.$email,get_class($this));
            return false;
        }
        
        if ($r['lastModification'] == 'deleted') {
            $errorHandler->error('login','<span>This user has been deleted:</span> '.$email,get_class($this));
            return false;
        }
        
        $this->id = $r['userid'];
        if ($r['confirm_hash']!==NULL || $r['id']===NULL) {
            if ($r['confirm_hash']!==NULL)
                $errorHandler->error('login','This user hasn\'t been confirmed yet<br /><a href="../user/register.php?mode=resend&amp;email=' . htmlentities(rawurlencode($email)) . '">resend confirmation mail</a>',get_class($this));
            $this->type = 'register';
            return false;
        } else
            $this->type = $r['usertype'];
            
        if ($r['id']!==NULL)
            $this->load($r['id']);
        
    }
    
    /**
    * deletes the user from database
    * @global DB used for database connection
    * @global PluginManager used for plugin hooks
    */
function delete() {
        global $db, $pluginManager;
        
        $pluginManager->changedUserRecord($this,'deleted');
        
        $db->query('DELETE FROM ' . TABLE_USERS . ' WHERE userid = ' . $db->escape($this->id));
        $this->id = null;
        $this->type = null;
        $this->loggedIn = null;
        $this->contact = null;
    }
    
    /**
    * logs user in
    *
    * This method checks, if the given password is correct and then
    * sets {@link $loggedIn} to true or raises an error
    * Not called if logged in by SSL certificate (see init.php)!
    * @param string $pw password of user
    * @global DB used for database connection
    * @global ErrorHandler used for error handling
    */
function login($pw) {
        
        global $db, $errorHandler, $CONFIG_TAB_SERVER_ROOT;
        
        $db->query('SELECT * FROM ' . TABLE_USERS . '
            WHERE userid = ' . $db->escape($this->id) . '
            AND password = MD5(' . $db->escape($pw) . ')');
        
        // record remote address
        $raddr = $_SERVER['REMOTE_ADDR'];
        
        // we have found one user with correct password
        if ($db->rowsAffected() == 1) {
            $r = $db->next();
            if($r['failedLogins']>5) { // we reject login of user with correct password, user needs new password, do NOT increment counter
                $errorHandler->error('login','Too many failed login attempts. Login denied. Please <a href="'.$CONFIG_TAB_SERVER_ROOT.'user/register.php?mode=lostpasswd">request a new password</a>.',get_class($this));
                return false;
            }
            
            $this->loggedIn = true;
            $db->query('UPDATE ' . TABLE_USERS . ' SET lastLogin=NOW(), failedLogins=0, lastRemoteIP="' . $raddr .'" 
                WHERE userid = ' . $db->escape($this->id));
            return true;
        }
        else // increment failedLogin counter, record the IP if the password was incorrect
        {
            $db->query('UPDATE ' . TABLE_USERS . ' SET failedLogins=failedLogins+1, lastRemoteIP="' . $raddr .'" WHERE userid = ' . $db->escape($this->id));            
        }
        
        $errorHandler->error('login','Incorrect password. Login denied.',get_class($this));
        return false;
    } 
    
    /**
    * logs user in after creating the User object from $_SERVER['SSL_CLIENT_S_DN_Email'] if possible
    *
    * @return User object or NULL
    * @static
    */
function sslCertificateLogin()
    {
        $user = new User($_SERVER['SSL_CLIENT_S_DN_Email']);
        
        if($user->id && $user->isConfirmed())
        {
            //echo 'SSL Login: '.$user->id;
            $user->loggedIn = true;
        
            global $db;
            $db->query('UPDATE ' . TABLE_USERS . ' SET lastLogin=NOW(), failedLogins=0, lastRemoteIP="' . $_SERVER['REMOTE_ADDR'] .'" 
                WHERE userid = ' . $db->escape($user->id));
                
            return $user;
        }
        
        return NULL;
    }
    
    /**
    * returns user id
    *
    * because it is obvious, that a user is always a user, the database has not
    * to be queried to determine, wheter a user is a user or not
    * @return integer user id
    */
function isUser() {
        return $this->id;
    }
    
    /**
    * checks if a user is at least of the given type
    * @param string $type type to check
    * @return boolean is the user at least of that type?
    */
function isAtLeast($type) {
        global $errorHandler;
        
        // type should be one of admin, manager, user, guest
        
        if (!$this->loggedIn)
            return false;
        
        switch ($type) {    // do NOT add break; to this switch statement!!!
            case 'guest':
                if ($this->type == 'guest')
                    return true;
            case 'user':
                if ($this->type == 'user')
                    return true;
            case 'manager':
                if ($this->type == 'manager')
                    return true;
            case 'admin':
                if ($this->type == 'admin')
                    return true;
                
                // in any case ... (do not add breaks above!)
                return false; // this will return false (as long as $type is correct)
                
            default: // catch incorrect args like isAtLeast('administrator')
                $errorHandler->error('invArg','Incorrect argument passed to isAtLeast(' . $type .')', get_class($this));
                return false;
        }
        
    }
    
    /**
    * get type of user
    * @return string type of user
    */
function getType() {
        return $this->type;
    }
    
    /**
    * check whether user is logged in or not
    * @return boolean is user logged in
    */
function isLoggedIn() {
        return $this->loggedIn;
    }
    
    /**
    * check whether this user is confirmed or not
    * @return boolean is user confirmed
    * @global DB used for database connection
    */
function isConfirmed() {
        
        global $db;
        
        $db->query('SELECT * FROM ' . TABLE_USERS . ' AS users WHERE users.userid = ' . $db->escape($this->id));
        $r = $db->next();
        
        return ($r && $r['confirm_hash'] === null);
        
    }
    
    /**
    * sets the type of the user
    * @param string $type new type of user
    * @global DB used for database connection
    */
function setType($type) {
        
        global $db;
        
        $this->type = $type;
        $db->query('UPDATE ' . TABLE_USERS . '
            SET usertype = ' . $db->escape($type) . '
            WHERE userid = ' . $db->escape($this->id));
    }
    
    /**
    * Get the id of the associated User
    * @return int database record id of User or 0 if not exists.
    */
function getUserId()
    {
        return $this->id;
    }
    
    /**
    * Get the DB record from the user table if it esxists. Result is not cached because it is used rarely. Implemented in Contact.class.php
    * @return associative array of user or FALSE if not found
    */
function getUserRecord()
    {
        return parent::getUserRecord();
    }
    
    /**
    * sets the password of the user
    * @param string $pw password of the user
    * @global DB used for database connection
    * @global PluginManager used for plugin hooks
    */
function setPassword($pw) {
        
        global $db, $pluginManager;
        
        $db->query('UPDATE ' . TABLE_USERS . '
            SET password = MD5(' . $db->escape($pw) . '), failedLogins=0
            WHERE userid = ' . $db->escape($this->id));
        
        $pluginManager->changedUserRecord($this, 'password');
            
    }
    
    /**
    * sets the e-mail of a user and sends a confirmation e-mail, if requested
    * @param string $mail new e-mail address
    * @param boolean $sendEmail whether to send a confirmaton e-mail or not
    * @global DB used for database connection
    * @global ErrorHandler used for error handling
    * @global PluginManager used for plugin hooks
    * @global Options used for resend confirmation
    */
function setEmail($mail,$sendEmail=true) {
        
        global $db, $errorHandler, $pluginManager, $options;
        
        if (!StringHelper::isEmail($mail)) {
            $errorHandler->error('register','This is not an e-mail address.',get_class($this));
            return false;
        }
        
        $r = User::getUserFromEmail($mail);
        
        if ($r && $r['userid'] != $this->id) {
            $errorHandler->error('register','A user with this e-mail already exists.',get_class($this));
            return false;
        }
        
        $count = Contact::getContactFromEmail($mail,$__);
        
        if ($count > 1) {
            $errorHandler->error('register','Multiple contacts with this e-mail address exist.',get_class($this));
            return false;
        }
        
        if ($count < 1) {
            $errorHandler->error('register','The contact of this user does not have this e-mail.',get_class($this));
            return false;
        }
        
        $hash = md5($mail . time());
        
        $db->query('UPDATE ' . TABLE_USERS . ' SET reg_email = ' . $db->escape($mail) . ', confirm_hash = ' . $db->escape($hash) . ' WHERE userid = ' . $db->escape($this->id));
        
        $this->loggedIn = false;
        
        $pluginManager->changedUserRecord($this, 'email');
        
        if (!$sendEmail)
            return;
        
        $this->sendConfirmationEMail($mail,$hash,$options->getOption('eMailAdmin'));
        
    }
    
    /**
    * sends a confirmation e-mail
    *
    * @param string $email address to send e-mail to
    * @param string $hash hash to send with e-mail
    * @param boolean $bccAdmin send BCC to admin?
    * @global string used for the link in the e-mail
    * @global array used to configure phpmailer
    */
    
function sendConfirmationEMail($email,$hash,$bccAdmin) {
    
        global $db,$CONFIG_TAB_ROOT, $CONFIG_PHPMAILER, $options, $errorHandler;
        
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
 
        if($bccAdmin)
        {
            $db->query("SELECT * FROM " . TABLE_USERS . ' AS users, ' . TABLE_CONTACT . ' AS contact WHERE users.id = contact.id AND usertype="admin"');
            while($r=$db->next())
                $mailer->addBCC($r['reg_email']);
        }
        
        $mailer->Subject = $options->getOption('adminEmailSubject') . ' - Registration Confirmation';
        $mailer->Body    = 'This is an auto-generated message from The Address Book Reloaded at ' . $_SERVER['SERVER_NAME'] .
            ".\nTo confirm your new account, please use the following link:\n\n" .
            $CONFIG_TAB_ROOT . 'user/register.php?mode=confirm&userid=' . $this->id . '&hash=' . $hash . "\n\n" . 
            $options->getOption('adminEmailFooter');
        
        if(!$mailer->Send())
            $errorHandler->error('mail','Email sending failed: ' . $mail->ErrorInfo,get_class($this));
        else
            $errorHandler->error('ok','Mail sent!');
    }
    
    /**
    * confirm a user
    * 
    * if {@link $hash} is not set, the user is just confirmed, if {@link $hash} is
    * set, it is first checkend and then the user is confirmed
    * @param string $hash the hash needed to confirm the user
    * @return boolean returns true on success
    * @global DB used for database access
    * @global ErrorHandler used for error handling
    * @global PluginManager used for plugin hooks
    */
function confirm($hash = NULL) {
    
        global $db, $errorHandler, $pluginManager;
        
        if ($hash !== NULL) {
            
            if (!$this->loggedIn) {
                $errorHandler->error('register','You are not logged in.',get_class($this));
                return false;
            }
            
            $db->query('SELECT * FROM ' . TABLE_USERS . '
                WHERE userid = ' . $db->escape($this->id));
                
            $r = $db->next();
            
            if ($r['confirm_hash'] == NULL) {
                $this->loggedIn = false;
                $errorHandler->error('register','You are already confirmed. Please log in.',get_class($this));
                return false;
            }
            
            if ($r['confirm_hash'] != $hash) {
                $this->loggedIn = false;
                $errorHandler->error('register','You may be confirmed already. The confirmation hash is wrong.',get_class($this));
                return false;
            }
            
        }
    
        $db->query('UPDATE ' . TABLE_USERS . ' SET confirm_hash = NULL
            WHERE userid = ' . $db->escape($this->id));
    
        $pluginManager->changedUserRecord($this, 'confirmed');
            
        return true;
    
    }
    
    /**
    * attaches a contact to the user
    * 
    * searches a contact which has the same e-mail as the user. If found,
    * the contact is connected to the user, if not found, a new contact entry is created
    * with e-mail as lastname and e-mail entry
    * @global DB used for database connection
    * @global ErrorHandler used for error handling
    * @return boolean returns true, if a contact was found, false if one was created
    */
function attachContact() {

        global $db, $errorHandler;
        
        $db->query('SELECT * FROM ' . TABLE_USERS . ' AS users WHERE userid = ' . $db->escape($this->id));
        $u = $db->next();
        $db->free();
        
        $db->query('SELECT * FROM ' . TABLE_PROPERTIES . ' AS properties
            WHERE properties.type = ' . $db->escape('email') . ' AND properties.value = ' . $db->escape($u['reg_email']));
            
        if ($db->rowsAffected() >= 1) {
            $r = $db->next();
            $db->query('UPDATE ' . TABLE_USERS . ' SET id = ' . $db->escape($r['id']) . '
            WHERE userid = ' . $db->escape($this->id));
            $this->load($r['id']);
            return true;
        }

        $this->contact['lastname'] = $u['reg_email'];
        $this->contact['whoAdded'] = $this->id;
        $this->setValueGroup('email',array(array(
            'value' => $u['reg_email'],
            'label' => '',
            'visibility' => 'visible'
        )));

        $this->save(false); // attach myself
        
        $db->query('UPDATE ' . TABLE_USERS . ' SET id = ' . $db->escape($this->contact['id']) . '
            WHERE userid = ' . $db->escape($this->id));
        
        return false;

    }
    
    /**
    * Used by serialize, when serializing class
    *
    * @return array list of variables that should be saved
    */
function __sleep() {
        return array_merge(parent::__sleep(),array('id','loggedIn'));
    }
    
    /**
    * Used by serialize, when deserializing class
    * @global DB used for database access
    * @global ErrorHandler used for error handling
    */
function __wakeup() {
        
        global $db,$errorHandler;
        
        if ($this->id) {
            $db->query('SELECT * FROM ' . TABLE_USERS . ' AS users WHERE userid = ' . $db->escape($this->id));
            $r = $db->next();
            if ($r)
                if ($r['confirm_hash']!==NULL || $r['id']===NULL)
                    $this->type = 'register';
                else
                    $this->type = $r['usertype'];
            else {
                $this->loggedIn = false;
                return;
            }
        }
        
        parent::__wakeup();
        
    }
    
    /**
    * get the user which uses the passed e-mail
    *
    * @param string $email e-mail of requested user
    * @return array null if no user was found, database row, if found
    * @global DB used for database access
    */
function getUserFromEmail($email) {
        global $db;
        
        $db->query('SELECT *, users.id AS id FROM ' . TABLE_USERS . ' AS users
            LEFT JOIN ' . TABLE_CONTACT . ' AS contact ON users.id = contact.id
            LEFT JOIN ' . TABLE_PROPERTIES . ' AS properties ON contact.id = properties.id
            WHERE properties.type = ' . $db->escape('email') . '
            AND properties.value = ' . $db->escape($email) . '
            OR users.reg_email = ' . $db->escape($email) . '
            GROUP BY users.id');
        
        return $db->next();
    }
}

?>
