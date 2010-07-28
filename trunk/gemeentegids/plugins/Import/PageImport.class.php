<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link PageImport}
* @author Thomas Katzlberger
* @package plugins
* @subpackage Import
*/

/** */
require_once('Page.class.php');
require_once('HTMLHelper.class.php');

/**
* Submit interface for imports.
* @package plugins
* @subpackage Import
*/
class PageImport extends Page {
    
    var $nav;
    
    /**
    * Constructor
    */
function PageImport() 
    {
        $this->Page('Import');
        $this->nav = new Navigation('admin-menu');
        $this->nav->addEntry('return','return',Navigation::previousPageUrl());
    }
    
    /**
    * creates list of contacts, or a no entries found page
    * @return string html-content
    */
function innerCreate()
    {
        $cont = '<div class="options">';                         
        $cont .= $this->nav->create();
        
        $box = '<div class="options-title">Import</div>';
        $box .= '<form action="import.php" method="post">';
        $box .= '<div><label for="mails">Paste text data:</label></div>';
        $box .= '<div><textarea name="text" id="text" cols="60" rows="20"></textarea></div>';
        $box .= '<br><div>'; //<fieldset><legend>Format</legend>';
        $box .= HTMLHelper::createRadioButton('format','vCard(s)','vCard',true,null,false);
        //$box .= HTMLHelper::createRadioButton('format','vCard(s)','csv',true,null,false);
        $box .= '</div>';
        $box .= '<br><div>'; //<fieldset><legend>Next</legend>';
        $box .= HTMLHelper::createRadioButton('continue','More input','interface',true,null,false);
        $box .= HTMLHelper::createRadioButton('continue','Review last card','card',false,null,false); 
        $box .= '</div>';
        $box .= '<br><div><input type="submit"/></div>';
        $box .= '</form>';
                
        return $cont . HTMLHelper::createNestedDivBoxModel('options-content',$box) .'</div>';;   
    }    
}


?>
