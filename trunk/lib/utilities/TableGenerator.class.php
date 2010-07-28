<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link TableGenerator}
* @package utilities
* @author Tobias Schlatter
*/

/**
* generator for tables of a database-like form
* @package utilities
*/
class TableGenerator {
    
    /**
    * @var string css class to use for the table
    */
    var $style;
    
    /**
    * @var array list of headers to display (can be associative, see description of {@link generateTable()}
    */
    var $headArr;
    
    /**
    * @var array breaks to new row after x1,x2,x3 columns array(3,6,9) splits input after each n columns to new row
    */
    var $rowFolding;
    
    /**
    * @var string of field name in $data to use as dom ID attribute for rows
    */
    var $tableRowIdFieldName;
    
    /**
    * Constructor
    * 
    * expects css class of table and array of headers
    * @param string $style css class of table
    * @param array $headerArray headers of table or null, if no headers should be displayed
    * @param array $rowFolding breaks to new row after x1,x2,x3 columns array(3,6,9) splits input after each n columns to new row
    * @param string $tableRowIdFieldName ->> <tr id="$data[$tableRowIdFieldName]">
    * @return string html-content 
    */                              
function TableGenerator($style,$headerArray=null,$rowFolding=null,$tableRowIdFieldName=null) {
        
        $this->style = $style;
        $this->headArr = $headerArray;
        $this->rowFolding = $rowFolding;
        $this->tableRowIdFieldName = $tableRowIdFieldName;
    }
    
    /**
    * generates the head of the table (if necessary)
    * @param array $fields fields to display headers for (see description of {@link generateTable()})
    */
function generateHead($fields=null) {
        
        $cont = "<thead>\n<tr>\n";
        
        if ($fields!==null && isset($this->headArr[$fields[0]]))
        {
            $i=0;
            foreach($fields as $f)
            {
                if($this->rowFolding && in_array($i++,$this->rowFolding))
                    $cont .= "</tr>\n<tr><td class='{$this->style}-tdblank'></td>";
                    
                $cont .= '<th>' . $this->headArr[$f] . "</th>\n";
            }
        }
        else
        {
            $i=0;
            foreach($this->headArr as $h)
            {
                if($this->rowFolding && in_array($i++,$this->rowFolding))
                    $cont .= "</tr>\n<tr><td class='{$this->style}-tdblank'></td>";
                    
                $cont .= '<th>' . $h . "</th>\n";
            }
        }
            
        $cont .= "</tr>\n</thead>\n";
        
        return $cont;
        
    }
    
    /**
    * generates the body of the table
    * @param array $data data to output (format: see description of {@link generateTable()}
    * @param array $fields fields to display (see description of {@link generateTable()})
    * @param string $className field to use for css class
    * @param string $groupBy field to group by
    * @param boolen $firstOnly only group by first character of group by field
    * @return string html-content
    */
function generateBody($data,$fields=null,$className='',$groupBy='',$firstOnly=true) {
        
        $cont = "<tbody>\n";
        
        $cGr = null;
        
        if ($fields !== null)
            $span = count($fields);
        else
            $span = count($data[0]);
        
        foreach ($data as $d) {
            
            if ($groupBy) {
                
                $tmp = $d[$groupBy];
                
                if ($firstOnly)
                    $tmp = mb_substr($tmp,0,1);
                
                if (mb_strtolower($cGr) != mb_strtolower($tmp)) {
                    $cont .= '<tr><th colspan="' . $span . '" class="' . $this->style . '-subheader">' . $tmp . "</th></tr>\n";
                    $cGr = $tmp;
                }
            }
            
            $domId = !empty($this->tableRowIdFieldName) ? 'id="'.$d[$this->tableRowIdFieldName].'"' : '';
            $cssclass = ($className && isset($d[$className])) ? 'class="'.$d[$className].'"' : '';
            $cont .= "<tr $domId $cssclass>\n";
            
            if ($fields !== null)
            {
                $i=0;
                foreach ($fields as $f)
                {
                    if($this->rowFolding && in_array($i++,$this->rowFolding))
                        $cont .= "</tr>\n<tr><td class='{$this->style}-tdblank'></td>";
                        
                    $cont .= '<td>' . $d[$f] . "</td>\n";
                }
            }
            else
            {
                $i=0;
                foreach ($d as $v)
                {
                    if($this->rowFolding && in_array($i++,$this->rowFolding))
                        $cont .= "</tr>\n<tr><td class='{$this->style}-tdblank'></td>";
                        
                    $cont .= '<td>' . $v . "</td>\n";
                }
            }
         
            $cont .= "</tr>\n";
                    
        }
        
        $cont .= "</tbody>\n";
        
        return $cont;
        
    }
    
    /**
    * generates the the table
    *
    * if fields is not set, the header array (if set) is output in the given order, also each array element of
    * {@link $data} is considered as array and output in the given order
    * if fields is set though, header array and each array element of {@link $data} are considered as
    * associative array mapping the field names to the field values respectively to the field headers.
    * fields which do not appear in the fields array are not displayed, can be used for groupBy and className though.
    * @param array $data data to output
    * @param array $fields fields to display
    * @param string $className field to use for css class
    * @param string $groupBy field to group by
    * @param boolen $firstOnly only group by first character of group by field
    * @return string html-content
    */
function generateTable($data,$fields=null,$className='',$groupBy='',$firstOnly=true) {
        
        $cont = "<table class=\"{$this->style}\">\n";
        
        if ($this->headArr !== null)
            $cont .= $this->generateHead($fields);
        
        $cont .= $this->generateBody($data,$fields,$className,$groupBy,$firstOnly);
        
        $cont .= "</table>\n";
        
        return $cont;
        
    }
    
}




?>
