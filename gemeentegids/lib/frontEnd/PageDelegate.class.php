<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* contains class {@link Page}
* @author Tobias Schlatter
* @package frontEnd
*/

/**
*
* This class is used create everything that should be displayed
* on every page of tab (e.g. html head, styles, footer).
* Implements all functionality of Page and is overrideable.
* @package frontEnd
*/

class PageDelegate {

    /**
    * @var string saves the title of the page
    */
    var $title;

    /**
    * @var boolean is the page cachable? Default: FALSE
    */
    var $cachable;

    /**
* @var boolean allow robots to scan teh page? Default: FALSE
    */
    var $robots;

    /**
    * Constructor
    *
    * initializes {@link $title}
    * @param string $title the title of the page, normally something is added before this title
    * @param boolean $cachable whether the page disables browser caching with Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0 and Pragma: no-cache headers (default: false)
    * @param boolean $robots allow robots (meta tag) (default: false)
    */
function PageDelegate($title,$cachable=FALSE,$robots=FALSE) {
        $this->title = $title;
        $this->cachable = $cachable;
        $this->robots = $robots;

        // Ajax may not use the headerCreate() function!!
        header('Content-Type: text/html; charset=UTF-8',true);
    }

    /**
    * creates the html, head, body, errorDiv
    * @return string html of page
    */
function headerCreate($sender)
    {
        global $CONFIG_TAB_SERVER_ROOT, $CONFIG_INSTALL_SUBDIR, $CONFIG_HEADER_HTML, $CONFIG_ADD_HEAD, $errorHandler;

        // see constructor: header('Content-Type: text/html; charset=UTF-8',true);

        $cont = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0//EN' --'http://www.w3.org/TR/REC-html40/strict.dtd'-->\n<html>\n<head>\n <title>";
        $cont .= $this->title;
        $cont .= "</title>\n <link rel='stylesheet' href='" . $this->getStyleFile() . "' type='text/css'>\n";

        if($this->cachable)
        {
            header('Cache-Control: public', true);
            header("Pragma: cache");
        }
        else
        {
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
            header("Pragma: no-cache");
            $cont .= '<meta http-equiv="cache-control" content="no-cache"><meta http-equiv="pragma" content="no-cache"><meta http-equiv="expires" content="-1">';
        }

        if($this->robots == FALSE)
            $cont .= '<meta name="robots" content="noindex, nofollow">';

        $cont .= <<<EOC
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <script type="text/javascript"> var InternetExplorer = false; </script>
 <!--[if IE]> <script type="text/javascript"> InternetExplorer = true; </script> <![endif]-->
EOC;
        // Insert headers from Page::addHeaderSection() method
        $cont .= $sender->getHeaderSections(FALSE);

        if(file_exists($CONFIG_INSTALL_SUBDIR . 'lib/js/all.pre.js')) // precompiled
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/all.pre.js"></script>'."\n";
        else
        {
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/scriptaculous/prototype.js"></script>'."\n";
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/scriptaculous/scriptaculous.js"></script>'."\n";
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/general.js"></script>'."\n";
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/iexHover.js"></script>'."\n";
            //$cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/sorttable.js"></script>'."\n";
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/tabber.js"></script>'."\n";
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/lytebox.js"></script>'."\n";
            $cont .= ' <script type="text/javascript" src="' . $CONFIG_TAB_SERVER_ROOT . 'lib/js/widgEditor.js"></script>'."\n";
        }

        // traditional config method
        $cont .= $CONFIG_ADD_HEAD."\n";

        // Insert headers from Page::addHeaderSection() method
        $cont .= $sender->getHeaderSections(TRUE);

        // Insert body attributes from Page::addBodyAttribute() method
        $cont .= "</head>\n<body ".$sender->getBodyAttributes().">\n";

        // Add a banner etc
        $cont .= $CONFIG_HEADER_HTML;

        $cont .= $errorHandler->errorDIVs();

        return $cont;
    }

