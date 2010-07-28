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
*/
class PageFeatureList extends Page {

    var $featureLabel,$ascending,$limit;
    
    /**
    * Constructor: ONLY TO BE CALLED like this: Page::newPage(classname,$featureLabel,$ascending=false,$limit=0) factory method!! 
    * 
    * @param $featureLabel label of property in property table
    * @param $ascending sort order
    * @param $limit show first n entries
    * @global Options determine how many days after change the contact should still be shown
    */
function PageFeatureList($featureLabel,$ascending=false,$limit=0)
    {
        $this->Page('Feature List');
        $this->featureLabel=$featureLabel;
        $this->ascending=$ascending;
        $this->limit=$limit;
    }
    
    /**
    * create the content of recently changed
    * @return string html-content
    * @global Options determine how many days after change the contact should still be shown
    */
function innerCreate()
    {
        global $options;
        global $db;
        
        $db->query('SELECT *
            FROM ' . TABLE_CONTACT . ' AS contact, ' . TABLE_PROPERTIES . " AS prop 
            WHERE contact.id=prop.id AND prop.label=" . $db->escape($this->featureLabel) . '
            ORDER BY prop.value ' . ($this->ascending ? 'ASC' : 'DESC') . ($this->limit > 0 ? ' LIMIT '.$this->limit : ''));

        $data = array();
        
        while($c = $db->next()) {
            
            $data[] = array(
                //'display_name' => '<a href="../contact/contact.php?id=' . $c['id'] . '">' . $c['lastname'] . ', ' . $c['firstname'] . '</a>',
                'display_name' => $c['lastname'] . ', ' . $c['firstname'],
                'feature' => $c['value']
            );
            
        }
        
        $tGen = new TableGenerator('changed-list');
        
        $cont = '<table class="changed-list">';
        
        $cont .= '<caption>Ranklist';
         
        $cont .= '</caption>';
        
        if (count($data) > 0)
            $cont .= $tGen->generateBody($data,array('display_name','feature'));
        else
            $cont .= '<tr class="noentry"><td>Not found</td></tr>';
        
        $cont .= '</table>';
        
        return $cont;
        
    }
    
    /**
    * Suppress footer.
    * @return string empty
    */
function footerCreate()
    {
        return '';
    }

}

?>
