<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 *  SiNgLeSiGnOn PLUGIN
 *
 * This plugin is under development and does not work ... yet.
 * @package plugins
 * @author Thomas Katzlberger
 */

/** */

class SSOClient
{
    var $appName;
    var $mode;
    //var $privilege;
    var $DB_host;
    var $DB_user;
    var $DB_pass;
    var $DB_name;
    var $DB_table;
    var $DB_primaryKey;
    var $fetchFunctions;
    
function SSOClient($appName,$DB_host,$DB_name,$DB_user,$DB_pass,$DB_table,$DB_primaryKey,$fetchFunctions)
    {
        static $counter=0;
        
        $this->appName = $appName;
        $this->mode = "sso".$counter;
        $counter++;
        $this->privilege = $privilege;
        $this->DB_host = $DB_host;
        $this->DB_name = $DB_name;
        $this->DB_user = $DB_user;
        $this->DB_pass = $DB_pass;
        $this->DB_table = $DB_table;
        $this->DB_primaryKey = $DB_primaryKey;
        $this->fetchFunctions = $fetchFunctions;
    }
    
    /** DEBUG functionality only! No DB query executed yet! */
function createAccount(&$c)
    {
        $otherMySQL = new DB($this->DB_host,$this->DB_user,$this->DB_pass,$this->DB_name);
        $fields = array();
        $values = array();
        
        $u = $c->getUserRecord(); // define $u for eval function
        if($u === FALSE) // not a user
            return;
        
        //Now we should fetch the foreign UserId (stored in this contact) if available
        $fields[] = $this->DB_primaryKey;
        $values[] = $c->getProperty('other',$this->DB_primaryKey);
        
        // compile a debug message
        $msg = '';
        foreach($this->fetchFunctions as $field => $func)
        {
            $fields[] = $field;
            $v = eval($func);
            $values[] = $v;
            $msg .= $field .' = '.$v.', ';
        }
        
        // Create SQL
        $sql = 'UPDATE '.$this->DB_table.' SET (' . implode(',',$fields) . ') VALUES ("' . implode('","',$values) . '")';
        
        //Perform query
        //$otherMySQL->query($sql);
        
        //Now we should fetch the foreign UserId and store it in the contact for future updates of the record in the foreign Application!
        //$c->set....(); unfortunately we have no setProperty() function yet!
        
        echo $sql;
        return $msg;
    }
}

?>