    /**
    * creates the html of the whole page
    * @return string html of page
    * @uses headerCreate() used to create the actual content of the page
    * @uses innerCreate() used to create the actual content of the page
    * @uses footerCreate() used to create the actual content of the page
    */
function create($sender) {

        $cont = $sender->innerCreate(); // inner create could add styles with Page::addHeaderSection();
        $cont = $sender->headerCreate() . $cont;
        $cont .= $sender->footerCreate();

        return $cont;
    }

    /**
    * returns the path to the style file, according to {@link $CONFIG_STYLE}
    *
    * this method checks, whether the requested style is available or not,
    * if it is available it returns the path to that style, otherwise it returns
    * the path to the default style
    * @global Options admin options
    * @global string absolute server path (not filesystem) to project
    * @return string path to css file
    * @static
    */
function footerCreate()
    {
        global $CONFIG_FOOTER_HTML,$CONFIG_TAB_SERVER_ROOT;

        $cont  = '        <div id="footer"><p id="f_version">The Address Book Reloaded version: ' . VERSION_NO .
                " | <a href='" . URL_HOMEPAGE . "' target='_blank'>homepage</a> | <a href='" . URL_SOURCEFORGE .
                "' target='_blank'>sourceforge</a></p>";
        $cont .= "        <p id='f_copy'>&copy; 2006-2007 Tobias Schlatter, Thomas Katzlberger. All rights reserved.</p>";
        global $PAGE_TIMER;
        $time = isset($PAGE_TIMER) ? substr($PAGE_TIMER->stop(),0,5) : '?';
        $cont .= "        <p id='f_gnu'>This application is distributed under the <a href='".$CONFIG_TAB_SERVER_ROOT."lib/gpl.html'>GNU General Public Licence</a>.</p>";
        $cont .= "        <p class='f_extra'>This application includes the following <a href='http://en.wikipedia.org/wiki/MIT_License'>MIT Licenced</a> frameworks: <a href='http://prototypejs.org/'>Prototype</a>, <a href='http://script.aculo.us/'>Scriptaculous</a>, <a href='http://kryogenix.org/code/browser/sorttable/'>Sorttable</a>, <a href='http://www.barelyfitz.com/projects/tabber/'>Tabber</a></p>\n";
        $cont .= "        <p class='f_extra'>Page creation time: $time seconds.</p>\n";
        $cont .= "        <p class='f_extra'>" . $CONFIG_FOOTER_HTML . "</p>\n";

        $cont .= '</div></body></html>';

        return $cont;
    }

    /**
    * returns the path to the style file, according to {@link $CONFIG_STYLE}
    *
    * this method checks, whether the requested style is available or not,
    * if it is available it returns the path to that style, otherwise it returns
    * the path to the default style
    * @global Options $options admin options
    * @global string $CONFIG_INSTALL_SUBDIR absolute filesystem path
    * @global string $CONFIG_TAB_SERVER_ROOT absolute server path (not filesystem)
    * @return string relative URL path to css file
    * @static
    */
function getStyleFile() {
        global $options, $CONFIG_TAB_SERVER_ROOT, $CONFIG_INSTALL_SUBDIR;

        if(is_object($options)) // admin/upgrade.php does not have options
        {
            $css = $options->getOption('interfaceStyle');

            // Precompiled file?
            if(file_exists($CONFIG_INSTALL_SUBDIR . 'styles/' . $css .'/'. $css .'.pre.css'))
                return $CONFIG_TAB_SERVER_ROOT . 'styles/' . $css .'/'. $css .'.pre.css';

            // Normal file?
            if(file_exists($CONFIG_INSTALL_SUBDIR . 'styles/' . $css . '.css'))
                return $CONFIG_TAB_SERVER_ROOT . 'styles/' . $css . '.css';
        }

        // default file
        return $CONFIG_TAB_SERVER_ROOT . 'styles/default.css';
    }
}

?>
