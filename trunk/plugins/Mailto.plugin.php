<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  SAMPLE PLUGIN for THE ADDRESS BOOK
 * @package plugins
 * @author Thomas Katzlberger
 */
 
 // This plugin adds a login button on the top of the contact/list.php
 
require_once('StringHelper.class.php');

class Mailto {
    
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
    function isType($t) { return $t=='listMenu'; }
    
function help()
    {
        return '<script type="text/javascript">
        function open_help_mailto() {
                help_win = window.open( "", "help", "width=300, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Mailto</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Mailto</h3>");
                help_win.document.write("<p>If a contact group is selected in the main list this plugin shows a mailto: link in the main menu to mail all members of the group.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_mailto()">help</a>';
    }

    /* There is not much to do here except to generate a link that will perform the actual work
     *
     *Useful globals:
     *        $_SESSION['user'] 
     */
    function makeMenuLink(&$contactList,&$nav)
    {    

        if (!is_a($contactList,'GroupContactList') || $contactList->group == '')
            return;
        
        $epp = $contactList->entriesPerPage;
        $start = $contactList->start;
        
        $contactList->setEntriesPerPage(0);
        $contactList->setStartString('');
        
        $c = $contactList->getContacts();
        
        $contactList->setEntriesPerPage($epp);
        $contactList->setStartString($start);
        
        $allEmails = '';
        foreach ($c as $cont) {
            $eml = $cont->getValueGroup('email');
            if (count($eml) > 0)
                $allEmails .= StringHelper::obscureString($cont->contact['lastname'] . ' ' . $cont->contact['firstname'] . ' <' . $eml[0]['value'] . '>,');
        }
        
        $nav->addEntry('plugin.Mailto','mailto','mailto:' . $allEmails);
                            
    }
    
function version() {
        return '1.0';
    }
}
?>