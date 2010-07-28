<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/** 
* contains class {@link PluginManager}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('DB.class.php');
require_once('StringHelper.class.php');
require_once('Navigation.class.php');
require_once('Contact.class.php');
require_once('ContactList.class.php');

/**
* used to administrate plugins
*
* plugins are classes which implement certain functions. they have to be in a file
* named after the class and plugin name, which has to be in plugins/.
* @package backEnd
*/
class PluginManager {
    
    /**
    * @var array array of all loaded plugins
    */
    var $plugins;

    /**
    * @var string file extension of plugins
    */
    var $pluginExtension = ".plugin.php";
    
    /**
    * scans a directory for files with a certain extensions
    *
    * @param string $dir the directory to scan
    * @param string $ext the extension of the files wanted
    * @return array list of files matchin extention, without path
    * @static
    */
function scanDirectory($dir,$ext)
    {
        $dh  = opendir($dir);
        
        if($dh===false) //open error PHP warning should do
            return array();
        
        while (false !== ($filename = readdir($dh)))
        {
            if(StringHelper::strEndsWith($filename,$ext)) // Single file Plugin '.plugin.php'
                $files[] = $filename;
            else // Plugin in its own directory
            if (is_dir($dir . '/' . $filename) && file_exists($dir . '/' . $filename . '/' . $filename . $ext))
                $files[] = $filename . '/' . $filename . $ext;
        }
        return $files;
    }

    /**
    * constructor
    *
    * scans the plugin/ directory and checks, which of the found plugins are
    * activated, loads activated plugins
    *
    * @global DB used for database access
    */
function PluginManager() 
    {
        global $db, $CONFIG_INSTALL_SUBDIR;
            
        // load the plugins
        $files = $this->scanDirectory($CONFIG_INSTALL_SUBDIR.'plugins',$this->pluginExtension);        
        $oarr = array();
        foreach ( $files AS $i )
        {
            
            if ($tmp = strpos($i,'/'))
                $classname = mb_substr($i, 0 , $tmp);
            else
                $classname = mb_substr($i, 0, mb_strlen( $i ) - mb_strlen( $this->pluginExtension ));
                
            $db->query('SELECT * FROM ' . TABLE_PLUGINS . ' AS plugins WHERE name = ' . $db->escape($classname));
            $n = $db->next();
            
            if ($n && $n['state'] == 'activated') {
                
                require_once($CONFIG_INSTALL_SUBDIR.'plugins/'.$i); // load the class
                $o = new $classname; // instantiate plugins
                $oarr[] = $o;
                
            }
            
        }
        
        $this->plugins = &$oarr;
    }
    
    /**
    * Fetches a plugin instance by name to invoke some function or perform queries.
    * @return plugin instance or NULL
    */
function getPluginInstance($className)
    {
        foreach($this->plugins as &$p)
            if($className == get_class($p))
                return $p;
                
        return NULL;
    }
    
    /**
    * scans the plugin/ directory and checks, if any new plugins
    * are available which are not in the database
    *
    * @global DB used for database access
    */
function checkForNewPlugins()
    {
    
        global $db,$CONFIG_INSTALL_SUBDIR;
        
        // load the plugins
        $files = $this->scanDirectory($CONFIG_INSTALL_SUBDIR.'plugins',$this->pluginExtension);
        $oarr = array();
        foreach ( $files AS $i )
        {
            
            if ($tmp = strpos($i,'/'))
                $classname = mb_substr($i, 0 , $tmp);
            else
                $classname = mb_substr($i, 0, mb_strlen( $i ) - mb_strlen( $this->pluginExtension ));
                
            $db->query('SELECT * FROM ' . TABLE_PLUGINS . ' AS plugins WHERE name = ' . $db->escape($classname));
            $n = $db->next();
            
            require_once($CONFIG_INSTALL_SUBDIR.'plugins/'.$i); // load the class
            
            if (!$n) {
                
                if (intval(mb_substr(PHP_VERSION,0,1)) >= 5)
                    $installable = in_array('installPlugin',get_class_methods($classname));
                else
                    $installable = in_array('installplugin',get_class_methods($classname));
                
                $db->query('INSERT INTO ' . TABLE_PLUGINS . ' (name,state) VALUES (
                    ' . $db->escape($classname) . ',
                    ' . $db->escape($installable ? 'not installed':'deactivated') . '
                )');
                
            }
        }
        
        $this->plugins = array_merge($this->plugins,$oarr);
    
    }

