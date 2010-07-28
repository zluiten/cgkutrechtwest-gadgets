<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link GroupContactList}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('DB.class.php');
require_once('ContactList.class.php');
require_once('StringHelper.class.php');

/**
* a list of contacts, selected by an group
*
* Handles string bordering of contacts (i.e. contacts from 'hu' on will return
* all contacts which begin with 'hu' or a string that is later in order
* @package backEnd
*/
class GroupContactList extends ContactList {
    
    /**
    * @var string group to be used to select contacts or ''
    */
    var $group;
    
    /**
    * @var string the point, where the list should start (e.g. 'hu')
    */
    var $start;
    
    /**
    * Constructor
    *
    * Initializes the class with values, calls parent constructor
    * @param string $group name of group to use or '' (no group, all contacts)
    * @global DB used for database connection
    */
function GroupContactList($group='') {
        
        global $db;
        
        $this->group = $group;
        $this->start = '';
        
        $this->ContactList('');
        
        $this->resetQuery();
        
    }
    
    /**
    * sets the string, where the list should start
    * @param string $start where to start
    */
function setStartString($start) {
        $this->start = $start;
        $this->resetQuery();
    }
    
    /**
    * returns the left string bound of the list
    *
    * returns the shortest substring of the first contact of the list,
    * that matches not yet the more previous contact, which means,
    * you can reconstruct the beginning of the list, if you know this value
    * @return string left string bound
    * @global DB used for database connection
    */
function getLeftStringBound() {
        
        global $db;
        
        $cont = $this->getContacts();
        $c = $cont[0];
        
        $db->query('SELECT * ' . $this->fromQueryPart() . '(STRCMP(contact.lastname,' . $db->escape($c->contact['lastname']) . ') < 0
            OR contact.lastname = ' . $db->escape($c->contact['lastname']) . ' AND STRCMP(contact.firstname,' . $db->escape($c->contact['firstname']) . ') < 0)
            ORDER BY lastname DESC, firstname DESC LIMIT 1');
            
        $r = $db->next();
        
        return mb_substr($c->contact['lastname'],0,StringHelper::lengthSame($r['lastname'],$c->contact['lastname'])+1);
        
    }
    
    /**
    * returns the left outer string bound of the list
    *
    * returns the shortest substring of the first contact not in the list anymore
    * @return string left outer string bound
    * @global DB used for database connection
    */
function getLeftOuterStringBound() {
        
        global $db;
        
        $cont = $this->getContacts();
        $c = $cont[0];
        
        $db->query('SELECT * ' . $this->fromQueryPart() . '(STRCMP(contact.lastname,' . $db->escape($c->contact['lastname']) . ') < 0
            OR contact.lastname = ' . $db->escape($c->contact['lastname']) . ' AND STRCMP(contact.firstname,' . $db->escape($c->contact['firstname']) . ') < 0)
            ORDER BY lastname DESC, firstname DESC LIMIT 1');
            
        $r = $db->next();
        
        if(!$r)
            return '';
        
        return mb_substr($r['lastname'],0,StringHelper::lengthSame($r['lastname'],$c->contact['lastname'])+1);
        
    }
    
    /**
    * returns the right string bound of the list
    *
    * @see getLeftStringBound()
    * @return string right string bound
    * @global DB used for database connection
    */
function getRightStringBound() {
        
        global $db;
        
        $cont = $this->getContacts();
        
        $x = count($cont)-1;
        
        if($x<0) // list is empty?
            return '';
        
        $c = $cont[$x];
        
        $db->query('SELECT * ' . $this->fromQueryPart() . '(STRCMP(contact.lastname,' . $db->escape($c->contact['lastname']) . ') > 0
            OR contact.lastname = ' . $db->escape($c->contact['lastname']) . ' AND STRCMP(contact.firstname,' . $db->escape($c->contact['firstname']) . ') > 0)
            ORDER BY lastname ASC, firstname ASC LIMIT 1');
            
        $r = $db->next();
        
        return mb_substr($c->contact['lastname'],0,StringHelper::lengthSame($r['lastname'],$c->contact['lastname'])+1);
        
    }
    
