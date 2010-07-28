<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link PageSearchResult}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');


/**
* the search result page
* 
* the search result page displays the results of a search,
* passed with a {@link ContactList}
* @package frontEnd
* @subpackage pages
*/
class PageSearchResult extends Page {
    
    /**
    * @var ContactList list of contacts to display
    */
    var $contactList;
    
    /**
    * Constructor
    *
    * inits superclass and sets {@link $contactList}
    * @param ContactList $contactList list of contacts to display
    */
function PageSearchResult($cList) {
        
        $this->Page('Search results');
        
        $this->contactList = $cList;
        
    }
    
    /**
    * creates list of contacts, or a no entries found page
    * @return string html-content
    */
function innerCreate() {        
        $cont = '<div class="search-result">';
        
        $cont .= '<div class="search-title">Search results</div>';
        
        $conts = $this->contactList->getContacts();
        
        if (count($conts) <= 0)
        
            $cont .= '<div class="search-text">No entries found</div>';
            
        else {
            
            $cont .= '<div class="search-text">Multiple entries found. Please select one</div>';
        
            $cont .= '<ul class="search-contacts">';
            
            foreach ($conts as $c)
                $cont .= '<li><a href="../contact/contact.php?id=' . $c->contact['id'] . '">' . $c->contact['lastname'] . ', ' . $c->contact['firstname'] . '</a></li>';
            
            $cont .= '</ul>';
            
        }
        
        $cont .= '<div class="search-text"><a href="'.Navigation::previousPageUrl().'">return</a></div>';
        
        $cont .= '</div>';
        
        return $cont;
    }
}

?>
