<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
  /**
   * RELATIONSHIPS PLUGIN for THE ADDRESS BOOK
   * @package plugins
   * @author Thomas Katzlberger
   */

  /** */
if(!@include_once('plugins/Relationships/pconfig.php'))
    require_once('plugins/Relationships/pconfig.template.php');

class Relationships {

function isType($t) { return $t=='editContactInterface' || $t=='changedContactRecord' || $t=='xmlExport'; }

   /**
    * Returns the Javascript to generate the help text for the admin. Used by {@link PluginManager}.
    * @return string script type="text/javascript" section for HTML output
    */
function help()
    {
        return '<script type="text/javascript">
            function open_help_Relationships() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Relationships</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Relationships</h3>");
                help_win.document.write("<p>Model Relationships between contacts. Relationships have a descriptive text and can retrieve properties of other related contacts in the XML export (options in configure.php). Adds a new DB table to model the relationships.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_Relationships()">help</a>';
    }

function getDropdown(&$db)
    {
        // create dropdown selector box from all contacts (might be HUGE)
        $dropdown = array(''=>'none');
        global $CONFIG_DB_PREFIX, $CONFIG_REL_DROPDOWN_SUBSET;
        //$db->query("SELECT firstname, lastname, id FROM `{$CONFIG_DB_PREFIX}contact` WHERE hidden=0 AND $CONFIG_REL_DROPDOWN_SUBSET ORDER BY lastname, firstname");
        $db->query("SELECT firstname, lastname, id FROM `{$CONFIG_DB_PREFIX}contact` WHERE hidden=0 ORDER BY lastname, firstname");
        while($r = $db->next())
            $dropdown[$r['id']] = $r['lastname'] . ', ' . $r['firstname'] . ', ' . $r['id'];
        return $dropdown;
    }

    /**
    * Called by {@link PluginManager} to append a user interface section to the edit contact form.
    * The result is delivered to the changedContactRecord() method in $_POST['PluginName']
    * @return string XHTML content
    */
function editContactInterface(&$contact, $location)
    {
		//die("?????????????");
    	if($location!='ownFieldset')
            return "";

        //if(!$_SESSION['user']->isAtLeast('manager'))
        //    return "";

        $content = '<fieldset class="edit-names">';
        $content .= '<legend>Relationships</legend>';

        // create dropdown selector box from all contacts (might be HUGE)

        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();

        // fetch dropdown
        $dropdown = $this->getDropdown($db);

        $id = $contact->contact['id'];
        $i=0;

        // list existing relationships if not a new/added contact
        if(!empty($id))
        {
            $db->query("SELECT r.* FROM `{$CONFIG_DB_PREFIX}Relationships` as r, `{$CONFIG_DB_PREFIX}contact` as c WHERE r.ownerId=$id AND c.id=r.ownerId AND c.hidden=0",'Relationships');

            while($r = $db->next('Relationships'))
            {
                $content .= '<div class="edit-line">';
                $content .= HTMLHelper::createDropdown('Relationship['.$i.'][relatedToId]','Relationship to',$dropdown,$r['relatedToId'],'edit-input');
                $content .= HTMLHelper::createTextField('Relationship['.$i.'][relationship]','Relationship Type',$r['relationship'],'edit-property-value',false);
                $content .= '</div>';
                $i++;
            }
        }

        for($k=$i;$k<$i+2;$k++)
        {
            $content .= '<div class="edit-line">';
            $content .= HTMLHelper::createDropdown('Relationship['.$k.'][relatedToId]','Relationship to',$dropdown,' ','edit-input');
            $content .= HTMLHelper::createTextField('Relationship['.$k.'][relationship]','Relationship Type','','edit-property-value',false);
            $content .= '</div>';
        }

        return $content . '</fieldset>';
    }

    /**
    * Appends a 'relationships' section to the output XML that contains an XHTML table with loglines
    * @return string XML content
    */
function xmlExport(&$contact)
    {
        //global $errorHandler;
        global $CONFIG_DB_PREFIX, $CONFIG_REL_XML_OTHER_PROPERTIES, $CONFIG_REL_XML_DATE_PROPERTIES, $CONFIG_RELT_XML_OTHER_PROPERTIES, $CONFIG_RELT_XML_DATE_PROPERTIES;;
        $db = DB::getSingleton();

        $content = "<relationships>\n";

        // fetch dropdown
        $dropdown = $this->getDropdown($db);
        $content .= '<ddJSON>'.HTMLHelper::arrayToJSON($dropdown).'</ddJSON>';

        // list outgoing relationships
        $id = $contact->contact['id'];
        $db->query("SELECT r.* FROM `{$CONFIG_DB_PREFIX}Relationships` as r, `{$CONFIG_DB_PREFIX}contact` as c WHERE r.ownerId=$id AND c.id=r.ownerId AND c.hidden=0",'Relationships');

        $i=0;
        while($r = $db->next('Relationships'))
        {
            $to = htmlspecialchars($r['relatedToId'],ENT_NOQUOTES,'UTF-8');
            $desc = htmlspecialchars($r['relationship'],ENT_NOQUOTES,'UTF-8');
            $content .= "<relationship>\n<ownerId>$id</ownerId>\n<relatedToId>$to</relatedToId>\n<relatedTo>";

            $relatedTo = new Contact($r['relatedToId']);
            $content .= $relatedTo->generateFullName()."</relatedTo>\n";

            foreach($CONFIG_REL_XML_OTHER_PROPERTIES as $tag => $prop)
            {
                $p = $relatedTo->getProperty('other',$prop);
                $content .= "<$tag>".($p !== FALSE ? $p : '')."</$tag>";
            }

            foreach($CONFIG_REL_XML_DATE_PROPERTIES as $tag => $prop)
            {
                $p = $relatedTo->getDate($prop);
                $content .= "<$tag><from>".($p !== FALSE ? $p[0] : '')."</from><to>".($p !== FALSE ? $p[1] : '')."</to></$tag>";
            }

            $content .= "<description>$desc</description>\n</relationship>\n";
        }

        // list incoming relationships
        $id = $contact->contact['id'];
        $db->query("SELECT r.* FROM `{$CONFIG_DB_PREFIX}Relationships` as r, `{$CONFIG_DB_PREFIX}contact` as c WHERE r.relatedToId=$id AND c.id=r.relatedToId AND c.hidden=0",'Relationships');

        $i=0;
        while($r = $db->next('Relationships'))
        {
            $id = $r['ownerId'];
            $to = htmlspecialchars($r['relatedToId'],ENT_NOQUOTES,'UTF-8');
            $desc = htmlspecialchars($r['relationship'],ENT_NOQUOTES,'UTF-8');
            $content .= "<relationshipTarget>\n<ownerId>$id</ownerId>\n<relatedToId>$to</relatedToId>\n<relatedTo>";

            $relatedTo = new Contact($id);
            $content .= $relatedTo->generateFullName()."</relatedTo>\n";

            foreach($CONFIG_RELT_XML_OTHER_PROPERTIES as $tag => $prop)
            {
                $p = $relatedTo->getProperty('other',$prop);
                $content .= "<$tag>".($p !== FALSE ? $p : '')."</$tag>";
            }

            foreach($CONFIG_RELT_XML_DATE_PROPERTIES as $tag => $prop)
            {
                $p = $relatedTo->getDate($prop);
                $content .= "<$tag><from>".($p !== FALSE ? $p[0] : '')."</from><to>".($p !== FALSE ? $p[1] : '')."</to></$tag>";
            }

            $content .= "<description>$desc</description>\n</relationshipTarget>\n";
        }
        $content .= "</relationships>\n";

        return $content;
    }

    /** Function to query ids for external use (ProjectsSearchList)
    */
function relationshipsArray($id, $incoming = FALSE)
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();

        if($incoming)
            $db->query("SELECT r.* FROM `{$CONFIG_DB_PREFIX}Relationships` as r, `{$CONFIG_DB_PREFIX}contact` as c WHERE r.relatedToId=$id AND c.id=r.relatedToId AND c.hidden=0",'Relationships');
        else
            $db->query("SELECT r.* FROM `{$CONFIG_DB_PREFIX}Relationships` as r, `{$CONFIG_DB_PREFIX}contact` as c WHERE r.ownerId=$id AND c.id=r.ownerId AND c.hidden=0",'Relationships');

        $ret = array();
        $i=0;
        while($r = $db->next('Relationships'))
        {
            $id = $r['ownerId'];
            $to = htmlspecialchars($r['relatedToId'],ENT_NOQUOTES,'UTF-8');
            $desc = htmlspecialchars($r['relationship'],ENT_NOQUOTES,'UTF-8');

            $ret[] = array('ownerId'=>$id,'relatedToId'=>$to,'relationship'=>$desc);
        }

        return $ret;
    }

