<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageSearchList}
* @author Thomas Katzlberger
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('DB.class.php');
require_once('Page.class.php');
require_once('PageList.class.php');
require_once('PageContact.class.php');
require_once('TableGenerator.class.php');
require_once('EmailHelper.class.php');
require_once('GroupNormalizer.class.php');

/**
* the search list page
* 
* the search list page allows searches similar to the autocomplete function.
* The page usually displays the full search result, although it can limit the result (without navigation)
* @package frontEnd
* @subpackage pages
*/
class PageSearchList extends Page {
    
    /**
    * @var ContactList list of contacts to display
    */
    var $contactList;
        
    /**
    * @var boolean whether to expand contact entries or not
    */
    var $expand;

    /**
    * @var last searchstring
    */
    var $search;

    /**
    * @var last searchtype, searchtype result is sorted
    */
    var $searchtype, $isSorted;

    /**
    * @var Navigation basic nav menu: return (to list) and expand
    */
    var $nav;

    /**
    * Constructor: ONLY TO BE CALLED by factory method of Page::newPage(...)! 
    *
    * init {@link $contactList}, and menu
    * @param string $search part of a searchtype (e.g. name or email)
    * @param string $searchtype [name|email|www|chat|phone|custom_?] Custom searches defined in config.php, shared with autocomplete.
    * @param boolean $expand whether to expand entries or not
    * @param integer $maxEntriesPerPage limit of entries (default = 0 which means unlimited) 
    */
function PageSearchList($search,$searchtype,$expand=false,$maxEntriesPerPage=0)
    {
        $this->Page('Search List');
        
        $this->search = $search;
        $this->searchtype = $searchtype;
        $this->expand = $expand;

        $this->nav = new Navigation('options-menu');
        $this->nav->addEntry('expand','expand','../contact/searchlist.php?search=' . $search .
                '&amp;type=' . $searchtype . '&amp;expand=1');
        $this->nav->addEntry('return','return',Navigation::previousPageUrl());
        
        $sql=$this->createQuery();
        $this->contactList = new ContactList($sql);
        $this->contactList->setEntriesPerPage($maxEntriesPerPage);
    }

    /**
    * Create search query
    *
    * init {@link $contactList}, and menu
    * @param search $search partial string to match
    * @param searchtype $searchtype [name|email|www|chat|phone|custom_?] Custom searches defined in config.php, shared with autocomplete.
    * @global array custom searchtypes defined in config.php
    * @global DB used for database access
    */
function createQuery()
    {
        global $CONFIG_SEARCH_CUSTOM, $db;
        
        $sql="SELECT * FROM " . TABLE_CONTACT . " AS contact WHERE id=-1"; // create an empty result - any better way to do this??
                                                                       
        $admin = intval($_SESSION['user']->isAtLeast('admin'));
        
        $search = $this->search;
        
        if($search=='')
            return $sql;
        
        // unsorted union queries
        $this->isSorted = $this->searchtype!='name' && $this->searchtype!='phone';
        
        switch ($this->searchtype)
        {
            case 'name':
                $p = $db->escape("$search%");
                $sel1 = "SELECT * FROM " . TABLE_CONTACT . " AS contact WHERE ";
                $sel2 = "SELECT * FROM " . TABLE_CONTACT . " AS contact WHERE ";
                $where1 = "(lastname LIKE $p) AND (hidden = 0 OR $admin) ORDER BY lastname";
                $where2 = "(firstname LIKE $p OR nickname LIKE $p) AND (hidden = 0 OR $admin) ORDER BY firstname";
                $sql = "($sel1 $where1) UNION ($sel2 $where2)";
                break;
            case 'email':
            case 'www':
            case 'chat':
                $p = $db->escape("%$search%");
                $sel = "SELECT * FROM " . TABLE_CONTACT . " AS contact, " . TABLE_PROPERTIES . " AS properties WHERE ";
                $where = "contact.id=properties.id AND properties.type = " . $db->escape($this->searchtype) . " AND properties.value LIKE $p AND (properties.visibility = 'visible' OR $admin) AND (contact.hidden = 0 OR $admin) ORDER BY lastname";
                $sql = "$sel $where";
                break;
            case 'phone':
                $p = $db->escape("%$search%");
                $sel = "SELECT contact.* FROM " . TABLE_CONTACT . " AS contact, " . TABLE_PROPERTIES . " AS properties WHERE ";
                $where = "contact.id=properties.id AND properties.type = " . $db->escape($this->searchtype) . " AND properties.value LIKE $p AND (properties.visibility = 'visible' OR $admin) AND (contact.hidden = 0 OR $admin) ORDER BY lastname";
                $sql = "$sel $where";
                break;
            case 'address':
                $p = $db->escape("%$search%");
                $sel = "SELECT * FROM " . TABLE_CONTACT . " AS contact, " . TABLE_ADDRESS . " AS address WHERE ";
                $where = "contact.id=address.id AND (line1 LIKE $p OR line2 LIKE $p OR city LIKE $p) AND (hidden = 0 OR $admin) ORDER BY lastname";
                $sql = "$sel $where";
                break;
            default:
                $p = $db->escape("%$search%");
                $n=count($CONFIG_SEARCH_CUSTOM);
                for($i=0;$i<$n;$i++)
                {
                    if($this->searchtype=="custom_$i")
                    {
                        $sel = "SELECT *
                        FROM " . TABLE_CONTACT . " AS contact, " . TABLE_PROPERTIES . " AS properties WHERE ";
                        $where = "contact.id=properties.id AND properties.type = 'other' AND properties.label = '".$CONFIG_SEARCH_CUSTOM[$i]."' AND properties.value LIKE $p AND (properties.visibility = 'visible' OR $admin) AND (contact.hidden = 0 OR $admin) ORDER BY lastname";
                        $sql = "$sel $where";
                        break;
                    }
                }
                break;
        }
        
        return $sql;
    }
    
