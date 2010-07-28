<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageRegister}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');
require_once('Contact.class.php');
require_once('User.class.php');
require_once('ErrorHandler.class.php');

/**
* the register page
* 
* this page generates several small pages,
* all used for registering or lost passwords
* if an admin wants to create a user, this class is
* also used
* @package frontEnd
* @subpackage pages
*/
class PageRegister extends Page {

    /**
    * @var string which mode are we in (lostpasswd,confirm,resend,register,cuser)
    */
    var $mode;
    
    /**
    * @var string submode of page (e.g. error,ok)
    */
    var $flag;
    
    /**
    * Constructor
    *
    * saves passed vars and inits superclass with right title according to {@link $mode}
    * @param string $mode mode of page
    * @param string $flag submode of page
    */
function PageRegister($mode,$flag,$redirect) {
    
        $this->mode = $mode;
        $this->flag = $flag;
        $this->redirect = $redirect;
        
        switch ($this->mode) {
            case 'lostpasswd':
                $this->Page('Lost password');
            break;
            case 'confirm':
                $this->Page('Confirm Email');
            break;
            case 'resend':
                $this->Page('Resend confirmation Email');
            break;
            case 'register':
                $this->Page('Register Account');
            break;
            case 'cuser':
                $this->Page('Create new user');
            break;
        }
    
    }

    /**
    * create the content of register page
    *
    * just calls the function for current mode and returns return value of func.
    * @return string html-content
    * @uses createLostPassword()
    * @uses createConfirm()
    * @uses createRegister()
    * @uses createCreateUser()
    * @uses createResend()
    */
function innerCreate() {
    
        switch ($this->mode) {
            case 'lostpasswd':
                return $this->createLostPassword();
            case 'confirm':
                return $this->createConfirm();
            case 'register':
                return $this->createRegister();
            case 'cuser':
                return $this->createCreateUser();
            case 'resend':
                return $this->createResend();
        }
    
    }
    
    /**
    * create message, that confirmation email has been resent
    * @global ErrorHandler used to catch errors that occured
    * @return string html-content
    */
function createResend() {
        
        global $errorHandler;
        
        $cont = '<div class="login-form">';
        
        $cont .= '<img class="tab-title" src="../images/banner.png" />';
        
        switch ($this->flag) {
        
            case 'error':
                ($err = $errorHandler->getLastError('register'))
                || ($err = $errorHandler->getLastError('login'));
                
                $cont .= '<div class="login-error">' . $err['cause'] . '</div>';
                
            break;
            
            case 'ok':
                
                $cont .= '<div class="login-message">Your confirmation email has been resent</div>';
                
            break;
            
        }
        
        $cont .= '</div>';
        
        return $cont;
        
    }
    
    /**
    * create form to create a new user (only used by admin)
    * @global ErrorHandler used to catch errors that occured
    * @return string html-content
    */
function createCreateUser() {
        
        global $errorHandler;
        
        $cont = '<div class="login-form">';
        
        $cont .= '<img class="tab-title" src="../images/banner.png" />';
        
        switch ($this->flag) {
            
            case 'ok':
                $cont .= '<div class="login-message">Successfully added user</div>';
            break;
            
            case 'error':
                ($err = $errorHandler->getLastError('register'))
                || ($err = $errorHandler->getLastError('login'));
                
                $cont .= '<div class="login-error">' . $err['cause'] . '</div>';
                
            break;
            
            default:
        
                if (!isset($_GET['id']))
                    $_GET['id'] = '';
                    
                $contact = Contact::newContact(intval($_GET['id']));
                
                $mails = $contact->getValueGroup('email');
                
                if ($contact->isUser()) {
                    $cont .= '<div class="login-error">This contact is already a user</div>';
                    break;
                }
                
                if (count($mails) <= 0) {
                    $cont .= '<div class="login-error">This contact has no email-address</div>';
                    break;
                }

                
                $cont .= '<div class="login-message">Please choose the email address to use and enter a password.</div>';
                $cont .= '<form action="../user/register.php?mode=cuser&amp;id=' . $contact->contact['id'] . '" method="post">';
                $cont .= '<div><label class="register-label" for="email">email</label></div>';
                
                $cont .= '<div><select name="email" id="email">';
                
                foreach ($mails as $m)
                    $cont .= '<option>' . $m['value'] . '</option>';
                
                $cont .= '</select></div>';
                        
                $cont .= '<br/><div><label class="register-label" for="password1">password</label>';
                $cont .= '<input class="register-input" type="password" name="password1" id="password1" /></div>';
                $cont .= '<br/><div><label class="register-label" for="password2">repeat</label>';
                $cont .= '<input class="register-input" type="password" name="password2" id="password2" /></div>';
                $cont .= '<br/><div><input class="register-input" type="submit" value="ok" /></div>';
                $cont .= '</form>';
        }
        
        $cont .= '<br/><div><a href="../contact/contact.php?id=' . $_GET['id'] . '">return</a></div>';
        $cont .= '</div>';
        
        return $cont;
        
    }
    
