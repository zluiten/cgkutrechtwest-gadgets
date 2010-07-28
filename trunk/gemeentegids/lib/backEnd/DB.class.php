<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* Contains class {@link DB DB}
*
* @package backEnd
* @author Tobias Schlatter, extensions by Thomas Katzlberger
*/

/** */
require_once('ErrorHandler.class.php');

/**
* Singleton object to connect to a database
* 
* Handles database-connection opening, querying and data fetching;
* has also methods to backup the database
*
* @package backEnd
*/
class DB {
    
    /**
    * @var int saves database connection
    */
    var $connection;
    
    /**
    * saves descriptors of open sql resources
    * @var array 
    */
    var $queries = array();
    
    /**
    * saves index in {@link $this->queries} of the last query made
    * @var integer|string 
    */
    var $last;
    
    /**
    * saves account data, used by {@link backup()}
    * @var array 
    */
    var $accData;
    
    /**
    * Records profiling info if switched on by startProfiling().
    * @var array ( string $sql, float $execTime )
    */
    var $profiling = null;
    var $timer = null;
    
    /**
    * Opens DB connection and selects database
    * @param string $location Location of the database-server
    * @param string $user Username used to log in
    * @param string $password Password used to log in
    * @param string $database Name of the Database
    * @global ErrorHandler Used to report errors
    */
function DB($location,$user,$password,$database) {
        global $errorHandler;
        
        if (!$this->connection = @mysql_connect($location,$user,$password))
            $errorHandler->error('db',"Failed to connect to database server '$location' as user '$user': " . mysql_error());
            
        if (!@mysql_select_db($database,$this->connection))
            $errorHandler->error('db',"Failed to select database $database: " . mysql_error());
            
        @ini_set('magic_quotes_runtime','0');
        
        $this->accData = array(
            'user' => $user,
            'location' => $location,
            'password' => $password,
            'database' => $database
        );
        
        $this->setCharacterSet(); // default
    }

    /**
    * This method 'fixes' the use of global $db. Currently this is a fix until the whole app can be refactored.
    */
function getSingleton()
    {
        global $db;
        return $db;
    }
    
    /**
    * This method 'fixes' the global use of $CONFIG_DB_PREFIX for future use. Currently this is a fix until the whole app can be refactored.
    * @param $table
    * @return $CONFIG_DB_PREFIX . $table
    */
function getTableName($table)
    {
        global $CONFIG_DB_PREFIX;
        return $CONFIG_DB_PREFIX . $table;
    }

    /**
    * Sets the connection charset to communicate with the server.
    * @param string $charset DEFAULT='utf8'
    */
function setCharacterSet($charset='utf8')
    {
        //$db->query("SET CHARACTER SET $charset");  ... does not work ...
            // Equivalent to:
            // SET character_set_client = x;
            // SET character_set_results = x;
            // SET collation_connection = @@collation_database;
        $this->query("SET NAMES $charset");
            // Equivalent to:
            // SET character_set_client = x;
            // SET character_set_results = x;
            // SET character_set_connection = x;
        //$this->query("SET character_set_connection=$charset");
            // changes collation_connection too and makes connection binary safe
    }
    
    /**
    * Queries the database and ignores errors
    *
    * Mainly to be used in upgrade queries, which may have been carried out already
    * after calling this function, you will NOT be able to fetch results, query insertIDs
    * or the rows which were affected
    * @param string $sql SQL to be used to query the database
    * @return boolean true on success
    */
function queryNoError($sql) {
        return @mysql_query($sql,$this->connection);
    }
    
    /**
    * Queries the database
    * @param string $sql SQL to be used to query the database
    * @param integer|string $id Index in {@link $this->queries} where the result should be saved
    * @global ErrorHandler Used to report errors
    */
function query($sql,$id=0) {
        global $errorHandler;
        
        if($this->profiling!==null)
            $this->timer->start();
        
        if (!$this->queries[$id] = @mysql_query($sql,$this->connection))
            $errorHandler->error('db','Failed to do query: ' . mysql_error() . '<br />In query: ' . $sql);
            
        $this->last = $id;
        
        if($this->profiling!==null)
            $this->profiling[]=array($sql,$this->timer->stop());
    }
    
    /**
    * Fetches next row of a query
    * @param integer|string $id Index of query
    * @return array Associative array with one row data or null 
    */
function next($id=0) {
        if (ini_get('magic_quotes_runtime') == '0')
            return @mysql_fetch_array($this->queries[$id]);
        else
            return $this->unescape(@mysql_fetch_array($this->queries[$id]));
    }
    
    /**
    * Frees query memory (only needed with big queries)
    * @param integer|string $id Index of query
    */
function free($id=0) {
        @mysql_free_result($this->queries[$id]);
    }
    
    /**
    * Returns affected rows or number of rows returned
    *
    * This function unions two mysql functions in one. It uses
    * {@link mysql_affected_rows() mysql_affected_rows()} if the last query wasn't a query that yielded a
    * result, and it uses {@link mysql_num_rows() mysql_num_rows()} if an id was given, or the
    * last query returned a result
    * @param $id Index of query to return row number
    * @global ErrorHandler Used to report errors
    * @return integer Number of rows
    */
function rowsAffected($id=0) {
        global $errorHandler;
        
        if ($this->queries[$id] === TRUE)
            if ($id != $this->last)
                $errorHandler->error('db','Tried to get affected rows of non-last, non-result query!');
            else
                return @mysql_affected_rows($this->connection);
        else
            return @mysql_num_rows($this->queries[$id]);
    }
    
