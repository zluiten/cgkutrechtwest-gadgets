<?php  // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageList}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('DB.class.php');
require_once('Page.class.php');
require_once('PageContact.class.php');
require_once('PageChangedList.class.php');
require_once('PageDateList.class.php');
require_once('Navigation.class.php');
require_once('GroupContactList.class.php');
require_once('TableGenerator.class.php');
require_once('GroupNormalizer.class.php');
require_once('HTMLHelper.class.php');

/**
* the list page
* 
* the list page is the main page of TAB.
* it shows links to other features of TAB and displays a list
* of all the contacts.
* @package frontEnd
* @subpackage pages
*/
class PageList extends Page {
    
    /**
    * @var GroupContactList list of contacts to display
    */
    var $contactList;
    
    /**
    * @var Navigation main navigation of list page
    */
    var $menu;
    
    /**
    * @var boolean whether to expand contact entries or not
    */
    var $expand;
    
    /**
    * Constructor: ONLY TO BE CALLED by Page factory: Page::newPage($group,$expand,$begin,$page) factory method!! 
    *
    * init {@link $contactList}, and menu
    * @param string $group group to display list for
    * @param boolean $expand whether to contact entries or not
    * @param string $begin the point in the alphabet, where the list should start (does not have to be just one character 'Sch' is also ok)
    * @param integer $page number of page to show
    * @global Options used to determine how many entries per page should be shown
    * @global PluginManager used for menu completion
    * @global string name of popup window (unused)
    */
function PageList($group='',$expand=false,$begin='',$page=0)
    {
        global $options,$pluginManager;
        
        $this->Page('List entries');
        
        // determine group to display
        if(empty($group))
        {
            if(isset($_SESSION['group']))
                $group = $_SESSION['group'];
            else
                $group = $options->getOption('defaultGroup');
        }
        else // cache current group in session
            $_SESSION['group'] = $group;
        
        $this->contactList = new GroupContactList($group);
        $this->contactList->setStartString($begin);
        $this->contactList->setPage($page);
        $this->contactList->setEntriesPerPage($options->getOption('limitEntries'));
        
        $this->expand = $expand;
        
        $this->menu = new Navigation('list-menu');
                
        $this->menu->addEntry('search','search','../contact/searchlist.php');
        
        if ($_SESSION['user']->isAtLeast('user'))
        {
            if(RightsManager::getSingleton()->currentUserIsAllowedTo('create'))
                $this->menu->addEntry('add','add new entry','../contact/contact.php?mode=new');
            
            if(RightsManager::getSingleton()->currentUserIsAllowedTo('edit-options',$_SESSION['user']))
                $this->menu->addEntry('settings','personal settings','../user/options.php');
            
            if ($_SESSION['user']->isAtLeast('admin'))
                $this->menu->addEntry('admin','admin panel','../admin/adminPanel.php');
            
            $this->menu->addEntry('logout','log out','../user/logout.php');
        }
        else
            $this->menu->addEntry('login','log in','../user/login.php');
                
        $pluginManager->listMenu($this->contactList,$this->menu);
        
    }
    
