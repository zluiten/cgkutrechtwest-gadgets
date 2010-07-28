<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageSearchList}
* @author Thomas Katzlberger
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('DB.class.php');
require_once('Page.class.php');
require_once('PageList.class.php');
require_once('PageContact.class.php');
require_once('TableGenerator.class.php');
require_once('EmailHelper.class.php');

/**
* the search list page
* 
* the search list page allows searches similar to the autocomplete function.
* The page usually displays the full search result, although it can limit the result (without navigation)
* @package frontEnd
* @subpackage pages
*/
class PageProjectSearchList extends Page {
    
    /**
    * @var ContactList list of contacts to display
    */
    var $contactList;
        
    /**
    * @var boolean whether to expand contact entries or not
    */
    var $expand;

    /**
    * @var string 'export' for CSV export, anything else will just display normally
    */
    var $type;

    /**
    * @var Navigation basic nav menu: return (to list) and expand
    */
    var $nav;

    /**
    * Constructor: ONLY TO BE CALLED by factory method of Page::newPage(...)! 
    *
    * init {@link $contactList}, and menu
    * @param string $search DUMMY
    * @param string $searchtype $_GET['type'] may be 'export' for CSV export of the result
    * @param boolean $expand whether to expand entries or not
    * @param integer $maxEntriesPerPage limit of entries (default = 0 which means unlimited) 
    */
function PageProjectSearchList($search,$searchtype,$expand=false,$maxEntriesPerPage=0)
    {
        
        $this->Page('Search List');
        
        $this->expand = $expand;
        $this->type = $searchtype;
        
        $this->nav = new Navigation('options-menu');
        //$this->nav->addEntry('expand','expand','../contact/searchlist.php?search=' . $search .
        //        '&amp;type=' . $searchtype . '&amp;expand=1');
        $this->nav->addEntry('return','return',Navigation::mainPageUrl());
        
        $this->contactList = new ContactList($this->createQuery());
        $this->contactList->setEntriesPerPage($maxEntriesPerPage);
    }

    /**
    * Create search query
    *
    * init {@link $contactList}, and menu
    * @param search $search partial string to match
    * @param searchtype $searchtype [name|email|www|chat|phone|custom_?] Custom searches defined in config.php, shared with autocomplete.
    * @global array custom searchtypes defined in config.php
    * @global DB used for database access
    */
function createQuery()
    {
        // create an empty default result - any better way to do this
        $sql="SELECT * FROM " . TABLE_CONTACT . " AS contact WHERE id=-1";
        
        $db = DB::getSingleton();
        
        $admin = intval($_SESSION['user']->isAtLeast('admin'));
        
        $post = StringHelper::cleanGPC($_POST);
        
        // projects
        $props  = array();
        $tbls  = array();
        if(!empty($_POST['p-category'])) { $tbls[] = TABLE_PROPERTIES . ' AS p1'; $props[] = 'c.id=p1.id AND p1.type="other" AND (p1.visibility = "visible" AND p1.label="Project Category" AND p1.value LIKE BINARY "%' . substr($db->escape($post['p-category']),1,-1) .'%" )'; }
        if(!empty($_POST['p-role'])    ) { $tbls[] = TABLE_PROPERTIES . ' AS p2'; $props[] = 'c.id=p2.id AND p2.type="other" AND (p2.visibility = "visible" AND p2.label="Contract Role" AND    p2.value=' . $db->escape($post['p-role'] ) .')';                                  }
        if(!empty($_POST['p-company']) ) { $tbls[] = TABLE_PROPERTIES . ' AS p3'; $props[] = 'c.id=p3.id AND p3.type="other" AND (p3.visibility = "visible" AND p3.label="Applicant" AND        p3.value LIKE "%' . substr($db->escape($post['p-company']),1,-1) .'%" )';         }
        if(!empty($_POST['p-value'])   ) { $tbls[] = TABLE_PROPERTIES . ' AS p4'; $props[] = 'c.id=p4.id AND p4.type="other" AND (p4.visibility = "visible" AND p4.label="SWARCO Value" AND     p4.value > ' . $db->escape($post['p-value'] ) .')';                               }
        if(!empty($_POST['p-after'])   ) { $tbls[] = TABLE_DATES . ' AS d'; $props[] = 'c.id=d.id AND (d.label="Completed" AND         d.value1 > ' . $db->escape($post['p-after'] ) .')';                                                                                        }
        
        $propsel = implode(' AND ',$props);
        if(!empty($propsel))
        {
            $tables = implode(', ',$tbls);
            $sel = "SELECT DISTINCT c.* FROM " . TABLE_CONTACT . " AS c, $tables WHERE ";
            $where = "c.xsltDisplayType='project' AND c.hidden=0 AND $propsel ORDER BY lastname";
            $sql = "$sel $where";
                        
            //echo $sql;
            return $sql;
        }
        
        // project opportunity
        $props  = array();
        $tbls  = array();
        if(!empty($_POST['o-category']))  { $tbls[] = TABLE_PROPERTIES . ' AS p1'; $props[] = 'c.id=p1.id AND p1.type="other" AND (p1.label="Project Category" AND p1.value LIKE BINARY "%' . substr($db->escape($post['o-category']),1,-1) .'%" )'; }
        if(!empty($_POST['o-role'])    )  { $tbls[] = TABLE_PROPERTIES . ' AS p2'; $props[] = 'c.id=p2.id AND p2.type="other" AND (p2.label="Contract Role" AND    p2.value=' . $db->escape($post['o-role']  ) .')';                                 }
        if(!empty($_POST['o-company']) )  { $tbls[] = TABLE_PROPERTIES . ' AS p3'; $props[] = 'c.id=p3.id AND p3.type="other" AND (p3.label="Applicant" AND        p3.value LIKE "%' . substr($db->escape($post['o-company']),1,-1) .'%" )';         }
        if(!empty($_POST['o-value'])   )  { $tbls[] = TABLE_PROPERTIES . ' AS p4'; $props[] = 'c.id=p4.id AND p4.type="other" AND (p4.label="SWARCO Value" AND     p4.value > ' . $db->escape($post['o-value']     ) .')';                           }
        
        $propsel = implode(' AND ',$props);
        if(!empty($propsel))
        {
            $tables = implode(', ',$tbls);
            $sel = "SELECT DISTINCT c.* FROM " . TABLE_CONTACT . " AS c, $tables WHERE ";
            $where = "c.xsltDisplayType='opportunity' AND c.hidden=0 AND $propsel ORDER BY lastname";
            $sql = "$sel $where";
            
            //echo $sql;
            return $sql;
        }
        
        // project candidate
        $props  = array();
        if(!empty($_POST['c-position'])) $props[] = 'd1.label=' . $db->escape($post['c-position']);
        if(!empty($_POST['c-experience'])) $props[] = 'd1.value1 < ' . $db->escape($post['c-experience']);
        
        $propsel = implode(' AND ',$props);
        if(!empty($propsel))
        {
            $sel = "SELECT DISTINCT c.* FROM " . TABLE_CONTACT . " AS c, " . TABLE_DATES . " AS d1 WHERE ";
            $where = "c.id=d1.id AND ($propsel) AND (d1.visibility = 'visible' OR $admin) AND (c.hidden = 0 OR $admin) AND c.xsltDisplayType='expertise' ORDER BY lastname";
            $sql = "$sel $where";
                        
            //echo $sql;
            return $sql;
        }
        
        return $sql;
    }
    
