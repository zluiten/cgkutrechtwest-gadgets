<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

require_once('Page.class.php');
require_once('Navigation.class.php');
require_once('TableGenerator.class.php');

class PageCA extends Page {
    
    var $menu;
    var $mode;
    var $data;
    var $real;
    
function PageCA($mode,$data,$real = false)
    {
        $this->Page('Certificate Authority');
        
        $this->menu = new Navigation('wide-menu');
        $this->menu->addEntry('return','return',Navigation::previousPageUrl());
        $this->menu->addEntry('usage-track','usage-track','gencerts.php?mode=utrack');
        $this->menu->addEntry('usage-list','usage-list','gencerts.php?mode=expired-list');
        //$this->menu->addEntry('added','added','gencerts.php?mode=added');
        //$this->menu->addEntry('deleted','deleted','gencerts.php?mode=deleted');
        //$this->menu->addEntry('expired','expired','gencerts.php?mode=expired');
        $this->menu->addEntry('imported','imported','gencerts.php?mode=imported');
        $this->menu->addEntry('crl','crl','gencerts.php?mode=crl');
        $this->menu->addEntry('relist','relist','gencerts.php?mode=relist');
        $this->menu->addEntry('pwlist','pwlist','pwlist.php?mode=pwlist');
        $this->menu->addEntry('stats','stats','gencerts.php?mode=stats');
        $this->menu->addEntry('default','default','gencerts.php');
        
        $this->mode = $mode;
        $this->data = $data;
        $this->real = $real;
    }
    