    /**
    * create the content of admin panel
    * @return string html-content
    * @global boolean whether to display whole address in the list (unused)
    * @global Options used to determine several options
    * @uses PageDateList
    * @uses PageChangedList
    * @uses createGOTO()
    * @uses createGroupSelector()
    * @uses createPageNav()
    * @uses createCharNav()
    * @uses createTable()
    */
function innerCreate() {
    
        global $options,$CONFIG_LIST_BANNER,$CONFIG_LIST_RECENTLY_CHANGED_LIMIT;
    
        $cont = '<div class="contact-list"><a name="top" id="top">'.$CONFIG_LIST_BANNER.'</a>';
        
        $cont .= $this->createInfoBox();
        
        $cont .= '<div class="list-box">';
        if ($options->getOption('bdayDisplay')) {
            // display dates and changed list
                $cont .= '<div class="upcoming-dates">';
                    $dates = Page::newPage('PageDateList');
                    $cont .= $dates->innerCreate();
                $cont .= '</div>';
        }
        if ($options->getOption('recentlyChangedDisplay')) {
                $cont .= '<div class="recently-changed">';
                    $changed = Page::newPage('PageChangedList',false);
                    $cont .= $changed->innerCreate();
                $cont .= '</div>';
        }
        $cont .= '</div><div class="clear-both"></div>'."\n";
        
        $cont .= $this->menu->create();
        
        $cont .= '<div class="contact-list-search">';
        $cont .= $this->createGOTO();
        $cont .= $this->createGroupSelector();
        $cont .= '<div class="clear-both"></div></div>'."\n";
        
        $cont .= '<div class="contact-list-navigation">';
        $cont .= $this->createPageNav();
        $cont .= $this->createCharNav();
        $cont .= '<div class="clear-both"></div></div>'."\n";
        
        $cont .= $this->createTable();
        
        $cont .= '</div>';
        
        return $cont;
        
    }
    
    /**
    * create the goto text-box
    * @return string html-content
    */
function createGOTO() {
        global $CONFIG_SEARCH_CUSTOM;
        
        $cont = '<div class="goTo">';
        
        $cont .= '<form method="post" action="search.php">';
        
        $cont .= '<label for="goTo">go to</label>';
        
        $cont .= '<input type="text" name="goTo" id="goTo">&nbsp;&nbsp;&nbsp;';
        
        $cont .= '<select name="type" onchange="var v=this.options[this.selectedIndex].value; createCookie(\'searchtype\',v,365);">';
        
        $cont .= '<option value="name">Name</option>';
        $cont .= '<option value="email">E-Mail addresses</option>';
        $cont .= '<option value="www">Websites</option>';
        $cont .= '<option value="chat">Messaging</option>';
        $cont .= '<option value="address">Addresses</option>';
        $cont .= '<option value="phone">Phonenumbers</option>';
        
        $n=count($CONFIG_SEARCH_CUSTOM);
        for($i=0;$i<$n;$i++)
            $cont .= "<option value='custom_$i'>".$CONFIG_SEARCH_CUSTOM[$i].'</option>';
        
        $cont .= '</select>';
        
        $cont .= '</form>';
        
        $cont .= '<div id="autocompletegoto-hint"></div>';
        
        $cont .= <<<EOC
<script type="text/javascript">
new Ajax.Autocompleter("goTo","autocompletegoto-hint","../contact/autocompletegoto.ajax.php");
document.getElementById('goTo').focus();
createCookie("searchtype","name",365);
</script>
EOC;
        
        $cont .= '</div>';
        
        return $cont;
        
    }
    
    /**
    * create the info-box
    *
    * the info box shows the user which is currently logged in,
    * it also shows a short description of the user rights and
    * the custom welcome-message
    * @return string html-content
    * @global Options used to determine the welcome message
    */
function createInfoBox() {
        
        global $options;
        
        // create info box
        $cont = '<div class="info-box">';
        
        if ($options->getOption('msgWelcome') != '')
            $cont .= '<div class="welcome-message">' . $options->getOption('msgWelcome') . '</div>';
        
        if (!is_a($_SESSION['user'],'GuestUser'))
        {
            $cont .= '<div class="login-status"><span>You are currently logged in as:</span> ' . $_SESSION['user']->contact['firstname'] . ' ' . $_SESSION['user']->contact['lastname'] . '</div>';
            if ($_SESSION['user']->isAtLeast('admin'))
                $cont .= '<div class="access-status">You have administrator access.</div>';
            elseif ($_SESSION['user']->isAtLeast('manager'))
                $cont .= '<div class="access-status">You have manager access.</div>';
            elseif ($_SESSION['user']->isAtLeast('user'))
                $cont .= '<div class="access-status">You have normal user access.<br>Entries you may modify are displayed in green.</div>';
        }
        $cont .= '</div>';
        
        return $cont;
        
    }
    
