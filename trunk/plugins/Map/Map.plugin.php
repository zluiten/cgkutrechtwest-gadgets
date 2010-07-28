<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 * Creates a link to display a google map below each address of a contact.
 *
 * @package plugins
 * @author Thomas Katzlberger
 */
 
    require_once("plugins/Map/Map.GoogleMaps.php");

class Map {
    
    // CONSTRUCTOR FUNCTION - not needed
    //function AdminInstantDelete() { }

    /* There are 2 classes of plugins: #1 with and #2 without user interface
     * #1 Plugins with UI can place a menu at an appropriate location.
     * #2 Plugins will be triggered by an event.
     *
     * UI Plugins have a makeMenuLink() function:
     *        contactMenu: top menu of address.php makeMenuLink($contact_id)
     *        addressMenu: (below an address in address.php makeMenuLink($contact_id,$address_id)
     *        listMenu makeMenuLink($listOfIds)
     *
     * Event Plugins have a changedRecord() function:
     *        changedContactRecord: triggered after a contact was changed/added/deleted changedRecord($contact_id,$mode)
     *        changedUserRecord: triggered after a user was changed/added/deleted changedRecord($user_id,$mode)
     */
    function isType($t) { return $t=='addressMenu' || $t=='changedContactRecord'; }
    
    /* There is not much to do here except to generate a link that will perform the actual work
     *
     *Useful globals:
     *        $_SESSION['usertype'] (admin,manager,user)
     *        $_SESSION['username']
     */
    function makeMenuLink(&$contact,$address_id,&$nav)
    {
        global $php_ext, $db_link;
        
        // this is just a copy of the code from address.php
        $add = $contact->getValueGroup('addresses');
        foreach ($add as $a)
            if ($a['refid'] == $address_id) {
                    $add = $a;
                    break;
            }
            
        $address_country  = $a['country'];

        $gm = new GoogleMapsLink();
        
        // display link only if we have lat/long or a valid geocoder
        if($gm->canMap($address_country)) // || isset($address_latitude) && $gm->canMap($address_country))
            $nav->addEntry('plugin.Map','map',"../plugins/Map/Map.display.php?id=$address_id&cid={$contact->contact['id']}");
    }
    
    /* $mode is changed/added/deleted */ 
    function changedContactRecord(&$contact,$mode)
    {
        global $db;
        
        if($mode != 'changed')
            return;
            
        $db->query('UPDATE ' . TABLE_ADDRESS . ' SET latitude=NULL,longitude=NULL WHERE id=' . $db->escape($contact->contact['id']));
    }
    
function installPlugin() {
        global $db;
        
        $db->queryNoError('ALTER TABLE ' . TABLE_ADDRESS . ' ADD latitude DECIMAL(15,12) DEFAULT NULL');
        $db->queryNoError('ALTER TABLE ' . TABLE_ADDRESS . ' ADD longitude DECIMAL(15,12) DEFAULT NULL');
    }
    
function uninstallPlugin() {
        global $db;
        
        $db->queryNoError('ALTER TABLE ' . TABLE_ADDRESS . ' DROP latitude');
        $db->queryNoError('ALTER TABLE ' . TABLE_ADDRESS . ' DROP longitude');
    }
    
function version() {
        return '0.7';
    }

}
?>