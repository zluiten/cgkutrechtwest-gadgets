<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/** RememberMe - persistent login plugin */
  
class RememberMe 
{
function isType($t)
    { return $t=='listMenu'; }

function makeMenuLink(&$contactList,&$nav)
    {
        $sin = "'" . session_name() . "'";
        $sid = "'" . session_id() . "'";
        $nav->addEntry('plugin.RememberMe','remember me','#',"onclick=\"createCookie($sin,$sid,5);\"");
    }
    
function help()
    {
        return '<script type="text/javascript">
        function open_help_RememberMe() {
                help_win = window.open( "", "help", "width=300, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Remember Me</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Remember Me</h3>");
                help_win.document.write("<p>Adds a list menu to extend the session cookie lifetime by 5 days (permanent login).</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_RememberMe()">help</a>';
    }
    
function version() 
    {
        return '1.0';
    }
}
?>