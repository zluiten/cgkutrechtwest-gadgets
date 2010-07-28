<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link Page}
* @author Thomas Katzlberger
* @package frontEnd
*/

require_once("PageDelegate.class.php");
require_once("Localizer.class.php");

/**
* Super-class of every page displayed in tab.
* 
* This class is a factory for all other pages.
* Delegates content creation (header/footer) to {@link PageDelegate}.
* PageDelegate may be replaced by a subclass for different applications of TABR.
* It is also possible to set the PageDelegate with the Page constructor, this affects
* then only your pages and leaves original TAB pages as they are.
* This is useful if one might embed TABR into another web application as 
* module to handle login and authentication.
* @package frontEnd
*/

class Page {
    
    /**
    * @var Object that creates the actual page.
    */
    var $delegate;
    
    /**
    * @var array of strings that holds additional page specific header sections e.g. inline style sections. Output in headerCreate().
    */
    var $preHeaderSections,$postHeaderSections;
    
    /**
    * @var array of strings that holds additional page specific body attribute (mostly javascript for onload etc.).
    */
    var $bodyAttributes;
    
    /**
    * @var boolean default TRUE by constructor, set to FALSE to disable showing the footer.
    */
    var $showFooter;
    
    /**
    * Constructor
    * 
    * initializes {@link $title}
    * @param string $title the title of the page, normally something is added before this title
    * @param boolean $cachable whether the page disables browser caching with Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0 and Pragma: no-cache headers (default: false)
    * @param boolean $robots whether the page allows robots (meta tag) (default: false)
    * @deprecated $delegate PageDelegate can only be set by config.php $CLASS_POSING_ARRAY
    */
function Page($title,$cachable=FALSE,$robots=FALSE)
    {
        $this->preHeaderSections = array();
        $this->postHeaderSections = array();
        $this->bodyAttributes = array();
        $this->showFooter = TRUE;
        
        $this->delegate = Page::newPage('PageDelegate',$title,$cachable,$robots);
    }
    
    /**
    * Page class factory.
    * Actually this function is generic and would not only return Pages.
    * 
    * @param string $class the name of the class that should be instantiated. If the class is not found in CONFIG_CLASS_POSING_ARRAY: return new $class;
    * @param variable argument list, pass as if it were a normal constructor
    * @global array $CONFIG_CLASS_POSING_ARRAY associative array of old classname => newClassname
    * @return the new class
    */
function newPage($class) {
        global $CONFIG_CLASS_POSING_ARRAY;
        
        $args = func_get_args();
        
        if(isset($CONFIG_CLASS_POSING_ARRAY[$class]))
        {
            $class = $CONFIG_CLASS_POSING_ARRAY[$class];
            include_once($class . '.class.php');
        }
        
        $argstr = array();
        
        for($i=1;$i<count($args);$i++)
            $argstr[] = '$args[' . $i . ']';
        
        $call = 'return new ' . $class . ' (';
        $call .= implode(',',$argstr) . ');';

        return eval($call);
    }

    /**
    * add additional page specific attributes to the body tag. Warning attributes will be merged on repeated calls!
    * addBodyAttributes('onload','alert("A");'); addBodyAttributes('onload','alert("B");'); will result in
    * body onload='alert("A");alert("B");'
    * @param attribute attribute
    * @param content will be enclosed in single quotes in html output. Must be double quoted only!
    */
function addBodyAttributes($attribute,$content)
    {
        $a = strtolower($attribute);
        if(isset($this->bodyAttributes[$a]))
            $this->bodyAttributes[$a] .= $content;
        else
            $this->bodyAttributes[$a] = $content;
    }
    
    /**
    * @return body attributes HTML code.
    */
function getBodyAttributes()
    {
        $ret='';
        foreach($this->bodyAttributes as $k => $v)
            $ret .= "$k=\"$v\"";
            
        return $ret;
    }
    
    /**
    * add additional page specific header HTML sections. Mostly used for unique page specific CSS. Output in headerCreate().
    * Example: $this->addHeaderSection('<style type="text/css"> td.mytd { background-color: red; } </style>');
    * @param string $html e.g. a meta or style HTML section
    * @param boolean $post if FALSE adds the section BEFORE standard CSS and libraries! Default: TRUE (after) to allow overriding of CSS etc.
    */
function addHeaderSection($html,$post=TRUE)
    {
        if($post)
            $this->postHeaderSections[] = $html;
        else
            $this->preHeaderSections[] = $html;
    }
    
    /**
    * @param boolean $post if FALSE retrieves the section BEFORE standard CSS and libraries! Default: TRUE (after) to allow overriding of CSS etc.
    * @return body attributes HTML code.
    */
function getHeaderSections($post=TRUE)
    {
        $sec = $post ? $this->postHeaderSections : $this->preHeaderSections;
        $ret="\n";
        foreach($sec as $h)
            $ret .= ' ' . $h . "\n";
            
        return $ret;
    }
    
    /**
    * Creates header (html, head, body, errorDiv). Called by create().
    * @return string html of page
    */
function headerCreate()
    {
        return $this->delegate->headerCreate($this);
    }
    
    /**
    * creates the html of the whole page, localizes the HTML
    * @return string html of page
    * @uses innerCreate() used to create the actual content of the page
    * @global string additional footer info of page
    */
function create()
    {
        $l = Localizer::getSingleton();
        return $l->translateHTML($this->delegate->create($this));
    }
    
    /**
    * Creates footer (called by create())
    * @return string html of page
    */
function footerCreate()
    {
        if($this->showFooter == FALSE)
            return '';
        
        return $this->delegate->footerCreate($this);
    }
     
    /**
    * this function returns the actual content of a page
    * 
    * this function is overloaded by superclasses of page to return
    * the content they need
    * @return string html-content of page
    */
function innerCreate() {}
        
    /**
    * This function returns a HTML section witha '?' link. Pressing the link inserts context help. The help text comes from the language files: Page-ContextHelp-$type
    * @param string $type a label for the help.
    * @return string HTML
    */
function contextHelp($type)
    {
        static $htmlId=0;
        return '<div class="context-help" id="contextHelp'.$htmlId.'"><a href="#" onclick="Element.hide(\'contextHelp'.$htmlId.'\'); Effect.Appear(\'contextHelpText'.$htmlId.'\',{duration:1.2}); return false;">?</a></div><div class="context-help-on" id="contextHelpText'.($htmlId++).'" style="display: none;">
        <div>Page-ContextHelp-'.$type.'</div></div>';  
    }
}

?>
