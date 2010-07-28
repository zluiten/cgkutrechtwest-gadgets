<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link PageUpgrade}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');

/**
* the upgrade page
* 
* the upgrade page allows users to upgrade the database scheme
* it is more to display information,
* then to actually ask data from the user
* @package frontEnd
* @subpackage pages
*/
class PageUpgrade extends Page {
    
    /**
    * @var array associative array with upgrades that have been performed
    */
    var $upgrades;
    
    /**
    * @var string the current (updated) version of the database
    */
    var $endVersion;
    
    /**
    * Constructor
    * 
    * init superclass, init vars
    */
function PageUpgrade($upgrades=null,$end=null) {
        
        $this->Page('Upgrade');
        
        $this->upgrades = $upgrades;
        $this->endVersion = $end;
        
    }
    
    /**
    * create the content of update page
    * @return string html-content
    */
function innerCreate() {
        
        $cont = '<div class="login-form">';
        
        $cont .= '<img src="../images/banner.png" class="tab-title" alt="The Address Book" />';
        
        if ($this->upgrades !== null) {
        
            $cont .= '<p>Upgrading your copy of TAB ...</p>';
    
            foreach ($this->upgrades as $up) {
                $cont .= '<p>Upgrading from version ' . $up['from'] . ' to version ' . $up['to'] . '</p>';
                if ($up['notes'])
                    foreach ($up['notes'] as $n)
                        $cont .= '<p><strong>Note:</strong> ' . $n . '</p>';
            }
            
            $cont .= '<p>TAB now fully upgraded to version ' . $this->endVersion . '</p>';
            $cont .= '<p><strong>Note:</strong> Please check if there were any changes to the configruation file and add them if necessary</p>';
            $cont .= '<p><a href="../user/login.php">login</a></p>';
            
        } else {
            
            $cont .= '<p>This is the upgrade script for TAB.</p>';
            $cont .= '<p>Upgrading the database-scheme of TAB can result in wanted or unwanted change or even loss of information.</p>';
            $cont .= '<p>Please read the release notes and be sure to understand, what the update will do.</p>';
            $cont .= '<p>Please also make a backup of your database before you upgrade.</p>';
            $cont .= '<p style="color: red;"><blink>A 2.0->3.0 upgrade will remove user accounts which have the same email as another user account (even admins).</blink></p>';
            $cont .= '<p><a href="../admin/upgrade.php?do=1">upgrade now</a></p>';
            
        }
            
        $cont .= '</div>';
            
        
        return $cont;
        
    }
    
}

?>
