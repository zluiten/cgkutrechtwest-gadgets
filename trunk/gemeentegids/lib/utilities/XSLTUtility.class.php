<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link XSLTUtility}
* @author Thomas Katzlberger
* @package frontEnd
* @subpackage utilities
*/

/** */
require_once('Page.class.php');

/**
* Performs an XSLT Transform from 2 strings and returns the result PHP5 required!
*
* @package frontEnd
* @subpackage pages
*/
class XSLTUtility
{    
    /**
    * @var DomDocument
    */
    var $xml;
    
    /**
    * @param $xml string
    */
function XSLTUtility($xmlOrFilename)
    {
        $this->xml = substr($xmlOrFilename,0,1)=='<' ? DomDocument::loadXML($xmlOrFilename) : DomDocument::load($xmlOrFilename);
        
        if($this->xml === FALSE)
        {
            echo 'load error';
            $this->xml=null;
        }
    }
    
    /**
    * transform
    * @param $xslt string if string starts with less-than character XSLT is passed as string **OTHERWISE** filename!!
    * @return string html-content
    */
function transform($xsltOrFilename)
    {
        $xslt = new XSLTProcessor();
        $style = substr($xsltOrFilename,0,1)=='<' ? DomDocument::loadXML($xsltOrFilename) : DomDocument::load($xsltOrFilename);

        // error in loading the xslt
        if($style == null)
            return '<b>Failed to load:</b><pre>'.$xsltOrFilename.'</pre>';
        
        $xslt->importStyleSheet($style);
        return $xslt->transformToXML($this->xml); // return result
    }
    
    /**
    * Array to XML transformation. Numbered elements will transform to <row></row> but should be avoided
    
    array('root' => 
        array( 'name'=>
                array( 'firstname' => 'F', 'lastname' => 'N')
              )
        array( 'table'=>
                array( 'tr' => array ('1', '2', 'X', 'W') )
              )
           );
       
    <root>
        <name>
            <firstname>F</firstname>
            <lastname>N</lastname>
        </name>
        <table>
            <tr>1</tr>
            <tr>2</tr>
            <tr>X</tr>
            <tr>W</tr>
        </table>
    </root>

    * @param array Array representation of a XML tree: array('contact' => array( 'firstname' => , 'lastname' => ));
    * @return string XML with ?xml declaration
    */
function arrayToXML(&$array)
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' ."\n". XSLTUtility::arrayToXMLraw($array,$h=false);
    }
    
    /**
    * Array to XML transformation
    * @param string $xslt if string starts with less-than character XSLT is passed as string **OTHERWISE** filename!!
    * @param boolean $suppressOuter this RETURN(!) value is set to true by recursion if outer lablel would be incorrect
    * @param string $outerLabel is used if suppressOuter is true default is '!--'
    * @return string html-content
    */
function arrayToXMLraw(&$array,&$suppressOuter,$outerLabel='!--')
    {
        $x = '';
        foreach($array as $k => &$v)
        {
            if(is_numeric($k))
            {
                $k=$outerLabel;
                $suppressOuter=TRUE;
            }
            else
                $suppressOuter=FALSE;
            
            if(is_array($v))
            {
                $h = XSLTUtility::arrayToXMLraw($v,$suppress,$k);
                
                if($suppress)
                    $x .= $h;
                else
                {   // echo "=$k=$h=$k=\n";
                    $x .= "<$k>$h</$k>\n";
                }
            }
            else
                $x .= "<$k>".htmlspecialchars($v,ENT_NOQUOTES,'UTF-8')."</$k>\n";
        }
        
        return $x;
    }
}

?>