    /**
    * create the content of the search list
    * @return string html-content
    */
function innerCreate()
    {
        if(isset($_POST['export']))
        {
            $this->exportTableAsCSV();
            exit(0);
        }
        
        $cont = '<div class="contact-list">';
                
        $x = &$this->createTable(); // loads a mailto in nav!
        $cont .= $this->nav->create();
        $cont .= $this->createSearchInterface();
        $cont .= $x;
        
        $cont .= '</div>';
        
        return $cont;
    }
    
    /**
    * create the goto text-box
    * @return string html-content
    * @global array custom searchtypes defined in config.php
    */
function createSearchInterface() {
        global $CONFIG_SEARCH_CUSTOM;
        
        $this->addHeaderSection('<style type="text/css"> label { display: block; } table { width:100%; } td { vertical-align: top; } </style>');
        
        $cont = '<br/><form method="post" action="../contact/searchlist.php">';
        $cont .= "\n<table><tr><td>";
        
        $projPos = array('','Project Manager','UTC Project Specialist','IUTC Project Specialist',
                'PT Project Specialist','Parking Project Specialist','Technical Engineer','Civil Engineer',
                'Traffic Signal Specialist','UTC System Specialist');
                
        $projCat = array('','Urban Traffic','Interurban Traffic','Public / Freight',
                'Transport','Tunnel','Infomobility','Service / Maintenance');
                
        $projCon = array('','Prime Contractor','Subcontractor','Managing Contractor',
                'Partner in a joint venture');
                
        // Projects
        $cont .= "\n<br/><h3>Project Reference Search</h3>";
        $cont .= HTMLHelper::createDropdownValuesAreKeys('p-category','Project Category',$projCat,isset($_POST['p-category']) ? $_POST['p-category'] : '');                                     
        
        $cont .= HTMLHelper::createDropdownValuesAreKeys('p-role','Contract Role',$projCon,isset($_POST['p-role']) ? $_POST['p-role'] : '');
         
        $cont .= HTMLHelper::createTextField('p-company','Lead Company',isset($_POST['p-company']) ? $_POST['p-company'] : '');
        
        
        $cont .= HTMLHelper::createTextField('p-value','Value of SWARCO part more than (EUR)',isset($_POST['p-value']) ? $_POST['p-value'] : '');
        $cont .= HTMLHelper::createTextField('p-after','Projects completed after',isset($_POST['p-after']) ? $_POST['p-after'] : '');
        
        // Project Ops
        $cont .= "</td><td>\n<br/><h3>Project Opportunity Search</h3>";
        $cont .= HTMLHelper::createDropdownValuesAreKeys('o-category','Project Category',$projCat,isset($_POST['o-category']) ? $_POST['o-category'] : '');
        
        $projCon = array('','Prime Contractor','Subcontractor','Managing Contractor',
                'Partner in a joint venture');
        $cont .= HTMLHelper::createDropdownValuesAreKeys('o-role','Contract Role',$projCon,isset($_POST['o-role']) ? $_POST['o-role'] : '');
         
        $cont .= HTMLHelper::createTextField('o-company','Lead Company',isset($_POST['o-company']) ? $_POST['o-company'] : '');
        
        $cont .= HTMLHelper::createTextField('o-value','Value of SWARCO part more than (EUR)',isset($_POST['o-value']) ? $_POST['o-value'] : '');
        
        // Candidates
        $cont .= "</td><td>\n<br/><h3>Project Candidate Search</h3>";
        
        $cont .= HTMLHelper::createDropdownValuesAreKeys('c-position','Project Position',$projPos,isset($_POST['c-position']) ? $_POST['c-position'] : '');
        
        $cont .= HTMLHelper::createTextField('c-experience','Experience with the Project Position since',isset($_POST['c-experience']) ? $_POST['c-experience'] : '');
        
        //$cont .= HTMLHelper::createDropdownValuesAreKeys('c-category','SWARCO Project Category',$projCat,'');
        //$cont .= HTMLHelper::createTextField('c-value','Value of SWARCO part more than (EUR)');
        //$cont .= HTMLHelper::createTextField('c-after','Projects completed after');

        $cont .= '</td></tr><tr><td colspan="2" style="text-align: right;">'.HTMLHelper::createButton('clear','button','onclick="window.location=\''.$_SERVER['PHP_SELF'].'\';"').
            '</td><td style="text-align: right;">' . HTMLHelper::createButton('search') .
            '</td><td style="text-align: right;">' . HTMLHelper::createButton('export','submit','name="export"') . '</td></tr>';
        
        $cont .= '</table><br>'; // make some space ...
        
        $cont .= '</form>';
        
        return $cont;
    }
        