    /**
    * create the page navigation
    *
    * the page navigation is only shown, if the currently selected character
    * contains more entries, than fit on one page
    * @return string html-content
    */
function createPageNav() {
        
        $count = $this->contactList->getBoundedPageCount();
        
        $cont = '';
        
        if ($count > 1) {
            
            $showCount = 2;
            $distThreshold = 10;
            $cur = $this->contactList->page;
            
            for ($i=$cur-$showCount;$i<=$cur+$showCount;$i++)
                if ($i >= 0 && $i < $count)
                    $cont .= '<a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($this->contactList->start) . '&amp;page=' . $i . '"' . ($i==$this->contactList->page?' class="curpage"':'') . '>[' . intval($i+1) . ']</a>';
            
            $i = floor($cur/2);
            if ($cur >= $distThreshold)
                $cont = '<a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($this->contactList->start) . '&amp;page=' . $i . '"' . ($i==$this->contactList->page?' class="curpage"':'') . '>[' . intval($i+1) . ']</a>' .
                        '...' . $cont;
                
            $i = 0;
            if ($cur-$showCount > 1)
                $cont = '<a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($this->contactList->start) . '&amp;page=' . $i . '"' . ($i==$this->contactList->page?' class="curpage"':'') . '>[' . intval($i+1) . ']</a>' .
                        '...' . $cont;
                        
            if ($cur-$showCount == 1)
                $cont = '<a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($this->contactList->start) . '&amp;page=' . $i . '"' . ($i==$this->contactList->page?' class="curpage"':'') . '>[' . intval($i+1) . ']</a>' .
                        $cont;
            
            $i = ceil(($count-1+$cur)/2);
            if ($count-$cur > $distThreshold)
                $cont .= '...<a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($this->contactList->start) . '&amp;page=' . $i . '"' . ($i==$this->contactList->page?' class="curpage"':'') . '>[' . intval($i+1) . ']</a>';
            
                        
            $i = $count-1;
            if ($cur+$showCount < $i-1)
                $cont .= '...<a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($this->contactList->start) . '&amp;page=' . $i . '"' . ($i==$this->contactList->page?' class="curpage"':'') . '>[' . intval($i+1) . ']</a>';
            
            if ($cur+$showCount == $i-1)
                $cont .= '<a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($this->contactList->start) . '&amp;page=' . $i . '"' . ($i==$this->contactList->page?' class="curpage"':'') . '>[' . intval($i+1) . ']</a>';
            
            
            $cont = '<div class="page-nav">' . $cont;
            $cont .= '</div>';
        
        }
        
        return $cont;
        
    }
    
    /**
    * create the char navigation
    *
    * the char navigation shows a link for each first character which is
    * in the contact list. this allows easy navigation even in big lists
    * @return string html-content
    * @global GroupNormalizer needed to convert characters in their basic form (i.e. to convert ö to o)
    */
function createCharNav() {
        
        global $groupNormalizer;
        
        $chars = $this->contactList->getFirstChars();
        $start = $groupNormalizer->normalize(mb_strtolower(mb_substr($this->contactList->start,0,1)));
        $finish = $groupNormalizer->normalize(mb_strtolower(mb_substr($this->contactList->getRightStringBound(),0,1)));
        $rf = ($finish != mb_strtolower(mb_substr($this->contactList->getRightOuterStringBound(),0,1)));
        $in = false;

        $cont = '<ul class="char-nav">';
        
        foreach ($chars as $char) {
            $class = '';
            if ($start != '') {
                if ($groupNormalizer->normalize(mb_strtolower($char)) == $start) {
                    $class = 'cur';
                    $in = true;
                }
                if ($groupNormalizer->normalize(mb_strtolower($char)) == $finish) {
                    $in = false;
                    if ($finish == $start) {
                        // don't change class from start
                    } elseif ($rf) {
                        $class = 'obs';
                    } else
                        $class = 'tail';
                }
                if ($in && $groupNormalizer->normalize(mb_strtolower($char)) != $start)
                    $class = 'obs';
            }
                
            $cont .= '<li' . ($class?' class="' . $class . '"':'') . '><a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=' . rawurlencode($char) . '">' . $groupNormalizer->normalize(mb_strtoupper($char)) . '</a></li>';
                
        }
        
        $class = '';
        
        if (!$this->contactList->start)
            $class = 'cur';
        
        $cont .= '<li' . ($class?' class="' . $class . '"':'') . '><a href="../contact/list.php?group=' . $this->contactList->group . '&amp;begin=">[all]</a></li>';
    
        if ($this->expand)
            $cont .= '<li><a href="../contact/list.php?group=' . $this->contactList->group .
                '&amp;begin=' . $this->contactList->start .
                '&amp;page=' . $this->contactList->page . '">collapse</a></li>';
        else
            $cont .= '<li><a href="../contact/list.php?group=' . $this->contactList->group .
                '&amp;begin=' . $this->contactList->start .
                '&amp;page=' . $this->contactList->page .
                '&amp;expand=1">expand</a></li>';
            
        $cont .= '</ul>';
        
        return $cont;
        
    }
    
