<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  SAMPLE PLUGIN for THE ADDRESS BOOK
 *************************************************************
* @package plugins
* @author Thomas Katzlberger
*/

require_once('Navigation.class.php');

class AddNewEntryMenu {
    
    function isType($t) { return $t=='contactMenu'; }

    function makeMenuLink(&$contact,&$nav)
    {
        
        if($_SESSION['user']->isAtLeast('user')) {
            $nav->addEntry('plugin.AddNewEntryMenu.new','new','../contact/contact.php?mode=new');
            $nav->addEntry('plugin.AddNewEntryMenu.duplicate','duplicate',"../contact/contact.php?mode=new&id={$contact->contact['id']}");
        }
        
    }
    
function version() {
        return '1.0';
    }
}

?>