    /**
    * create the table containing the contacts
    *
    * @uses Contact
    * @return string html-content
    * @uses TableGenerator
    */
function createTable()
    {
        // create big table
        $contacts = $this->contactList->getContacts();
        
        $mailtohref = ''; // email link
        $data = array();
        
        $htmlId=0;
        foreach($contacts as $c) {
            
            if ($this->expand) {
                
                $p = Page::newPage('PageContact',$c);
                
                $data[] = array(
                    'cont' => $p->innerCreate(),
                    'css_class' => 'list-expanded-card',
                    'group_n' => $groupNormalizer->normalize(mb_substr($c->contact['lastname'],0,1)));
                
                continue;
            }
            
            // fetch first email ...
            $mails = $c->getValueGroup('email');
            $e = EmailHelper::sendEmailHref($c->rawEmail($mails[0]));
            $mailtohref .= $e .',';
            
            $r = $c->generateListRowArray();
            //$r[] = "<input type='checkbox' checked=1 name='$e' id='cx$htmlId' onchange='generateMailto();'/>"; 
            $data[] = $r;
            $htmlId++;
        }
        
        $tGen = new TableGenerator('contact-list');
        
        $cont = '<table class="contact-list">';
        
        //$cont .= '<caption>' . $this->contactList->getGroupCaption() . '</caption>';
        
        if (count($data) > 0)
        {
            if ($this->expand)
                $cont .= $tGen->generateBody($data,array('cont'),'css_class',$this->isSorted ? 'group_n' : null);
            else 
                $cont .= $tGen->generateBody($data,range(0,count($data[0])-3),'css_class',$this->isSorted ? 'group_n' : null);
        }
        else
            $cont .= '<tr class="noentry"><td>No Entries.</td></tr>';
        
        $cont .= '</table>';
        
        //$this->nav->addEntry('mailtoSelected','mail to selected',"mailto:$mailtohref");
        
        return $cont;   
    }
    
function exportTableAsCSV()
    {
        // set headers for download
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"export.csv\"");
        header("Content-Transfer-Encoding: binary");
        
