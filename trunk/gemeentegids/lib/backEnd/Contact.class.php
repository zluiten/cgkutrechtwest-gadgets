<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link Contact}
* @package backEnd
* @author Tobias Schlatter, modifications by Thomas Katzlberger
*/

/** */
require_once('lib/constants.inc');
require_once('StringHelper.class.php');
require_once('EmailHelper.class.php');
require_once('DB.class.php');
require_once('ErrorHandler.class.php');
require_once('PluginManager.class.php');
require_once('RightsManager.class.php');

$VALUE_GROUP_TYPES_ARRAY = array('email','phone','chat','www','other','date');

/**
* Represents a contact
*
* Handles all database-communication, in order to retrieve and save contact-data
* @package backEnd
*/
class Contact
{
    /**
    * @var array comes from database, contains 1 row from contact table
    */
    var $contact;
    
    /**
    * @var array used to cache value groups
    */
    var $valueGroups;
    
    /**
    * @var integer used to cache, if the contact has a user attached (isUser() method)
    */
    var $user;

    /**
    * Constructor: ONLY TO BE CALLED by Contact::newContact($val) factory method!! 
    *
    * If {@link $val} is an array, it is used as the data array,
    * if {@link $val} isn't defined, a new contact is created
    * else the class with id {@link $val} is loaded from database
    * @param integer|array $val Id of contact, or array with data for contact
    */
function Contact($val=false)
    {                
        if (is_array($val))
            $this->contact = $val;
        elseif ($val === false)
            $this->contact = array();
        else
            $this->load($val);
    }

//==============================================
//===== FACTORY                           ======
//==============================================

    /**
    * Contact class factory.
    * 
    * @param variable argument list, pass as if it were a normal constructor
    * @global array $CONFIG_CLASS_POSING_ARRAY associative array of oldClassname => newClassname
    * @return the new class
    */
function newContact() {
        global $CONFIG_CLASS_POSING_ARRAY;
        
        $class = 'Contact';
        $args = func_get_args();
        
        if(isset($CONFIG_CLASS_POSING_ARRAY[$class]))
        {
            $class = $CONFIG_CLASS_POSING_ARRAY[$class];
            include_once($class . '.class.php');
        }
        
        $argstr = array();
        
        for($i=0;$i<count($args);$i++)
            $argstr[] = '$args[' . $i . ']';
        
        $call = 'return new ' . $class . ' (';
        $call .= implode(',',$argstr) . ');';

        return eval($call);
    }
    
//==============================================
//==== Persistence and consistency methods  ====
//==============================================
     
    /**
    * Loads the contact with id {@link $id} from databse
    * @param $id Id of contact
    * @global ErrorHandler used to report errors
    * @global DB used to connect to the database
    */
function load($id)
    {
        global $errorHandler,$db;
        
        if(!is_numeric($id))
            $errorHandler->error('invArg','<span>Cannot load contact with record id: </span>'.$id,get_class($this));

        // uncache variable for isUser() method
        $this->user = null;

        $db->query('SELECT * FROM ' . TABLE_CONTACT . ' AS contact WHERE contact.id = ' . $db->escape($id));
        
        if ($db->rowsAffected() != 1)
            $errorHandler->error('invArg','<span>No contact with record id: </span>'.$id,get_class($this));
        
        $this->contact = $db->next();
    }
    
    /**
     * Make all checks before writing to DB true on success false on errors (check errorHandler then).
     * @global array Value groups to save
     * @global ErrorHandler
     */
function check()
    {
        global $VALUE_GROUP_TYPES_ARRAY,$errorHandler;
        
        // must have lastname
        if (!trim($this->contact['lastname'])) {
            $errorHandler->error('formVal','Please enter a last name or company name for the contact.',get_class($this));
            return false;
        }
        
        $rightsManager = RightsManager::getSingleton();
        $currentUser = $rightsManager->getUser();
        $privateOk = $rightsManager->mayViewPrivateInfo($this);
        
        $vgTypes = $VALUE_GROUP_TYPES_ARRAY;
        //$vgTypes[]='addresses';
        
        $retval = TRUE;
        
        foreach($vgTypes as $vgt)
        {
            // iterate through each item directly
            if(isset($this->valueGroups[$vgt]))
                for($i=0;$i<count($this->valueGroups[$vgt]);$i++)
                {
                    if(!isset($this->valueGroups[$vgt][$i])) // some indices may have been unset (deleted)
                        continue;
                        
                    $p = $this->valueGroups[$vgt][$i];
                    
                    // general checks
                    
                    // Only admin may post admin-hidden property visibility value (manipulation of $_POST data)
                    if(isset($p['visibility']) && $p['visibility'] == 'admin-hidden' && !$currentUser->isAtLeast('admin'))
                    {
                        $this->valueGroups[$vgt][$i]['visibility']='visible';
                        $errorHandler->warning('Property changed from admin to public, because you are not an administrator.');
                    }
                    
                    // Only if viewable we may post a hidden property visibility value (manipulation of $_POST data)
                    if(isset($p['visibility']) && $p['visibility'] == 'hidden' && !$privateOk)
                    {
                        $this->valueGroups[$vgt][$i]['visibility']='visible';
                        $errorHandler->warning('Property changed from private to public, because you cannot view private info.');
                    }
                    
                    // specific checks
                    switch($vgt)
                    {
                        case 'email': // check emails: a user must have a unique email
                            if (($this->isUser() && Contact::getContactFromEmail($p['value'],$tmp,$this->contact['id']) > 0 || ($tmp = User::getUserFromEmail($p['value']))) && (!isset($tmp['id']) || $tmp['id'] != $this->contact['id'])) {
                                if (!isset($tmp['id']))
                                    $errorHandler->error('formVal','The e-mail address ' . $p['value'] . ' cannot be set, because it is in use by a user which is in the registration process.',get_class($this));
                                elseif (isset($tmp['hidden']) && $tmp['hidden'] && !$currentUser->isAtLeast('admin'))
                                    $errorHandler->error('formVal','The e-mail address ' . $p['value'] . ' cannot be set, because it is in use by a hidden contact.',get_class($this));
                                else
                                    $errorHandler->error('formVal','The e-mail address ' . $p['value'] . ' cannot be set, because it is in use by the contact <a href="../contact/contact.php?id=' . $tmp['id'] . '">' . $tmp['lastname'] . ', ' . $tmp['firstname'] . '</a>.',get_class($this));
                                    
                                return false;
                            }
                            else
                            if(!$this->isUser())
                            {
                                $n = Contact::getContactFromEmail($p['value'],$firstRecord,isset($this->contact['id'])?$this->contact['id']:null);
                                if($n>0)
                                    $errorHandler->warning('Duplicate email detected. You may ignore this warning if this is intentional, but the corresponding contacts cannot become users and log in. Reference: <a href="../contact/contact.php?id=' . $firstRecord['id'] . '">' . $firstRecord['lastname'] . ', ' . $firstRecord['firstname'] . '(' . $firstRecord['id'] . ')</a> - ' .
                                        $this->contact['lastname'] . ', ' . $this->contact['firstname'] ,get_class($this));
                            }
                            
                            if(empty($p['value']))
                            {
                                unset($this->valueGroups[$vgt][$i]);
                                $errorHandler->error('formVal',"<span>Not saved. An input value is missing:</span> {$p['label']}",get_class($this));
                            }
                            break;
                            
                        case 'other':
                        case 'phone':
                        case 'chat':
                        case 'www':
                            if(empty($p['value']))
                            {
                                unset($this->valueGroups[$vgt][$i]);
                                $errorHandler->error('formVal',"<span>Not saved. An input value is missing:</span> {$p['label']}",get_class($this));
                                $retval = FALSE;
                            }
                            break;
                            
                       /*  case 'addresses':
                            // SEE ... delete address if incomplete in save()
                            break; */
                    }
                } // property
        }
        
        return $retval;
    }

