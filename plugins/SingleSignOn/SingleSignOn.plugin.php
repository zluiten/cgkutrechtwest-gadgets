<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  SiNgLeSiGnOn PLUGIN for THE ADDRESS BOOK
 *************************************************************
* @package plugins
* @author Thomas Katzlberger
*/

require_once('plugins/SingleSignOn/SSOClient.class.php');

/** */
if(!@include_once('plugins/SingleSignOn/pconfig.php'))
    require_once('plugins/SingleSignOn/pconfig.template.php');

class SingleSignOn {
    
    function isType($t) { return $t=='contactOutput' || $t=='changedUserRecord'; }

function help()
    {
        return '<script type="text/javascript">
        function open_help_SSO() {
                help_win = window.open( "", "help", "width=320, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>SingleSignOn</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>SingleSignOn (EXPERIMENTAL, NON-FUNCTIONAL)</h3>");
                help_win.document.write("<p><b>SingleSignOn: </b>parasitically places user records into the DB of known host applications. Host applications need to be configured in the config.php of this plugin.</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_SSO()">help</a>';
    }

    /* 
     * Generate links that will do the main work ...
     */
function contactOutput(&$contact, $location)
    {
        if($location != 'beforeNotes' || FALSE === $contact->getUserId())
            return '';
        
        $cont = '';
        
        $rightsManager = RightsManager::getSingleton();
        if($rightsManager->mayViewPrivateInfo($contact))
        {
            $cont .= '<div class="other-spacer"></div>';
    
            $cont .= '<div class="other"><span class="other-label">Single Sign On</span><span class="other-info">';
            
            global $CONFIG_SSO_CLIENTS;
            foreach($CONFIG_SSO_CLIENTS as $cl)
                $cont .= '<a href="../plugins/SingleSignOn/createAccount.php?mode=' . $cl->mode . '&id=' . $contact->contact['id'] . '">' . $cl->appName . '</a>';
            
            $cont .= '</span></div>';
        } // end mayViewPrivateInfo()
        
        return $cont;
    }
    
    /* 
     * one could be notified that the password has changed here ...
     */
function changedUserRecord(&$user,$mode)
    {
        echo $user->generateFullName().' - '.$mode;
    }
    
function version() {
        return '0.3';
    }
}
?>