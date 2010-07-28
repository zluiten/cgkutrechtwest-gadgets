<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link ChangedContactList}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('DB.class.php');
require_once('ContactList.class.php');

/**
* a list of contacts, selected by the time since they have been changed last
* @package backEnd
*/
class ChangedContactList extends ContactList {
    
    /**
    * @var integer how many days after change should a contact still be shown
    */
    var $daysAfter;
    
    /**
    * @var boolean show deleted contacts
    */
    var $showDeleted;
    
    /**
    * @var boolean show imported contacts
    */
    var $showImported;
    
    /**
    * Constructor
    * 
    * intializes the class with values, calls parent constructor
    * @param integer $daysAfter how long should a contact entry be shown after change
    * @param integer $limit how many entries to display at most
    */
function ChangedContactList($daysAfter) {
        
        $this->daysAfter = $daysAfter;
        $this->showDeleted = false;
        $this->showImported = true;
        
        $this->ContactList('');
        
        $this->resetQuery();
        
    }
    
    /**
    * set days after
    * 
    * set how many days after change a contact entry should still be shown
    * @param integer $daysAfter day count
    */
function setDaysAfter($daysAfter) {
        $this->daysAfter = $daysAfter;
        $this->resetQuery();
    }
    
    /**
    * set wheter to show deleted contacts or not
    * @param boolean $val show deleted contacts
    */
function setShowDeleted($val) {
        $this->showDeleted = $val;
        $this->resetQuery();
    }
    
    /**
    * set wheter to show imported contacts or not
    * @param boolean $val show imported contacts
    */
function setShowImported($val) {
        $this->showImported = $val;
        $this->resetQuery();
    }
    
    /**
    * reset the query (used if a value has changed)
    * @global DB used for data escaping
    */
function resetQuery() {
        
        global $db;
        
        $this->setSQLQuery('SELECT *
            FROM ' . TABLE_CONTACT . ' AS contact
            WHERE ' . ($this->daysAfter >= 0 ? '(TO_DAYS(CURRENT_DATE) - TO_DAYS(lastUpdate)) < ' . $db->escape($this->daysAfter) : $db->escape(1)) . '
            AND ' . (!$this->showImported ? 'lastModification != ' . $db->escape('imported') : $db->escape(1)) . '
            AND ' . ($this->showDeleted ? '(hidden != 1 OR lastModification = ' . $db->escape('deleted') . ')': 'hidden != 1') . '
            ORDER BY lastUpdate DESC, lastname ASC, firstname ASC'
        );
        
    }
    
}

?>
