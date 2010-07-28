<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link TableEditor}
* @package utilities
* @author Tobias Schlatter
*/

/** */
require_once('DB.class.php');
require_once('StringHelper.class.php');
require_once('TableGenerator.class.php');

/**
* class that allows automatic editing of (certain) table entries
* @package utilities
*/
class TableEditor {
    
    /**
    * @var DB database to use
    */
    var $db;
    
    /**
    * @var string name of the primary key in the table
    */
    var $primKey;
    
    /**
    * @var string name of the table to use/edit
    */
    var $tableName;
    
    /**
    * @var string sql query which selects the entries that may be edited
    */
    var $sql;
    
    /**
    * @var array associative array which specifies special field types (especially enums)
    */
    var $fieldTypes;
    
    /**
    * @var string|array default field type, if not specified for a field
    */
    var $defaultType;
    
    /**
    * @var boolean should the table be rotated by 90° if only one row is returned by the sql query
    */
    var $vertSingleRow;
    
    /**
    * @var array associative array of type fieldname => fieldcaption
    */
    var $caption;
    
    /**
    * @var array of numbers; column breaks (rowFolding parameter) for TableGenerator
    */
    var $rowFolding;
    
    /**
    * @var string get string (without beginning ?) that should be added to the post-action-url
    */
    var $get;
    
    /**
    * @var int counts data saved
    */
    var $saved,$processed;

    /**
    * Constructor
    *
    * initializes all variables and calls {@link save()} if necessary
    * @param DB $db database to use
    * @param string $tName name of the table to use
    * @param string $pKey name of the primary key of the table
    * @param array $fTypes field types, associative array of type fieldname => fieldtype, where fieldtype may be one of the following: 
    *   <ul><li>visible: just show the text but do not allow editing.<li></li>hidden: (omit any output, cannot be saved - This is NOT a hidden input!<li></li>generated: TableEditor will produce no output, it expects that this input is generated with a callback so it is saveable<li></li>text (simple text field)<li></li>text-width simple text field with a certain width<li></li>textarea-height-width<li></li>Name_tableEditorCallback: any string (no dashes) is expected to be a callback<li></li>array(value => caption, ...) (displays a dropdown)<li></ul>
    * @param array $caption field/header caption, array that maps fieldname to fieldcaption, if no caption is given, fieldname is output
    * @param string $sql sql to select entries that should be edited and shown, selects all entries, if null
    * @param string|array $defType default type for fields which are not in {@link $fTypes}
    * @param boolean $vsr flip table by 90° if only a single row is returned by {@link $sql}
    * @param string $get additional get parameters for form (without heading ?)
    */
function TableEditor($db,$tName,$pKey,$fTypes=array(),$caption=array(),$sql=null,$defType='text',$vsr = false,$get = '') {
        
        $this->db = $db;
        $this->tableName = $tName;
        $this->primKey = $pKey;
        $this->fieldTypes = $fTypes;
        
        $this->sql = $sql;
            
        $this->defaultType = $defType;
        $this->vertSingleRow = $vsr;
        
        $this->get = $get;
        
        $this->caption = $caption;
        
        $this->saved = 0;
        $this->processed = 0;
        $this->rowFolding = NULL;
        
        if (isset($_POST[$this->tableName]['confirmer']) && $_POST[$this->tableName]['confirmer'] == $this->tableName)
            $this->save();
    }
    
    /**
    * @var array of numbers e.g. array(5,10,15) to set column breaks (rowFolding parameter) for TableGenerator 
    * After output of N columns the table will wrap to the next line, indent 1 row and continue. Avoids horizontal scroller, milage may vary.
    */
function setTableRowFolding($array) 
    {
        $this->rowFolding = $array;
    }
    
    /**
    * saves the table (has not to be called by user, called by {@link TableEditor} itself)
    * 
    * this function checks for each row and field, if it may be saved by the user, and
    * if the value passed by the user is a legal value (for enum types)
    */
function save() {
        
        $fields = array();
        $header = null;
        
        $this->popFields($fields,$header,TRUE);
        
        $allowedIDs = null;
        
        if ($this->sql !== null) { // same query as display again before save to verify the primKeys that we sent out?
            $allowedIDs = array();
            $this->db->query($this->sql);
            while ($r = $this->db->next())
                $allowedIDs[] = $r[$this->primKey];
        }
        
        for ($i=0;isset($_POST[$this->tableName][$i]);$i++) {
            $cur = StringHelper::cleanGPC($_POST[$this->tableName][$i]);
            $this->processed++;
            
            if (!isset($cur[$this->primKey]))
                continue;
            
            if ($allowedIDs !== null && !in_array($cur[$this->primKey],$allowedIDs))
                continue;
            
            $tmp = '';
            
            foreach ($fields as $k => $v) {
                if ($v == 'visible' || !isset($cur[$k]))
                    continue;
                    
                if (is_array($v) && !isset($v[$cur[$k]]))
                    continue;
                    
                if (is_array($v) && $cur[$k] == 'NULL')
                    $tmp .= $k . ' = NULL, ';
                else
                    $tmp .= $k . ' = ' . $this->db->escape($cur[$k]) . ', ';
            }
            
            if (!$tmp)
                continue;
            
            $tmp = 'UPDATE ' . $this->tableName . ' SET ' . mb_substr($tmp,0,-2);
            
            $tmp .= ' WHERE ' . $this->primKey . ' = ' . $this->db->escape($cur[$this->primKey]);
            
            $this->db->query($tmp);
            $this->saved++;
        }
        
    }
    
