<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
    // On some servers PHP does not display anything if config.php is missing
    ini_set('display_errors',true);
    // E_STRICT cannot be used because PHP5 has too many warnings
    //error_reporting(E_ALL);

    if((@include_once('config.php'))!=1)
    {
        echo '<h1>Please copy config.template.php to config.php and make the necessary changes.</h1>';
        exit;
    }

    // admin/upgrade.php and install.php do not call this script: has identical code!
    $path = 'lib/backEnd' . PATH_SEPARATOR . 'lib/frontEnd' . PATH_SEPARATOR . 'lib/utilities' . PATH_SEPARATOR . 'lib/custom' . PATH_SEPARATOR . 'lib/pdf' . PATH_SEPARATOR . 'lib/phpmailer' . PATH_SEPARATOR . ini_get('include_path');
    ini_set('include_path',$path);

    // Start page creation timer. This includes now most of the substantial (65% of total) file loading time
    require_once('Timer.class.php');
    $PAGE_TIMER = new Timer();

    if(!isset($CONFIG_DB_NAME) || !isset($CONFIG_MAIN_PAGE) || !isset($CONFIG_LOGOUT_PAGE))
    {
        echo '<h2>Please change config.php to v3.1 by changing and adding:</h2>';
        echo '<pre>$CONFIG_DB_HOSTNAME = $db_hostname;'."\n".'$CONFIG_DB_NAME = $db_name;'."\n".'$CONFIG_DB_USER = $db_username;'."\n";
        echo '$CONFIG_DB_PASSWORD = $db_password;'."\n".'$CONFIG_DB_PREFIX = $db_prefix;'."\n".'$CONFIG_MAIN_PAGE="contact/list.php";'."\n";
        echo '$CONFIG_LOGOUT_PAGE="user/logout.php";</pre>You can append this to the end of the config file. ALSO ADAPT $TMP_INSTALLDIR, $CONFIG_TAB_ROOT, $CONFIG_TAB_SERVER_ROOT from the template!';
        exit;
    }

    if(!isset($CONFIG_INSTALL_SUBDIR)) // backwards compatibility
        $CONFIG_INSTALL_SUBDIR='';

    require_once('lib/constants.inc');

    require_once('ErrorHandler.class.php');
    require_once('Page.class.php');
    require_once('DB.class.php');
    require_once('GuestUser.class.php');
    require_once('User.class.php');
    require_once('Options.class.php');
    require_once('StringHelper.class.php');

    if(ini_get('register_globals'))  // If register_globals is enabled
        $errorHandler->error('version','Fatal error: php.ini register_globals MUST BE off. TABR will malfunction.');

    // $db->setCharacterSet('utf8'); is the default of the DB class
    mb_internal_encoding('UTF-8');

    // Load standard options to check DB before session is started
    $options = new Options();
    if ($options->getOption('TABversion') != DB_VERSION_NO)
        $errorHandler->error('version','Your database version (' . $options->getOption('TABversion') . ') is not the one applicable for this code package (' . DB_VERSION_NO . '), please run an upgrade.');

    session_name('TheAddressBookSID-'.$CONFIG_DB_NAME);
    session_start();
    //echo session_id() . "#" . isset($_SESSION['user']);

    // is $_SESSION['user'] a User object?
    if (isset($_SESSION['user']) && !is_a($_SESSION['user'],'User'))
    {
        session_unset();
        $_SESSION = array();
    }

    // PRE AUTHENTICATION
    if (isset($_SESSION['user']) && $_SESSION['user']->isLoggedIn())
        $options = new Options($_SESSION['user']);
    else // we have no user yet
    {
        // Automatic SSL Client Certificate authentication
        if(isset($_SERVER['SSL_CLIENT_S_DN_Email']) && isset($CONFIG_SSL_CLIENT_AUTHENTICATION) && $CONFIG_SSL_CLIENT_AUTHENTICATION==true)
        {
            $user = User::sslCertificateLogin();
            // was the email correct/does the user exist?
            if ($user!=null)
            {
                $_SESSION['user'] = &$user;
                $options = new Options($user);
            }
            else
                $errorHandler->clear(); // clear ID does not exist error
        }

        // ... no user yet
        if(!isset($_SESSION['user']))
        {
            // Guest login if allowed
            if($options->getOption('requireLogin') != 1) // we require no login? then create a guest user
                $_SESSION['user'] = new GuestUser();

            session_regenerate_id(TRUE); // try to fix logout problem with expired server session
            //echo "---- SESSION USER NOT SET!! ----";
        }
    }

    // If guest login is disallowed $_SESSION['user'] is not set at this point!

    // allow config to exec a few satements after session creation - if needed
    if(isset($CONFIG_POST_INIT_EVAL))
        eval($CONFIG_POST_INIT_EVAL);

    // At this point RightsManager takes over ...
     // RightsManager::setUser() ->
     // ErrorHandler::checkFatalError('noLogin') ->
     // header('location: login.php')
     header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
?>
