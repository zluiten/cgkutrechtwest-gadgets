<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageExpiredList}
* @author Thomas Katzlberger
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');
require_once('TableGenerator.class.php');
require_once('StringHelper.class.php');

/**
* this is a list of 'expired' users of the ssl-ca 
* 
* @package frontEnd
* @subpackage pages
* @uses DateContactList
*/
class PageExpiredList extends Page {
    
    /**
    * Constructor
    * 
    * init superclass, init {@link $contactList}
    * @global Options determine how many days after change the contact should still be shown
    */
function PageExpiredList() {
        
        $this->Page('SSL_CA Expired List');
    }
    
function privateExpiredData($c)
    {
    }
    
    /**
    * create the content of recently changed
    * @return string html-content
    * @global Options determine how many days after change the contact should still be shown
    * @param boolean $compact whether list should be displayed with imported link and user who changed contact
    */
function innerCreate() {
        
        global $options;
        global $db;
        
        $db->query('SELECT *
            FROM ' . TABLE_CONTACT . ' AS contact, ' . TABLE_GROUPS . ' AS groups, ' . TABLE_GROUPLIST . ' AS grouplist  
            WHERE ( certState="issued" OR  certState="used" ) AND ( DATE_ADD(certLastUsed, INTERVAL 3 MONTH) < NOW() OR certLastUsed IS NULL ) AND contact.id=groups.id AND groups.groupid=grouplist.groupid
            ORDER BY groupname, certLastUsed DESC',1);
          
        $mailto ='';
        $currentGroup = null;
        $data = array();
        $neverUsed = 0;
        while(1) 
        {
            $c = $db->next(1);
            
            if($currentGroup != $c['groupname'] || $c==null)
            {
                if($currentGroup != null)
                {
                    $subject = htmlentities($options->getOption('adminEmailSubject') . ': Security Certificate Expiration Notice');
                    $body = str_replace("\n",'%0A',htmlentities($options->getOption('adminEmailFooter')));
                    $data[] = array(
                        'display_name' => "Expired: <a href='mailto:?bcc=$mailto&subject=".$subject.'&body='.$body."'>mailto expired</a>",
                        'certLastUsed' => '&nbsp;',
                        'certState' => '&nbsp;',
                        'groupname' => $currentGroup
                    );
                    $mailto = '';
                }
                
                if($c==null)
                    break;
                
                $currentGroup = $c['groupname'];
            }

            $data[] = array(
                'display_name' => '<a href="../../contact/contact.php?id=' . $c['id'] . '">' . $c['lastname'] . ', ' . $c['firstname'] . '</a>',
                'certLastUsed' => $c['certLastUsed'],
                'certState' => $c['certState'],
                'groupname' => $c['groupname']
            );
            
            if($c['certState']=='issued') // never used
                $neverUsed++;
            
            $co = new Contact($c['id']);
            $mailto .= $co->getFirstEmail().',';            
        }                   
        
        // START OUTPUT
        $cont = '<div>&nbsp;</div>';
        
        $db->query('SELECT MAX(certLastUsed) AS number 
            FROM ' . TABLE_CONTACT . ' AS contact 
            WHERE certState="issued" OR  certState="used"');
        $r = $db->next();
        
        $cont .= '<div>Newest update: '.$r['number'].'</div><br>';
        
        $db->query('SELECT COUNT(*) AS number 
            FROM ' . TABLE_CONTACT . ' AS contact 
            WHERE certState="issued" OR  certState="used"');
        $r = $db->next();
        $cont .= '<div>Certificates (issued or used): '.$r['number'].'</div><br>';

        $db->query('SELECT COUNT(*) AS number 
            FROM ' . TABLE_CONTACT . ' AS contact 
            WHERE certState="used" AND DATE_ADD(certLastUsed, INTERVAL 3 MONTH) >= NOW()');
        $r = $db->next();
        $cont .= '<div>Certificates used within last 3 month: '.$r['number'].'</div><br>';
        
        $cont .= '<div>Unused certificates (entries in this list): '.count($data).' (multi grouped entries listed multiple times)</div><br>';
        $cont .= '<div>Never used (issued): '.$neverUsed.'</div><br>';
        
        // generate Table
        $tGen = new TableGenerator('changed-list');
        
        $cont .= '<table class="changed-list">';
        
        $cont .= '<caption>Unused Certificates (unused >3 month)</caption>';
        
        if (count($data) > 0)
            $cont .= $tGen->generateBody($data,array('display_name','certLastUsed','certState'),'','groupname',false);
        else
            $cont .= '<tr class="noentry"><td>Not found</td></tr>';
        
        $cont .= '</table>';

        $cont .= '<div><a href="gencerts.php">return</a></div><br>';
                
        return $cont;
        
    }

}

?>
