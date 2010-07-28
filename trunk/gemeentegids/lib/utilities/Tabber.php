<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link Tabber}
* @package utilities
* @author Thomas Katzlberger
*/

/**
* UNUSED! Create div of tabs to be formatted with tabber.js. CSS classes: tabber, tabbertab
* @package utilities
*/
class Tabber {
    
    var $tabArray;
    
    /**
    * constructor
    */
function Tabber()
    {
        $tabArray = array();
    }
    
    /**
    * add a tab
    */
function addTab($title,$htmlContent)
    {
        $tabArray[] = array('title'=>$title,'html'=>$htmlContent);
    }
    
    /**
    * create the html delivered to the client
    * @return string html-content
    */
function create()
    {
        $cont = '<div class="tabber">';
        
        foreach($tabArray as $tab)
            $cont .= "<div class='tabbertab' title='".$tab['title']."'>".$tab['html']."</div>";
            
        $cont .= '</div>';
        
        return $cont;
    }
}
    
?>
