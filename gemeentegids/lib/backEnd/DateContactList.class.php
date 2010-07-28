<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link DateContactList}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('DB.class.php');
require_once('ContactList.class.php');

/**
* a list of contacts, selected by the time until they will have their birthday
* @package backEnd
*/
class DateContactList extends ContactList {
    
    /**
    * @var integer how many days before the birthday change should be shown
    */
    var $daysBefore;
    
    /**
    * @var array cache the dates selected from database
    */
    var $dates;
    
    /**
    * Constructor
    * 
    * intializes the class with values, calls parent constructor
    * @param integer $daysBefore how many days should a contact entry be shown before his birthday
    */
function DateContactList($daysBefore) {
        
        $this->daysBefore = $daysBefore;
        
        $this->ContactList('');
                          
        $this->resetQuery();
        
    }
    
    /**
    * set days before
    * 
    * set how many days before the birthday a contact entry should be shown
    * @param integer $daysBefore day count
    */
function setDaysBefore($daysBefore) {
        $this->daysBefore = $daysBefore;
        resetQuery();
    }
    
    /**
    * returns the dates, belonging to the contacts in the same order
    * @return array list of associative arrays with info about the dates
    */
function getDates() {
        $this->getContacts();
        return $this->dates;
    }
    
    /**
    * deletes the data
    */
function resetResults() {
        $this->dates = array();
        parent::resetResults();
    }
    
    /**
    * processes each row returned from db
    *
    * overrides function from {@link ContactList};
    * saves the dates from db to {@link $dates}, then passes
    * contoll to {@link ContactList::processQueryResults()}
    */
function processQueryResult($r) {
        $this->dates[] = array(
            'type' => $r['label'],
            'daysAway' => $r['daysAway'],
            'repeat' => $r['type'],
            'ongoing' => $r['ongoing'],
            'day' => $r['day'],
            'month' => $r['month'],
            'year' => $r['year']
        );
        parent::processQueryResult($r);
    }
    
    /**
    * reset the query (used if a value has changed)
    * @global DB used for data escaping
    */
function resetQuery() {
        
        global $db;
        
        $this->setSQLQuery("SELECT *,
        CASE type
            WHEN 'yearly' THEN
                TO_DAYS((value1 + INTERVAL ( YEAR(CURRENT_DATE) - YEAR(value1) + ( RIGHT(CURRENT_DATE,5) > RIGHT(value1,5)) ) YEAR)) - TO_DAYS(CURRENT_DATE)
            WHEN 'monthly' THEN
                TO_DAYS(value1 +
                INTERVAL ( YEAR(CURRENT_DATE) - YEAR(value1) ) YEAR +
                INTERVAL ( MONTH(CURRENT_DATE) - MONTH(value1) + ( DAYOFMONTH(CURRENT_DATE) > DAYOFMONTH(value1)) ) MONTH)
                - TO_DAYS(CURRENT_DATE)
            WHEN 'weekly' THEN
                IF ( WEEKDAY(CURRENT_DATE) <= WEEKDAY(value1) ,  WEEKDAY(value1) - WEEKDAY(CURRENT_DATE) , 6 -  WEEKDAY(value1) + WEEKDAY(CURRENT_DATE) )
            WHEN 'once' THEN
                IF ( value1 >= CURRENT_DATE , TO_DAYS( value1 ) - TO_DAYS( CURRENT_DATE ), NULL)
            WHEN 'autoremove' THEN
                IF ( value1 >= CURRENT_DATE , TO_DAYS( value1 ) - TO_DAYS( CURRENT_DATE ), NULL)
        END AS daysAway,
        MONTH(value1) AS month, 
        DAYOFMONTH(value1) AS day,
        YEAR(value1) AS year,
        FALSE AS ongoing
        FROM " . TABLE_DATES . " as dates, " . TABLE_CONTACT . " AS contact
        WHERE hidden != 1 AND visibility = " . $db->escape('visible') . " AND contact.id = dates.id " .
        ($this->daysBefore >= 0?'HAVING daysAway < ' . $db->escape($this->daysBefore) . ' ':'') .
        'ORDER BY daysAway ASC, lastname ASC, firstname ASC');
        
        /*
        $this->setSQLQuery('SELECT *,
            (TO_DAYS((value + INTERVAL (YEAR(CURRENT_DATE)-YEAR(value) + (RIGHT(CURRENT_DATE,5)>RIGHT(value,5)) ) YEAR)) - TO_DAYS(CURRENT_DATE)) AS daysAway,
            MONTH(value) AS month, 
            DAYOFMONTH(value) AS day,
            YEAR(value) AS year
            FROM ' . TABLE_DATES . ' as dates, ' . TABLE_CONTACT . ' AS contact
            WHERE hidden != 1 AND visibility = ' . $db->escape('visible') . ' AND contact.id = dates.id' . ($this->daysBefore >= 0? ' AND
            (TO_DAYS((value + INTERVAL (YEAR(CURRENT_DATE)-YEAR(value) + (RIGHT(CURRENT_DATE,5)>RIGHT(value,5)) ) YEAR)) - TO_DAYS(CURRENT_DATE)) <
            ' . $db->escape($this->daysBefore):'') . '
            ORDER BY daysAway ASC, lastname ASC, firstname ASC' . ($this->limit>0 ? " LIMIT $this->limit" : '')
        );
        */
        
    }
    
    
}



?>
