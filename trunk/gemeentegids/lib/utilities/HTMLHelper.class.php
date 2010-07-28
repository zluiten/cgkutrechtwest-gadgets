<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link HTMLHelper}
* @package utilities
* @author Thomas Katzlberger (based on code by Tobias Schlatter), contributions by Armin Pfurtscheller
*/

/**
* Utility class to generate various HTML constructs, like checkboxes, textareas, dropdowns etc.
* @package utilities
*/

class HTMLHelper
{
    /**
    * Converts array to either JSON hash { key:value, ... } or JSON array [ value1, value2, ... ] and returns the string.
    *
    * @param array $array the source array
    * @param boolean $produceHash how to convert. default=true which means produce a JSON hash-table (associative array)
    * @static
    */
function arrayToJSON($array,$produceHash=true)
    {
        if($produceHash)
        {
            $json = '{';
            foreach($array as $k => $v)
                $json .= "'$k':'$v',";
            
            return substr($json,0,-1).'}';
        }
        
        $json = '[';
        foreach($array as $v)
            $json .= "'$v',";
        
        return substr($json,0,-1).']';
    }
    
    /**
    * STATIC method that creates a textfield
    *
    * @static
    * @param string $name which name is to be used for the textfield
    * @param string $label Label text of the checkbox. default=null
    * @param string $default default text of the textfield. Text will be processed with htmlspecialchars().
    * @param string $cssClass default=null
    * @param boolean $insideDiv Is the box within its own div? default=true
    * @param string $extraAttributes extra attributes added to <input ... $extraAttributes/> for javascript onclick, onchange, style, etc.
    * @return string html-content
    */
function createTextField($name,$label=null,$default=null,$cssClass=null,$insideDiv=true,$extraAttributes='')
    {    
        $cssClass = $cssClass ? "class='$cssClass'" : '';
        
        if($insideDiv)
        {
            $cont = "\n<div $cssClass>";
            $cssClass = ''; // clear cssClass - we do not need it for input and label
        }
        else
            $cont = '';
        
        if($label) $cont .= '<label for="' . $name . '">' . $label . '</label>';
        $cont .= '<input '.$cssClass.' type="text" name="' . $name . '" id="' . $name . '" ' . ($default!==null?'value="' . htmlspecialchars($default) . '" ':'') . $extraAttributes . '/>';
        if($insideDiv) $cont .= "</div>\n";
        
        return $cont;
    }
    
function createMandatoryTextField($name,$label=null,$default=null,$cssClass=null,$insideDiv=true,$extraAttributes='')
    {
        $color = empty($default) ? '#FAA' : '#AFA';
        return $this->createTextField($name,$label,$default,$cssClass,$insideDiv,$extraAttributes." style='background-color:$color;'");
    }
    
    /**
    * STATIC method that generates a standard checkbox. Unchecked checkboxes are not set in $_POST
    *
    * @static
    * @param string $name name used when submitting the form.
    * @param string $label Label text of the checkbox. default=null
    * @param boolean $isChecked Is the box selected? default=false (not selected)
    * @param string $cssClass default=''
    * @param boolean $insideDiv Is the box within its own div? default=true
    * @return string HTML
    */
function createStandardCheckbox($name,$label=null,$isChecked=false,$cssClass=null,$insideDiv=true)
    {
        return HTMLHelper::createCheckbox($name,$label,$isChecked,$cssClass,$insideDiv,FALSE);
    }
    
    /**
    * STATIC method that generates a checkbox. 
    * WARNING: By default the code generates a input type=hidden with value 0 for the same name.
    * THIS IS NOT STANDARD HTML BEHAVIOUR. isset($_POST['name']) WILL ALWAYS BE TRUE!
    * IT IS MANDATORY TO CHECK WITH $_POST['name']=='1' when using this function!
    *
    * @static
    * @param string $name name used when submitting the form.
    * @param string $label Label text of the checkbox. default=null
    * @param boolean $isChecked Is the box selected? default=false (not selected)
    * @param string $cssClass default=''
    * @param boolean $insideDiv Is the box within its own div? default=true
    * @param boolean $createHiddenInput Switch to standard HTML behavior when false. default=true
    * @return string HTML
    */
function createCheckbox($name,$label=null,$isChecked=false,$cssClass=null,$insideDiv=true,$createHiddenInput=true)
    {
        $cssClass = $cssClass ? " class='$cssClass'" : '';
        
        if($insideDiv)
        {
            $cont = "\n<div $cssClass>";
            $cssClass = ''; // clear cssClass - we do not need it for input and label
        }
        else
            $cont = '';
        
        // This hidden thingy is needed to avoid undefined index notices. HTML does not send unchecked boxes.
        // However I wonder if all brosers interpret this correctly: 2 inputs with same value?
        // There is only a requirement for multipart/form-data encoding to send in correct order
        if($createHiddenInput)
            $cont .= '<input type="hidden" name="' . $name .'" id="' . $name . '" value="0" />';
        
        $cont .= "\n<input $cssClass type='checkbox' name=\"" . $name .'" id="' . $name . '" value="1" ' . ($isChecked?'checked="checked" ':'') . '/>';
        if($label) $cont .= "<label $cssClass for='" . $name . "'>" . $label . "</label>\n";
        if($insideDiv) $cont .= "</div>\n";
        
        return $cont;
        
    }
    
