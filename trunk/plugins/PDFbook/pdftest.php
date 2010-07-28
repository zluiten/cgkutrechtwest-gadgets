<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  THE ADDRESS BOOK RELOADED 3.0 Plugin Module
 *
 *  PDF EXAMPLE retrieve data from DB and display as string in HTML
 *  Tryout: 
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

    // this WILL corrupt PDF if there are errors!
    error_reporting(E_ALL);

    /* This section fetches the contact data from each contact and returns it as string */
function createLinesFromContact(&$c) 
    {
        global $addressFormatter;
       
        // alle Gruppen sammeln
        $val = $c->getValueGroup('groups');
        $groups = '';
        if (count($val) > 0) {
            foreach ($val as $v)
                $groups .= $v['groupname'] . ', ';
               
            $groups = substr($groups,0,-2);       
        }
        
        // alle Extra Telefonnummern sammeln
        $phones = '';
        $val = $c->getValueGroup('phone');
        if (count($val) > 0) {
            foreach ($val as $v)
                $phones .= $v['label'].': '. $v['value'] . ', ';
               
            $phones = substr($phones,0,-2);       
        }    
    
        // Telefonnummern der Adressen sammeln
        $val = $c->getValueGroup('addresses');
        $p2 = '';
        if (count($val) > 0) {
            foreach ($val as $v)
                $p2 .= $v['phone1'] . ', ';
               
            $p2 = substr($p2,0,-2);       
        }    
        
        // one line one string, alternatively one could return an array here
        return $c->contact['lastname'] . ', ' . $c->contact['firstname'] . ' ' . $c->contact['middlename'] . ' ' . $groups . ' ' . $phones;
    }

// DISABLE TO create PDF output, enable to produce HTML output 
if(TRUE)
{
    if (!isset($_GET['group']))
        $_GET['group'] = '';
    
    $list = new GroupContactList(StringHelper::cleanGPC($_GET['group']));
    $conts = $list->getContacts();
    foreach ($conts as $c)
        echo '<br>'.createLinesFromContact($c);
        
    exit(1);
}   
    
// Frontpage title 
$frontpage_title = empty($CONFIG_PDFBOOK_TITLE) ? "Addressbook..." : $CONFIG_PDFBOOK_TITLE;
$your_domain = empty($CONFIG_PDFBOOK_LINE) ? 'http://portal.example.com/contacts'  : $CONFIG_PDFBOOK_LINE;
$footer = 'This addressbook was generated from sourcedata at '. $your_domain ;

//DEFAULT 10,10,19
$CONFIG_PDFBOOK_LINE_HEIGHT=10;
$CONFIG_PDFBOOK_FONT_SIZE=10;

// ** SET CHARSET FOR mbstring
mb_internal_encoding('utf8');
mb_http_output('iso-8859-1');

header('Cache-Control: must-revalidate, post-check=0, pre-check=0', true);
header('Pragma: none', true);

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

function dots($info){
  // draw a dotted line over to the right and put on a page number
  $tmp = $info['p'];
  $lvl = $tmp[0];
  $lbl = mb_substr($tmp,1);
  $xpos = 520;

  switch($lvl){
    case '1':
      $size=16;
      $thick=1;
      break;
    case '2':
      $size=12;
      $thick=0.5;
      break;
  }

  $this->saveState();
  $this->setLineStyle($thick,'round','',array(0,10));
  $this->line($xpos,$info['y'],$info['x']+5,$info['y']);
  $this->restoreState();
  $this->addText($xpos+5,$info['y'],$size,$lbl);


}


}

class PDFBookGen extends Creport {
    
/////////////////////////////////////////////////////////////////////////////
//
// function frontpage_title
//  
//
// This function does the actual rendering of of the title on the front of the PDF
//
/////////////////////////////////////////////////////////////////////////////

    var $counter = 0;
    var $leftMargin=20;
    var $y1=822;   // some kind of page height offset??

