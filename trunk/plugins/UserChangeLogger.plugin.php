<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  SAMPLE PLUGIN for THE ADDRESS BOOK
 * @package plugins
 * @author Thomas Katzlberger
 */
 
class UserChangeLogger {
    
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
     *        changedUserRecord: triggered on added, deleted, confirmed, email, password; changedRecord($user_id,$mode)
     */
    function isType($t) { return $t=='changedUserRecord'; }
        
    /* $mode is changed/added/deleted (before)/trashed and matches contact.lastModification in the DB
     *
     *Useful globals:
     *        $_SESSION['usertype'] (admin,manager,user)
     *        $_SESSION['username']
     */
function changedUserRecord(&$user,$mode)
    {
        $u = $_SESSION['user'];
        $t = 'lost password';
        $who = 'login panel';

        if(isset($u))
        {
            $t = $u->getType();
            $who = $u->id;
        }
            
        $message = "User record #{$user->id} $mode by $who ($t)\n";
        $this->log($message);
    }
    
    // log function - this will give some ugly warnings if you server has no write-perms or safe_mode is on
    function log($message)
    { $handle = fopen("tabchangelog",'a+'); fwrite($handle,date('m.d.Y H:m:s ') . $message); fclose($handle); }
    
function version() {
        return '1.0';
    }
    
}
?>