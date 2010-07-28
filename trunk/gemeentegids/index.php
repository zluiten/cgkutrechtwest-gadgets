<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link RightsManager}
* @package utilities
* @author Tobias Schlatter, Thomas Katzlberger
*/

    // this will be reset in lib/init.php !!
    ini_set('display_errors',true);
    error_reporting(E_ALL);

// ** GET CONFIGURATION DATA *
    require_once('lib/init.php');
    
    require_once('Navigation.class.php');
    
    header('Location:'.Navigation::mainPageUrl());
?>
