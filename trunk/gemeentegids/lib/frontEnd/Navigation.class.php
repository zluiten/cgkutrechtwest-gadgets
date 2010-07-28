<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link Navigation}
* 
* @author Tobias Schlatter
* @package frontEnd
*/

/** */
require_once('HTMLHelper.class.php');

/**
* class representing navigation
* 
* handles addition of entries and sub-entries,
* removal of entries is also possible
* @package frontEnd
*/
class Navigation {
    
    /**
    * @var array associative array, used to save the links
    */
    var $content;
    
    /**
    * @var how to create the menu
    */
    var $cssclass,$opening;
    
    /**
    * Constructor
    * 
    * creates new, empty navigation
    */
function Navigation($cssclass='',$opening=true) {
        $this->cssclass = $cssclass;
        $this->opening = $opening;        
        $this->content = array();
    }

    /** URL for navigation links and Location: header to the main page
     *@static
     */
function mainPageUrl()
    {
        global $CONFIG_TAB_SERVER_ROOT, $CONFIG_MAIN_PAGE;
        return $CONFIG_TAB_SERVER_ROOT . $CONFIG_MAIN_PAGE;
    }
    
    /** Saves the current page ($_SERVER['PHP_SELF'] . $mode) on the page stack 
      @param string $mode script parameters to create the current page (if multiple pages share same url) e.g. "?mode=options"
    */
function pushCurrentPage($mode='')
    {
        $phself = $_SERVER['PHP_SELF'] . $mode;
        $aa = array();
        $a = isset($_SESSION['pageUrlStack']) ? $_SESSION['pageUrlStack'] : array(Navigation::mainPageUrl());
        $n = count($a);
        
        for($i=0;$i<$n;$i++) // cut any navigation loop - no URL on stack 2 times
        {
            if($a[$i] == $phself)
                break;
            
            $aa[] = $a[$i];
        }
        
        $aa[] = $phself;
        $_SESSION['pageUrlStack'] = $aa;
    }
    
    /** Returns previous page from the page stack or the mainPageUrl() (does not pop the stack) */
function previousPageUrl()
    {
        if(isset($_SESSION['pageUrlStack']))
        {
            $c = count($_SESSION['pageUrlStack']) - 2;
            
            if($c >= 0)
            {
                $t = $_SESSION['pageUrlStack'][$c];
            
                if($t !== null)
                    return $t;
            }
        }
        
        return Navigation::mainPageUrl();
    }
    
    /** URL for navigation links to the logout page
     *@static
     */
function logoutPageUrl()
    {
        global $CONFIG_TAB_SERVER_ROOT, $CONFIG_LOGOUT_PAGE;
        return $CONFIG_TAB_SERVER_ROOT . $CONFIG_LOGOUT_PAGE;
    }
    
    /** URL for navigation links to the logout page
     *@static
     */
function registerPageUrl()
    {
        global $CONFIG_TAB_SERVER_ROOT;
        return $CONFIG_TAB_SERVER_ROOT . 'user/register.php';
    }
    
    /**
    * add a new top-level entry to the navigation
    * @param integer|string $id unique identifier of the entry
    * @param string $caption displayed value of the link
    * @param string $href link target (use javasript: href to excute javascript)
    * @param string $extraAttributes attributes added to <A ... $extraAttributes> entry, e.g. title for tooltips, etc.
    */
function addEntry($id,$caption,$href,$extraAttributes='') {
        $this->content[$id]['caption'] = $caption;
        $this->content[$id]['href'] = $href;
        $this->content[$id]['extraAttributes'] = $extraAttributes;
    }
    
    /**
    * remove a top-level entry
    * @param integer|string $id the identifier of the entry to remove
    */
function removeEntry($id) {
        unset($this->content[$id]);
    }
    
    /**
    * add a sub entry to a top-level entry
    * @param integer|string $parentID the identifier of the entry, to which the sub-entry should be added
    * @param string $caption displayed value of the link
    * @param string $href link target (use javasript: href to excute javascript)
    * @param string $extraAttributes attributes added to <A ... $extraAttributes> entry, e.g. title for tooltips, etc.
    */
function addSubEntry($parentID,$caption,$href,$extraAttributes='') {
        $this->content[$parentID][] = array(
            'caption' => $caption,
            'href'    => $href,
            'extraAttributes'    => $extraAttributes
        );
    }
    
    /**
    * creates html from currently saved links
    * 
    * creates a navigation based on nested &lt;ul&gt;'s. Formatting has to be done with css
    * @param string $class css class for the top-level &lt;ul&gt;
    * @return string html for the navigation
    */
function create() {
        
        if (empty($this->content) || count($this->content) <= 0)
            return '';
        
        $classhtml = ($this->cssclass?' class="' . $this->cssclass . '"':'');
        $cont = "<ul $classhtml>\n";
        
        foreach ($this->content as $id => $v) {
            $cont .= '<li' . ($this->opening?' onmouseover="einblenden(this);" onmouseout="ausblenden(this);"':'') . '><a id="'.$id.'" href="' . $v['href'] . '" '.$v['extraAttributes'].'>' . $v['caption'] . "</a>\n";
            
            if (isset($v[0])) {
                $cont .= "<ul>\n";
                $i = 0;
                while (isset($v[$i])) {
                    $cont .= '<li><a href="' . $v[$i]['href'] . '" '.$v[$i]['extraAttributes'].'>' . $v[$i]['caption'] . "</a></li>\n";
                    $i++;
                }
                $cont .= "</ul>\n";
            }
            
            $cont .= "</li>\n";
            
        }
        
        $cont .= "</ul>\n";
        
        return HTMLHelper::createNestedDivBoxModel($this->cssclass,$cont);
    }
    
}

?>