    var $xspace=279;   // width of a card
    var $yspace=195.5; // height of a card
    
function PDFBookGen($p,$o){
        $this->Creport($p,$o);
    }
    
function frontpage_title($frontpage_title,$your_domain,$x,$y,$height,$wl=0,$wr=0){
      $this->saveState();
      $h=100;
      $factor = $height/$h;
      $this->selectFont('./lib/pdf/fonts/Times-BoldItalic.afm');
      $text = $frontpage_title;
      $ts=100*$factor;
      $th = $this->getFontHeight($ts);
      $td = $this->getFontDecender($ts);
      $tw = $this->getTextWidth($ts,$text);
      $this->setStrokeColor(0.6,0,0);
      $z = 0.86;
    
      $this->setLineStyle(150);
      $this->ellipse($x-$wl,$y-$z*$h*$factor,75);
      $this->setLineStyle(120);
      $this->ellipse($x-$wl+120,($y-$z*$h*$factor)+70,60);
      $this->setLineStyle(80);
      $this->ellipse($x-$wl+220,($y-$z*$h*$factor)-50,40);
      $this->setLineStyle(60);
      $this->ellipse($x-$wl+260,($y-$z*$h*$factor)+30,30);
    
      $this->setLineStyle(70);
      $this->ellipse($x-$wl+300,($y-$z*$h*$factor)-20,35);
      $this->setLineStyle(80);
      $this->ellipse($x-$wl+350,($y-$z*$h*$factor)+60,30);
    
      $this->setLineStyle(70);
      $this->ellipse($x-$wl+400,($y-$z*$h*$factor)-20,35);
      $this->setLineStyle(60);
      $this->ellipse($x-$wl+440,($y-$z*$h*$factor)+10,30);
    
      $this->setLineStyle(50);
      $this->ellipse($x-$wl+490,($y-$z*$h*$factor)-5,25);
      $this->setLineStyle(40);
      $this->ellipse($x-$wl+560,($y-$z*$h*$factor)+10,20);
    
    
      //$pdf->filledRectangle($x-$wl,$y-$z*$h*$factor,$tw*1.2+$wr+$wl,$h*$factor*$z);
      $this->setColor(1,1,1);
      $this->addText($x,$y-$th-$td,$ts,$text);
      $this->setStrokeColor(1,1,1);
      $this->setLineStyle(15,'round');
      $this->line(-25,$y-$th-$td-12,280,$y-$th-$td-12);
      $this->setColor(0.6,0,0);
      $this->addText($x - 5,$y-$th-$td-14,$ts*0.2,$your_domain);
      $this->restoreState();
      return $height;
    }
    
/////////////////////////////////////////////////////////////////////////////
//
//  function renderAddress
//  
//
// This function does the actual rendering of a block for one contact in the PDF
//
/////////////////////////////////////////////////////////////////////////////


function renderAddress($linespacing,$fontsize,$lines,&$image) {
        
      global $options, $CONFIG_PDFBOOK_MAX_LINES;
      
      if ($this->counter == 8) {
        $this->y1=822;
        $this->ezNewPage();
        $this->counter=0;
      }
      
      $this->counter++;
      
      $this->saveState();

      // render the name
      if ($this->counter % 2) { // left and right column
        $this->clipRectangle($this->leftMargin,$this->y1-$this->yspace,$this->xspace-2,$this->yspace-3);
        $this->rectangle($this->leftMargin,$this->y1-$this->yspace,$this->xspace-2,$this->yspace-3);
        $this->filledRectangle($this->leftMargin,$this->y1-$this->yspace,12,$this->yspace-3);
        $this->setColor(1,1,1);
        $this->addText($this->leftMargin+10,$this->y1-$this->yspace+3,12,utf8_decode($lines[0]),-90);
        //$this->addTextWrap($this->leftMargin+10,$this->y1-$this->yspace+3,$this->xspace-2,12,utf8_decode($lines[0]),-90); // returns wrapped text; discarded
        $this->setColor(0,0,0);
        $startx = $this->leftMargin+17+75;
        $starty = $this->y1-10;
      } else {
        $this->clipRectangle($this->leftMargin+$this->xspace+2,$this->y1-$this->yspace,$this->xspace-2,$this->yspace-3);
        $this->rectangle($this->leftMargin+$this->xspace+2,$this->y1-$this->yspace,$this->xspace-2,$this->yspace-3);
        $this->filledRectangle($this->leftMargin+$this->xspace+2,$this->y1-$this->yspace,12,$this->yspace-3);
        $this->setColor(1,1,1);
        $this->addText($this->leftMargin+$this->xspace+2+10,$this->y1-$this->yspace+3,12,utf8_decode($lines[0]),-90);
        //$this->addTextWrap($this->leftMargin+$this->xspace+2+10,$this->y1-$this->yspace+3,$this->xspace-2,12,utf8_decode($lines[0]),-90); // returns wrapped text; discarded
        $this->setColor(0,0,0);
        $startx = $this->leftMargin+$this->xspace+2+17+75;
        $starty = $this->y1-10;
      }
            
      // render the image
      $size = $image->getSize();
      if($size)
        $h = 70 * $size['height'] / $size['width'];
      else
        $h = 70;
      
      switch ($image->getType()) {
          case 'data':
             $tmp = $image->getData(); // cannot be passed directly, because php4 requires by-ref vars to be real vars
             $this->addJpegImage_common($tmp,$startx-73,$starty-$h-3,70,$h,$size['width'],$size['height']);
             break;
          case 'file':
             $this->addJpegFromFile($image->getData(),$startx-73,$starty-$h-3,70,$h);
             break;
          default:
             $h = 0;
      }
            
      // render the cards content lines (emails, address etc.) clip path set above
      $currentY = $starty-$linespacing;
      for ($i=1;$i<count($lines);$i++)
      {
          if(!empty($lines[$i])) // empty line -> lower height
          {
            $this->addText($startx-($currentY <= $starty-$h-$linespacing ? 73 : 0),$currentY,$fontsize,utf8_decode($lines[$i]));
            $currentY -= $linespacing;
          }
          else
            $currentY -= $linespacing/3;
      }
      // wrapping solution
      //for ($i=1;$i<min(count($lines),$CONFIG_PDFBOOK_MAX_LINES);$i++) // we have at most x lines of space!!
      //  $this->addTextWrap($startx-(($i-1) * $linespacing >= $h + 3 || $h == 0?73:0),$starty-$i*$linespacing,$this->xspace-17,$fontsize,utf8_decode($lines[$i]));
    
      $this->restoreState();
      
      if (!($this->counter % 2)) {
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

// I am in OZ, so will design my page for A4 paper.. but don't get me started on that.
// (defaults to legal)
// this code has been modified to use ezpdf.

//$pdf = new Cezpdf('a4','portrait');
$pdf = new PDFBookGen('a4','portrait');

$pdf -> ezSetMargins(50,70,50,50);

// put a line top and bottom on all the pages
$all = $pdf->openObject();
$pdf->saveState();
$pdf->setStrokeColor(0,0,0,1);
$pdf->line(20,37,578,37);
$pdf->line(20,822,578,822);
$pdf->addText(20,31,6, $footer . '   (' . $cur_date . ')' );
$pdf->restoreState();
$pdf->closeObject();
// note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
// or 'even'.
$pdf->addObject($all,'all');

$pdf->ezSetDy(-100);

$mainFont = 'lib/pdf/fonts/Helvetica.afm';
//$mainFont = $path_prefix . '/fonts/Times-Roman.afm';
$codeFont = 'lib/pdf/fonts/Courier.afm';
// select a font
$pdf->selectFont($mainFont);

//$pdf->ezText("<b>Addressbook</b>\n",30,array('justification'=>'centre'));
//$pdf->ezText("<i>the source for up-to-date address info</i>\n",20,array('justification'=>'centre'));
//$pdf->ezText("\n<c:alink:http://sourceforge.net/projects/pdf-php>http://sourceforge.net/projects/pdf-php</c:alink>\n\nversion 0.09",18,array('justification'=>'centre'));

$pdf->ezSetDy(-100);
// modified to use the local file if it can

$pdf->openHere('Fit');

//Render the frontpage title
$pdf->frontpage_title($frontpage_title,$your_domain,50,$pdf->y-100,80,80,200);
$pdf->selectFont($mainFont);

$pdf->ezNewPage();

$pdf->ezStartPageNumbers(560,25,10,'','',1);

$size=10;
$height = $pdf->getFontHeight($size);
$textOptions = array('justification'=>'left');
$collecting=0;
$code='';
$counter=0; //used to count 8 to a page

if (!isset($_GET['group']))
    $_GET['group'] = '';

$list = new GroupContactList(StringHelper::cleanGPC($_GET['group']));

$conts = $list->getContacts();

foreach ($conts as $c)
    $pdf->renderAddress($CONFIG_PDFBOOK_LINE_HEIGHT,$CONFIG_PDFBOOK_FONT_SIZE,createLinesFromContact($c),new ContactImage($c));
    

$pdf->ezStopPageNumbers(1,1);



// Debug section...............................................................................................

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