    /**
    * STATIC method that creates a textarea
    *
    * @static
    * @param string $name which name is to be used for the textarea
    * @param string $text content of the textarea. Text will be processed with htmlspecialchars().
    * @param integer $rows how many rows should the textarea have
    * @param integer $cols how many columns should the textarea have
    * @param boolean $insideDiv Is the box within its own div? default=true
    * @return string html-content
    */
function createTextarea($name,$text,$rows,$cols,$cssClass=null,$insideDiv=true)
    {
        $cssClass = $cssClass ? " class='$cssClass'" : '';
        
        if($insideDiv)
        {
            $cont = "\n<div $cssClass>";
            $cssClass = ''; // clear cssClass - we do not need it for input and label
        }
        else
            $cont = '';
            
        $cont .= "\n<textarea $cssClass cols=\"" . $cols . '" rows="' . $rows . '" name="'. $name . '">';
        $cont .= htmlspecialchars($text);
        $cont .= "</textarea>\n";
        if($insideDiv) $cont .= "</div>\n";
        
        return $cont;   
    }
    
    /**
    * STATIC method that creates a dropdown list
    *
    * @param string $name which name is to be used for the dropdown
    * @param string $label label of the dropdown (null if none)
    * @param array $options (associative) array with of type: key (option value) => displayed option text
    * @param string $selected key (option value) of the item that should be pre-selected
    * @param boolean $insideDiv Is the box within its own div? default=true
    * @param array $extraAttributes = Javascript functions or style='...' could be placed here - placed in the <select ...> tag
    * @return string html-content
    * @static
    */
function createDropdown($name,$label,$options,$selected,$cssClass=null,$insideDiv=true,$extraAttributes='')
    {
        $cssClass = $cssClass ? " class='$cssClass'" : '';
        
        if($insideDiv)
        {
            $cont = "\n<div $cssClass>";
            $cssClass = ''; // clear cssClass - we do not need it for input and label
        }
        else
            $cont = '';
        
        if($label) $cont .= '<label for="' . $name . '">' . $label . '</label>';

        $cont .= "\n<select $cssClass name=\"" . $name . '" id="' . $name . '" ' . $extraAttributes . '>';
        
        foreach($options as $k => $s)
            $cont .= '<option value="' . $k . '"' . ($k == $selected?' selected="selected"':'') . '>' . $s . '</option>';
        
        $cont .= "</select>\n";

        if($insideDiv) $cont .= "</div>\n";
        
        return $cont;   
    }    

    /**
    * STATIC method that creates a dropdown list from an array of options
    *
    * @param string $name which name is to be used for the dropdown
    * @param string $label label of the dropdown (null if none)
    * @param array $options normal array with displayed option text == option value (must be unique!)
    * @param string $selected option value of the item that should be pre-selected
    * @param boolean $insideDiv Is the box within its own div? default=true
    * @param array $extraAttributes = Javascript functions or style='...' could be placed here - placed in the <select ...> tag
    * @return string html-content
    * @static
    */
function createDropdownValuesAreKeys($name,$label,$options,$selected,$cssClass=null,$insideDiv=true,$extraAttributes='')
    {
        $newOpt = array();
        foreach($options as $k => $s) // automatically delete duplicates
            $newOpt[$s] = $s; 
            
        return HTMLHelper::createDropdown($name,$label,$newOpt,$selected,$cssClass,$insideDiv,$extraAttributes);   
    }    
    
    /**
    * STATIC method that creates a single radio button
    *
    * @param string $name which name is to be used for the button (respectively it's group)
    * @param string $label label of the dropdown (null if none)
    * @param string $value value of the button within it's group, if selected
    * @param boolean $isChecked is this radio button selected
    * @param boolean $insideDiv Is the box within its own div? default=true
    * @return string html-content
    * @author Armin Pfurtscheller
    */
function createRadioButton($name,$label,$value,$isChecked=false,$cssClass=null,$insideDiv=true) {
     
        static $idNrs = array();
        
        if (!isset($idNrs[$name]))
            $idNrs[$name] = 0;
        else
            $idNrs[$name]++;
        
        $cssClass = $cssClass ? " class='$cssClass' " : '';
        
        if($insideDiv)
        {
            $cont = "\n<div $cssClass >";
            $cssClass = ''; // clear cssClass - we do not need it for input and label
        }
        else
            $cont = '';
            
        if($label) $cont .= '<label for="' . $name . $idNrs[$name] . '">' . $label . '</label>';
        $cont .= '<input type="radio" name="' . $name .'" id="' . $name . $idNrs[$name] . '" value="' . $value . '" ' . ($isChecked?'checked="checked" ':'') . '/>';
        if($insideDiv) $cont .= "</div>\n";
        
        return $cont;
        
    }

