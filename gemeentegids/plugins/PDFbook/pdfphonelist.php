<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 * contains class {@link PDFLabelGenerator}
 * @author Wicher Minnaard, jesper at krusedulle dot net
 * @package plugins
 * @subpackage PDFBook
 *
 *  THE ADDRESS BOOK RELOADED - Label Generator for PDF-plugin module
 *  
 *  Generates a PDF of all address book entries, suitable for printing addressing labels
 *  on Avery No. L7160 sheets (3x7 stickers).
 *
 *  GET PARAMETERS:
 *   paper     ... ID/Part number of label paper (determines a4/letter and portrait/landscape)
 *   DEFAULT   ... pdflabels.php?paper=L7160
 *
 *  AddressFormatter.class.php is responsible for formatting the
 *  address according to each country's postal standards
 *
 *  original code for a PDF booklet by: jesper at krusedulle.net
 *  modified for Avery labels by:        Wicher Minnaard - wicher at glaznost.nl
 *
 *  Requires PDF classes class.ezpdf.php
 *
 *    // ie. thisfile.php?group_id=6&page=2&letter=c&limit=20
 *  adding ?d=1 to the url calling this will cause the pdf code itself to be echoed to the
 *  browser, this is quite useful for debugging purposes.
 *
 *************************************************************/

chdir('../../');

require_once('lib/init.php');

if(!@include_once('plugins/PDFbook/config.php'))
    require_once('plugins/PDFbook/config.template.php');

require_once('GroupContactList.class.php');
require_once('StringHelper.class.php');
require_once('AddressFormatter.class.php');
require_once('lib/pdf/class.ezpdf.php');
require_once('ContactImage.class.php');

$cur_date = date("l, d F Y h:i a",time());

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAtLeast('guest'))
    $errorHandler->standardError('NOT_LOGGED_IN',basename($_SERVER['SCRIPT_NAME']));

// Frontpage title
//$frontpage_title = empty($CONFIG_PDFBOOK_TITLE) ? "Addressbook..." : $CONFIG_PDFBOOK_TITLE;
//$your_domain = empty($CONFIG_PDFBOOK_LINE) ? 'http://portal.example.com/contacts'  : $CONFIG_PDFBOOK_LINE;
//$footer = 'This addressbook was generated from sourcedata at '. $your_domain ;

// ** SET CHARSET FOR mbstring
mb_internal_encoding('utf8');
mb_http_output('iso-8859-1');

header('Cache-Control: must-revalidate, post-check=0, pre-check=0', true);
header('Pragma: none', true);

// don't want any warnings turning up in the pdf code if the server is set to 'anal' mode.
//error_reporting(7);
error_reporting(E_ALL);

// function to split long (label-bleeding) lines into an array of shorter lines
// With courier.afm monospace font, line length can be 28 characters
function split2array($text){
    return explode('%%newline%%', wordwrap($text,28,'%%newline%%'));
}

if (!function_exists('createLinesFromContact')) {
function createLinesFromContact(&$c) {

        global $addressFormatter;

        $l = array();

        $l = split2array($c->contact['lastname'] . ', ' . $c->contact['firstname'] . ' ' . $c->contact['middlename']);
        // line to separate namefields from addressfields
        array_push($l,"----------------------------");

        $val = $c->getValueGroup('addresses');
        foreach ($val as $v)
            if ($v['refid'] == $c->contact['primaryAddress']) {
                $data = $v;
                break;
            }

        if (!isset($data) || !$data)
            if (isset($val[0]))
                $data = $val[0];

        if (isset($data) && $data) {

            $d = $addressFormatter->formatAddress($data,'%%newline%%');
            $addresslines = array();
            $addresslines = explode('%%newline%%',$d);
            // we now have addresslines, some of which will be too long, so we insert linebreaks
            $addresswrappedlines = array();
            for ($i=0;$i<count($addresslines);$i++){
                $addresswrappedlines = split2array($addresslines[$i]);
                for ($j=0;$j<count($addresswrappedlines);$j++){
                    array_push($l,$addresswrappedlines[$j]);
                }
                // pretty line between address fields
                array_push($l,"----------------------------");
            }

            $l[] = '';

        }
        return $l;

    }
}

// define a clas extension to allow the use of a callback to get the table of contents, and to put the dots in the toc
class Creport extends Cezpdf {

var $reportContents = array();



function Creport($p,$o){
  $this->Cezpdf($p,$o);
}

function rf($info){
  // this callback records all of the table of contents entries, it also places a destination marker there
  // so that it can be linked too
  $tmp = $info['p'];
  $lvl = $tmp[0];
  $lbl = rawurldecode(mb_substr($tmp,1));
  $num=$this->ezWhatPageNumber($this->ezGetCurrentPageNumber());
  $this->reportContents[] = array($lbl,$num,$lvl );
  $this->addDestination('toc'.(count($this->reportContents)-1),'FitH',$info['y']+$info['height']);
}

}

class PDFLabelGenerator extends Creport {

/////////////////////////////////////////////////////////////////////////////
//
// function frontpage_title
//
//
// This function does the actual rendering of of the title on the front of the PDF
//
/////////////////////////////////////////////////////////////////////////////

