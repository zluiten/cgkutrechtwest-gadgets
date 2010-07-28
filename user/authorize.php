<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
    /**
    * THE ADDRESS BOOK RELOADED: authorize; form processing for login panel
    * @author Thomas Katzlberger, Tobias Schlatter
    * @package default
    */
            
    /** */
    
    chdir('..');
    require_once('lib/init.php');
    require_once('PageLoginScreen.class.php');
    require_once('PageRegister.class.php');
    
// First defense against hacking - require the session cookie to be received
    if (!isset($_COOKIE[session_name()]))
    {
        $errorHandler->error('login','Cookies must be enabled to login.');
        
        // redisplay login page
        $page = new PageLoginScreen(isset($_GET['redirect']) ? $_GET['redirect'] : '');
        echo $page->create();
        exit();
    }
    
    if(!isset($_SESSION['failedLoginAttempts']))
        $_SESSION['failedLoginAttempts'] = 0;
    
// Second defense against hacking - max 3 attempts then lock session (different accounts possible)
    if ($_SESSION['failedLoginAttempts']>2)
    {
        $errorHandler->error('login','Too many incorrect logins. Access denied. Delete your cookies (restart your browser or use a different computer).');
        
        // redisplay login page
        $page = new PageLoginScreen(isset($_GET['redirect']) ? $_GET['redirect'] : '');
        echo $page->create();
        exit();
    }
    
// Third defense against hacking - yes there is one, but not here :-)
    
// do we have a login-email?
    if (!isset($_POST['user_email']) || !$_POST['user_email'] || is_numeric($_POST['user_email']))
    {
        $errorHandler->error('login','Please enter an email address');
        
        // redisplay login page
        $page = new PageLoginScreen(isset($_GET['redirect']) ? $_GET['redirect'] : '');
        echo $page->create();
        exit();
    }
    
// do we have a password?
    if (!isset($_POST['user_password']) || !$_POST['user_password'])
    {
        $errorHandler->error('login','Please enter a password'); // fatal
        // redisplay login page
        $page = new PageLoginScreen(isset($_GET['redirect']) ? $_GET['redirect'] : '');
        echo $page->create();
        exit();
    }
    
// create user class with email
    $user = new User(StringHelper::cleanGPC($_POST['user_email']));
    
    // was the email correct?
    if ($user->id!==null) 
    {
        // was the password correct?
        if ($user->login(StringHelper::cleanGPC($_POST['user_password']))) 
        {
            $_SESSION['user'] = &$user;
            $options = new Options($user);
            
            if ($user->getType() == 'register') 
            {
                if ($user->isConfirmed()) 
                {
                    // New User -> Attach Contact
                    if ($user->attachContact())
                        $flag = 'found';
                    else
                        $flag = 'created';
                        
                    $page = new PageRegister('confirm',$flag,(isset($_GET['redirect']) ? $_GET['redirect'] : ''));
                    echo $page->create();
                    exit();
                }
                else 
                {
                    // User#136 has set an error message; redisplay login page
                    $page = new PageLoginScreen(isset($_GET['redirect']) ? $_GET['redirect'] : '');
                    echo $page->create();
                    exit();
                }
            }
            
            // DONE WE ARE LOGGED IN - REDIRECT TO REQUESTED PAGE
            
            // we loose the session cookie here (not sent reliably), but it is unchanged from last time,
            // so the browser will restore it with the next request automatically (if the redirect is to THIS site)
            if(isset($_GET['redirect']))
                header('Location: ' . $_GET['redirect']);
            else
                header('Location: '.Navigation::mainPageUrl());
                
            exit();
        }            
        
        // FAILED LOGIN ... clear session variables
        $f = 1 + $_SESSION['failedLoginAttempts'];
        session_unset();
        $_SESSION = array();
        $_SESSION['failedLoginAttempts']=$f;
    }
    
// FAILED LOGIN redisplay login page
    $page = new PageLoginScreen(isset($_GET['redirect']) ? $_GET['redirect'] : '');
    echo $page->create();
?>