    /**
    * Saves this contact in the database, creates a new contact, if {@link $contact->contact['id']} is not set
    * Calls check() before saving to verify data consitencyand policies.
    * This function uses the mysql command SHOW COLUMNS, and then inserts all
    * data from {@link $contact->contact}, which has keys that are in the columns
    * of the contact table
    * @param boolean $checkConsistency whether to check the consistency of the Contact or not USE WITH CAUTION!!!!!!!!
    * @global DB used to connect to the database
    * @global ErrorHandler used to report errors
    * @global PluginManager used to execute plugin-hooks
    * @global integer config: how many days is a contact still marked as added upon change, after addition
    */
function save($checkConsistency = true,$adminsave=false)
    {
        global $db,$errorHandler,$pluginManager, $CONFIG_ADDED_CHANGED_HYSTERESIS;
        
        // make all consitency checks before saving to db
        if($checkConsistency && !$this->check())
            return false;

        $rightsManager = RightsManager::getSingleton();
        $currentUser = $rightsManager->getUser();
        $privateOK = $rightsManager->mayViewPrivateInfo($this);
        
        $adding = !isset($this->contact['id']) || !$this->contact['id'];

        $pluginManager->changedContactRecord($this,'will_' . ($adding?'add':'change'));
        
        if ($adding) { 
            $this->contact['lastModification'] = 'added';
            $this->contact['whoAdded'] = $currentUser->id;
        } else { // avoid changing added -> changed too soon
            if(!isset($CONFIG_ADDED_CHANGED_HYSTERESIS))
                $CONFIG_ADDED_CHANGED_HYSTERESIS = 31;
            
            if($this->contact['lastModification'] == 'added') // only for added! deleted -> changed must be instantly possible
            {
                $db->query('SELECT TO_DAYS(NOW()) >= ' . $db->escape($CONFIG_ADDED_CHANGED_HYSTERESIS) . ' +
                            TO_DAYS(' . $db->escape($this->contact['lastUpdate'])  . ') AS ok');
                $r = $db->next();
                if ($r['ok'])
                    $this->contact['lastModification'] = 'changed';
            }
            else
                $this->contact['lastModification'] = 'changed';
        }
            
        if(!$adminsave)
        {
            $this->contact['whoModified'] = $currentUser->id; // no hysteresis needed
            $this->contact['lastUpdate'] = date('Y-m-d H:i:s');
        }
        
        $db->query('SHOW COLUMNS FROM ' . TABLE_CONTACT);
        
        $queryHead = 'REPLACE INTO ' . TABLE_CONTACT . ' (';
        $queryBody = 'VALUES (';

        while ($r = $db->next())
            if (isset($this->contact[$r['Field']])) {
                $queryHead .= $r['Field'] . ',';
                $queryBody .= $db->escape($this->contact[$r['Field']]) . ',';
            }

        $db->free();
        
        $queryHead = mb_substr($queryHead,0,-1) . ') ';
        $queryBody = mb_substr($queryBody,0,-1) . ')';

        $db->query($queryHead . $queryBody);
        
        if ($adding) {
            $this->contact['id'] = $db->insertID();
            if (!$this->contact['id'])
                $errorHandler->error('retVal','insertID() did not return a proper id.',get_class($this));
        }
        
        if(isset($this->valueGroups)) // may be empty if never used
            foreach ($this->valueGroups as $k => $v) {
                
                $tbl = '';
                
                if ($k == 'groups') {
                    
                    $db->query('DELETE FROM ' . TABLE_GROUPS . ' WHERE id = ' . $db->escape($this->contact['id']));
                    
                    if (count($v) <= 0)
                        continue;
                    
                    $gQuery = 'REPLACE INTO ' . TABLE_GROUPS . ' (id,groupid) VALUES ';
                    
                    foreach ($v as $g) {
                        $db->query('SELECT * FROM ' . TABLE_GROUPLIST . ' WHERE groupname = ' . $db->escape($g['groupname']));
                        if ($r = $db->next())
                            $g['groupid'] = $r['groupid'];
                        else {
                            $db->query('INSERT INTO ' . TABLE_GROUPLIST . ' (groupname) VALUES (' . $db->escape($g['groupname']) . ')');
                            $g['groupid'] = $db->insertID();
                        }
                        
                        $gQuery .= '(' . $db->escape($this->contact['id']) . ',' . $db->escape($g['groupid']) . '),';
                        
                    }
                    
                    $db->query(mb_substr($gQuery,0,-1));
                    // Remove overhead
                    $db->query('OPTIMIZE TABLE ' . TABLE_GROUPS);
                    
                    continue;
                }
                
                if ($k == 'addresses') {
                    
                    $db->query('SHOW COLUMNS FROM ' . TABLE_ADDRESS);
                    
                    $cols = array();
                
                    while ($r = $db->next())
                        $cols[] = $r['Field'];
        
                    $db->free();
                    
                    $prim = -1;
                    
                    foreach ($v as $vk => $vv) {
                        $tmp = '(';
                        $empty = empty($vv['line1']) && empty($vv['city'])  && empty($vv['zip']); // delete address if incomplete
                        foreach ($cols as $c) {
                            if(!isset($vv[$c])) // skip entries not in input (e.g. latitude, longitude)
                                $vv[$c]='';
                                
                            if ($c == 'id')
                                $tmp .= $db->escape($this->contact['id']) . ',';
                            elseif ($c == 'refid') {
                                if (isset($vv[$c])) {
                                    $tmp .= $db->escape($vv[$c]) . ',';
                                    $new = false;
                                } else {
                                    $tmp .= "'',";
                                    $new = true;
                                }
                            } else {
                                $tmp .= $db->escape($vv[$c]) . ',';
                            }
                        }
                        
                        if ($empty && !$new)
                            $db->query('DELETE FROM ' . TABLE_ADDRESS . ' WHERE refid = ' . $db->escape($vv['refid']));
                        elseif (!$empty) {
                            $db->query('REPLACE INTO ' . TABLE_ADDRESS . ' VALUES ' . mb_substr($tmp,0,-1) . ')');
                            if (isset($vv['primary']) && $vv['primary']) {
                                $id = $db->insertID();
                                $db->query('UPDATE ' . TABLE_CONTACT . ' SET primaryAddress = ' . $db->escape($id) . ' WHERE id = ' . $db->escape($this->contact['id']));
                            }
                        }
                    }
                    
                    continue;
                    
                }
                
                if ($k == 'date') {
                    $tbl = TABLE_DATES;
                    // Remove old entries (admin-hidden only if current user is an admin, hidden only if can view hidden)
                    $db->query('DELETE FROM ' . $tbl . '
                        WHERE (visibility = ' . $db->escape('visible') . '
                            OR visibility = ' . $db->escape('hidden') . ' AND ' . $db->escape($privateOK) . '
                            OR visibility = ' . $db->escape('admin-hidden') . ' AND ' . $db->escape($currentUser->isAtLeast('admin')) . ')
                        AND id = ' . $db->escape($this->contact['id']));
                } else {
                    $tbl = TABLE_PROPERTIES;
                    // Remove old entries (admin-hidden only if current user is an admin, hidden only if can view hidden)
                    $db->query($sql='DELETE FROM ' . $tbl . '
                        WHERE type = ' . $db->escape($k) . '
                        AND (visibility = ' . $db->escape('visible') . '
                            OR visibility = ' . $db->escape('hidden') . ' AND ' . $db->escape($privateOK) . '
                            OR visibility = ' . $db->escape('admin-hidden') . ' AND ' . $db->escape($currentUser->isAtLeast('admin')) . ')
                        AND id = ' . $db->escape($this->contact['id']));
                }
                
                if (count($v) <= 0)
                    continue;
                    
                $db->query('SHOW COLUMNS FROM ' . $tbl);
                
                $cols = array();
                
                while ($r = $db->next())
                    $cols[] = $r['Field'];
                
                $queryContent = 'VALUES ';
    
                $db->free();
                
                foreach ($v as $vk => $vv) {
                    $queryContent .= '(';
                    foreach ($cols as $c) {
                        if ($c == 'id')
                            $queryContent .= $db->escape($this->contact['id']) . ',';
                        elseif ($c == 'type' && !isset($vv[$c]))
                            $queryContent .= $db->escape($k) . ',';
                        elseif (($c == 'value2' || $c == 'value1') && $k == 'date' && trim($vv[$c]) == '?')
                            $queryContent .= 'NULL,';
                        elseif ($c == 'refid' && (!isset($vv[$c]) || $vv[$c] == 0))
                            $queryContent .= 'NULL,';
                        else
                            $queryContent .= $db->escape($vv[$c]) . ',';
                    }
                    $queryContent = mb_substr($queryContent,0,-1) . '),';
                }
                
                $queryContent = mb_substr($queryContent,0,-1);
                
                $db->query('INSERT INTO ' . $tbl . ' (' . implode(',',$cols) . ') ' . $queryContent);
    
                // Remove overhead
                $db->query('OPTIMIZE TABLE ' . $tbl);
            }
        
        $this->load($this->contact['id']);
        
        $pluginManager->changedContactRecord($this,($adding?'added':'changed'));
        
        return true;
    }
    
    /**
    * Trash the contact (mark as deleted)
    * @param User $currentUser The current user who wants to perform the action (for security checks)
    * @global DB used for database connection
    * @global PluginManager used to execute hooks
    * @global ErrorHandler used to handle errors
    * @return boolean true on success
    */
function trash(&$currentUser)
    {
        global $db, $pluginManager, $errorHandler;
        
        if (!isset($this->contact['id']) || !$this->contact['id'])
            $errorHandler('invArg','This contact does not exist in the database.',get_class($this));
        
        $db->query('UPDATE ' . TABLE_CONTACT . ' SET
            whoModified = ' . $db->escape($currentUser->id) . ',
            lastUpdate = NOW(),
            hidden = 1,
            lastModification = ' . $db->escape('deleted') . '
            WHERE id=' . $db->escape($this->contact['id']) . ' LIMIT 1');

        $pluginManager->changedContactRecord($this,'trashed');
        
        $this->load($this->contact['id']);
        
        return true;
        
    }
    
    /**
    * Delete the contact (really delete)
    * @global DB used for database connection
    * @global PluginManager used to execute hooks
    * @global ErrorHandler used to handle errors
    * @return boolean true on success
    */
function delete()
    {
        global $db, $pluginManager, $errorHandler;
        
        if (!isset($this->contact['id']) || !$this->contact['id'])
            $errorHandler('invArg','This contact does not exist in the database.',get_class($this));

        $pluginManager->changedContactRecord($this,'deleted');
            
        $db->query('SELECT * FROM ' . TABLE_USERS . ' WHERE id = ' . $db->escape($this->contact['id']));
        if ($db->rowsAffected()>0) {
            $r = $db->next();
            $pluginManager->changedUserRecord(new User($r),'deleted');
        }
        
        $tables = array(
            TABLE_CONTACT,
            TABLE_ADDRESS,
            TABLE_PROPERTIES,
            TABLE_GROUPS,
            TABLE_USERS,
            TABLE_DATES
        );
        
        foreach ($tables as $t)
            $db->query('DELETE FROM ' . $t . ' WHERE id = ' . $db->escape($this->contact['id']));
        
        return true;
        
    }
    
    /**
    * return modifications of the contact as nested arrays: $return[''][0] = main data array, $return['email'][0] = value group data array
    * @return array
    */    
function diff($otherContact)
    {
        $n = 0;
        
        $r = array_diff_assoc($this->contact,$otherContact->contact);
        
        foreach($r as $kk => $vv) // retrieve old not new value
            $r[$kk] = $this->contact[$kk];
        
        $diff[''][] = $r;
        
        global $VALUE_GROUP_TYPES_ARRAY;
        foreach($VALUE_GROUP_TYPES_ARRAY as $vgx) // VG TYPES LOOP
        {
            $vg = $this->getValueGroup($vgx); // NEW contact from post
            $vgo = $otherContact->getValueGroup($vgx);
            
            // sort by label in the same way as it comes from the DB
            $h = array();
            foreach($vg as $k => $v)
                $h[] = $vgx=='date' ? $v['value1'] : $v['label'];
            
            array_multisort($h,$vg);
            
            foreach($vg as $k => $v) // calculate the diff
            {
                $keep = false;
                
                if(is_array($vgo[$k]))
                    $d = array_diff_assoc($v,$vgo[$k]);
                else // new entry
                {
                    $d = $v;
                    $keep = true;
                }
                
                if(count($d)>0)
                {
                    $r = array();
                    
                    if($keep)
                        $r = $d;
                    else
                    {
                        foreach($d as $kk => $vv) // retrieve old not new value
                            $r[$vgx . '-' . $kk] = $vgo[$k][$kk];
                    }
                    
                    $diff[$vgx][] = $r;
                }
            }
        }
        
        return $diff;
    }
    
//===========================================================
//===== Standard Instance access methods                =====
//===========================================================
    /**
    * Get the id of the associated Contact. Warning User::getId() returns the Contact ID
    * @return int database record id of Contact or 0 if not found
    */
function getId()
    {
        return $this->contact['id'];
    }
    
    /**
    * Get the id of the associated User if it esxists. 
    * @return int database record id of an associated User or FALSE if not found
    */
function getUserId()
    {
        if ($this->user !== null)
            return $this->user;
        
        $this->getUserRecord();
        return $this->user;
    }
    
    /**
    * Get the DB record from the user table if it esxists. Result is not cached because it is used rarely.
    * @return associative array of user or FALSE if not found
    */
function getUserRecord()
    {
        $this->user = FALSE; // cache for getUserId()
        
        if (isset($this->contact['id']))
        {
            $db = DB::getSingleton();
            $db->query('SELECT * FROM ' . TABLE_USERS . ' WHERE id = ' . $db->escape($this->contact['id']));
            if($db->rowsAffected() == 1) 
            {
               $r = $db->next();
               $this->user = intval($r['userid']); // cache for getUserId()
               return $r;
            }
        }
        
        return FALSE;
    }
    
    /**
    * Deprecated because a function like isUser should return TRUE or FALSE only.
    * @return integer user id, if there is no such user: 0
    * @global DB used for database connection
    * @deprecated USE getUserId() instead!
    */
function isUser() // DEPRECATED !!!!!!!!!!!!!!!
    {
        $this->getUserId();
        return ($this->user === FALSE ? 0 : $this->user);
    }
    
    /**
    * Gets a value group
    * 
    * A value group is an array, containing associative arrays with column names of
    * the database.
    * @param string $type which value group to return
    * @param number | NULL $refid return address associated items. $refid === null return not associated items. $refid == 0 return all items; Default: 0
    * @return array value group
    * @global DB used for database connection
    */
function getValueGroup($type,$refid = 0)
    {
        $rightsManager = RightsManager::getSingleton();
        $privateOK = ($rightsManager->mayViewPrivateInfo($this) ? 'TRUE' : 'FALSE');

        // retrieve nothing if not allowed to view
        if(false==$rightsManager->mayViewContact($this))
            return array();
        
        if ($refid === 0 || $type == 'addresses')
            return $this->_getValueGroup($type,$privateOK);
        
        if (empty($refid))
            $func = create_function('$val','return !array_key_exists("refid",$val) || $val["refid"] === null;');
        else
            $func = create_function('$val','return array_key_exists("refid",$val) && $val["refid"] == ' . $refid . ';');
            
        return array_filter($this->_getValueGroup($type,$privateOK),$func);
    }
    
    /**
    * Gets a value group without security (hasEmail(),hasValue()) to avoid recursion in RightsManager.
    */
function _getValueGroup($type,$privateOK)
    {
        global $db;
        
        if (isset($this->valueGroups[$type]))
            return $this->valueGroups[$type];
        
        $valueGroup = array();
        
        $id = isset($this->contact['id']) ? $db->escape($this->contact['id']) : 0;        
        
        // I know that the joins are not necessary and duplicate data, but it is practical 
        // to have everything at hand in a value group, especially when processing externally
        switch($type)
        {
            case 'addresses':
                $sel = "SELECT * FROM " . TABLE_ADDRESS . " AS address ";
                $where = "WHERE $id=address.id";
                break;
            case 'groups':
                $sel = "SELECT grouplist.* FROM " . TABLE_GROUPS . " AS groups LEFT JOIN " . TABLE_GROUPLIST . " AS grouplist ON groups.groupid=grouplist.groupid";
                $where = "WHERE id=$id";
                break;
            case 'date':
                $sel = "SELECT * FROM " . TABLE_DATES . " AS dates ";
                $where = "WHERE $id=dates.id
                    AND (visibility = " . $db->escape('visible'). " OR $privateOK) ORDER BY dates.value1";
                break;
            default:
                $sel = "SELECT * FROM " . TABLE_PROPERTIES . " AS props ";
                $where = "WHERE $id=props.id
                    AND props.type = " . $db->escape($type) . "
                    AND (visibility = " . $db->escape('visible'). " OR $privateOK) ORDER BY props.label";
        }
        
        // return a 2D array
        $db->query("$sel $where");
        while($row = $db->next())
            $valueGroup[]=$row;
        
        $this->valueGroups[$type] = $valueGroup;
        
        return $valueGroup;
    }
    
    /**
    * Sets a value group
    *
    * Saves the value group in the cache of the contact class. It is written in
    * the database, when the {@link save()} method is called
    * @param string $type which value group to save
    * @param array $valueGroup the value group to save
    * @return boolean true on success
    **/
function setValueGroup($type,$valueGroup)
    {
        $this->valueGroups[$type] = $valueGroup;
        return true;
    }
    
    /**
    * returns array of DISTINCT contact ids that have this email (User's registration emails are included) for registration and consitency checks
    * One cannot tell if the email is a registration or a contact's property email!
    * If count(returned value) .. 0 -> email not in DB; 1 -> 1 contact or user; 2+ -> multiple users
    *
    * @param string $email e-mail to check
    * @return array of contact ids that have this email
    * @global DB used for database access
    */
function contactsWithEmail($email)
    {
        global $db;
        
        $db->query('(SELECT id FROM '.TABLE_USERS.' WHERE reg_email=' . $db->escape($email) .') 
                    UNION DISTINCT 
                    (SELECT id FROM '.TABLE_PROPERTIES.' WHERE type="email" AND value=' . $db->escape($email) .' GROUP BY id)');
        
        $ret = array();
        while($r=$db->next())
            if($r['id']!=null)
                $ret[]=$r;
            
        return $ret; 
    }
    
    /**
    * Gets a contact, which has the e-mail {@link $email}
    * @static
    * @param string $email the e-mail to search for
    * @param array db result (first best contact found) use $db->next() to get more
    * @return number of matches
    * @global DB used for database connection
    */
function getContactFromEmail($email,&$result,$excludeId=null)
    {
        global $db;
        
        $db->query('SELECT *
                    FROM ' . TABLE_PROPERTIES . ' AS properties,
                         ' . TABLE_CONTACT . ' AS contact
                    WHERE properties.id = contact.id
                    ' . ($excludeId?'AND contact.id!=' . $excludeId : '') . '
                    AND properties.type = ' . $db->escape('email') . '
                    AND properties.value = ' . $db->escape($email));
        
        $result = $db->next();
        return $db->rowsAffected();
    }
    
//===========================================================
//===== Instance access methods (incl. HTML generation) =====
//===========================================================
    
    /**
    * Retrieves the first property with the label starting with the case insensitive argument.
    * Example: getProperty('messaging','yim') ... matches also "yim (private)"
    *
    * @param string value group name
    * @param string the beginning of the label
    */
function getProperty($valueGroup,$labelStartsWith)
    {
        $vg = $this->getValueGroup($valueGroup);
        foreach($vg as $v)
            if(stripos($v['label'],$labelStartsWith) === 0) // case insensitive comparison
                return $v['value'];
        
        return FALSE;
    }
    
    /**
    * Retrieves the first property with the label starting with the case insensitive argument.
    * Example: getDate('label') ... matches also "label (private)"
    *
    * @param string the beginning of the label
    * @return array array(from,to)
    */
function getDate($labelStartsWith)
    {
        $vg = $this->getValueGroup('date');
        foreach($vg as $v)
            if(stripos($v['label'],$labelStartsWith) === 0) // case insensitive comparison
                return array($v['value1'],$v['value2']);
        
        return FALSE;
    }

    /**
    * Checks if a contact has this value in a value group
    *
    * @param string value group name
    * @param string email, webaddress or some other full value, case INSENSITIVE
    * @return TRUE/FALSE
    */
function hasValue($valueGroup,$value)
    {
        $vg = $this->_getValueGroup($valueGroup,'FALSE');
        foreach($vg as $v)
            if(strtolower($v['value']) == strtolower($value))
                return TRUE;
                
        return FALSE;
    }

    /**
    * Checks if a contact has this email in his list
    *
    * @param string email
    * @return TRUE/FALSE
    */
function hasEmail($e)
    {
        return $this->hasValue('email',$e);
    }
    
    // private for generateFullName
function nameSpecials($in,$suppress)
    {
        if($suppress)
            return ($in[0]=='#' ? '' : $in);
        
        switch($in)
        {
            case '#!linkStart': return '<a href="../contact/contact.php?id=' . $this->contact['id'] . '">';
            case '#!linkEnd': return '</a>';
            case '#!groupAcronyms': return $this->groups(null,false,'acronym');
            case '#!groupNames': return $this->groups(null,false,'groupname');
            case '#!expander': return '<img src="../lib/icons/plus.png" alt="expand" onclick="TABR_ajaxTableRowExpander(event,\'../contact/contact.ajax.php?id='.$this->contact['id'].'\',4,\'../lib/icons/\');">';
        }

        return $in;
    }
    
    /**
    * Generate the full name of the contact as: string/link or inside a div with class='names[-hidden]' (default)
    * @param array $spec order of fieldnames and spacers. If an entry is an array it is an 
    * output group that only displays if it contains fields and at least one is not empty.
    * Default ($spec==NULL): array(array('namePrefix',' '),array('firstname', ' '),array('middlename', ' '), 'lastname', array(', ', 'nameSuffix'),array(' (', 'nickname' ,')'))
    * @param string $format 'html' [div class='fn'] or 'text' (text format SKIPS any specials #!... in $spec!)
    * @return string html
    */
function generateFullName($format='html',$spec = null)
    {
        $ret='';
        $suppressNameSpecials = ($format == 'text');
        
        if($spec == null)
            $spec = array('#!linkStart',array('namePrefix',' '),array('firstname', ' '),array('middlename', ' '), 'lastname', array(', ', 'nameSuffix'),'#!linkEnd');
        
        // assemble the name accordig to the specification
        $out = array();
        $n = count($spec);
        for($i=0;$i<$n;$i++)
        {
            if(is_array($spec[$i]))
            {
                $found = false;
                $group= array();
                foreach($spec[$i] as $v)
                {
                    if(array_key_exists($v,$this->contact))
                    {
                        if(!$found) $found=!empty($this->contact[$v]);
                        $group[] = $this->contact[$v];
                    }
                    else
                    {
                        if(!$found) $found=(substr($v,0,2)=='#!');
                        $group[] = $this->nameSpecials($v,$suppressNameSpecials);
                    }
                }
                
                // no output if not found one key
                if($found)
                    $out = array_merge($out,$group);
                
                continue;
            }

            if(array_key_exists($spec[$i],$this->contact))
                $out[] = $this->contact[$spec[$i]];
            else
                $out[] = $this->nameSpecials($spec[$i],$suppressNameSpecials);
        }
        
        $ret = implode('',$out);
        
        switch($format)
        {
            case 'html':
                if ($this->contact['hidden'])
                    $ret .= ' [!]';
                    
                return '<div class="fn' . ($this->contact['hidden'] ? '-hidden">' : '">') . $ret . '</div>';
        }
        
        return $ret; // default $format='text'
    }
    
    /**
    * Generates a table list entry from this contact according to $CONFIG_LIST_NAME_SPEC, $CONFIG_LIST_ROW_SPEC from config.php
    * You have to put the data then into a table form e.g. with TableGenerator.
    * APPENDS one extra row ['group_n'] for GroupNormalizer
    *
    * $CONFIG_LIST_ROW_SPEC = 
        array('' => array( // default MUST BE SET
                'return $this->generateFullName("html",$namespec);',
                'return $this->phones();',
                'return $this->emails();'),
              'xsltDisplayType' => array(
                'return $this->generateFullName("html",$namespec);',
                'return $this->adresses();',
                'return $this->emails();')
             );
    * @return array('Hugo Gloss','email: a@b.com',...);
    */
function generateListRowArray()
    {
        global $CONFIG_LIST_NAME_SPEC,$CONFIG_LIST_ROW_SPECS;
        
        $contactType = $this->contact['xsltDisplayType'];
        
        // find a valid rowspec, parts of the HTML are suppressed by CSS style in list
        if(!isset($CONFIG_LIST_ROW_SPECS))
            $rowspec = array(
                'return $this->generateFullName("html",$namespec) . $events;',
                'return $this->phones();',
                'return $this->emails() . $this->messaging();');
        else
            $rowspec = isset($CONFIG_LIST_ROW_SPECS[$contactType]) ? $CONFIG_LIST_ROW_SPECS[$contactType] : $CONFIG_LIST_ROW_SPECS[''];
        
        // DO NOT REMOVE! used in evaled code
        $namespec = isset($CONFIG_LIST_NAME_SPEC) ? $CONFIG_LIST_NAME_SPEC : NULL;
        
        // Prepare events text
        $evs = $this->ongoingEvents();
        $events = ''; // used in evaled code
        if(!empty($evs))
        {
            $class = 'event';
            $events = '<br>'.implode('',$evs);
        }
        else // CALC CSS CLASS
        if ($_SESSION['user']->contact['id'] == $this->contact['id'])
            $class = 'me';
        else if ($_SESSION['user']->getType() == 'user' && $this->contact['whoAdded'] == $_SESSION['user']->id)
            $class = 'editable';
        else if ($this->contact['hidden'])
            $class = 'special';
        else
            $class='';
        
        // EVAL THE GENERATION CODE
        $i=0;
        $tableRow = array();
        foreach($rowspec as $s) // eval the appropriate spec for the contact type
            $tableRow[$i++]= eval($s);
            
        $tableRow['css_class'] = $class;
        
        global $groupNormalizer;
        $tableRow['group_n'] = $groupNormalizer->normalize(mb_substr($this->contact['lastname'],0,1));
        
        return $tableRow;
    }
    
    /**
    * Returns any media data of this object. Currently only 'pictureData' available!
    * @param string $mediaName 'pictureData'
    * @param string $mimeType 'image/jpeg'
    * @param int $length byte size of binary data
    * @return string binary data or null
    */
function getMedia($mediaName,&$mimeType,&$length)
    {
        $mimeType = 'image/jpeg';
        $length = strlen($this->contact['pictureData']);
        return $this->contact['pictureData'];
    }
    
    /**
    * Assigns any media data of this object. Currently only 'pictureData' available!
    * @param string $mediaName MUST BE 'pictureData'
    * @param string $mimeType curretly unused!
    * @param string $binaryData 
    */
function setMedia($mediaName,$mimeType,$binaryData)
    {
        global $errorHandler;
        
        switch($mediaName)
        {
            case 'pictureData': $this->contact['pictureData'] = $binaryData; break;
        }
        
        $errorHandler->warning('Contact::setMedia() Invalid media cannot store.','Contact::setMedia');
    }
    
    /**
    * Output the phones of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @return string html
    */
function phones($vg_x = NULL,$refid = 0)
    {
        $cont = '';
        
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('phone',$refid);
            
        if (count($vg_x) > 0) {
            
            if ($refid === null)
                $cont = '<div class="phones-title">Other Phone Numbers</div><ul class="phones">';
            else
                $cont = '<ul class="phones">';
            
            foreach($vg_x as $p)
                $cont .= '<li' . ($p['visibility'] != 'visible'?' class="hidden">':'>') . $this->generatePhoneEntry($p) . '</li>';
            
            $cont .= '</ul>';
            
        }
        
        return $cont;
        
    }
    
    /**
    * Generate a messaging link from one entry of the messaging value group.
    * Depending on the service it generates remote control URIs (e.g. call/chat for Skype)
    *
    * @param array $m the entry of the value group to use
    * @param integer $format 1= label: value 2=value (label) I hate this!!!
    * @return string html
    * @static
    */
function generatePhoneEntry($m,$format=1) 
    {

        global $options;

        $l1 = '<span class="phones-label">';
        $l2 = $m['label'];
        $l3  = '</span>';
        $s  = '</span>';
        $v1 = '<span class="phones-info">';
        $v = $m['value'];
        
        $service = strtolower($m['label']);
        
        // SEE ALSO plugins/Export/export.php !!
        if(substr($service,0,4) == 'sips')
            $href = "sips:$v'";
        else // See: http://www.voip-info.org/wiki/view/SIP+URI
        if(substr($service,0,3) == 'sip' || substr($service,0,4) == 'voip')
            $href = "sip:$v'";
        else
        if(substr($service,0,4) == 'h323' || substr($service,0,4) == 'video')
            $href = "h323:$v'";
        else // See: http://www.voip-info.org/wiki/view/SIP+URI
        if(substr($service,0,3)=='fax')
        {         
            $ugen = explode('$',$options->getOption('faxURI'));
            $href = $ugen[0] . $v . (isset($ugen[1]) ? $ugen[1] : '');
        }
        else // tel
        {
            $ugen = explode('$',$options->getOption('telURI'));
            $href = $ugen[0] . $v . (isset($ugen[1]) ? $ugen[1] : '');
        }
        
        $a = "<a class='tel' href='$href'>";
        
        if($format==2)
            $ret = $v1.$a.$v.'</a>'.$s.(empty($m['label']) ? '' : $l1 . ' (' . $l2 .')' . $s );
        else
            $ret = (empty($m['label']) ? '' : $l1.$l2.': ') . $s.$v1.$a.$v.'</a>'.$s;
        
        return $ret;
    }

    /**
    * Output the messaging handles of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @return string html
    */
 function messaging($vg_x = NULL,$refid = 0)
    {
        $cont = '';
        
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('chat',$refid);
        
        if (count($vg_x) > 0) {
            
            if ($refid === null)
                $cont = '<div class="messaging-title">Messaging</div><ul class="messaging">';
            else
                $cont = '<ul class="messaging">';
            
            foreach($vg_x as $x)
                $cont .= '<li' . ($x['visibility'] != 'visible'?' class="hidden"':'') . '>'. $this->generateMessagingEntry($x)  .'</li>';
                    
            $cont .= '</ul>';
            
        }

        return $cont;

    }
    
    /**
    * Generate a messaging link from one entry of the messaging value group.
    * Depending on the service it generates remote control URIs (e.g. call/chat for Skype)
    *
    * @param array $m the entry of the value group to use
    * @return string html
    * @static
    */
function generateMessagingEntry($m) 
    {
        $default = '<span class="messaging-label">' . $m['label'] . ': </span><span class="messaging-info">' . $m['value'] . '</span>';
        $service = strtolower($m['label']);
        $v = $m['value'];
            
        if(substr($service,0,5) == 'skype')
        {
            $add = " <a class='messaging-link' href='skype:$v?add'>add</a>";
            $call = " <a class='messaging-link' href='skype:$v?call'>call</a>";
            $chat = " <a class='messaging-link' href='skype:$v?chat'>chat</a>";
            return $default . $add . $call . $chat;
        }
        
        if(substr($service,0,5) == 'yahoo' || substr($service,0,3) == 'yim' || substr($service,0,5) == 'ymsgr')
        {
            $chat = " <a class='messaging-link' href='ymsgr:sendIM?$v'>chat</a>";
            return $default . $chat;
        }
        
        if(substr($service,0,3) == 'msn' || substr($service,0,5) == 'msnim')
        {
            $chat = " <a class='messaging-link' href='msnim:chat?contact=$v'>chat</a>";
            return $default . $chat;
        }
        
        if(substr($service,0,3) == 'aim' || substr($service,0,5) == 'aol')
        {
            $chat = " <a class='messaging-link' href='aim:goim?screenname=$v'>chat</a>";
            return $default . $chat;
        }
        
        return $default;
    }
    

    /**
    * Output the homepages of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @return string html
    */
function webs($vg_x = NULL)
    {
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('www');

        global $lang;
        
        $cont = '';
        foreach($vg_x as $x)
        {
            if(!$cont) // title on first line only
                $cont = '<div class="urls"><span class="urls-label">Webs, News and Media</span>';
            else
                $cont .= '<div class="urls' . ($x['visibility'] != 'visible'?' hidden':'') . '"><span class="url-title">&nbsp;</span>';
            
            $cont .= $this->generateWebEntry($x) .'</div>';
        }
        
        return $cont;
    }

    /**
    * Generate a http:// or ftp:// etc. link from one entry of the www value group.
    *
    * @param array $m the entry of the value group to use
    * @return string html
    * @static
    */
function generateWebEntry($m)
    {
        $label = empty($m['label']) ? $m['value'] : $m['label'];
        $v = $m['value'];
        $uri = strtolower($m['label']);
        
        if(substr($m['value'],0,4) == 'www.') // fix the obvious
            $v = 'http://' . $m['value'];
        else if(substr($m['value'],0,4) == 'ftp.') // fix the obvious
            $v = 'ftp://' . $m['value'];
        
        //Other extensions (mms, irc, news, ...) are handled automatically ...
        
        return "<a class='url' href='$v'>" . $label . '</a>';
    }

    /**
    * Generate one entry as html
    *
    * @param array $m the entry of the value group to use
    * @return string html
    */
function generateAdditionalEntry($x)
    {
        if(StringHelper::isHTTP($x['value']))
            $val = '<a href="' . $x['value'] . '">' . $x['value'] . "</a>";
        else
            $val = $x['value'];
            
        return '<div class="other"><span class="other-label' . ($x['visibility'] != 'visible'?' hidden':'') . '">' . $x['label'] . '</span><span class="other-info">' . $val . '</span></div>';
    }

    /**
    * Output the additional information of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @return string html
    * @global string html
    */
function additionals($vg_x = NULL,$refid = 0)
    {
        $cont = '';
        
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('other',$refid);

        // special sorting requested? -- DEFINITELY NOT FAST n^2 code!!
        global $CONFIG_CONTACT_OTHER_INFO_ORDER;
        if(isset($CONFIG_CONTACT_OTHER_INFO_ORDER))
        {
            $output = false; // avoid spacer if no output
            foreach($CONFIG_CONTACT_OTHER_INFO_ORDER as $label)
            {
                if($label=='#') // spacer
                {
                    if($output)
                    {
                        $cont .= '<br/>';
                        $output = false; // avoid double spacer 
                    }
                    
                    continue;
                }
                
                $output = true;
                
                $n = count($vg_x);
                for($i=0;$i<$n;$i++)
                {
                    if($vg_x[$i]['label']==$label)
                    {
                        $cont .= $this->generateAdditionalEntry($vg_x[$i]); // dump
                        $vg_x[$i]=NULL; // remove it
                    }
                }
            }
        }
        
        // dump the remaining groups
        foreach($vg_x as $x)
            if($x != NULL)
                $cont .= $this->generateAdditionalEntry($x);
        
        return $cont;
    }

    /**
    * Return first plain email address to send an email to the contact from TAB
    *
    * @return string plain email address to send an email to the contact from TAB
    */
function getFirstEmail()
    {
        $vg_x = $this->getValueGroup('email');
        return $vg_x[0]['value'];
    }

    /**
    * sends a generic e-mail to the Contact's first email
    *
    * @param string $subject address to send e-mail to
    * @param string $message e-mail message
    * @global CONFIG_PHPMAILER array used to configure phpmailer
    * @global options
    * @global errorHandler    
    */
    
function sendEMail($subject,$message)
    {
        global $CONFIG_TAB_ROOT, $CONFIG_PHPMAILER, $options, $errorHandler;
        
        require_once("lib/phpmailer/class.phpmailer.php");
        
        $mailer = new PHPMailer();
        
        if(isset($CONFIG_PHPMAILER))
        {
            $mailer->Mailer   = $CONFIG_PHPMAILER['Mailer']; 
            $mailer->Sendmail = $CONFIG_PHPMAILER['Sendmail'];
            $mailer->Host     = $CONFIG_PHPMAILER['Host'];
            $mailer->Port     = $CONFIG_PHPMAILER['Port'];
            $mailer->SMTPAuth = $CONFIG_PHPMAILER['SMTPAuth'];
            $mailer->Username = $CONFIG_PHPMAILER['Username'];         
            $mailer->Password = $CONFIG_PHPMAILER['Password'];
        }
        
        $mailer->From = 'noreply@' . $_SERVER['SERVER_NAME'];
        $mailer->FromName = 'noreply@' . $_SERVER['SERVER_NAME'];
        $mailer->AddAddress($this->getFirstEmail());
        
        $mailer->Subject = $options->getOption('adminEmailSubject') . ' - ' . $subject;
        $mailer->Body    = 'This is an auto-generated message from The Address Book Reloaded at ' . $_SERVER['SERVER_NAME'] .
            ".\n\n" . $message . "\n\n" . $options->getOption('adminEmailFooter');
        
        if(!$mailer->Send())
            $errorHandler->error('mail','Email sending failed: ' . $mail->ErrorInfo,get_class($this));
        else
            $errorHandler->error('ok','Mail sent!');
    }

    /**
    * Output the e-mails of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @return string html
    */
function emails($vg_x = NULL,$refid = 0)
    {
        $cont = '';
        
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('email',$refid);
        
        if (count($vg_x) > 0) {
        
            if ($refid === null)
                $cont = '<div class="emails-title">E-Mail</div><ul class="emails">';
            else
                $cont = '<ul class="emails">';
            
            foreach($vg_x as $m)
                    $cont .= '<li' . ($m['visibility'] != 'visible'?' class="hidden"':'') . '><span class="email">' . $this->generateEmail($m) . '</span></li>';
            
            $cont .= '</ul>';
            
        }
    
        return $cont;
    }
    
    /**
    * Generate an email address from one entry of an email value group.
    * @param array $m the entry of the value group to use
    * @return string 'Firstname Lastname (label) <email@example.com>'
    */
function rawEmail($m)
    {
        return $this->contact['firstname'] . ' ' . $this->contact['lastname'] . ($m['label']?' ('.$m['label'].')':'') . ' <' . $m['value'] . '>';
    }
    
    /**
    * Generate a HTML to send email (a full A tag) from one entry of an e-mail value group.
    * @param array $m the entry of the value group to use
    * @return string <a href='Firstname Lastname (label) <email@example.com>'>email@example.com (label)</a>
    */
function generateEmail($m)
    {
        $ret = '<span class="emails-info">' . EmailHelper::sendEmailLink($this->rawEmail($m),$m['value']) . '</span>';
        
        if(!empty($m['label']))
            return '<span class="emails-label">' . $m['label'] . ': </span>' . $ret;
            
        return $ret;
    }
    
    /**
    * Output the addresses of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @return string html
    * @global PluginManager used to call hooks
    * @global AddressFromatter used to format the addresses, depending on the countries they are in
    * @todo Add code to output webs and additionals as well, not only phones, chat and email
    */
function addresses($vg_x = NULL)
    {
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('addresses');

        global $pluginManager, $addressFormatter;

        $cont = '<ul class="card-addresses">';
        
        foreach($vg_x as $a) {
            $cont .= '<li>';
            $cont .= '<div class="address-head-line"><span class="address-title">' .
                ($this->contact['primaryAddress'] == $a['refid']?'Primary Address':'Address');
            
            if ($a['type'])
                $cont .= ' <span class="type">(' . $a['type'] . ')</span>';
                
            $cont .= '</span></div>';
            $cont .= $addressFormatter->formatAddress($a);
            $cont .= $this->phones(null,$a['refid']);
            $cont .= $this->emails(null,$a['refid']);
            $cont .= $this->messaging(null,$a['refid']);
            
            // handle plugins
            $nav = new Navigation('address-menu');
            $cont .= $pluginManager->addressOutput($this,$a['refid'],$nav);
            $pluginManager->addressMenu($this,$a['refid'],$nav);
            $cont .= '<div class="adl">' . $nav->create() . '</div>';
            
            $cont .= '</li>';
        }
        
        $cont .= '</ul>';
        
        return $cont;
    }

    /**
    * Output the notes of the contact in nice html
    *
    * @return string html
    */
function notes()
    {
        $notes = $this->contact['notes'];
        
        if(!$notes)
            return '';
            
        $cont = '<div class="notes-title">Notes</div><div class="notes">';
        $cont .= $notes;
        $cont .= '</div>';
        
        return $cont;
        
    }

    /**
    * Output the dates of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @return string html
    */
function dates($vg_x = NULL)
    {
        $cont='';
        
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('date');
                    
        foreach($vg_x as $x)
        {
            if(strlen($x['label'])<22) // Avoid label and text overlaps (this is a heuristic because the font is proportional).
                $cssFormat = 'other';
            else 
                $cssFormat = 'wide';
                
            $cont .= '<div class="other' . ($x['visibility'] != 'visible'?' hidden':'') . '"><span class="'.$cssFormat.'-label">' .$x['label'] . '</span><span class="'.$cssFormat.'-info">';
            if ($x['value2'] != '0000-00-00')
                $cont .= ($x['value1'] !== null ? $x['value1'] . ' ':'') . ($x['value2'] !== null?'until ' . $x['value2']:' until further notice');
            else
                $cont .= $x['value1'];
            
            if ($x['value1'] !== null && $x['value2'] !== null)
                switch ($x['type']) {
                    case 'yearly':
                        $cont .= ', every year';
                        break;
                    case 'monthly':
                        $cont .= ', every month';
                        break;
                    case 'weekly':
                        $cont .= ', every week';
                        break;
                    case 'once':
                        $cont .= ', once';
                        break;
                    case 'autoremove':
                        $cont .= ', once (autoremove)';
                        break;
                }
            
            $cont .= '</span></div>';
            
        }
        
        return $cont;
    }
    
    /**
    * Output the groups of the contact in nice html
    *
    * @param array $vg_x the value group to use, if not set, the one of the contact is used
    * @param boolean $list create HTML UL if true 
    * @param string $field default 'groupname' alternates: acronym, logoURL
    * @param boolean $asLink return HTML A search tags linking to the main list, plain strings if false
    * @return string html
    */
function groups($vg_x = NULL,$list=true,$field='groupname',$asLink=true)
    {
        if($vg_x == NULL)
            $vg_x = $this->getValueGroup('groups');

        $cont = $list ? '<ul class="groups">' : '';

        $separator = '';
        foreach($vg_x as $g)
        {
            $l = !empty($g[$field]) ? $g[$field] : $g['groupname'];
            $cont .= ($list ? '<li>' : $separator) .  ($asLink ? "<a class='org' href=\"../contact/list.php?group={$g['groupname']}\" title=\"{$g['groupname']}\" >" : '') . $l . ($asLink ? '</a>' : '') . ($list ? '</li>' : '');
            $separator = ', ';
        }
        
        $cont .=  $list ? '</ul>' : '';        
        
        return $cont;
    }
    
    /**
     * Returns TRUE on success FALSE on errors (contact NOT saved -> check errorHandler then)
     * SPECIAL: $post['URLtoMugshot'] is path / URL to mugshot (must be accessible by fopen PHP restrictions!).
     * The pic referenced by URLtoMugshot will be uploaded into the DB after resampling (shrinking).
     
       An address must have a refid or it will be added to the DB.
       Example Post: array(9) {
          ["contact"]=>
          array(12) {
            ["id"]=>
            string(1) "4"
            ["lastname"]=>
            string(5) "Lastname"
            ["firstname"]=>
            string(8) "Firstname"
            ["middlename"]=>
            string(0) ""
            ["namePrefix"]=>
            string(4) "Ing."
            ["nameSuffix"]=>
            string(0) ""
            ["nickname"]=>
            string(0) ""
            ["sex"]=>
            string(4) "male"
            ["pictureURL"]=>
            string(41) "/gallery/People/medium/w.jpg"
            ["notes"]=>
            string(0) ""
            ["xsltDisplayType"]=>
            string(9) "expertise"
            ["hidden"]=>
            string(1) "0"
          }
          ["address"]=>
          array(4) {
            [0]=>
            array(10) {
              ["refid"]=>
              string(1) "3"
              ["type"]=>
              string(16) "Employer Address"
              ["line1"]=>
              string(12) "Zich 8"
              ["line2"]=>
              string(0) ""
              ["city"]=>
              string(4) "Nashville"
              ["state"]=>
              string(0) ""
              ["zip"]=>
              string(6) "1010"
              ["phone1"]=>
              string(0) ""
              ["phone2"]=>
              string(0) ""
              ["country"]=>
              string(2) "de"
            }
            [1]=> ...
            [2]=> ...
            [3]=> ...
          }
          ["email"]=>
          array(1) {
            [0]=>
            array(4) {
              ["type"]=>
              string(5) "email"
              ["label"]=>
              string(4) "work"
              ["value"]=>
              string(25) "a@b.com"
              ["visibility"]=>
              string(7) "visible"
            }
          }
          ["phone"]=>
          array(3) {
            [0]=>
            array(4) {
              ["type"]=>
              string(5) "phone"
              ["label"]=>
              string(3) "fax"
              ["value"]=>
              string(13) "+12345"
              ["visibility"]=>
              string(7) "visible"
            }
            [1]=> ...
          }
          ["blank"]=> // new entries will be sorted into groups
          array(4) {
            [0]=>
            array(4) {
              ["type"]=>
              string(5) "email"
              ["label"]=>
              string(0) ""
              ["value"]=>
              string(0) ""
              ["visibility"]=>
              string(7) "visible"
            }
            [1]=> ...
          }
          ["www"]=>
          array(1) {
            [0]=>
            array(4) {
              ["type"]=>
              string(3) "www"
              ["label"]=>
              string(3) "www"
              ["value"]=>
              string(14) "www.example.com"
              ["visibility"]=>
              string(7) "visible"
            }
          }
          ["other"]=>
          array(7) {
            [0]=>
            array(4) {
              ["type"]=>
              string(5) "other"
              ["label"]=>
              string(16) "Company Position"
              ["value"]=>
              string(28) "Development"
              ["visibility"]=>
              string(7) "visible"
            }
            [1]=> ...
          }
          ["date"]=>
          array(4) {
            [0]=>
            array(5) {
              ["type"]=>
              string(6) "yearly"
              ["label"]=>
              string(13) "Date of Birth"
              ["value1"]=>
              string(10) "1956-07-28"
              ["value2"]=>
              string(0) ""
              ["visibility"]=>
              string(7) "visible"
            }
            [1]=> ...
          }
          ["newgroup"]=>
          string(0) ""
        }
     * @param array $post value groups array
     * @param boolean $adminsave true skips setting the whoModifiedFlag and modifiedDate to avoid 
     * being target for INCORRECT notifications and listing on recently changed list.
     * @global Options to determite whether to allow Upload or not
     * @global ErrorHandler
     */
function saveContactFromArray(&$post,$adminsave=false)
    {
        global $options,$errorHandler;
        
        $data = $post['contact'];
        foreach ($data as $k => $v) {
            if ($k == 'pictureData') // NEEDS TO BE REMOVED FOR VCARD IMPORT avoid post. Will be obsoleted by Media Class!!
                continue;
            $this->contact[$k] = $v;
        }
        
        if ($options->getOption('picAllowUpload')) {
            if (isset($post['URLtoMugshot']) && $post['URLtoMugshot']) {
                $img = new ContactImage($this);
                $this->contact['pictureData'] = $img->resampleAndReturn($post['URLtoMugshot']);
            } elseif (isset($post['contact']['pictureData']['remove']) && $post['contact']['pictureData']['remove']) {
                $this->contact['pictureData'] = '';
            }
        }
        
        $addr = $post['address'];       
        if (isset($post['address_primary']))
            $addr[intval($post['address_primary'])]['primary'] = true;

        if(isset($post['address']))
        {
            foreach ($addr as $k => $v) {
                $empty = true;
                foreach ($v as $d)
                    if (!empty($d)) {
                        $empty = false;
                        break;
                    }
                    
                if ($empty)
                    unset($addr[$k]);
            }
            
            $this->setValueGroup('addresses',$addr);
        }
        
        $this->saveValueGroupsFromArray($post);
        
        if (isset($post['groups']) || isset($post['newgroup'])) {
            $valGroup = array();
            if (isset($post['groups']))
                foreach ($post['groups'] as $k => $v)
                    if ($v == '1')
                        $valGroup[]['groupname'] = $k;
            
            if (isset($post['newgroup']) && $post['newgroup'])
                $valGroup[]['groupname'] = $post['newgroup'];
                    
            $this->setValueGroup('groups',$valGroup);
        }
        
        if($errorHandler->getErrors('formVal') || $errorHandler->getErrors('image'))
            return false;
        
        $this->save(true,$adminsave);
        
        if($errorHandler->getErrors('formVal') || $errorHandler->getErrors('image'))
            return false;
        
        return true;
    }
    
    /**
     * Returns ongoing events from the dates table.
     * <pre>
     *            value1 value2
     * day        date   0000-00-00
     * open end   date   null
     * open start null   date
     * range      date1  date2
     * </pre>
     * COST: 0.04 seconds / 100 contacts (1GHz CPU)
     * @return array of strings of ongoing event labels, or empty array (if nothing found)
     */
function ongoingEvents()
    {
        global $db;
        
        $ongoingEvents = array();

        $myid=$this->contact['id'];
                
        $db->query("SELECT label, value1, value2,
            ((value1<=NOW() AND value2>=NOW()) OR (value1 IS NULL) AND value2>=NOW()) as until, " .  // range or end & no start
            "((value1<=NOW() AND value2 IS NULL) OR (value1=NOW() AND value2='0000-00-00') OR " .  // exact date or end & no start
            "(type='yearly' AND MONTH(value1)=MONTH(NOW()) AND DAY(value1)=DAY(NOW())) OR " .
            "(type='monthly' AND DAY(value1)=DAY(NOW())) OR " .
            "(type='weekly' AND WEEKDAY(value1)=WEEKDAY(NOW())) " .
            ') AS ongoing, ' .
            "(type='autoremove' AND value2<NOW()) AS deleteme " . // run delete query?
            "FROM " . TABLE_DATES . " as dates, " . TABLE_CONTACT . " AS contact
            WHERE hidden != 1 AND visibility = " . $db->escape('visible') . 
            " AND contact.id = dates.id AND contact.id = $myid ORDER BY value1 ASC");

        $cleanup = false; // save about 200 queries in the mainlist
        while($v=$db->next())
        {
            if($v['ongoing'])
                $ongoingEvents[]=$v['label'] .'<br/>';
            else if($v['until'])
                $ongoingEvents[]=$v['label'] .' - '. $v['value2'] .'<br/>';
            else if($v['deleteme'])
                $cleanup = true;
        }

        if($cleanup) // saves 0.1 seconds
            $db->query("DELETE FROM " . TABLE_DATES . " WHERE type='autoremove' AND value2<NOW()");
        
        return $ongoingEvents;
    }
    
// === private section ===

    // private used only by saveContactFromPost
function saveValueGroupsFromArray(&$post)
    {
        global $errorHandler,$VALUE_GROUP_TYPES_ARRAY;
        
        $rightsManager = RightsManager::getSingleton();
        $currentUser = $rightsManager->getUser();

        $valGroups = $VALUE_GROUP_TYPES_ARRAY;
        
        $newPost = array(); // prepare new, sorted value Groups array
        foreach ($valGroups as $vg)
            $newPost[$vg]=array();
        
        $valGroups[] = 'blank'; // special magic group for new entries
        
        // sort value groups (user may reassign type of entry)
        foreach ($valGroups as $vg) 
        {
            if(isset($post[$vg]))
            {
                foreach($post[$vg] as $vgi)
                {
                     // blank or value and label empty ... then delete the entry
                    if($vgi['type']==' ' || (empty($vgi['label']) && empty($vgi['value']) && empty($vgi['value1']) && empty($vgi['value2'])))                    
                        continue;
                    
                    if(!empty($vgi['type']) && $vgi['type']!=$vg && $vg != 'date') // user changed type
                    {
                        $newPost[$vgi['type']][] = $vgi; // copy to appropriate array
                        continue;
                    }
                    
                    $newPost[$vg][]=$vgi;
                }
            }
        }
        
        // store everything ... that's the easiest
        foreach ($valGroups as $vg)
            if($vg!='blank' && isset($newPost[$vg]))
                $this->setValueGroup($vg,$newPost[$vg]);
    }
    
    /**
    * Used by serialize, when serializing class
    *
    * @return array list of variables that should be saved
    */
function __sleep() {
        
        $this->valueGroups = null;
        $id = $this->contact['id'];
        $this->contact = array();
        $this->contact['id'] = $id;
        
        return array('contact');
        
    }
    
    /**
    * Used by serialize, when deserializing class
    */
function __wakeup() {
        
        $this->load($this->contact['id']);
        
    }
    
}

?>