    var $counter = 0;
    // Top-left position on the A4
    var $x1=20;
    var $y1=800;
    // Width of label
    var $xspace=180;
    // Height of label
    var $yspace=108;
    // Space between stickers - x-axis
    var $interx=8;
    
    /**
    * Creates a PDF containing mailing labels
    *
    * @param string $paperFormat Output format of paper (e.g. 'a4' or 'letter')
    * @param string $alignment 'landscape' or 'portrait'
    * @param string $formatID For Avery L7160 paper use 'L7160'
    * @uses Creport
    */
function PDFLabelGenerator($formatID){
        
        switch($formatID)
        {
            default: // do nothing
            case 'L7160':
                $paperFormat = 'a4';
                $alignment = 'portrait';
                $this->labelsPerPage = 21;
                $this->columnsPerPage = 3;
                // Top-left position on the paper
                $this->x1=20;
                $this->y1=800;
                // Width of label
                $this->xspace=180;
                // Height of label
                $this->yspace=108;
                // Space between stickers - x-axis
                $this->interx=8;
        }

        $this->Creport($paperFormat,$alignment);
    }

/////////////////////////////////////////////////////////////////////////////
//
//  function renderAddress
//
//
// This function does the actual rendering of a block for one contact in the PDF
//
/////////////////////////////////////////////////////////////////////////////


function renderAddress($linespacing,$fontsize,$lines) {

      global $options;

    // 21 labels on a page
      if ($this->counter == $this->labelsPerPage) {
        $this->y1=800;
        $this->ezNewPage();
        $this->counter=0;
      }
    
      $this->setColor(0,0,0);
      
      $this->counter++;
      if ($this->counter % $this->columnsPerPage) {
          // not divisible by 3, so we're talking column 1 or 2
          if (($this->counter % $this->columnsPerPage) == 1) {          
              // divisible by 2 nor 3, so we're talking column 1
              // rectangle code for testing (outlining) purposes
            //$this->rectangle($this->x1,$this->y1-$this->yspace,$this->xspace,$this->yspace);
            $startx = $this->x1+80;
            $starty = $this->y1-15;
          } else {
              // no? must be 2nd column then
            // rectangle code for testing (outlining) purposes
            //$this->rectangle(8+$this->x1+$this->xspace,$this->y1-$this->yspace,$this->xspace,$this->yspace);
            $startx = $this->interx+$this->x1+$this->xspace+80;
            $starty = $this->y1-15;
          }
      } else {
          // divisible by 3, so must be 3rd column
          // rectangle code for testing (outlining) purposes
        //$this->rectangle(16+$this->x1+$this->xspace+$this->xspace,$this->y1-$this->yspace,$this->xspace,$this->yspace);
        $startx = $this->interx+$this->interx+$this->x1+$this->xspace+$this->xspace+80;
        $starty = $this->y1-15;
      }


      $this->saveState();

      $i=0;
      $h=0;

      for ($i=0;$i<count($lines);$i++){
        $this->addText($startx-(($i-1) * $linespacing >= $h + 3 || $h == 0?73:0),$starty-$i*$linespacing,$fontsize,utf8_decode($lines[$i]));
      }
      $this->restoreState();

      // if we've just rendered a sticker in the 3rd column, move down one row
      if (!($this->counter % $this->columnsPerPage)) {
        $this->y1 = $this->y1-$this->yspace;
      }

    } //END renderAddress()

}




/////////////////////////////////////////////////////////////////////////////
//
// Main application
//
//
// This code gets the data and calls the functions above to get it rendered in the PDF
//
/////////////////////////////////////////////////////////////////////////////

$pdf = new PDFLabelGenerator(empty($_GET['paper']) ? '' : $_GET['paper']);
$pdf->ezSetMargins(50,70,50,50);
$pdf->ezSetDy(-100);

$mainFont = 'lib/pdf/fonts/Helvetica.afm';
$codeFont = 'lib/pdf/fonts/Courier.afm';
// select a font
$pdf->selectFont($mainFont);

$pdf->ezSetDy(-100);
// modified to use the local file if it can

$pdf->openHere('Fit');

// we use a monospace font for printing labels
$pdf->selectFont($codeFont);

$size=10;
$height = $pdf->getFontHeight($size);
$textOptions = array('justification'=>'left');
$collecting=0;
$code='';

if (!isset($_GET['group']))
    $_GET['group'] = '';

$list = new GroupContactList(StringHelper::cleanGPC($_GET['group']));

$conts = $list->getContacts();

foreach ($conts as $c)
        $pdf->renderAddress(10,10,createLinesFromContact($c));

// Debug section
// adding ?d=1 to the url calling this will cause the pdf code itself to ve echoed to the
// browser, this is quite useful for debugging purposes.

if (!empty($_GET['d'])){
  $pdfcode = $pdf->ezOutput(1);
  $pdfcode = str_replace("\n","\n<br>",htmlspecialchars($pdfcode));
  echo '<html><body>';
  echo trim($pdfcode);
  echo '</body></html>';
} else {
  $pdf->ezStream();
}
?>