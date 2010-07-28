<?php

/**
 * Contains plugin inTouch
 * @package plugins
 * @author Tobias Schlatter
 */

if (!@include_once('plugins/inTouch/config.php'))
    require_once('plugins/inTouch/config.template.php');

global $CONFIG_DB_PREFIX;

define("INTOUCH_TABLE_TOUCHES", $CONFIG_DB_PREFIX . 'inTouch_touches');
define("INTOUCH_TABLE_CONDS", $CONFIG_DB_PREFIX . 'inTouch_conds');

/**
 * inTouch Plugin
 * @package plugins
 */
class inTouch {

    /**
     * Checks whether a plugin is of a specific type
     * @param string $t type to check for
     * @return boolean is of type $t
     */
function isType($t)
    {
        return $t=='editContactInterface' || $t=='contactOutput' || $t=='changedContactRecord';
    }
    
    /**
     * Outputs help
     * @return string html of help
     */
function help()
    {
        return '<script type="text/javascript">
        function open_help_inTouch() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>inTouch</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>inTouch</h3>");
                help_win.document.write("<p>inTouch helps you to keep in touch with your friends and partners.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_inTouch()">help</a>';
    }

    /**
     * Outputs content in contactCard
     * @param Contact $contact Contact which is displayed
     * @param string $location String defining location in card
     * @return string html to display
     */
function contactOutput(&$contact, $location)
    {

    }

    /**
     * Saves touches and conditions
     * @param Contact $contact Contact which is changed
     * @param string $mode what happened exactly
     */
function changedContactRecord(&$contact, $mode)
    {
        
        if ($mode != 'added' && $mode != 'changed')
            return;

        $db = DB::getSingleton();

        $ownid = $_SESSION['user']->contact['id'];
        $cid = $contact->contact['id'];

        $db->query("DELETE FROM " . INTOUCH_TABLE_CONDS . " WHERE touchedID = $cid");
        $db->query("DELETE FROM " . INTOUCH_TABLE_TOUCHES . " WHERE touchedID = $cid");

        if ($mode == 'deleted')
            return;

        $sql = '';
        foreach ($_POST['inTouch']['conds'] as $v) {
            if (is_numeric($v['days']) && $v['days'] > 0)
                $sql .= "($ownid,$cid," . $db->escape($v['initiative']) . "," . $db->escape($v['distance']) . "," . $db->escape($v['type']) . "," . intval($v['days']) . "),";
        }

        if ($sql) {
            $sql = "REPLACE INTO " . INTOUCH_TABLE_CONDS . " (ownerID,touchedID,initiative,distance,`type`,`days`) VALUES " . substr($sql,0,-1);
            $db->query($sql);
        }


        $sql = '';
        foreach ($_POST['inTouch']['touches'] as $v) {
            if ($v['start'])
                $sql .= "($ownid,$cid," . $db->escape($v['initiative']) . "," . $db->escape($v['distance']) . "," . $db->escape($v['type']) . "," . $db->escape($v['start']) . "," . ($v['end']?$db->escape($v['end']):'NULL') . "," . ($v['desc']?$db->escape($v['desc']):'NULL') . "),";
        }

        if ($sql) {
            $sql = "INSERT INTO " . INTOUCH_TABLE_TOUCHES . " (ownerID,touchedID,initiative,distance,`type`,`start`,`end`,`desc`) VALUES " . substr($sql,0,-1);
            $db->query($sql);
        }

    }

    /**
     * Outputs content in edit mode of contact
     * @param Contact $contact Contact which is displayed
     * @param string $location String defining location in form
     * @return string html to display
     */
function editContactInterface(&$contact, $location)
    {

        if ($location != 'ownFieldset')
            return '';

        $cont = '<fieldset class="edit-additionals">';
        $cont .= '<legend>inTouch</legend>';

        $cid = (isset($contact->contact['id'])?$contact->contact['id']:null);

        if ($_SESSION['user']->contact['id'] != $cid) {

            $cont .= $this->createConditionsPart($cid);
            $cont .= $this->createTouchesPart($cid);

        } else {
            
            $cont .= "<div>Sorry, you can't use inTouch for yourself.</div>";

        }

        $cont .= '</fieldset>';

        return $cont;

    }

    /**
     * Creates the part of the fieldset containing touches
     * @return string html for part
     */
function createTouchesPart($cid)
    {
        
        $db = DB::getSingleton();

        $cont = '<h2>touches</h2>';

        $ownid = $_SESSION['user']->contact['id'];

        if ($cid)
            $db->query("SELECT * FROM " . INTOUCH_TABLE_TOUCHES . " AS touches WHERE ownerID = $ownid AND touchedID = $cid ORDER BY start ASC");

        for ($i=0;$i<($cid?$db->rowsAffected():0)+2;$i++) {
            if (!($val = $db->next()))
                $val = array('initiative' => 'passive', 'distance' => 'remote', 'type' => '', 'start' => '', 'end' => '', 'desc' => '');
            
            $cont .= '<div>';
            $cont .= HTMLHelper::createDropdownValuesAreKeys("inTouch[touches][$i][initiative]",
                                                             null,
                                                             array('active','passive'),
                                                             $val['initiative'],
                                                             null,false);
            $cont .= HTMLHelper::createDropdownValuesAreKeys("inTouch[touches][$i][distance]",
                                                             null,
                                                             array('remote','local'),
                                                             $val['distance'],
                                                             null,false);

            $cont .= HTMLHelper::createTextField("inTouch[touches][$i][type]",null,$val['type'],'edit-property-label',false);
            $cont .= HTMLHelper::createTextField("inTouch[touches][$i][start]",null,$val['start'],'edit-date-value',false);
            $cont .= HTMLHelper::createTextField("inTouch[touches][$i][end]",null,$val['end'],'edit-date-value',false);
            $cont .= HTMLHelper::createTextField("inTouch[touches][$i][desc]",null,$val['desc'],'edit-date-label',false);

        }

        $cont .= '</div>';

        return $cont;

    }

    /**
     * Creates the part of the fieldset for touch conditions
     * @return string html for part
     */
function createConditionsPart($cid)
    {
        
        $db = DB::getSingleton();
        
        $cont = '<h2>conditions</h2>';

        $ownid = $_SESSION['user']->contact['id'];

        if ($cid)
            $db->query("SELECT * FROM " . INTOUCH_TABLE_CONDS . " AS conds WHERE ownerID = $ownid AND touchedID = $cid");

        for ($i=0;$i<($cid?$db->rowsAffected():0)+2;$i++) {
            if (!($val = $db->next()))
                $val = array('initiative' => 'active', 'distance' => 'remote', 'type' => '%', 'days' => '');

            $cont .= '<div>';
            $cont .= HTMLHelper::createDropdownValuesAreKeys("inTouch[conds][$i][initiative]",
                                                             null,
                                                             array('active','passive'),
                                                             $val['initiative'],
                                                             null,false);
            $cont .= HTMLHelper::createDropdownValuesAreKeys("inTouch[conds][$i][distance]",
                                                             null,
                                                             array('remote','local'),
                                                             $val['distance'],
                                                             null,false);

            $cont .= HTMLHelper::createTextField("inTouch[conds][$i][type]",null,$val['type'],null,false);
            $cont .= HTMLHelper::createTextField("inTouch[conds][$i][days]",null,$val['days'],'edit-property-label',false);

            $cont .= '</div>';
            
        }

        return $cont;

    }
    
    /**
     * Installs plugin
     */
function installPlugin() {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();
        
        $db->query("CREATE TABLE IF NOT EXISTS `{$CONFIG_DB_PREFIX}inTouch_touches` (
                    `ownerID` INT(11) NOT NULL DEFAULT 0,
                    `touchedID` INT(11) NOT NULL DEFAULT 0,
                    `initiative` ENUM('active','passive') NOT NULL DEFAULT 'active',
                    `distance` ENUM('remote','local') NOT NULL DEFAULT 'remote',
                    `type` VARCHAR(40) NOT NULL DEFAULT '',
                    `start` DATE NOT NULL,
                    `end` DATE NULL DEFAULT NULL,
                    `desc` TEXT )
                    DEFAULT CHARSET=utf8;");

        $db->query("CREATE TABLE IF NOT EXISTS `{$CONFIG_DB_PREFIX}inTouch_conds` (
                    `ownerID` INT(11) NOT NULL DEFAULT 0,
                    `touchedID` INT(11) NOT NULL DEFAULT 0,
                    `initiative` ENUM('active','passive') NOT NULL DEFAULT 'active',
                    `distance` ENUM('remote','local') NOT NULL DEFAULT 'remote',
                    `type` VARCHAR(50) NOT NULL DEFAULT '%',
                    `days` INT(11) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`ownerID`,`touchedID`,`initiative`,`distance`,`type`))
                    DEFAULT CHARSET=utf8;");

    }
    
    /**
     * Uninstalls plugin
     */
function uninstallPlugin() {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();
        
        // DB extensions
        $db->query("DROP TABLE `{$CONFIG_DB_PREFIX}inTouch_touches`");
        $db->query("DROP TABLE `{$CONFIG_DB_PREFIX}inTouch_conds`");
    }
    
    /**
     * returns version of the plugin
     * @return string version
     */
function version() {
        return '0.1';
    }

}

?>