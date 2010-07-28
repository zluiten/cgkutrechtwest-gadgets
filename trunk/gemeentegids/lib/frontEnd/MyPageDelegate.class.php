<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

require_once('PageDelegate.class.php');

class MyPageDelegate extends PageDelegate {


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
/*
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

        $cont .= '<script type="text/javascript">';
		$cont .= 'function onLoad() {';
		$cont .= 'var height = Math.max( document.body.offsetHeight, document.body.scrollHeight );';
		$cont .= 'parent.resizeFrame();';
		$cont .= '}';

		$cont .= 'window.onload = onLoad;';
        
        $cont .= '</script>';
*/

        $cont .= '</div></body></html>';
        return $cont;
    }

}

?>
