<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  EXPORT PLUGIN for THE ADDRESS BOOK
 *************************************************************
* @package plugins
* @author Thomas Katzlberger
*/

require_once('Navigation.class.php');

class Import {
    
    function isType($t) { return $t=='listMenu'; }

    function makeMenuLink(&$contact,&$nav)
    {
        // single vcard export allowed for guest (if viewable)
        $nav->addEntry('plugin.Import.vcard','import','../plugins/Import/interface.php');
    }
    
function help()
    {
        return '<script type="text/javascript">
        function open_help_Import() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Import</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Import</h3>");
                help_win.document.write("<p><b>vCard 3.0: </b>Places the import menu in the main menu. Then paste the text of one or more vCards into the import interface to import them. See help of the export plugin for restrictions.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_Import()">help</a>';
    }

function version() {
        return '0.9';
    }
}
?>