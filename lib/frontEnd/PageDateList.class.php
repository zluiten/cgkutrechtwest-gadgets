<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageDateList}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('DateContactList.class.php');
require_once('Page.class.php');
require_once('TableGenerator.class.php');
require_once('HTMLHelper.class.php');

/**
* the upcoming dates page
* 
* this page is mainly used from inside {@link PageList}
* it displays the contacts which have dates upcoming
* it is also possible to used it standalone (just call {@link create()}
* instead of {@link innerCreate()}
* @package frontEnd
* @subpackage pages
* @uses DateContactList
*/
class PageDateList extends Page {
    
    /**
    * @var DateContactList list of contacts which have upcoming dates
    */
    var $contactList;
    
    /**
    * Constructor
    * 
    * init superclass, init {@link $contactList}
    * @global Options determine how many days before event should contact be shown?
    */
function PageDateList() {
        
        global $options;
        
        $this->Page('Upcoming dates (next ' . $options->getOption('bdayInterval') . ' days)');
        
        $this->contactList = new DateContactList($options->getOption('bdayInterval')); // NO EXTRA OPTION, REUSE THIS
        $this->contactList->setEntriesPerPage($options->getOption('recentlyChangedLimit'));
        $this->contactList->setPage(0);
        
    }
    
    /**
    * create the content of upcoming dates
    * @return string html-content
    * @global Options determine how many days before event should contact be shown?
    */
function innerCreate() {
        
        global $options,$CONFIG_DATELIST_NAME_SPEC;
        
        $contacts = $this->contactList->getContacts();
        $dates = $this->contactList->getDates();
        
        $data = array();
        
        $time = getdate();
        
        if(!isset($CONFIG_DATELIST_NAME_SPEC))
            $spec = null;
        else
            $spec = $CONFIG_DATELIST_NAME_SPEC;
        
        foreach($contacts as $k => $c) {
            
            $data[] = array(
                'display_name' => $c->generateFullName('html',$spec),
                'type' => $dates[$k]['type'],
                'time' => date('M j',strtotime('1975-' . $dates[$k]['month'] . '-' . $dates[$k]['day'])) . ($dates[$k]['year']!='0000'?', ' . $dates[$k]['year']:''),
                'age' => intval($time['year'] - $dates[$k]['year']) . 'y',
                'daysAway' => ($dates[$k]['daysAway']>0?$dates[$k]['daysAway'] . 'd':'today')
            );
            
        }
        
        $tGen = new TableGenerator('changed-list');
        
        $cont = '<table class="changed-list">';
        $cont .= '<caption>Upcoming dates (Next ' . $options->getOption('bdayInterval') . ' days)</caption>';
        
        if (count($data) > 0)
            $cont .= $tGen->generateBody($data,array('display_name','type','time','age','daysAway'));
        else
            $cont .= '<tr class="noentry"><td>Nothing upcoming</td></tr>';
        
        $cont .= '</table>';
        
        return HTMLHelper::createNestedDivBoxModel('changed-list',$cont);
    }
}

?>
