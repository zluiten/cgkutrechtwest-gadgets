<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageInstall}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');

/**
* the install page
* 
* the install page is used upon installation of
* TAB. It displays a welcome message and installation instructions
* @package frontEnd
* @subpackage pages
*/
class PageInstall extends Page {
    
    /**
    * @var array warnings that occured during installation
    */
    var $warnings;
    
    /**
    * Constructor
    * 
    * init superclass and vars
    * @param array $warn null, if just welcome message, array with warnings, if installation is complete
    */
function PageInstall($warn=null) {
        
        $this->Page('Install');
        
        $this->warnings = $warn;
        
    }
    
    /**
    * create the content of the installation page
    * @return string html-content
    */
function innerCreate() {
        
        $cont = '<div class="login-form">';
        
        $cont .= '<img src="images/banner.png" class="tab-title" alt="The Address Book" />';
        
        if ($this->warnings === null) {
        
            $cont .= '<p>Hello, welcome to TAB.</p>';
            $cont .= '<p>Please press install to install your personal copy of TAB.</p>';
            $cont .= '<p>Before you install TAB, please copy config.php.template to config.php, have a look at it and enter at least the database information.</p>';
            $cont .= '<p><a href="../admin/install.php?do=1">install</a></p>';
            
        } else {
            
            $cont .= '<p>Your copy of TAB has been successfully installed</p>';
    
            if (count($this->warnings) > 0) {
                $cont .= '<p>The installation produced the following warnings:</p>';
                foreach($this->warnings as $w)
                    $cont .= '<p>' . $w . '</p>';
            }
            
            $cont .= '<p>A default admin with e-mail-address <span style="color: red;"><blink>admin@example.com</blink></span> and password <span style="color: red;"><blink>admin</blink></span> has been created.</p>';
            $cont .= '<p><a href="../admin/upgrade.php">Next Step</a> (upgrade Database)</p>';
            
        }
        
        $cont .= '</div>';
        
        return $cont;
        
    }
    
}

?>