    /**
    * Populates the field types and headers.
    * 
    * This function reads the columns from the specified
    * table and then assigns a field type and a header caption for each field.
    * After that the function sorts the fields into the same order as they occur 
    * in the fieldTypes array passed to the constructor.
    *
    * @param array $fields this array will be populated with field types
    * @param array $header this array will be populated with header captions
    * @param boolean $save add generated fields to save
    */
function popFields(&$sortedFields,&$sortedHeaders,$save=false) {
        
        if ($sortedHeaders == null)
            $sortedHeaders = array();
        
        $headers = array();
        
        $this->db->query('SHOW COLUMNS FROM ' . $this->tableName,'fpop');
        
        $fields = array();
        while ($r = $this->db->next('fpop')) {
            if (isset($this->fieldTypes[$r['Field']]))
                $fields[$r['Field']] = $this->fieldTypes[$r['Field']];
            else
                $fields[$r['Field']] = $this->defaultType;
                
            if ($fields[$r['Field']] == 'hidden' || (!$save && $fields[$r['Field']] == 'generated') ||$r['Field'] == $this->primKey)
                unset($fields[$r['Field']]);
            else
                if (isset($this->caption[$r['Field']]))
                    $headers[$r['Field']] =  $this->caption[$r['Field']];
                else
                    $headers[$r['Field']] =  $r['Field'];
        }
        
        $this->db->free('fpop');
        
        // sort the fields according the order of fieldTypes
        $order = array_keys($this->fieldTypes);
        
        // copy in the order of fieldTypes
        foreach ($order as $o => $key)
        {
            if(isset($fields[$key]))
            {
                $sortedFields[$key]=$fields[$key];
                $sortedHeaders[$key]=$headers[$key];
            }
            
            $fields[$key]=null;
        }
        
        // copy remaining leftovers
        foreach ($fields as $k => $v)
            if($v!==null)
            {
                $sortedFields[$k]=$fields[$k];
                $sortedHeaders[$k]=$headers[$k];
            }
    }
    
    /**
    * create the table editor html. Calls singleRowCreate() if result is a single row.
    *
    * @param string $title the title for the table
    * @param string $cssClass the css class for the table
    * @uses TableGenerator
    * @return string html-content
    */
function create($title,$cssClass) {
        
        if ($this->sql !== null)
            $this->db->query($this->sql);
        else
            $this->db->query('SELECT * FROM ' . $this->tableName);
        
        if($this->db->rowsAffected()==0) // oops empty table
            return "<table class='$cssClass'><tr><td>Cowardly refusing to edit empty table.</td></tr></table>";
            
        if ($this->db->rowsAffected() == 1 && $this->vertSingleRow)
            return $this->singleRowCreate($title,$cssClass);
        
        $fields = array();
        $header = array();
        
        $this->popFields($fields,$header);
                    
        $data = array();
        
        $i = 0;
        while ($r = $this->db->next()) {
            
            $tmp = array();
            $first = true;
            
            foreach ($fields as $f => $t) {
                
                if ($first) {
                    $first = false;
                    $tmp[$f] = '<input type="hidden" name="' . $this->tableName . '[' . $i . '][' . $this->primKey . ']" value="' . htmlentities($r[$this->primKey],ENT_COMPAT,'UTF-8') . '" />';
                } else
                    $tmp[$f] = '';
                
                if (is_array($t)) {
                    $tmp[$f] .= '<select name="' . $this->tableName . '[' . $i . '][' . $f . ']">';
                    foreach ($t as $val => $opt)
                        $tmp[$f] .= '<option value="' . $val . '"' . (strval($val)==strval($r[$f])?' selected="selected">':'>') . $opt . '</option>';
                    $tmp[$f] .= '</select>';
                } else {
                
                    // parse text sizes text-30 or textarea-h-w
                    $x = explode('-',$t);
                    $t = $x[0];
                    if(isset($x[1]) && is_numeric($x[1])) $size=$x[1]; else $size=null;
                    if(isset($x[2]) && is_numeric($x[2])) $cols=$x[2]; else $cols=null;
                    
                    $name = $this->tableName . '[' . $i . ']';
                    switch ($t) { 
                        case 'text':
                            $tmp[$f] .= '<input type="text"'. ($size ? ' size="'.$size.'"' : '') .' name="' . $name . '[' . $f . ']" value="' . htmlentities($r[$f],ENT_COMPAT,'UTF-8') . '" />';
                        break;
                        case 'textarea':
                            $tmp[$f] .= '<textarea' . ($size ? ' rows="'.$size.'"' : '') . ($cols ? ' cols="'.$cols.'"' : '') .' name="' . $name . '[' . $f . ']" >' . htmlentities($r[$f],ENT_COMPAT,'UTF-8') . '</textarea>';
                        break;
                        case 'visible': // output just plain not editable text
                            $tmp[$f] .= $r[$f];
                        break;
                        case 'generated': // do nothing - omit output (required for save)
                        case 'hidden': // do nothing - omit output
                        break;
                        default: // global static function callback
                            $tmp[$f] .= $t($name,$r,$f);
                        break;
                    }
                }
            }
            
            $data[$i] = $tmp;
            
            $i++;
            
        }
        
        return $this->genHTML($cssClass,$header,$data,null);
    }
    
