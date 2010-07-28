<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link Options}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('DB.class.php');
require_once('ErrorHandler.class.php');

/**
* Class used to retrieve and save user specific and global options
* @package backEnd
* @uses User saves the user, for which the options are loaded
*/
class Options {
    
    /**
    * @var array associative array of global options
    */
    var $global_options;
    
    /**
    * @var array associative array of user specific options
    */
    var $user_options;
    
    /**
    * @var User user, for which the options are loaded
    */
    var $user;

    /**
    * Constructor
    * 
    * Loads the options from the database
    * @param User $user if this variable is null, only the global options are loaded
    * @global DB used for database access
    */
function Options($user = null) {
        
        global $db;
        
    $this->user = $user;
        
        $db->query('SELECT * FROM ' . TABLE_OPTIONS);
        $this->global_options = $db->next();
        $db->free();
        
        if ($this->user) {
            $db->query('SELECT * FROM ' . TABLE_USERS . ' WHERE userid = ' . $db->escape($this->user->id));
            $this->user_options = $db->next();
            $db->free();
        }
    }

    /**
    * gets the value of the option with the specified label
    * 
    * this function checks, whether the requested option exists in the user specific array and returns the value if so,
    * otherwise it returns the global value
    * @param string $label name of the option
    * @param string $type get _global_, _user_ or _combined_ option ## not yet implemented
    * @return string value of the option
    */
function getOption($label,$type='combined') {
        switch ($type) {
        case 'combined':
            if (isset($this->user_options[$label]) && (is_numeric($this->user_options[$label]) || $this->user_options[$label]))
                return $this->user_options[$label];
        case 'global':  // NO break before this line
            return (isset($this->global_options[$label])?$this->global_options[$label]:null);
        case 'user':
            return (isset($this->user_options[$label])?$this->user_options[$label]:null);
        }
    }
    
    /**
    * sets a user option
    * @param string $label name of option to set
    * @param string $val value to set 
    */
function setUserOption($label,$val) {
        $this->user_option[$label] = $val;
    }
    
    /**
    * sets a global option
    * @param string $label name of option to set
    * @param string $val value to set 
    */
function setGlobalOption($label,$val) {
        $this->global_option[$label] = $val;
    }
    
    /**
    * save the global options
    * @global DB used for database connection 
    */
function save_global() {
        
        global $db;
        
    // This function saves global settings to the database, in the options table.
        
        $db->query('SHOW COLUMNS FROM ' . TABLE_OPTIONS);
        
        $query = 'UPDATE ' . TABLE_OPTIONS . ' SET ';
        
        while ($r = $db->next())
            if (isset($this->global_options[$r['Field']]))
                $query .= $r['Field'] . ' = ' . $db->escape($this->global_options[$r['Field']]) . ',';
        
        $db->free();
        
        $query = mb_substr($query,0,-1);
        
        $db->query($query);

    return true;
    }
    
    /**
    * save the user options
    * @global DB used for database connection 
    */
function save_user() {
        
        global $db;
        
        $db->query('SHOW COLUMNS FROM ' . TABLE_USERS);
        
        $query = 'UPDATE ' . TABLE_USERS . ' SET ';
        
        while ($r = $db->next())
            if (isset($this->global_options[$r['Field']]) && isset($this->user_options[$r['Field']]))
                $query .= $r['Field'] . ' = ' . $db->escape($this->user_options[$r['Field']]) . ',';
        
        $db->free();
        
        $query = mb_substr($query,0,-1) . ' WHERE userid = ' . $db->escape($this->user->id);
        
        $db->query($query);

    return true;
        
    }
    
    /**
    * reset the user options to default (global options)
    * @global DB used for database connection 
    */
function reset_user() {

        global $db;
        
        $db->query('SHOW COLUMNS FROM ' . TABLE_USERS);
        
        $query = 'UPDATE ' . TABLE_USERS . ' SET ';
        
        while ($r = $db->next())
            if (isset($this->global_options[$r['Field']]))
                $query .= $r['Field'] . ' = NULL,';
        
        $db->free();
        
        $query = mb_substr($query,0,-1) . ' WHERE userid = ' . $db->escape($this->user->id);
        
        $db->query($query);

    return true;
        
    }

}


?>