    /** Called by {@link PluginManager} if a contact is saved.
     * $mode is will_change | will_add | will_delete | changed | added | deleted
     *
     * Useful globals: $_SESSION['username'] (the logged in {@link User})
     */
function changedContactRecord(&$contact,$mode)
    {
        //global $errorHandler;
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();

        if ($mode == 'deleted')
        {
            // delete all relationships owned by this contact
            $id = $contact->contact['id'];
            $db->query("DELETE FROM `{$CONFIG_DB_PREFIX}Relationships` WHERE ownerId=$id");

            return;
        }

        if ($mode == 'added' || $mode == 'changed') // fetch and store data
        {
            // delete all relationships owned by this contact
            //$id = $contact->contact['id'];
            //$db->query("DELETE FROM `{$CONFIG_DB_PREFIX}Relationships` WHERE ownerId=$id");

            // recreate them
            if(isset($_POST['Relationship']))
            {
                $this->save($contact,$_POST['Relationship']);
                //$errorHandler->success('Administrative Request submitted.',get_class($this));
            }

            return;
        }
    }

function save(&$contact,$v)
    {
        global $CONFIG_DB_PREFIX;
        $tbl = $CONFIG_DB_PREFIX . 'Relationships';

        // delete all relationships owned by this contact
        $id = $contact->contact['id'];
        $db = DB::getSingleton();
        $db->query("DELETE FROM $tbl WHERE ownerId=$id");

        if (count($v) <= 0)
            continue;

        // add all relationships, text may be empty
        foreach($v as $rShip)
        {
            if(empty($rShip['relatedToId']))
                continue;

            $sql = 'INSERT INTO ' . $tbl . ' (ownerId, relatedToId, relationship) VALUES ('.
                $contact->contact['id'] .','. $db->escape($rShip['relatedToId']) .','. $db->escape($rShip['relationship']).')';

            $db->query($sql);
        }

        // Remove overhead
        $db->query('OPTIMIZE TABLE ' . $tbl);
    }

    /** DB statements to create a table within the TABR DB -- MUST USE PLUGIN NAME as table name
     */
function installPlugin()
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();

        // DB extensions
        $db->query("CREATE TABLE IF NOT EXISTS `{$CONFIG_DB_PREFIX}Relationships` (
                    `ownerId` int(11) NOT NULL default '0',
                    `relatedToId` int(11) NOT NULL default '0',
                    `relationship` TEXT NOT NULL )
                    DEFAULT CHARSET=utf8;"); // , PRIMARY KEY  (`requestId`) )
    }

    /** DB statements to drop table within the TABR DB -- MUST USE PLUGIN NAME as table name
     */
function uninstallPlugin()
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();

        // DB extensions
        $db->query("DROP TABLE IF EXISTS `{$CONFIG_DB_PREFIX}Relationships`");
    }

    /** DB statements to upgrade the plugin's DB scheme within the TABR DB.
      * Automatically called if the pluginversion was changed.
      */
function version() {
        return '1.0';
    }
}
?>