        $exportFieldsProject = array(
            array('other','Project Category','Project Category'),
            array('other','Contract Role','Contract Role'),
            array('other','Project Partner','Swarco Partner or joint venture'),
            array('other','SWARCO Value','Value of SWARCO part more than'),
            array('date','Completed','Completed'),
            array('','lastname','Contract Name'),
            array('other','Project Country','Project Country')
            );
            
        $exportFieldsOpportunity = array(
            array('other','Project Category','Project Category'),
            array('other','Project Status','Project Status'),
            array('other','Project Partner','Swarco Partner or joint venture'),
            array('other','SWARCO Value','Value of SWARCO part more than'),
            array('date','Awarded','Estimated contract start date'),
            array('','lastname','Contract Name'),
            array('other','Project Country','Project Country')
            );
        
        // create big table
        $contacts = $this->contactList->getContacts();
        
        if(count($contacts)==0)
        {
            echo 'Empty result.';
            exit(0);
        }
        
        switch($contacts[0]->contact['xsltDisplayType'])
        {
            case 'project': $fields = $exportFieldsProject; break;
            case 'opportunity': $fields = $exportFieldsOpportunity; break;
            case 'expertise': $this->exportCandidate($contacts); break;
            default: echo 'not implemented.'; exit(0);
        }
        
        $columnTitles = array();
        $outputOrder = array();
        
        // collect headers
        foreach($fields as $f)
        {
            $outputOrder[]=$f[1];
            $columnTitles[$f[1]]=$f[2];
        }
        
        echo StringHelper::csvLine($columnTitles,$outputOrder,';');
        
        foreach($contacts as $c)
        {
            $out = array();
            
            // collect info
            foreach($fields as $f)
            {
                if(empty($f[0]))
                    $out[$f[1]]=$c->contact[$f[1]];
                else
                if($f[0]=='date')
                {
                    $a = $c->getDate($f[1]);
                    $out[$f[1]] = $a[0];
                }
                else
                    $out[$f[1]] = $c->getProperty($f[0],$f[1]);
            }
            
            echo StringHelper::csvLine($out, $outputOrder, ';');
        }
        
        exit(0);
    }
    
    /** Unreusable function to link project contacts with projects via the Relationships plugin
     *
     */
function exportCandidate($contacts)
    {
        global $pluginManager,$addressFormatter;
        $relationshipsPlugin = $pluginManager->getPluginInstance('Relationships');
        
        $columnTitles = array('Name','Project Position','Experience with Project Position','Employer','SWARCO Project Category',
            'Value (EUR) of SWARCO part more than','Project completed after','Contract Name');
        $outputOrder = array(0,1,2,3,4,5,6,7);
        
        echo StringHelper::csvLine($columnTitles,$outputOrder,';');
        
        foreach($contacts as $c)
        {
            $out = array();
            
            $address = '';
            $vg = $c->getValueGroup('addresses');
            foreach($vg as $adr)
            {
                if($adr['type']!='Employer Address')
                    continue;
                
                $address = $addressFormatter->formatAddress($adr,"\r\n");
                break;
            }
            
            $name = $c->generateFullName('text');
            
            // incoming relationships
            $relationships = $relationshipsPlugin->relationshipsArray($c->contact['id'],TRUE);
            
            $vg = $c->getValueGroup('date');
            $n=0;
            foreach($vg as $v)
            {
                if($v['label']=='Project Manager' || $v['label']=='UTC Specialist' || $v['label']=='IUTC Specialist' || 
                   $v['label']=='PT Project Specialist' || $v['label']=='Parking Project Specialist' || 
                   $v['label']=='Technical Engineer' || $v['label']=='Civil Engineer' || $v['label']=='Traffic Signal Specialist' ||
                   $v['label']=='UTC System Specialist')
                {
                    $out = array();
                    $out[] = $name;
                    $out[] = $v['label'];
                    $out[] = $v['value1'];
                    $out[] = $address;
                    
                    $nr = 0;
                    foreach($relationships as $rel) // HORRRRRRRRRRRRRRRRRRIBLE!!!! AHHHHHHHHHHHH!
                    {
                        $rout = $out;
                        
                        $proj = new Contact($rel['ownerId']);
                        $rout[] = $proj->getProperty('other','Project Category');
                        $rout[] = $proj->getProperty('other','SWARCO Value');
                        $d = $proj->getDate('Completed');
                        $rout[] = $d[0];
                        $rout[] = $proj->contact['lastname'];
                        
                        echo StringHelper::csvLine($rout, $outputOrder, ';');
                        $nr++;
                    }
                    
                    if($nr == 0)
                        echo StringHelper::csvLine($out, $outputOrder, ';');
                    
                    $n++;
                }
            }
            
            if($n == 0) // no matches inside
            {
                $out[] = $name;
                $out[] = '';
                $out[] = '';
                $out[] = $address;
                
                echo StringHelper::csvLine($out, $outputOrder, ';');
            }
        }
        
        exit(0);
    }
}
?>
