<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
    /**
    * Login Page. Creates a blank session and displays the login panel.
    * @author Thomas Katzlberger
    * @package default
    */
    
    /** */
    
    chdir('..');
    require_once('lib/init.php'); // create new session or reuse old one
    require_once('PageLoginScreen.class.php');
    
    // We keep any current session id, but clear all variables.
    // Required to make header("Location:") work in authorize.php
    session_unset();
    $_SESSION = array();
    
    $options = new Options();
    $page = new PageLoginScreen(isset($_GET['redirect']) ? $_GET['redirect'] : '');
    echo $page->create();
    exit();
?>