    /**
    * Returns last auto increment value
    *
    * This function is needed for INSERT queries which insert in a table with
    * auto increment value, if the id is needed later.
    * @return integer Id of last inserted row
    */
function insertID() {
        return @mysql_insert_id($this->connection);
    }
    
    /**
    * Escapes strings and binary data to be inserted in the database
    * WARNING: this function may convert strings to numbers ("2" -> 2 and "2a" -> "'2a'")!
    *
    * If the function {@link mysql_real_escape_string() mysql_real_escape_string()} exists, it will be used;
    * otherwise the old and deprecated function {@link mysql_escape_string() mysql_escape_string()} is used
    * @param string $val String to escape
    * @staticvar boolean Caches, if {@link mysql_real_escape_string() mysql_real_escape_string()} exists or not
    * @return string Escaped string
    */
function escape($val) {
        if (strval(floatval($val)) === strval($val))
            return $val;

        static $real=NULL;
        if ($real === NULL)
            $real = function_exists('mysql_real_escape_string');

        if ($real)
            return "'" . mysql_real_escape_string($val,$this->connection) . "'";
        else
            return "'" . mysql_escape_string($val) . "'";
    }
    
    /**
    * Escapes strings and binary data to be inserted in the database
    *
    * If the function {@link mysql_real_escape_string() mysql_real_escape_string()} exists, it will be used;
    * otherwise the old and deprecated function {@link mysql_escape_string() mysql_escape_string()} is used
    * @param string $val String to escape
    * @staticvar boolean Caches, if {@link mysql_real_escape_string() mysql_real_escape_string()} exists or not
    * @return string Escaped string
    */
function strEscape($val) 
    {
        static $fun=NULL;
        if ($fun === NULL)
            $fun = function_exists('mysql_real_escape_string') ? 'mysql_real_escape_string' : 'mysql_escape_string';
        
        return "'" . $fun($val) . "'";
    }
    
    /**
    * Unescapes string or array of results. Useful, if magic_quotes_runtime is turned on
    *
    * The PHP "feature" magic_quotes is capable of quoting data, returned from functions
    * such as {@link mysql_fetch_array() mysql_fetch_array()}, this function unescapse it again, if needed
    * @static
    * @param string $val string to be unescaped
    * @return string unescaped string
    */
function unescape($val) {
        if (!is_array($val))
            return stripslashes ($val);
            
        $ret = $val;
        foreach ($ret as $k => $v)
            $ret[$k] = $this->unescape($v);
            
        return $ret;
    }
    
    /**
    * Dumps the database and outputs it to the server
    *
    * Because of php-safe-mode, some ifs are required, to ensure the command is not
    * escaped twice
    * 
    * @global ErrorHandler Used to report errors
    */
function backup() {
        global $errorHandler,$CONFIG_DB_NAME;
        
        $filename = date('Y-m-d').'_'.$CONFIG_DB_NAME. (isset($_SERVER['HTTP_HOST']) ? '_'.$_SERVER['HTTP_HOST'] : '');
        
        if (ini_get('safe_mode') && (
            strpos($this->accData['location'],' ') !== false ||
            strpos($this->accData['user'],' ') !== false ||
            strpos($this->accData['password'],' ') !== false ||
            strpos($this->accData['database'],' ') !== false))
                $errorHandler->error('invArg','One of the database-variables contains spaces');
                
        
        header('Content-type: application/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.sql"');
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: 0");

        if (ini_get('safe_mode'))
            passthru('mysqldump --opt' .
            ' --host=' . $this->accData['location'] .
            ' --user=' . $this->accData['user'] .
            ' --password=' . $this->accData['password'] .
            ' --databases ' . $this->accData['database']);
        else
            passthru('mysqldump --opt' .
            ' --host=' . escapeshellarg($this->accData['location']) .
            ' --user=' . escapeshellarg($this->accData['user']) .
            ' --password=' . escapeshellarg($this->accData['password']) .
            ' --databases ' . escapeshellarg($this->accData['database']));
        
    }
    
    /**
    * Starts recording each query and exec time.
    */
function startProfiling()
    {
        $this->profiling = array();
        $this->timer = new Timer();
    }
    
    /**
    * Stops profiling and dumps result as HTML table.
    */
function dumpProfiling()
    {
        echo '<table>';
        
        $n=0;
        $t=0;
        foreach($this->profiling as $p)
        {
            echo '<tr><td>'.$p[1].'</td><td>'.htmlentities($p[0],ENT_COMPAT,'UTF-8')."</td></tr>\n";
            $n++;
            $t+=$p[1];
        }
        echo '<tr><td>Time: '.$t.'</td><td>Queries: '.$n."</td></tr>\n";
        echo '</table>';
        $this->profiling=null;
    }    
}

/**
* holds Sigleton instance of DB Object for the db connection.
* @global AddressFormatter $GLOBALS['db']
* @name $db
*/
$GLOBALS['db'] = new DB($CONFIG_DB_HOSTNAME, $CONFIG_DB_USER, $CONFIG_DB_PASSWORD, $CONFIG_DB_NAME);

?>
