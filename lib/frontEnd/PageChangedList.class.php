<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageChangedList}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('ChangedContactList.class.php');
require_once('User.class.php');
require_once('Page.class.php');
require_once('TableGenerator.class.php');
require_once('HTMLHelper.class.php');

/**
* the recently changed entries page
* 
* this page is mainly used from inside {@link PageList}
* it displays the contacts were recently changed
* it is also possible to used it standalone (just call {@link create()}
* instead of {@link innerCreate()}
* @package frontEnd
* @subpackage pages
*/
class PageChangedList extends Page {
    
    /**
    * @var ChangedContactList list of contacts which were recently changed
    */
    var $contactList;
    
    /**
    * @var limit of entries
    */
    var $expanded;
    
    /**
    * Constructor: ONLY TO BE CALLED like this: Page::newPage(classname,$expanded=false) factory method!! 
    * 
    * init superclass, init {@link $contactList}
    * @param boolean $expanded expanded mode (show all)
    * @global Options determine how many days after change the contact should still be shown
    */
function PageChangedList($expanded=false) {
        global $options;
        
        $this->expanded = $expanded;
        $this->Page('Recently changed (past ' . $options->getOption('bdayInterval') . ' days)');
        
        $this->contactList = new ChangedContactList($options->getOption('bdayInterval'));
        if (!$expanded)
            $this->contactList->setEntriesPerPage($options->getOption('recentlyChangedLimit'));
        
        $this->contactList->setPage(0);
        
        $this->contactList->setShowDeleted(true);
        $this->contactList->setShowImported(false);
        
    }
    
    /**
    * create the content of recently changed
    * @return string html-content
    * @param boolean $compact whether list should be displayed with imported link and user who changed contact
    * @global Options determine how many days after change the contact should still be shown
    * @global CONFIG_LIST_NAME_SPEC
    */
function innerCreate() {
        
        global $options, $CONFIG_CHANGEDLIST_NAME_SPEC;
        
        $contacts = $this->contactList->getContacts();
        
        $data = array();
        
        foreach($contacts as $c) {
                
            $who = new User(intval($c->contact['whoModified']));
            
            if(!isset($CONFIG_CHANGEDLIST_NAME_SPEC))
                $spec = null;
            else
                $spec = $CONFIG_CHANGEDLIST_NAME_SPEC;
            
            $data[] = array(
                'display_name' => $c->generateFullName('html',$spec),
                'chdate' => date('F j',strtotime($c->contact['lastUpdate'])),
                'change' => $c->contact['lastModification'],
                'reset' => '<a href="../admin/saveadmin.php?id=' . $c->contact['id'] . '&amp;mode=imported">imported</a>',
                'who' => isset($who->contact['id']) ? '<a href="../contact/contact.php?id=' . $who->contact['id'] . '">' . $who->contact['lastname'] . (!empty($who->contact['firstname']) ? ', ' . $who->contact['firstname'] : '') . '</a>' : 'null'
            );
            
        }
        
        $tGen = new TableGenerator('changed-list');
        
        $cont = '<table class="changed-list">';
        $cont .= '<caption>Recently changed (past ' . $options->getOption('bdayInterval') . ' days)';
        
        if (!$this->expanded)
            $cont .= '&nbsp;<a href="../contact/changedlist.php">expand</a>';
 
        $cont .= '</caption>';
        
        if (count($data) > 0)
            if ($_SESSION['user']->isAtLeast('admin') && $this->expanded)
                $cont .= $tGen->generateBody($data,array('display_name','chdate','change','reset','who'));
            else
                $cont .= $tGen->generateBody($data,array('display_name','chdate','change'));
        else
            $cont .= '<tr class="noentry"><td>Nothing changed</td></tr>';
        
        $cont .= '</table>';
        
        if ($this->expanded)
            $cont .= '<div><a href="'.Navigation::previousPageUrl().'">return</a></div><br>';
        
        return HTMLHelper::createNestedDivBoxModel('changed-list',$cont);
    }

}

?>
