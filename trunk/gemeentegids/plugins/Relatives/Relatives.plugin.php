<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
 * Shows name and a link to relatives in the adressbook
 *
 * @package plugins
 * @author Zacharias Luiten
 */
class Relatives {

    //function isType($t) { return $t=='changedContactRecord' || $t=='contactOutput' || t=='editContactInterface'; }
	function isType($t) { return $t=='changedContactRecord' || $t=='editContactInterface' || $t=='contactOutput'; }
    
function help()
    {
        return '<script type="text/javascript">
        function open_help_relatives() {
                help_win = window.open( "", "help", "width=300, height=400,toolbar=no,location=no,directories=no,status=no,menubar=no,resizable=no,scrollbars=auto,alwaysRaised=yes");
                help_win.document.write("<html><head><title>Relatives</title></head>");
                help_win.document.write("<body>");
                help_win.document.write("<h3>Relatives</h3>");
                help_win.document.write("<p>Explation comes here...</p>");
                help_win.document.write("</body></html>");
                help_win.document.close();
                help_win.focus();
            }
            </script><a href="javascript:open_help_relatives()">help</a>';
     }

function changedContactRecord(&$contact,$mode)
    {
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();

        if ($mode == 'deleted')
        {
            $id = $contact->contact['id'];
            $db->query("UPDATE ".$CONFIG_DB_PREFIX."contact SET partnerId=NULL WHERE partnerId=".$id);            
            return;
        }

        if ($mode == 'added' || $mode == 'changed')
        {
        	$id = $contact->contact['id'];
            if($_POST['partner'] != "" && $_POST['delpartner'] != "1")
            {
                $db->query("UPDATE ".$CONFIG_DB_PREFIX."contact SET partnerId=".$_POST['partner']." WHERE id=".$id);
                $db->query("UPDATE ".$CONFIG_DB_PREFIX."contact SET partnerId=".$id." WHERE id=".$_POST['partner']);
            }
            if($_POST['delpartner'] == "1")
            {
                $db->query("UPDATE ".$CONFIG_DB_PREFIX."contact SET partnerId=NULL WHERE id=".$id." OR partnerId=".$id);            	
            }
            if(($_POST['ouder1'] != "" || $_POST['ouder2'] != "") && $_POST['delouders'] != "1")
            {
            	$adresvanouder = $_POST['ouder1'];
            	if($_POST['ouder1'] == "")
            	{
            		$adresvanouder = $_POST['ouder2'];                        		
            		$_POST['ouder1'] = "NULL";
            	}
            	if($_POST['ouder2'] == "")
            	{
            		$adresvanouder = $_POST['ouder1'];                        		
            		$_POST['ouder2'] = "NULL";                        		
            	}
            	
            	$db->query("UPDATE ".$CONFIG_DB_PREFIX."contact SET ouder1=".$_POST['ouder1'].", ouder2=".$_POST['ouder2'].",thuiswonend=".$_POST['thuiswonend']." WHERE id=".$id);
            	            	
	            if($_POST['thuiswonend'] == "1")
	            {
					$db->query("SELECT line1, line2, city, state, zip, country FROM ".$CONFIG_DB_PREFIX."address WHERE id=".$adresvanouder." LIMIT 0, 1");
					$rr = $db->next();
					$db->query("UPDATE ".$CONFIG_DB_PREFIX."address SET line1='".$rr['line1']."', line2='".$rr['line2']."', city='".$rr['city']."', state='".$rr['state']."', zip='".$rr['zip']."', country='".$rr['country']."' WHERE id=".$id);
	            }            
            }
            if($_POST['delouders'] == "1")
            {
                $db->query("UPDATE ".$CONFIG_DB_PREFIX."contact SET ouder1=NULL,ouder2=NULL,thuiswonend=0 WHERE id=".$id);            	
            }
            return;
        }
         
	}
	
function getDropdown(&$db, $selfid)
    {
        // create dropdown selector box from all contacts (might be HUGE)
        $dropdown = array(''=>'');
        global $CONFIG_DB_PREFIX;
        //$db->query("SELECT firstname, lastname, id FROM `{$CONFIG_DB_PREFIX}contact` WHERE hidden=0 AND $CONFIG_REL_DROPDOWN_SUBSET ORDER BY lastname, firstname");
        $db->query("SELECT firstname, middlename, lastname, id FROM `{$CONFIG_DB_PREFIX}contact` WHERE hidden=0 AND id<>'".$selfid."' ORDER BY lastname, firstname");
        while($r = $db->next())
            $dropdown[$r['id']] = $r['lastname'] . ', ' . $r['firstname'] . ' '. $r['middlename'];
        return $dropdown;
    }
	
function editContactInterface(&$contact, $location)
    {
    	if($location!='ownFieldset')
            return "";

        //if(!$_SESSION['user']->isAtLeast('manager'))
        //    return "";
        
        global $CONFIG_DB_PREFIX;
        $db = DB::getSingleton();

        // fetch dropdown
        $dropdown = $this->getDropdown($db,$contact->contact['id']);

        $db->query("SELECT partnerId,ouder1,ouder2,thuiswonend FROM ".$CONFIG_DB_PREFIX."contact WHERE id = '".$contact->contact['id']."'");
        $r = $db->next();
        $thuiswonend = false;
        if($r['thuiswonend']==1) $thuiswonend = true;
        $content = '<fieldset class="edit-names">';
        $content .= '<legend>Relatives</legend>';
        $content .= '<div class="edit-line">';
        $content .= HTMLHelper::createDropdown("partner",'Getrouwd met',$dropdown,$r['partnerId'],'edit-input');
        $content .= HTMLHelper::createCheckbox("delpartner",'Verwijder relatie',false,'edit-input-checkbox');
        $content .= '</div>';
        $content .= '<hr />';
        $content .= '<div class="edit-line">';
        $content .= HTMLHelper::createDropdown("ouder1",'Ouder 1',$dropdown,$r['ouder1'],'edit-input');
        $content .= HTMLHelper::createCheckbox("thuiswonend",'Thuiswonend',$thuiswonend,'edit-input-checkbox');
        $content .= '</div>';
        $content .= '<div class="edit-line">';
        $content .= HTMLHelper::createDropdown("ouder2",'Ouder 2',$dropdown,$r['ouder2'],'edit-input');
        $content .= HTMLHelper::createCheckbox("delouders",'Verwijder ouderrelaties',false,'edit-input-checkbox');
        $content .= '</div>';
        
        return $content . '</fieldset>';
    }
		
function contactOutput(&$contact, $location)
    {
    	global $db, $errorHandler, $CONFIG_DB_PREFIX;

        if($location != 'beforeNotes')
            return '';
  	    
        $cont = '';        
        $r = '';
        $parentIds = array();
        $childIds = array();
        
  	    	$r = '';
			if($contact->contact['partnerId'] != NULL) {
				$db->query('SELECT firstname, lastname, id, sex FROM ' . TABLE_CONTACT . ' AS contact WHERE contact.Id = ' . $db->escape($contact->contact['partnerId']));
				if($db->rowsAffected() == 1) {
					$r = $db->next();
					$partner['fulname'] = $r['firstname'] . ' ' . $r['lastname'];
					$partner['id'] = $r['id'];
					if($r['sex'] == 'male') $labelpartner = 'm';
					if($r['sex'] == 'female') $labelpartner = 'v';					
				}
  	    		$relationoutput = '';
			}
  	    	
			$r = '';
			if( ($contact->contact['ouder1'] != NULL || $contact->contact['ouder2'] != NULL) && $contact->contact['thuiswonend'] == 1 ) {
				if($contact->contact['ouder1'] != NULL)
					$subquerystring = $contact->contact['ouder1'];
				if($contact->contact['ouder2'] != NULL)
				{
					if($subquerystring != NULL)
						$subquerystring .= ',';
					$subquerystring .= $contact->contact['ouder2'];
				}					
				$querystring = 'SELECT firstname, lastname, id, sex FROM ' . TABLE_CONTACT . ' AS contact WHERE contact.Id IN (' . $subquerystring . ')'; 
				$db->query($querystring);
				if($db->rowsAffected() > 0)
					while($r = $db->next()) {
  	    				$tempparent['fulname'] = $r['firstname'] . ' ' . $r['lastname'];
  	    				$tempparent['id'] = $r['id'];
  	    				$tempparent['sex'] = $r['sex'];
  	    				$parents[] = $tempparent;
					}
			}
			
			$r = '';
  	    	$db->query('SELECT firstname, lastname, id, sex FROM ' . TABLE_CONTACT . ' AS contact WHERE ( contact.ouder1 = ' . $db->escape($contact->contact['id']) . ' OR contact.ouder2 = ' . $db->escape($contact->contact['id']) . ') AND contact.thuiswonend = \'1\'');
			if($db->rowsAffected() > 0)
  	    		while($r = $db->next()) {
  	    			$tempchild['fulname'] = $r['firstname'] . ' ' . $r['lastname'];
  	    			$tempchild['id'] = $r['id'];
  	    			$tempchild['sex'] = $r['sex'];
  	    			$children[] = $tempchild;
  	    		}
			
			
  	    	if(count($children) > 0)
	  	    	foreach($children as $kid) {
	  	    		if($kid['sex'] == 'male') $labelkid = 'm';
	  	    		if($kid['sex'] == 'female') $labelkid = 'v';
	  	    		$childrenoutput .= '<li style="margin-left: 0px"><a href="../contact/contact.php?id='.$kid['id'].'">'.$kid['fulname'].'</a> ('.$labelkid.')</li>';
	  	    	}
	  	    
	  	    if(count($parents) > 0)
	  	    	foreach($parents as $parent) {
	  	    		if($parent['sex'] == 'male') $labelparent = 'm';
	  	    		if($parent['sex'] == 'female') $labelparent = 'v';
	  	    		$parentsoutput .= '<li style="margin-left: 0px"><a href="../contact/contact.php?id='.$parent['id'].'">'.$parent['fulname'].'</a> ('.$labelparent.')</li>';
	  	    	}
	  	    	
	  	    	$cont .= '<div class="address-title">Geboortedatum</div>';
	  	    	$maanden = array('januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'); 
				$geboortedatum = explode("-",$contact->contact['geboortedatum']);
	  	    	$cont .= '<span style="margin-left:5px;">'.(int) $geboortedatum[2].' '.$maanden[$geboortedatum[1]-1].' '.$geboortedatum[0].'</span>';
        		$cont .= '<div class="other-spacer"></div>';
	  	    	
	  	    if($partner != NULL || count($children) > 0 || count($parents) > 0)
	  	    	$cont .= '<div class="address-title">Familie</div>';
	  	    if($partner != NULL) {
	  	    	$cont .= '<span style="float:left;font-weight:bold;margin-left:4px">Getrouwd met</span><a style="margin-left:5px;" href="../contact/contact.php?id='.$partner['id'].'">'.$partner['fulname'].'</a> ('.$labelpartner.')';
        		$cont .= '<div class="other-spacer"></div>';
	  	    }
        	if(count($children) > 0) {
	  	    	$cont .= '<span style="float:left;font-weight:bold;margin-left:4px">Thuiswonende kinderen</span>';
	  	    	$cont .= '<ul style="clear:both;list-style-type: none;margin-left:-25px">' . $childrenoutput . '</ul>';
        	}
        	if(count($parents) > 0) {
	  	    	$cont .= '<span style="float:left;font-weight:bold;margin-left:4px">Ouders/verzorgers</span>';
	  	    	$cont .= '<ul style="clear:both;list-style-type: none;margin-left:-25px">' . $parentsoutput . '</ul>';
        	}
        
        return $cont;
        
    }    

function installPlugin() {
        global $db;

        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' ADD partnerId INT(11) DEFAULT NULL');
        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' ADD ouder1 INT(11) DEFAULT NULL');
        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' ADD ouder2 INT(11) DEFAULT NULL');
        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' ADD thuiswonend INT(1) DEFAULT \'0\'');
}

function uninstallPlugin() {
		global $db;
		
        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' DROP partnerId');
        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' DROP ouder1');
        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' DROP ouder2');
        $db->queryNoError('ALTER TABLE ' . TABLE_CONTACT . ' DROP thuiswonend');
}

function version() {
    return '0.4';
    }
}

?>