    /**
    * Alternatively creates plain pages for faxing and printing
    */
function create()
    {
        if($this->mode != 'pwlist-print')
            return parent::create();
            
        // very plain page for faxing
        header('Content-Type: text/html; charset=UTF-8',true);
        
        $cont = "<html><center>";
        $cont .= $this->createGroupSelector($_GET['groupname'],"pwlist.php");
        $cont .= $this->passwordTable();
        $cont .= "</center></html>";
        
        return $cont;
    }
    
function innerCreate() {
        
        global $db, $errorHandler;
        
        $cont = "\n".'<div class="wide-container">';
        $cont .= $this->menu->create();
        $cont .= '<div class="wide-title">Certificate Authority</div>';
        $cont .= '<div class="wide-box">';
        
        $cont .= '<a href="#" onclick="effect_3 = Effect.SlideDown(\'help\',{duration:2}); return false;">help</a>';
        $cont .= $this->help();
        
        switch ($this->mode) {
            case 'expired': // Reissue expired:
            case 'imported': // Issue newly imported
            case 'added':// Issue added
            case 'deleted':
            case 'default':
                if(!$this->real) { // show only
                    $cont .= "<div><strong>PREVIEW ONLY</strong>. These commands would be recorded in the database if you perform the changes.</div>";
                    $cont .= '<div><a href="#" onclick="effect_2 = Effect.SlideDown(\'performer\',{duration:1.2}); return false;">perform-unlock</a></div>';
                    $cont .= '<div id="performer" style="display: none;"><a href="gencerts.php?mode=' . $this->mode . '&amp;performUpdates=1' . (isset($_GET['days'])?'&amp;days=' . $_GET['days']:'') . '">do it now</a></div>';
                } else {
                    $cont .= "<div>Paste these commands into the file 2006-mm-dd.caops and execute: ./performops 2006-mm-dd.caops</div>";
                    $cont .= "<div>UNDO SQL (import): UPDATE prefix_contact SET certState='none', certPassword='' WHERE certExpires='YYYY-MM-DD'</div>";
                }
                
                if ($this->mode == 'imported') {
                    $cont .= '<form action="gencerts.php" method="get">';
                    $cont .= '<input type="hidden" name="mode" value="imported" />';
                    $cont .= '<div><label for="days">Days since import</label></div>';
                    $cont .= '<div><input type="text" id="days" name="days" value="' . (isset($_GET['days'])?$_GET['days']:'') . '" /></div>';
                    $cont .= '<div><button type="submit">ok</button></div>';
                    $cont .= '</form>';
                }
            case 'crl':
            case 'relist':
                if ($this->mode == 'relist') {
                    $cont .= '<form action="gencerts.php" method="get">';
                    $cont .= '<input type="hidden" name="mode" value="relist" />';
                    $cont .= '<div><label for="date">Date of execution (yyyy-mm-dd)</label></div>';
                    $cont .= '<div><input type="text" id="date" name="date" value="' . (isset($_GET['date'])?$_GET['date']:'') . '" /></div>';
                    $cont .= '<div><button type="submit">ok</button></div>';
                    $cont .= '</form>';
                }
                $cont .= '<pre>';
                $cont .= $this->data;
                $cont .= '</pre>';
            break;
            case 'pwlist': //make a passwordlist of a specific group
                $cont .= $this->createGroupSelector($_GET['groupname'],"pwlist.php");
                $cont .= $this->passwordTable();
                break;
            case 'stats':
            
                $db->query("SELECT certState, COUNT(certState) as n, grouplist.groupname as gn FROM " . TABLE_CONTACT . " AS contact, " . TABLE_GROUPS . " as groups , " . TABLE_GROUPLIST . " as grouplist 
                                WHERE contact.id=groups.id AND groups.groupid=grouplist.groupid GROUP BY groupname, certState ORDER BY groupname, certState");
                
                $tGen = new TableGenerator('data',array('company' => 'Company','cstate' => 'Cert. State','n' => 'n'));
                                
                $data = array();
                
                while ($r = $db->next()) {
                    $data[] = array(
                        'company' => $r['gn'],
                        'cstate' => $r['certState'],
                        'n' => $r['n']
                    );
                }
            
                $cont .= '<table class="data">';
                $cont .= '<caption>Statistics</caption>';
                $cont .= $tGen->generateHead(array('cstate','n'));
                $cont .= $tGen->generateBody($data,array('cstate','n'),$className='',$groupBy='company',$firstOnly=false);
                $cont .= '</table>';
                
                break;
            case 'utrack':
                $cont .= '<form action="gencerts.php?mode=utrack" method="post">';
                $cont .= '<div><label for="mails">E-mail addresses of used certificates, one in a line (copy from server-log). Do not run the same email twice on the same day, or UPDATE will return 0 rows updated.</label></div>';
                $cont .= '<div><textarea name="mails" id="mails" cols="30" rows="10"></textarea></div>';
                $cont .= '<div><button type="submit">ok</button></div>';
                $cont .= '</form>';
            
                break;
        }
            
        $cont .= '</div>';
        
        $cont .= '</div>';
        
        return $cont;
        
    }
    
function help()
    {
        return <<<EOC
        <div id="help" style="display: none;">
        <dl>
            <dt>default</dt>
            <dd>Generate cert commands for all added, expired OR revoked certificates.</dd>
            
            <dt>crl</dt>
            <dd>Generate cert revokation commands for ALL contacts marked as revoked that are not expired</dd>
            
            <dt>expired</dt>
            <dd>Generate cert issuing commands for ALL users with a cert that expires within 30 days (CERT_DAYS_TILL_EXPIRE)</dd>
            
            <dt>imported</dt>
            <dd>Generate cert issuing commands for ALL users that were just imported (max. 2 days in the past is default; set certState to revoked to skip some)</dd>
            
            <dt>relist</dt>
            <dd>Relist commands for a specific date or today</dd>
            
            <dt>pwlist</dt>
            <dd>Generate a password list of certificates that were issued today (or in the last n days). This generates sheets for faxing pw lists to groups of people.</dd>
            
            <dt>stats</dt>
            <dd>Generates counts of used/unused certs by group</dd>
            
            <dt>usage-track</dt>
            <dd>Allows you to track the usage of certificates by entering a list of e-mail-addresses (one on each line) from the server-log or webalizer. This sets the state of the certificate of each user to 'used' and the lastUsed datestamp to TODAY.</dd>
            
            <dt>perform unlock</dt>
            <dd>Unlocks the menu to update the DB. Shows 'do it now'.</dd>
            
            <dt>do it now</dt>
            <dd><strong>WARNING:</strong> You cannot repeat or undo this! Performs updates, assigns passwords, revokes and stores that in the DB. The resulting commands MUST be executed in the CA NOW, otherwise the DB is out of sync.</dd>
            
        </dl>
        </div>
EOC;
/*
            <dt>added</dt>
            <dd>Generate cert issuing commands for ALL added contacts that have no cert</dd>
            
            <dt>deleted</dt>
            <dd>Generate cert revokation commands for ALL deleted contacts with a valid cert</dd>
            
*/
    }       
    
    /**
    * create the dropdown for group selection
    *
    * @return string html-content
    * @global DB used to query database for groups
    */
function createGroupSelector($groupname,$target="gencerts.php") {
        
        global $db;
        
        // create group selector
        $cont = '<div>';
        $cont .= '<form method="get" action="'.$target.'">';
        $cont .= '<label for="groupname">select group</label>';
        $cont .= '<input type="hidden" name="mode" value="'. $this->mode .'" />';
        $cont .= '<select name="groupname" id="group" onchange="document.selectGroup.submit()">';
        
        $cont .= '<option value=""' . ($groupname == ''?' selected="selected"':'') . '></option>';
        
        $db->query('SELECT * FROM ' . TABLE_GROUPLIST . ' ORDER BY groupname ASC');
        
        while ($r = $db->next())
            $cont .= '<option' . ($groupname == $r['groupname']?' selected="selected"':'') . '>' . $r['groupname'] . '</option>';
        
        $cont .= '</select>';
        if($this->mode != 'pwlist-print')
            $cont .= '<input type="submit" name="print" value="printable"\>';
        else
        $cont .= '</form>';
        $cont .= '</div>';
        
        return $cont;
    }
    
function passwordTable()
    {
        // font suitable for faxing
        $cont = "\n".'<style type="text/css"> .sslca-fax { font-family: courier; font-size: 20px; border: solid 1px; } </style>';
        
        $tGen = new TableGenerator('',array('company' => 'Company','name' => 'Name','password' => 'Password'));
                
        $data = array();
        
        foreach ($this->data->getContacts() as $c) {
            
            $gr = $c->getValueGroup('groups');
            $gr = $gr[0]['groupname'];
            
            $data[] = array(
                'company' => $gr,
                'name' => $c->contact['firstname'] . ' ' . $c->contact['lastname'],
                'password' => $c->contact['certPassword'],
                'hint' => '[0,1,lion,OK]'
            );
            
        }
        
        $cont .= "\n<table class='sslca-fax' width='80%'>";
        //$cont .= '<caption>Password List</caption>';
        //$cont .= $tGen->generateHead(array('name','password'));
        $cont .= $tGen->generateBody($data,array('name','password','hint'),'sslca-fax','company',false);
        $cont .= '</table>';
        
        return $cont;
    }
}

?>
