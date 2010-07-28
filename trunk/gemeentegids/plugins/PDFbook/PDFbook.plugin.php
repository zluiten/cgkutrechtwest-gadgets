<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PDFbook}
* @author Thomas Katzlberger
* @package plugins
* @subpackage PDFBook
*/
 
 // This plugin adds a login button on the top of the contact/list.php
 
class PDFbook {
    
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
        function open_help_pdf() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Mailto</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>PDF Book</h3>");
                help_win.document.write("<p>This plugin converts a group or the whole main list to a PDF document. It does not work with many UTF-8 encoded characters if they are not present in the standard PDF fonts.</p>");
                help_win.document.write("<h3>Mailing Labels</h3>");
                help_win.document.write("<p>Generates a PDF document to print mailing labels and address stickers. It does not work with many UTF-8 encoded characters if they are not present in the standard PDF fonts. Call: plugins/PDFbook/pdflabels.php?group=Any%20Group&paper=L7160&.pdf</p><p>Paper:<ul><li>Avery No. L7160 sheets</li> </ul></p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_pdf()">help</a>';
    }

    /* There is not much to do here except to generate a link that will perform the actual work
     *
     *Useful globals:
     *        $_SESSION['usertype'] (admin,manager,user)
     *        $_SESSION['username']
     */
    function makeMenuLink(&$contactList,&$nav)
    {
        
        if(isset($_GET['group']))
            $group = "?group=" . htmlentities(rawurlencode($_GET['group'])) .
                // HACK for stupid M$ iex, because it would not recognize the pdf otherwise
                '&amp;.pdf';
        else
            $group = '?.pdf';
        
        $nav->addEntry('plugin.PDFbook','PDF',"../plugins/PDFbook/pdfbook.php$group");
        $nav->addSubEntry('plugin.PDFbook','mailing labels L7160',"../plugins/PDFbook/pdflabels.php$group&amp;paper=L7160");
    }
    
function version() {
        return '0.9';
    }
    
}
?>