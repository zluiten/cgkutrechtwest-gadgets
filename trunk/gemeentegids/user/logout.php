<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
    /**
    * THE ADDRESS BOOK RELOADED: logout; displays CONFIG_LOGOUT_PAGE -- default is login panel
    * @author Thomas Katzlberger
    * @package default
    */
    
    /** */
    
    chdir('..');
    require_once('lib/init.php');
    require_once('PageLoginScreen.class.php');
    
    session_unset();
    $_SESSION = array();
    setcookie(session_name(), '', time()-42000, '/');
    session_destroy();
    
    header('Location:'.Navigation::logoutPageUrl()); // @TODO: static logout page? The login page creates a new session.
?>
