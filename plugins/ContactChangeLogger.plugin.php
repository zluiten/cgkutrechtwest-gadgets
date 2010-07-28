<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 * SAMPLE PLUGIN for THE ADDRESS BOOK
 *
 * @package plugins
 */
 
class ContactChangeLogger {
    
    // CONSTRUCTOR FUNCTION - not needed
    //function ContactChangeLogger() { }

    /** There are 2 classes of plugins: #1 with and #2 without user interface
     * #1 Plugins with UI can place a menu at an appropriate location or insert their user interface to the contact edit form.
     * #2 Plugins will be triggered by an event.
     *
     * UI Plugins have a makeMenuLink() or a editContactInterface() function:
     *        Plugin type contactMenu: top menu of address.php makeMenuLink($contactId)
     *        Plugin type addressMenu: (below an address in address.php makeMenuLink($contactId,$addressId)
     *        Plugin type listMenu: makeMenuLink($listOfIds)
     *        Plugin type editContactInterface: editContactInterface($contact,$location) $location = ownFieldset | ?
     *        
     * Event Plugins have a changed...Record() function:
     *        changedContactRecord: triggered before/after a contact will be/was changed/added/deleted changedRecord($contactId,$mode)
     *        changedUserRecord: triggered before/after a user will be/was changed/added/deleted changedRecord($userId,$mode)
     */
function isType($t) { return $t=='changedContactRecord' || $t=='xmlExport'; }
        
   /**
    * Returns the Javascript to generate the help text for the admin. Used by {@link PluginManager}.
    * @return string script type="text/javascript" section for HTML output
    */
function help()
    {
        return '<script type="text/javascript">
            function open_help_ContactChangeLogger() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Contact Change Logger</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Contact Change Logger</h3>");
                help_win.document.write("<p>Log changes of a contact so one can track what was modified by whom. Does not record change dates.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_ContactChangeLogger()">help</a>';
    }
    
    /**
    * Appends a 'changelog' section to the output XML that contains an XHTML table with loglines
    * @return string XML content
    */
function xmlExport(&$contact)
    {
        return "<changelog>\n" . $this->changeLogHtmlTable($contact) . "</changelog>\n";
    }
    
    /**
    * Creates a table with the changelog { WhoChangedIt | logLine } 
    * @return string XHTML content
    */
function changeLogHtmlTable(&$contact)
    {
        //global $errorHandler;
        global $CONFIG_DB_PREFIX, $CONFIG_REL_XML_OTHER_PROPERTIES, $CONFIG_REL_XML_DATE_PROPERTIES, $CONFIG_RELT_XML_OTHER_PROPERTIES, $CONFIG_RELT_XML_DATE_PROPERTIES;;
        $db = DB::getSingleton();
        
        // we prepare the content as XHTML
        $content = "<table>\n";
        
        // fetch log
        $id = $contact->contact['id'];
        $db->query("SELECT * FROM `{$CONFIG_DB_PREFIX}ContactChangeLogger` as log WHERE contactId=$id",'CCL');
        
        while($r = $db->next('CCL'))
        {
            $c = new User($r['whoModifiedId']);
            $content .= '<tr><td>' . htmlspecialchars($r['changeDescription']) . '</td><td>' . $c->generateFullName() . '</td></tr>';
        }
        
        $content .= "</table>\n";
        
        return $content;
    }
    
    /** Called by {@link PluginManager} if a contact is saved.
     * $mode is will_change | will_add | will_delete | changed | added | deleted 
     *
     * Useful globals: $_SESSION['username'] (the logged in {@link User})
     */
function changedContactRecord(&$contact,$mode)
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();
    	
        if ($mode == 'deleted')
        {
            // delete all relationships owned by this contact
            $id = $contact->contact['id'];
            $db->query("DELETE FROM `{$CONFIG_DB_PREFIX}ContactChangeLogger` WHERE contactId=$id");
            
            return;
        }
        
        if ($mode != 'will_change')
            return;
        
        $old = new Contact($contact->contact['id']);
        $diff = $contact->diff($old);
        $log = '';
        
        foreach($diff as $darray)
        {
            foreach($darray as $dd)
                foreach($dd as $k => $d)
                    $log .= ($log!='' ? ', ' : '') . $k . ': ' .$d;
        }
        
        if($log != '')
        {
            $whoContactId = $_SESSION['user']->getId();
            //echo '[' . $contact->contact['id'] . '] ' . $log;
            $this->logToDB($contact->contact['id'],$whoContactId,$log);
        }
    }
    
    // log function - log to DB table
function logToDB($id,$who,$message)
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();
        $db->query("INSERT INTO {$CONFIG_DB_PREFIX}ContactChangeLogger VALUES ($id,$who," . $db->escape($message) . ")");
    }
    
    /** DB statements to create a table within the TABR DB -- MUST USE PLUGIN NAME as table name
     */
function installPlugin()
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();
        
        // DB extensions
        $db->query("CREATE TABLE IF NOT EXISTS `{$CONFIG_DB_PREFIX}ContactChangeLogger` (
                    `contactId` int(11) NOT NULL default '0', 
                    `whoModifiedId` int(11) NOT NULL default '0', 
                    `changeDescription` TEXT NOT NULL default '' )
                    DEFAULT CHARSET=utf8;");
    }
    
    /** DB statements to drop table within the TABR DB -- MUST USE PLUGIN NAME as table name
     */
function uninstallPlugin()
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();
        
        // DB extensions
        $db->query("DROP TABLE `{$CONFIG_DB_PREFIX}ContactChangeLogger`");
    }
    
    /** DB statements to upgrade the plugin's DB scheme within the TABR DB.
      * Automatically called if the pluginversion was changed.
      */
function upgradePlugin($oldVersion)
    {
        /*
        $db = DB::getSingleton();
        
        ...
        */
    }
    
    /** Plugin version to determine if upgrade is needed. 
      * Increment to allow upgradePlugin to be called by the admin panel.
      */
function version() {
        return '1.1';
    }
}
?>