    /**
    * create the content of the search list
    * @return string html-content
    */
function innerCreate()
    {
        $cont = '<div class="contact-list">';
                
        $cont .= $this->createSearchInterface();
        $x = &$this->createTable(); // loads a mailto in nav!
        $cont .= $this->nav->create();        
        $cont .= $x;
        
        $cont .= '</div>';
        
        return $cont;        
    }
    
    /**
    * create the goto text-box
    * @return string html-content
    * @global array custom searchtypes defined in config.php
    */
function createSearchInterface() {
        global $CONFIG_SEARCH_CUSTOM;
        
        $cont = "\n<div class='goTo'>";
        
        $cont .= '<form method="get" action="../contact/searchlist.php">';
        
        $cont .= '<label for="search">Search</label>';
        
        $cont .= '<input type="text" name="search" id="search" value="'.$this->search.'"/>';
        
        $cont .= '&nbsp;&nbsp;&nbsp;<select name="type">';
        
        $cont .= '<option value="name"    '.($this->searchtype=='name' ? 'selected="selected"' : '').'>Name</option>';
        $cont .= '<option value="email"   '.($this->searchtype=='email' ? 'selected="selected"' : '').'>E-Mail addresses</option>';
        $cont .= '<option value="www"     '.($this->searchtype=='www' ? 'selected="selected"' : '').'>Websites</option>';
        $cont .= '<option value="chat"    '.($this->searchtype=='chat' ? 'selected="selected"' : '').'>Messaging</option>';
        $cont .= '<option value="address" '.($this->searchtype=='address' ? 'selected="selected"' : '').'>Addresses</option>';
        $cont .= '<option value="phone"   '.($this->searchtype=='phone' ? 'selected="selected"' : '').'>Phonenumbers</option>';
        
        $n=count($CONFIG_SEARCH_CUSTOM);                                               
        for($i=0;$i<$n;$i++)
            $cont .= "<option value='custom_$i' ".($this->searchtype=="custom_$i" ? 'selected="selected"' : '').">".$CONFIG_SEARCH_CUSTOM[$i].'</option>';
        
        $cont .= '</select>';
        $cont .= '&nbsp;&nbsp;&nbsp;<input type="submit" value="Go" id="submit" />';
        $cont .= '</form>';                
        $cont .= '</div><div><br><br></div>'; // make some space ...
        
        return $cont;
    }
        
    /**
    * create the table containing the contacts
    *
    * @uses Contact
    * @return string html-content
    * @uses TableGenerator
    */
function createTable()
    {
        // create big table
        $contacts = $this->contactList->getContacts();
        
        $mailtohref = ''; // email link
        $data = array();
        
        $htmlId=0;
        foreach($contacts as $c) {
            
            if ($this->expand) {
                
                $p = Page::newPage('PageContact',$c);
                
                global $groupNormalizer;
                $data[] = array(
                    'cont' => $p->innerCreate(),
                    'css_class' => 'list-expanded-card',
                    'group_n' => $groupNormalizer->normalize(mb_substr($c->contact['lastname'],0,1)));
                
                continue;
            }
            
            // fetch first email ...
            $mails = $c->getValueGroup('email');
            $e = EmailHelper::sendEmailHref($c->rawEmail($mails[0]));
            $mailtohref .= $e .',';
            
            $r = $c->generateListRowArray();
            $r[] = "<input type='checkbox' checked=1 name='$e' id='cx$htmlId' onchange='generateMailto();'/>"; 
            $data[] = $r;
            $htmlId++;
        }
        
        $tGen = new TableGenerator('contact-list');
        
        $cont = '<table class="contact-list">';
        
        //$cont .= '<caption>' . $this->contactList->getGroupCaption() . '</caption>';
        
        if (count($data) > 0)
        {
            if ($this->expand)
                $cont .= $tGen->generateBody($data,array('cont'),'css_class',$this->isSorted ? 'group_n' : null);
            else 
                $cont .= $tGen->generateBody($data,range(0,count($data[0])-3),'css_class',$this->isSorted ? 'group_n' : null);
        }
        else
            $cont .= '<tr class="noentry"><td>No Entries.</td></tr>';
        
        $cont .= '</table>';
        
        $this->nav->addEntry('mailtoSelected','mail to selected',"mailto:$mailtohref");
        
        return $cont;   
    }
}
?>