    /**
    * create the table editor html for fliped tables (do not call standalone, automatically called by class)
    *
    * @param string $title the title for the table
    * @param string $cssClass the css class for the table
    * @uses TableGenerator
    * @return string html-content
    */
function singleRowCreate($title,$cssClass) {
        
        $fields = array();
        $header = array();
        
        $this->popFields($fields,$header);
                
        $data = array();
        
        $r = $this->db->next();
        
        $first = true;
        $i=0;
        foreach ($fields as $f => $t) {
            
            $tmp = array();
            
            $tmp['fname'] = $header[$f];
            
            if ($first) { // store row id
                $first = false;
                $tmp['fval'] = '<input type="hidden" name="' . $this->tableName . '[0][' . $this->primKey . ']" value="' . htmlentities($r[$this->primKey],ENT_COMPAT,'UTF-8') . '" />';
            } else
                $tmp['fval'] = '';
            
            if (is_array($t)) { // array? - display a selection box
                $tmp['fval'] .= '<select name="' . $this->tableName . '[0][' . $f . ']">';
                foreach ($t as $val => $opt)
                    $tmp['fval'] .= '<option value="' . $val . '"' . (strval($val)==strval($r[$f])?' selected="selected">':'>') . $opt . '</option>';
                $tmp['fval'] .= '</select>';
            } else { // we have text
        
                // parse text sizes text-30 or textarea-h-w
                $x = explode('-',$t);
                $t = $x[0];
                if(isset($x[1]) && is_numeric($x[1])) $size=$x[1]; else $size=null;
                if(isset($x[2]) && is_numeric($x[2])) $cols=$x[2]; else $cols=null;

                switch ($t) { 
                    case 'text':
                        $tmp['fval'] .= '<input type="text"'. ($size ? ' size="'.$size.'"' : '') .' name="' . $this->tableName . '[0][' . $f . ']" value="' . htmlentities($r[$f],ENT_COMPAT,'UTF-8') . '" />';
                    break;
                    case 'textarea':
                        $tmp['fval'] .= '<textarea' . ($size ? ' rows="'.$size.'"' : '') . ($cols ? ' cols="'.$cols.'"' : '') .' name="' . $this->tableName . '[0][' . $f . ']" >' . htmlentities($r[$f],ENT_COMPAT,'UTF-8') . '</textarea>';
                    break;
                    case 'visible': // output just plain not editable text
                        $tmp['fval'] .= $r[$f];
                    break;
                    case 'generated': // do nothing - omit output (required for save)
                    case 'hidden': // do nothing - omit output
                    break;
                    default: // global static function callback
                        $tmp['fval'] .= $t($this->tableName . '[0]',$r,$f);
                    break;
                }
            }
            $tmp['class'] = 'vert';
                
            $data[] = $tmp;
                
        }
        
        return $this->genHTML($cssClass,null,$data,array('fname','fval'),'class');
        
    }

    // private -- gernerates the HTML output - should include the foreach loop too!
function genHTML($cssClass,$header,$data,$arr,$class='')
    {
        $cont = '<a name="' . $this->tableName .'"></a>';
        $cont .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . ($this->get?'?' . $this->get:'') . "#" . $this->tableName . '">';
        
        // consider if this should be an inline style or better be placed in a stylesheet
        if ($this->saved>0)
            $cont .= '<div style="padding: 3px; border: #3399FF 1px solid; border-color: ; background-color: #99FF99;">Successfully saved<b> '.$this->saved.' ('.$this->processed.')</b></div>';
        
        $cont .= '<input type="hidden" name="' . $this->tableName . '[confirmer]" value="' . $this->tableName . '" />';
        $cont .= '<table>';
        
        $tableGen = new TableGenerator($cssClass,$header,$this->rowFolding);
        $cont .= $tableGen->generateTable($data,$arr,$class);
        $cont .= '</table>';
        $cont .= '<button id="TableEditorSubmitButton" type="submit">save</button>';
        $cont .= '</form>';
        
        return $cont;   
    }
    
}

?>