    /**
    * create the dropdown for group selection
    *
    * @return string html-content
    * @global DB used to query database for groups
    */
function createGroupSelector() {
        
        global $db;
        
        // create group selector
        $cont = '<div class="group-selector">';
        $cont .= '<form id="selectGroup" method="get" action="list.php">';
        $cont .= '<label for="group">select group</label>';
        $cont .= '<select name="group" id="group" onchange="document.getElementById(\'selectGroup\').submit()">';
        
        $cont .= '<option value="all"' . ($this->contactList->group == ''?' selected="selected"':'') . '>(all entries)</option>';
        
        if ($_SESSION['user']->isAtLeast('admin'))
            $cont .= '<option value="hidden"' . ($this->contactList->group == 'hidden'?' selected="selected"':'') . '>(hidden entries)</option>';
            
        $cont .= '<option value="ungrouped"' . ($this->contactList->group == 'ungrouped'?' selected="selected"':'') . '>(ungrouped entries)</option>';
        
        $db->query('SELECT * FROM ' . TABLE_GROUPLIST . ' WHERE groupname NOT LIKE "#h#%" ORDER BY groupname ASC');
        
        while ($r = $db->next())
            $cont .= '<option' . ($this->contactList->group == $r['groupname']?' selected="selected"':'') . '>' . $r['groupname'] . '</option>';
        
        $cont .= '</select>';
        $cont .= '</form>';
        $cont .= '</div>';
        
        return $cont;
        
    }
    
    /**
    * create the table containing the contacts
    *
    * @uses Contact
    * @return string html-content
    * @global GroupNormalizer used to modify the contact names, in order to get them correctly grouped
    * @uses TableGenerator
    */
function createTable() {
        
        global $groupNormalizer;
        
        // create big table
        $contacts = $this->contactList->getContacts();
        
        $data = array();
        
        foreach($contacts as $c) {
            
            if ($this->expand) {
                
                $p = Page::newPage('PageContact',$c);
                
                $data[] = array(
                    'cont' => $p->innerCreate(),
                    'css_class' => 'list-expanded-card',
                    'group_n' => $groupNormalizer->normalize(mb_substr($c->contact['lastname'],0,1))
                );
                
                continue;
            }
            
            $data[] = $c->generateListRowArray();            
        }
        
        $tGen = new TableGenerator('contact-list');
        
        $cont = '<table class="contact-list">';
        $cont .= '<caption>' . $this->contactList->getGroupCaption() . '</caption>';
        
        if (count($data) > 0)
        {
            if ($this->expand)
                $cont .= $tGen->generateBody($data,array('cont'),'css_class','group_n');
            else 
                $cont .= $tGen->generateBody($data,range(0,count($data[0])-3),'css_class','group_n');
        }
        else
            $cont .= '<tr class="noentry"><td>No Entries.</td></tr>';
        
        $cont .= '</table>';
        
        return HTMLHelper::createNestedDivBoxModel('contact-list',$cont);
    }
    
}

?>