    /**
    * adds links in {@link $nav}
    *
    * this function calls all plugins and lets them add a link to {@link $nav},
    * which invokes the plugins
    * this function is just called for links, which should appear in a list-context
    * @param ContactList $contactList the list of contacts, which the menu should be added to
    * @param Navigation $nav the navigation, where the links whould be added to
    */
function listMenu(&$contactList,&$nav)
    {
        
        foreach ( $this->plugins AS $p )
            if( $p->isType('listMenu'))
                $p->makeMenuLink($contactList,$nav);
            
    }
    
    /**
    * adds links in {@link $nav}
    *
    * this function calls all plugins and lets them add a link to {@link $nav},
    * which invokes the plugins
    * this function is just called for links, which should appear in a single-contact-context
    * @param Contact $contact the contact, which the menu should be added to
    * @param Navigation $nav the navigation, where the links whould be added to
    */
function contactMenu(&$contact,&$nav)
    {
        
        foreach ( $this->plugins AS $p )
            if( $p->isType('contactMenu'))
                $p->makeMenuLink($contact,$nav);
        
    }
    
    /**
    * adds links in {@link $nav}
    *
    * this function calls all plugins and lets them add a link to {@link $nav},
    * which invokes the plugins
    * this function is just called for links, which should appear in a single-address-context
    * @param Contact $contact the contact, which the address belongs to
    * @param integer $address_id the id of the address
    * @param Navigation $nav the navigation, where the links whould be added to
    */
function addressMenu(&$contact,$address_id,&$nav)
    {

        foreach ( $this->plugins AS $p )
            if( $p->isType('addressMenu'))
                $p->makeMenuLink($contact,$address_id,$nav);
    }
    
    /**
    * hook, called if a contact record has been changed
    *
    * If {@link $mode} is 'deleted', the hook is called before the contact is deleted
    *
    * @param Contact $contact contact which has been changed
    * @param string $mode one of the following: added, trashed, changed, deleted, will_change, will_add
    */
function changedContactRecord(&$contact, $mode)
    {

        foreach ( $this->plugins AS $p )
            if( $p->isType('changedContactRecord'))
                $p->changedContactRecord($contact, $mode);
            
    }
    
    /**
    * hook, called if a user record has been changed
    *
    * If {@link $mode} is 'deleted', the hook is called before the user is deleted
    *
    * @param User $user user which has been changed
    * @param string $mode one of the following: added, deleted, confirmed, email, password
    */
function changedUserRecord(&$user, $mode)
    {

        foreach ( $this->plugins AS $p )
            if( $p->isType('changedUserRecord'))
                $p->changedUserRecord($user, $mode);
    }
    
    /**
    * this creates output from the plugins to be shown in a single-contact-context
    * 
    * @param Contact $contact contact of which the data should be created
    * @param string $location for which location in the contact entry should the data be generated
    * @return string html to be entered in contact page
    */
function contactOutput(&$contact, $location)
    {
        $cont = "";
        
        foreach ( $this->plugins AS $p )
            if( $p->isType('contactOutput'))
                $cont .= $p->contactOutput($contact,$location);
            
        return $cont;
    }
    
    /**
    * this creates output from the plugins to be shown in a single-address-context
    * 
    * @param Contact $contact contact to which the address belongs
    * @param integer $address_id id of the address of which the data should be created
    * @param string $location for which location in the contact entry should the data be generated
    * @return string html to be entered in contact page
    */
function addressOutput(&$contact,$address_id,$location)
    {
        $cont = "";
        
        foreach ( $this->plugins AS $p )
            if( $p->isType('addressOutput'))
                $cont .= $p->addressOutput($contact,$address_id,$location);
            
        return $cont;
    }
    
    /**
    * this creates output from the plugins to be shown in a single-contact-edit-context
    * 
    * @param Contact $contact contact which is to be edited
    * @param string $location for which location in the edit interface should the data be generated
    * @return string html to be entered in contact page
    */
function editContactInterface(&$contact, $location)
    {
        $menu = "";
        
        foreach ( $this->plugins AS $p )
            if( $p->isType('editContactInterface'))
                $menu .= $p->editContactInterface($contact, $location);
            
        return $menu;
    }
    
    /**
    * Creates XML output from plugins inserted into a contact's XML output used by (@link ContactImportExport)
    * 
    * @param Contact $contact to export
    * @return string XML to be entered in contact page
    */
function xmlExport(&$contact)
    {
        $cont = "";
        
        foreach ( $this->plugins AS $p )
            if( $p->isType('xmlExport'))
                $cont .= $p->xmlExport($contact);
            
        return $cont;
    }
}

/**
* @global PluginManager $pluginManager
*/
$pluginManager = new PluginManager();

?>