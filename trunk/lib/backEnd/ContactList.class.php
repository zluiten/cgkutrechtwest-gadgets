<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link ContactList}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('DB.class.php');
require_once('Contact.class.php');

/**
* a list of contacts, selected by an sql-statement
*
* Handles contact fetching from database, and page management
* @package backEnd
* @uses Contact used to save the retrieved contacts
*/

class ContactList {
    
    /**
    * @var array caches the contacts, which came from the database
    */
    var $contacts;
    
    /**
    * @var integer how many entries should be displayed per page (0 means as much as possible)
    */
    var $entriesPerPage;
    
    /**
    * @var integer which page should the contacts be selected from
    */
    var $page;
    
    /**
    * @var string which query should be used to select the contacts
    */
    var $sqlQuery;
    
    /**
    * Contructor
    * 
    * initializes the class with the passed query, {@link $entriesPerPage} and
    * {@link $page} are set to 0
    * @param string $sql SQL query 'SELECT * FROM ' . TABLE_CONTACT ...
    */
function ContactList($sql) {
        $this->sqlQuery = $sql;
        $this->entriesPerPage = 0;
        $this->page = 0;
    }
    
    /**
    * sets the sql query to be used. Do not include a LIMIT statement, use the
    * setEntriesPerPage() method instead.
    * @param string $sql SQL query 'SELECT * FROM ' . TABLE_CONTACT ...
    */
function setSQLQuery($sql) {
        $this->sqlQuery = $sql;
        $this->contacts = NULL;
    }
    
    /**
    * sets how many entries should be on one page. $nr >=0 will result in a LIMIT 
    * appended to the SQL query.
    * @param integer $nr
    */
function setEntriesPerPage($nr) {
        $this->entriesPerPage = $nr;
        $this->contacts = NULL;
    }
    
    /**
    * sets the current page
    * @param integer $nr page number
    */
function setPage($nr) {
        $this->page = $nr;
        $this->contacts = NULL;
    }
    
    /**
    * retrieves and caches the contacts
    * @global DB used for database connection
    * @return array list of contacts
    */
function getContacts() {
        global $db;
        
        if ($this->contacts !== NULL)
            return $this->contacts;
        
        if ($this->entriesPerPage <= 0)
            $query = $this->sqlQuery;
        else
            $query = $this->sqlQuery . ' LIMIT ' .
                $db->escape($this->entriesPerPage * $this->page) . ',' .
                $db->escape($this->entriesPerPage);
        
        $db->query($query);
        
        $this->resetResults();
        
        while ($r = $db->next())
            $this->processQueryResult($r);
            
        return $this->contacts;
        
    }
    
    /**
    * determine how many pages are needed to show the contacts
    * @global DB used for database connection
    * @return integer number of pages needed
    */
function getPageCount() {
        global $db;
        
        if ($this->entriesPerPage == 0)
            return 1;
        
        $db->query($this->sqlQuery);
        $c = intval($db->rowsAffected());
        $db->free();
        
        return ceil($c / $this->entriesPerPage);
        
    }
    
    /**
    * deletes the cached contacts
    */
function resetResults() {
        $this->contacts = array();
    }
    
    /**
    * creates a contact from the passed data
    * @param array $r data from database
    */
function processQueryResult($r) {
        $this->contacts[] = Contact::newContact($r);
    }
    
}

?>
