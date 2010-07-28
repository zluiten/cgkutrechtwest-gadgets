<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  EXPORT PLUGIN for THE ADDRESS BOOK
 *************************************************************
* @package plugins
* @author Thomas Katzlberger
*/

require_once('Navigation.class.php');

class Export {
    
    function isType($t) { return $t=='contactMenu'; }

    function makeMenuLink(&$contact,&$nav)
    {
        global $CONFIG_TAB_SERVER_ROOT;
        // single vcard export allowed for guest (if viewable)
        $nav->addEntry('plugin.Export.vcard','vCard',$CONFIG_TAB_SERVER_ROOT.'plugins/Export/export.php?format=vcard&id='.$contact->contact['id'].'&amp;.vcf');
        $nav->addEntry('plugin.Export.xml','xml',$CONFIG_TAB_SERVER_ROOT.'plugins/Export/export.php?format=xml&id='.$contact->contact['id'].'&amp;.xml');
    }
    
function help()
    {
        return '<script type="text/javascript">
        function open_help_Export() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Export</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Export</h3>");
                help_win.document.write("<p><b>vCard 3.0: </b>Places a vCard menu on each contact page to download a vCard. vCards can be directly imported to Outlook (Outlook does not support name suffixes). The vCard 3.0 format is by far not as powerful and flexible as this application, so you will loose messaging, sip/voip and other data. Notes and certificates are curretly not attached.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_Export()">help</a>';
    }

function version() {
        return '1.0';
    }
}
?>