    /**
    * Creates a button/submit-button (not an input) that is translateable by Localizer.
    */
function createButton($label,$type='submit',$extraAttributes='')
    {
        return "<button type='$type' $extraAttributes>$label</button>\n";
        //return "<input type='$type' $extraAttributes>$label</button>\n";
    }
    
    /**
    * STATIC method that creates a 9 nested div based CSS box model with the innermost class=$cssClass.
    * Other corners and sides are $cssClass-tl -tc -tr -l -r -bl -bc -br
    * http://www.sitepoint.com/article/rounded-corners-css-javascript
    * CSS example:
    * #$cssClass-tl { background: #1b5151 url(tr.gif) no-repeat top right; }
    * #$cssClass-tr { background:... url(tl.gif) no-repeat top left; }
    * div.$cssClass div div { background: transparent url(br.gif) no-repeat bottom right; }
    * div.$cssClass div div div { background: transparent url(bl.gif) no-repeat bottom left; padding: 15px; }
    */
function createNestedDivBoxModel($cssClass,$content)
    {
        return "\n<div class='$cssClass'><div class='$cssClass-tc'><div class='$cssClass-r'><div class='$cssClass-bc'><div class='$cssClass-l'><div class='$cssClass-tl'><div class='$cssClass-tr'><div class='$cssClass-bl'><div class='$cssClass-br'>\n" . $content . "\n</div></div></div></div></div></div></div></div></div>\n";
    }
    
    /**
    * Creates a dropdown menu with each file in a given directory
    * Example: HTMLHelper::createFileSelector('images/','list','Select File:','default.png',null,array('nada'=>'nothing selected'),
        $insideDiv=true,'onChange="javascript:alert(\'onClick\');" onblur="javascript:alert(\'onBlur\');"')
    *
    * @param string $dir = the directory to run through for the dropdownmenue content
    * @param string $name = name and id of the dropdownmenue
    * @param string $label = text for the dropdownmenue
    * @param string $selected = file that should be selected
    * @param string $alwaysIncludeSelected if set (not null) forces inclusion of existing value as selcted option: option.value=$selected option.text=$alwaysIncludeSelected
    * @param string $cssClass = CSS class which can be used for both <div> and <option> tags
    * @param string $extraAttributes = Javascript functions could be placed here - it is placed in the <select> tag
    * @param boolean $insideDiv = If true, the dropdownmenue would be nested into a <div> tag
    */
    
function createFileSelector($dir,$validFileExtensions,$name,$label,$selected,$alwaysIncludeSelected=null,$cssClass=null,$extraOptions=null,$insideDiv=true,$extraAttributes='')
    {
        //Check if CSS Class was set
        $cssClass = $cssClass ? " class='".$cssClass."' " : " '' ";
        
        if(!isset($extraOptions))
            $extraOptions=array();
                        
        //Check if Dropdownmenue is inside a <div>
        if($insideDiv)
        {
            $cont = "\n<div $cssClass>";
            $cssClass = ''; // clear cssClass - we do not need it for input and label
        }
        else
            $cont = '';
            
        //Check if Label was set
        if($label) $cont .= "<label for='" . $name . "'>" . $label . " </label>";
        
        //Build the Dropdownmenue
        $cont .= "<select name='" . $name . "' id='" . $name . "' " . $cssClass . " " . $extraAttributes.">";
        
        $foundSelected = FALSE;
        
        //Check if @param is dir
        if (is_dir($dir)) 
        {
            $fileArray = scandir($dir);            
            natcasesort($fileArray); // sort case insensitive
            
            //Check if ExtraOptions were set
            if($extraOptions)
            {
                foreach($extraOptions as $key => $value)
                    $cont .= "<option value='". $key ."'". ($key == $selected ? ' selected="selected"' : '') .">" . $value . "</option>";
            }
            
            $n = count($fileArray);
            for($i=0;$i<$n;$i++)
            {
                if(substr($fileArray[$i],0,1) == '.') // skip '.' files
                    continue;
                
                if(!in_array(strrchr($fileArray[$i],'.'),$validFileExtensions))
                    continue;
                
                if($fileArray[$i] == $selected)
                {
                     $foundSelected = TRUE;
                     $selAttribute = ' selected="selected"';
                }
                else
                    $selAttribute = '';
                    
                $cont .= "<option value='". $fileArray[$i] ."'". $selAttribute .">" . $fileArray[$i] . "</option>";
            }
        }
        else
            return 'The entered directory does not exist!<br />Please check your URL.';
        
        // Keep existing value
        if($foundSelected==FALSE && isset($alwaysIncludeSelected))
            $cont .= "<option value='$selected' selected='selected'>$alwaysIncludeSelected</option>";
        
        //Close the Menue
        $cont .= "</select>";
        
        //Check if Dropdownmenue is inside a <div>
        if($insideDiv) $cont .= "</div>\n";
        
        //give the Menue back
        return $cont;
    }
}
?>