    /**
    * returns the right outer string bound of the list
    *
    * @see getLeftOuterStringBound()
    * @return string right outer string bound
    * @global DB used for database connection
    */
function getRightOuterStringBound() {
        
        global $db;
        
        $cont = $this->getContacts();
        
        $x = count($cont)-1;
        
        if($x<0) // list is empty?
            return '';
        
        $c = $cont[$x];
        
        $db->query('SELECT * ' . $this->fromQueryPart() . '(STRCMP(contact.lastname,' . $db->escape($c->contact['lastname']) . ') > 0
            OR contact.lastname = ' . $db->escape($c->contact['lastname']) . ' AND STRCMP(contact.firstname,' . $db->escape($c->contact['firstname']) . ') > 0)
            ORDER BY lastname ASC, firstname ASC LIMIT 1');
            
        $r = $db->next();
        
        if (!$r)
            return '';
            
        return mb_substr($r['lastname'],0,StringHelper::lengthSame($r['lastname'],$c->contact['lastname'])+1);
        
    }
    
    /**
    * returns the page count of all contacts beginning with {@link $start}
    *
    * returns the number of pages, that would be needed to show
    * all entries beginning with {@link $start}
    * @return integer page count
    * @global DB used for database connection
    */
function getBoundedPageCount() {
        
        global $db;
        
        if ($this->start == '')
            return $this->getPageCount();
        
        if ($this->entriesPerPage == 0)
            return 1;
        
        $db->query('SELECT COUNT(*) AS c ' . $this->fromQueryPart() .
            'SUBSTRING(contact.lastname,1,' . $db->escape(strlen($this->start)) . ') = ' . $db->escape($this->start));
        
        $r = $db->next();
        
        return ceil(floatval($r['c']) / $this->entriesPerPage);
        
    }
    
    /**
    * get the caption for the group of the list
    *
    * this is just important for the "magic groups" '', 'hidden' and 'ungrouped'
    * all other group names are outputed as they are
    * @return string group caption
    * @global DB used for database connection
    */
function getGroupCaption() {
        if ($this->group == '' || $this->group == 'all')
            return 'All Entries';
            
        if ($this->group == 'hidden')
            return 'Hidden Entries';
            
        if ($this->group == 'ungrouped')
            return 'Ungrouped Entries';
            
        return $this->group;
        
    }
    
    /**
    * get all first characters of the contacts
    * @return array list of characters
    * @global DB used for database connection
    */
function getFirstChars() {
        
        global $db;
        
        $db->query('SELECT SUBSTRING(contact.lastname,1,1) AS c ' . $this->fromQueryPart() . '1 GROUP BY c ORDER BY c ASC');
        
        $ret = array();
        
        while ($r = $db->next())
            $ret[] = $r['c'];
            
        $db->free();
        
        return $ret;
        
    }
    
    /**
    * reset the query (used if a value has changed)
    * @global DB used for data escaping
    */
function resetQuery() {
        
        global $db;
        
        $this->setSQLQuery('SELECT *, contact.id AS id ' . $this->fromQueryPart() . 'STRCMP(contact.lastname,' . $db->escape($this->start) . ') >= 0
                    ORDER BY lastname ASC, firstname ASC');
    }
    
    /**
    * generate the part of the query used in the from clause
    * @global DB used for data escaping
    * @return string from query part
    */
function fromQueryPart() {
        
        global $db;

        if ($this->group && $this->group != 'all')
          
            if ($this->group == 'hidden')
            
                return 'FROM ' . TABLE_CONTACT . ' AS contact
                    WHERE contact.hidden = 1
                    AND ';
            
            elseif ($this->group == 'ungrouped')
                    
                return 'FROM ' . TABLE_CONTACT . ' AS contact
                    LEFT JOIN ' . TABLE_GROUPS . ' AS groups
                    ON contact.id = groups.id
                    WHERE groupid IS NULL
                    AND contact.hidden != 1
                    AND ';
            
            else
            
                return 'FROM ' . TABLE_CONTACT . ' AS contact,
                    ' . TABLE_GROUPS . ' AS groups,
                    ' . TABLE_GROUPLIST . ' AS grouplist
                    WHERE groups.groupid = grouplist.groupid
                    AND contact.id = groups.id
                    AND grouplist.groupname = ' . $db->escape($this->group) . '
                    AND contact.hidden != 1
                    AND ';
                
        else
        
            return 'FROM ' . TABLE_CONTACT . ' AS contact
                WHERE contact.hidden != 1 
                AND ';
        
    }
    
}


?>
