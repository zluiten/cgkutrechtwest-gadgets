<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageFeatureList}
* @author Thomas Katzlberger
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');
require_once('TableGenerator.class.php');

/**
* this is a ranklist that displays a ranking for a group of people
* 
* @package frontEnd
* @subpackage pages
* @uses DateContactList
*/
class PageAdministrativeRequestsReport extends Page {
    
    /**
    * Constructor                      
    * 
    * init superclass, init {@link $contactList}
    * @global Options determine how many days after change the contact should still be shown
    */
function PageAdministrativeRequestsReport() {
        
        $this->Page('Administrative Requests Report');
    }
    
    /**
    * create the content of recently changed
    * @return string html-content
    * @global Options determine how many days after change the contact should still be shown
    * @param boolean $compact whether list should be displayed with imported link and user who changed contact
    */
function innerCreate() {
        
        global $db,$CONFIG_DB_PREFIX,$CONFIG_ADMIN_REQUEST_INTERFACE,$CONFIG_ADMIN_REQUEST_BREAKS;
        
        $db->query("SELECT * FROM `{$CONFIG_DB_PREFIX}AdministrativeRequests` AS request WHERE dateProcessed IS NULL OR ( DATE_ADD(dateProcessed, INTERVAL 14 DAY) >= NOW() )",'AdministrativeRequest');
            //. TABLE_PROPERTIES . " AS prop 
            //WHERE contact.id=prop.id AND prop.label=" . $db->escape($this->featureLabel) . '
            //ORDER BY prop.value ' . ($this->ascending ? 'ASC' : 'DESC') . ($this->limit > 0 ? ' LIMIT '.$this->limit : ''));

        $fields = array('contactId','dateAdded','requesterId','dateProcessed','whoProcessedId');
        foreach($CONFIG_ADMIN_REQUEST_INTERFACE as $k => $v)
            if(substr($k,0,4)!='html' && $k!='submit') // not for DB!
                $fields[] = $k;
            
        $data = array();
        $i=0;
        while($c = $db->next('AdministrativeRequest'))
        {
            $data[$i] = $c;
            
            if(empty($data[$i]['dateProcessed']))
            {
                $id = $data[$i]['requestId'];
                $data[$i]['dateProcessed'] = "<a href='todo?mode=done&id=$id'>set done</a>";
            }
            
            if(!empty($data[$i]['whoProcessedId'])) // effectively who pressed the set done link
            {
                $contact = Contact::newContact($data[$i]['whoProcessedId']);
                $data[$i]['whoProcessedId'] = '<a href="../contact/contact.php?id=' . $contact->contact['id'] . '">' . $contact->contact['lastname'] . (!empty($contact->contact['firstname']) ? ', ' . $contact->contact['firstname'] : '') . '</a>';
            }
            
            $contact = Contact::newContact($data[$i]['contactId']);
            $data[$i]['contactId'] = '<a href="../contact/contact.php?id=' . $contact->contact['id'] . '">' . $contact->contact['lastname'] . (!empty($contact->contact['firstname']) ? ', ' . $contact->contact['firstname'] : '') . '</a>';
            
            $contact = Contact::newContact($data[$i]['requesterId']);
            $data[$i]['requesterId'] = '<a href="../contact/contact.php?id=' . $contact->contact['id'] . '">' . $contact->contact['lastname'] . (!empty($contact->contact['firstname']) ? ', ' . $contact->contact['firstname'] : '') . '</a>';
            
            $i++;
        }

        $cont = '<style>.parr-list { margin: 20px auto 20px auto; width: 90%; } .parr-list th { border: 1px solid; } .parr-list td { border: 1px solid  #AAA; } td.parr-list-tdblank { border: none; } </style>';
        
        $tGen = new TableGenerator('parr-list',$fields,$CONFIG_ADMIN_REQUEST_BREAKS);
        $cont .= $tGen->generateTable($data,$fields);
        
        $cont .= '<div><a href="'.Navigation::previousPageUrl().'">return</a></div><br>';
        
        return $cont;
        
    }

}

?>