    /**
    * create form to register (only used by new users)
    * @global ErrorHandler used to catch errors that occured
    * @return string html-content
    */
function createRegister() {
    
        global $errorHandler;
        
        $cont = '<div class="login-form">';
                
        $cont .= '<img class="tab-title" src="../images/banner.png" />';
        
        switch ($this->flag) {
            case 'error':
                ($err = $errorHandler->getLastError('register'))
                || ($err = $errorHandler->getLastError('login'));
                
                $cont .= '<div class="login-error">' . $err['cause'] . '</div>';
                
            break;
            case 'ok':
                
                $cont .= '<div class="login-message">An email has been send to you. Please use the link in the email to confirm your account.</div>';
                
            break;
            default:
                
                $cont .= '<div class="login-message">Please enter your email and select a password. To resend your confirmation email please use the login panel and try to log in.</div>';
                $cont .= '<form action="../user/register.php?mode=register" method="post">';
                $cont .= '<div><label class="register-label" for="email">email</label>';
                $cont .= '<input class="register-input" type="text" name="email" id="email" size="40"/></div>';
                $cont .= '<br/><div><label class="register-label" for="password1">password</label>';
                $cont .= '<input class="register-input" type="password" name="password1" id="password1" /></div>';
                $cont .= '<br/><div><label class="register-label" for="password2">repeat</label>';
                $cont .= '<input class="register-input" type="password" name="password2" id="password2" /></div>';
                $cont .= '<br/><div><input class="register-input" type="submit" value="ok" /></div>';
                $cont .= '</form>';
                
            break;
        }
        
        $cont .= '</div>';
            
        return $cont;
    
    }
    
    /**
    * create page to show user, that his contact has been confirmed
    * also show link to contact page
    * @global ErrorHandler used to catch errors that occured
    * @return string html-content
    */
function createConfirm() {
        
        global $errorHandler;
        
        $cont = '<div class="login-form">';
                
        $cont .= '<img class="tab-title" src="../images/banner.png" />';
        
        switch ($this->flag) {
            case 'found':
                $cont .= '<div>Your contact-entry has already been found in the address book.</div>';
                $cont .= '<div>Please check, if all data is correct by following this link:</div>';
                $cont .= '<br/><div><a href="../contact/contact.php?id=' . $_SESSION['user']->contact['id'] . '">open my address-card</a></div>';
            break;
            case 'created':
                $cont .= '<div>A contact-entry has been created for you.</div>';
                $cont .= '<div>Please enter, all contact data: <a href="../contact/contact.php?id=' . $_SESSION['user']->contact['id'] . '&amp;mode=edit">open my address-card</a></div>';
            break;
            case 'error':
                ($err = $errorHandler->getLastError('register'))
                || ($err = $errorHandler->getLastError('login'));
                
                $cont .= '<div class="login-error">' . $err['cause'] . '</div>';
                
            break;
            case 'ok':
                
                $cont .= '<div>Your email has been successfully confirmed.</div>';
                $cont .= '<div>You can <a href="'.Navigation::mainPageUrl().'">use</a> the application now.</div>';
                
            break;
            default:
                
                $cont .= '<div class="login-message">Email verified. Please log in to confirm your account.</div>';
                $cont .= '<form action="../user/register.php?mode=confirm" method="post">';
                $cont .= '<input class="register-input" type="hidden" name="userid" value="' . $_GET['userid'] . '" />';
                $cont .= '<input class="register-input" type="hidden" name="hash" value="' . $_GET['hash'] . '" />';
                $cont .= '<br/><div><label class="register-label" for="email">email</label>';
                $cont .= '<input class="register-input" type="text" name="email" id="email" size="40"/></div>';
                $cont .= '<br/><div><label class="register-label" for="password">password</label>';
                $cont .= '<input class="register-input" type="password" name="password" id="password" /></div>';
                $cont .= '<br/><div><input class="register-input" type="submit" value="confirm" /></div>';
                $cont .= '</form>';
                
                
            break;
        }
        
        $cont .= '</div>';
            
        return $cont;
        
    }
    
    /**
    * create form/page to restore password of a user
    * @global ErrorHandler used to catch errors that occured
    * @return string html-content
    */
function createLostPassword() {
        
        global $errorHandler;
        
        $cont = '<div class="login-form">';
                
        $cont .= '<img class="tab-title" src="../images/banner.png" />';
        
        switch ($this->flag) {
            case 'error':
                ($err = $errorHandler->getLastError('register'))
                || ($err = $errorHandler->getLastError('mail'));
                
                $cont .= '<div class="login-error">' . $err['cause'] . '</div>';
                
            break;
            case 'changed':
                $cont .= '<div>An email with your new password has been sent to you.</div>';
                $cont .= '<div>Go to the <a href="' . (empty($this->redirect) ? "index.php" : $this->redirect) .'">login page</a> to use it.</div>';
            break;
            default:
                
                $cont .= '<form action="../user/register.php?mode=lostpasswd' . (empty($this->redirect) ? '' : "&redirect={$this->redirect}") . '" method="post">';
                $cont .= '<div class="login-message"><label class="register-label" for="email">Please enter your email address, and we will send you a new password.</label></div>'; 
                $cont .= '<div><input class="register-input" type="text" name="email" id="email" size="40"/></div>';
                $cont .= '<br/><div><button class="register-input" type="submit">reset password</button></div>';
                $cont .= '</form>';
                
            break;
        }
        
        $cont .= '</div>';
        
        return $cont;
        
    }

}
