<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* DEPRECATED EXAMPLE CLASS. REPLACED BY XSLT PAGES.
* @author Tobias Schlatter, Thomas Katzlberger
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('ErrorHandler.class.php');
require_once('Options.class.php');
require_once('Page.class.php');
require_once('DB.class.php');
require_once('Contact.class.php');
require_once('Navigation.class.php');
require_once('RightsManager.class.php');
require_once('HTMLHelper.class.php');

/**
* DEPRECATED EXAMPLE CLASS. REPLACED BY XSLT PAGES.
* 
* the contact edit page allows users to
* add, edit and remove contact entries
* @package frontEnd
* @subpackage pages
*/
class PageProjectContactEdit extends Page {
    
    /**
    * @var Contact the contact that is to be modified
    */
    var $contact;
    
    /**
    * @var A contact's valueGroups are cached for output processing. see createFixedPropertyInput()
    */
    var $valueGroups;
    
    /**
    * @var Navigation the menu to be shown
    */
    var $menu;
    
    /**
    * @var boolean whether the contact is to be added or to be changed
    */
    var $add;
    
    /**
    * @var int counter to create html ids for for scriptaculous
    */
    var $htmlId;
    
    var $counters; // private (counters array for value group POST)
    
    /**
    * Constructor: ONLY TO BE CALLED like this: Page::newPage(classname,$id,$add) factory method!! 
    * 
    * @param $idOrContact integer|Contact the id of the contact, or the contact that is to be edited
    * @param $add boolean whether the contact is to be added or not (cannot be detected through {@link $id}, because a contact can be passed if an error occurs to preserve already inserted information)
    * @global Options admin options
    */
function PageProjectContactEdit($idOrContact,$add=false) {
        global $options;
        
        $this->counters = array();
        
        $this->add = $add;
        
        if ($idOrContact === null)
        {
            $this->contact = Contact::newContact();
            $this->add = TRUE;
        }
        elseif (is_numeric($idOrContact))
            $this->contact = Contact::newContact($idOrContact);
        else
            $this->contact = &$idOrContact;
            
        if ($add)
            $this->Page('Add new entry');
        else
            $this->Page('Edit entry for <span>' . $this->contact->contact['firstname'] . ' ' . $this->contact->contact['lastname'].'</span>');
            
        $this->menu = new Navigation('edit-menu');
        
        $this->menu->addEntry('save','save','javascript:saveEntry();');
        if (isset($this->contact->contact['id']))
            $this->menu->addEntry('cancel','cancel','?id=' . $this->contact->contact['id']);
        else
            $this->menu->addEntry('cancel','cancel',Navigation::mainPageUrl());
            
        if (!$this->add) {
            $rightsManager = RightsManager::getSingleton();
            if($rightsManager->mayDeleteContact($this->contact)) {
                $this->menu->addEntry('delete','delete','javascript:deleteEntry(' . $this->contact->contact['id'] . ');');
                if ($_SESSION['user']->isAtLeast('admin') && $options->getOption('deleteTrashMode'))
                    $this->menu->addEntry('trash','trash','?mode=trash&amp;id=' . $this->contact->contact['id']);
            }
        }
            
        if($_SESSION['user']->isAtLeast('admin')) // no putting on changed list
            $this->menu->addEntry('adminsave','adminsave','javascript:adminsaveEntry();');
    }
    
    /**
    * create the content of admin panel
    * @return string html-content
    * @global ErrorHandler used for error handling
    * @global PluginManager used for plugin handling (i.e. additional editable fields)
    * @uses createNameFieldset()
    * @uses createAddressFieldset()
    * @uses createProperties()
    * @uses createMugshotFieldset()
    * @uses createNotesFieldset()
    * @uses createGroupsFieldset()
    */
function innerCreate() {
        
        global $errorHandler,$pluginManager;
        
        $outer = '<div class="edit-container">';
        $outer .= $this->menu->create();
        $outer .= '<div class="clear-both"></div>';
        
        if ($this->add)
            $cont = '<div class="edit-title">Add new entry</div>';
        else
            $cont = '<div class="edit-title">Edit entry for ' . $this->contact->contact['firstname'] . ' ' . $this->contact->contact['lastname'] . '</div>';
        
        $cont .= '<div class="edit-box">';
        $cont .= '<form enctype="multipart/form-data" name="editEntry" method="post" action="?mode=save">';
        
        if (!$this->add)
            $cont .= '<input type="hidden" name="contact[id]" value="' . $this->contact->contact['id'] . '" />';
        
        // ========== NAME AND ADDRESS ==========
        $cont .= $this->createNameFieldset();
        $cont .= $this->createAddressFieldset();
        
        // ========== Preload Data from Contact ==========
        $this->valueGroups['phone'] = $this->contact->getValueGroup('phone');
        $this->valueGroups['email'] = $this->contact->getValueGroup('email');
        $this->valueGroups['chat'] = $this->contact->getValueGroup('chat');
        $this->valueGroups['www'] = $this->contact->getValueGroup('www');
        $this->valueGroups['other'] = $this->contact->getValueGroup('other');
        
        //================ MANDATORY ================
        // EXAMPLE for ADDING MANDATORY/SUGGESTED fields: uncomment the following code block
        // I suggest you put the class into MyPageContactEdit.class.php and replace this class (see config.php)
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Contact</legend>';
        $cont .= $this->createFixedPropertyInput('phone','Tel','visible');
        $cont .= $this->createFixedPropertyInput('phone','Mobile','visible');
        $cont .= $this->createFixedPropertyInput('other','Fax','visible');
        $cont .= $this->createFixedPropertyInput('email','E-Mail','visible');
        $cont .= '</fieldset>';
        
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Job</legend>';
        $cont .= $this->createFixedPropertyInput('other','Job Title','visible');
        $cont .= $this->createFixedPropertyInput('other','Superior','visible');
        $cont .= $this->createFixedPropertyInput('other','Prof. qualification','visible');
        $cont .= $this->createFixedPropertyInput('other','Prof. qualification','visible');
        $cont .= '</fieldset>';
        
        // ========== Communications ==========
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Communications</legend>';
        $cont .= $this->editHint();
        $opt = array('email'=>'email','phone'=>'phone','chat'=>'chat',' '=>'delete');
        $cont .= $this->createNormalProperties($opt,'email');
        $cont .= $this->createNormalProperties($opt,'phone');
        $cont .= $this->createNormalProperties($opt,'chat');
        $cont .= $this->createNormalProperties($opt,'blank');
        $cont .= '</fieldset>';
        
        // ========== Information ==========
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Information</legend>';
        $cont .= $this->editHint();
        $opt = array('other'=>'other','www'=>'url',' '=>'delete');
        $cont .= $this->createNormalProperties($opt,'www',90,310);
        $cont .= $this->createNormalProperties($opt,'other',90,310);
        $cont .= $this->createNormalProperties($opt,'blank',90,310);
        $cont .= '</fieldset>';
        
        // ========== Dates ==========
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Dates</legend>';
        $cont .= $this->editHint('date');
        $opt = array('yearly'=>'yearly','monthly'=>'monthly','weekly'=>'weekly','once'=>'once', 'autoremove'=>'autoremove', ' '=>'delete');
        $cont .= $this->createDates($opt,'date',18,array(10,10));
        $cont .= $this->createDates($opt,'blank_date',18,array(10,10));
        $cont .= '</fieldset>';

        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Plugins</legend>';
        $cont .= '<div class="edit-line">';
        $cont .= $pluginManager->editContactInterface($this->contact, 'otherInfo');
        $cont .= '</div>';
        $cont .= '</fieldset>';
        $cont .= $pluginManager->editContactInterface($this->contact, 'ownFieldset');
        
        $cont .= $this->createMugshotFieldset();
        $cont .= $this->createNotesFieldset();
        $cont .= $this->createGroupsFieldset();
        
        $cont .= '</form>';
        $cont .= $this->menu->create('edit-menu');
        $cont .= '<br></div>';
        
        if(isset($this->contact->contact['lastUpdate']))
            $cont .= '<div class="update">This entry was last updated on ' . $this->contact->contact['lastUpdate'] . '</div>';
        
        return $outer .  HTMLHelper::createNestedDivBoxModel('edit-content',$cont) . '</div>';
        
    }
    
    /**
    * Create the fieldset that allows a user to edit communication and other information.
    * Users can make each property private or public, admins can attach admin-hidden properties only 
    * visible to users and them.
    * @param array $options selections showed in dropdown
    * @param string $type type of the entry (not necessarily shown in dropdown)
    * @param integer $size1 size of first text-field
    * @param integer|array $size2 size of second text-field or an array of sizes for several fields
    * @return string html-content
    */
function createNormalProperties($options,$type,$w2=80,$w3=320,$w4=0) {
        
        if($type == 'blank')
        {
            $dummy = array('type'=>$type,'label'=>'','value'=>'','visibility'=>'visible');
            $vals = array($dummy,$dummy); // 2 blanks
        }
        else // use the array cached before
            $vals = $this->valueGroups[$type];
        
        $cont = '';
        foreach ($vals as $m)
        {
            if($m==null)
                continue;
            
            if($m['visibility']=='admin-hidden' && !$_SESSION['user']->isAtLeast('admin') || $m['visibility']=='hidden' && !$rightsManager->mayViewPrivateInfo($this->contact)) // do not show
                continue;
            
            $cont .= $this->createNormalPropertyInput($options,$type,$m['label'],$m['value'],$m['visibility'],$w2,$w3,$w4);                                               
        }
                
        return $cont;
    }
    
    /**
    * Creates a normal (not a date range) property input line.
    * @param array $options selections shown in dropdown
    * @param string $type type of the entry (not necessarily shown in dropdown)
    * @param integer css width of input field2 (input field 1 class='edit-input-dropdown')
    * @param integer css width of input field3
    * @param integer css width of input field4
    * @return string html-content
    */
function createNormalPropertyInput($options,$type,$label,$value,$visibility,$w2=80,$w3=320,$w4=0)
    {
        if(!array_key_exists($type,$this->counters))
            $this->counters[$type]=0;
            
        $c = $this->counters[$type]++;
        
        $cont = "\n<div class='edit-propertyset'>" . HTMLHelper::createDropdown($type."[$c][type]",'',$options,$type,'edit-input-dropdown',false);
        $cont .= "<input type='text' name='".$type."[$c][label]' value='$label' style='width:{$w2}px'/>\n";
        $cont .= "<input type='text' name='".$type."[$c][value]' value='$value' style='width:{$w3}px'/>\n";
        $cont .= "<input type='hidden' name='".$type."[$c][visibility]' value='$visibility' style='width:{$w4}px'/>\n";
        $cont .= "<br></div>\n";                                                    
                    
        return $cont;
    }

    /**
    * Creates a fixed, normal (not a date range) property input line. This is used for mandatory/suggested properties.
    * The property is removed from $this->valueGroups so it does not show again in createNormalProperties()
    * field1 and field2 are readonly inputs, although one could manipulate the post, so there is no strong protection.
    * @param array $options selections shown in dropdown
    * @param string $type type of the entry (not necessarily shown in dropdown)
    * @param integer css width of input field2 (input field 1 class='edit-input-dropdown')
    * @param integer css width of input field3
    * @param integer css width of input field4
    * @return string html-content
    */
function createFixedPropertyInput($type,$label,$visibility,$w2=80,$w3=320,$w4=0)
    {
        if(!array_key_exists($type,$this->counters))
            $this->counters[$type]=0;
            
        $value='';
        $n=count($this->valueGroups[$type]);
        for($i=0;$i<$n;$i++)
            if($this->valueGroups[$type][$i]['label']==$label) // search and remove if found from VG
            {
                $value=$this->valueGroups[$type][$i]['value'];
                $this->valueGroups[$type][$i]=null;
                break;
            }
        
        $c = $this->counters[$type]++;
        
        $cont = "\n<div class='edit-propertyset'>";
        $cont .= "<input type='text' readonly name='".$type."[$c][type]' value='$type' class='edit-input-dropdown'/>\n";
        $cont .= "<input type='text' readonly name='".$type."[$c][label]' value='$label' style='width:{$w2}px'/>\n";
        $cont .= "<input type='text' name='".$type."[$c][value]' value='$value' style='width:{$w3}px'/>\n";
        $cont .= "<input type='hidden' name='".$type."[$c][visibility]' value='$visibility' style='width:{$w4}px'/>\n";
        $cont .= "<br></div>\n";                                                    
        return $cont;
    }

    /**
    * Create the fieldset that allows user to edit communication and other information.
    * Users can make each property private or public, admins can attach properties only 
    * visible to users and them.
    * @param array $options selections showed in dropdown
    * @param string $type type of the entry (not necessarily shown in dropdown)
    * @param integer $size1 size of first text-field
    * @param integer|array $size2 size of second text-field or an array of sizes for several fields
    * @return string html-content
    */
function createDates($options,$type,$size1=6,$size2=39) {
        
        if(!array_key_exists($type,$this->counters))
            $this->counters[$type]=0;
                
        if(mb_substr($type,0,5) == 'blank')
        {
            $dummy = array('type'=>$type,'label'=>'','value'=>'','value1'=>'','value2'=>'','visibility'=>'visible');
            if($this->add==true)
                $vals = array($dummy,$dummy,$dummy,$dummy); // 4 blanks
            else
                $vals = array($dummy,$dummy); // 2 blanks
                
            if (mb_substr($type,6) != '')
                $type = mb_substr($type,6);
        }
        else
            $vals = $this->contact->getValueGroup($type);

        $visibilityOptions = array('visible'=>'public'); // default (actually used for manager)
        
        $rightsManager = RightsManager::getSingleton();
        if($rightsManager->mayViewPrivateInfo($this->contact)) // users and admins add private option
            $visibilityOptions['hidden'] = 'private';
        
        if($_SESSION['user']->isAtLeast('admin'))  // only admin can attach info private to user, changeable by admin, hidden from manager
            $visibilityOptions['admin-hidden'] = 'admin';
        
        $cont = '';
        foreach ($vals as $m)
        {
            if($m['visibility']=='admin-hidden' && !$_SESSION['user']->isAtLeast('admin') || $m['visibility']=='hidden' && !$rightsManager->mayViewPrivateInfo($this->contact)) // do not show
                continue;
                
            $c = $this->counters[$type];
            $cont .= "\n<div class='edit-propertyset'>" . HTMLHelper::createDropdown($type."[$c][type]",'',$options,$m['type'],null,false);
            $cont .= "\n<input type='text' name='".$type."[$c][label]' value='{$m['label']}' size='$size1'/>\n";
            if (is_array($size2)) {
                $i = 0;
                foreach ($size2 as $s) {
                    $i++;
                    $value = htmlspecialchars($m["value$i"]);
                    if($type == 'date') // special values of date
                    {
                        if($m["value$i"] === null)
                            $value = '?';
                        else if($m["value$i"] == '0000-00-00')
                            $value = '';
                    }       
                    $cont .= "<input type='text' name='".$type."[$c][value$i]' value='" . $value . "' size='$s'/>\n";
                }
            } else
                $cont .= "<input type='text' name='".$type."[$c][value]' value='{$m['value']}' size='$size2'/>\n";
            $cont .= HTMLHelper::createDropdown($type."[$c][visibility]",'',$visibilityOptions,$m['visibility'],null,false);
            $cont .= "<br></div>\n";                                                    
            $this->counters[$type]++;
        }
                
        return $cont;
    }
    
    /**
    * create the fieldset that allows user to select the groups of the contact, and whether the contact is hidden or not
    * @return string html-content
    * @global DB used to query database for groups
    */
function createGroupsFieldset() {
        
        global $db;
        
        $cont = '<fieldset class="edit-groups">';
        $cont .= '<legend>Groups</legend>';
        
        $gr = $this->contact->getValueGroup('groups');
        
        $groups = array();
        
        foreach ($gr as $g)
            $groups[] = $g['groupname'];
        
        $db->query('SELECT * FROM ' . TABLE_GROUPLIST . ' ORDER BY groupname ASC');
        
        $cont .= '<div class="edit-line">';
        
        while ($r = $db->next())
            $cont .= HTMLHelper::createCheckbox("groups[{$r['groupname']}]",$r['groupname'],in_array($r['groupname'],$groups),'edit-input-checkbox');

        $cont .= '</div>';
        
        $cont .= '<div class="edit-line">';
        
        $cont .= HTMLHelper::createTextField('newgroup','Add new group','','edit-input');
        $cont .= HTMLHelper::createCheckbox('contact[hidden]','Hide this entry',(isset($this->contact->contact['hidden']) ? $this->contact->contact['hidden'] : 0),'edit-input-checkbox-hide');
        
        $cont .= '</div>';
        
        $cont .= '</fieldset>';
        
        return $cont;
        
    }
    
    /**
    * create the fieldset that allows user to edit the notes of the contact
    * @return string html-content
    */
function createNotesFieldset() {
        
        $cont = '<fieldset class="edit-notes">';
        $cont .= '<legend>Notes</legend>';
        
        $cont .= '<div class="context-help" id="editHelp'.$this->htmlId.'"><a href="#" onclick="Element.hide(\'editHelp'.$this->htmlId.'\'); Effect.Appear(\'editHint'.$this->htmlId.'\',{duration:1.2}); return false;">?</a></div><div class="context-help" id="editHint'.($this->htmlId++).'" style="display: none;">Text in the Notes box will be displayed exactly as you type it. The area will grow after saving whenever you add more lines of text.</div>';
        
        $notes = isset($this->contact->contact['notes']) ? $this->contact->contact['notes'] : '';
        $n=count(explode("\n",$notes));
        $cont .= HTMLHelper::createTextarea('contact[notes]',$notes,$n+2,66,null,false);
        
        $cont .= '</fieldset>';
        
        return $cont;
        
    }
    
    /**
    * create the fieldset that allows user to edit and eventually upload the mugshot of the contact
    * @global Options used to determine whether mugshot may be uploaded or not
    * @return string html-content
    */
function createMugshotFieldset() {
       
        global $options;
        
        $cont = '<fieldset class="edit-mugshot">';
        $cont .= '<legend>Mugshot</legend>';

        $cont .= '<div class="context-help" id="editHelp'.$this->htmlId.'"><a href="#" onclick="Element.hide(\'editHelp'.$this->htmlId.'\'); Effect.Appear(\'editHint'.$this->htmlId.'\',{duration:1.2}); return false;">?</a></div><div class="context-help" id="editHint'.($this->htmlId++).'" style="display: none;">If an URL to the mugshot is set this overrides any uploaded picture! If you upload a picture to the DB make sure that the "URL to mugshot" field is empty and has no space in it. If the admin does not allow uploads the upload button will not show and you can only set an URL. In case of URLs you can omit the protocol and server name if the image is stored on the same server as this application runs. Then you could use /gallery/people/small/me.jpg to locate the image. PHP restrictions may prevent uploads.</div>';
        
        $cont .= HTMLHelper::createTextField("contact[pictureURL]",'URL to mugshot',(isset($this->contact->contact['pictureURL']) ? $this->contact->contact['pictureURL'] : ''),'edit-mugshot-text');
        
        if ($options->getOption('picAllowUpload')) {
           $cont .= '<div class="edit-input-file">
                      <label for="contact[pictureData][file]">Upload mugshot</label>
                      <input type="file" name="contact[pictureData][file]" id="contact[pictureData][file]" />
                      </div>';
                      
           if (isset($this->contact->contact['pictureData']) && $this->contact->contact['pictureData'])
               $cont .= HTMLHelper::createCheckbox('contact[pictureData][remove]','Remove current mugshot',false,'edit-input-checkbox');
        }
        
        $cont .= '</fieldset>';
        
        return $cont;
    }    
   
    /**
    * create the fieldset that allows user to the names of the contact
    * @return string html-content
    */
function createNameFieldset() {
        
        $cont = '<fieldset class="edit-names">';
        $cont .= '<legend>Names</legend>';
        
        $cont .= '<div class="context-help" id="editHelp'.$this->htmlId.'"><a href="#" onclick="Element.hide(\'editHelp'.$this->htmlId.'\'); Effect.Appear(\'editHint'.$this->htmlId.'\',{duration:1.2}); return false;">?</a></div><div class="context-help" id="editHint'.($this->htmlId++).'" style="display: none;">A Last Name/Company Name is required for an entry to exist. If you need an additional company name for a contact use the groups on the bottom of the page. A contact can be assigned to multiple groups.</div>';
        
        $cont .= HTMLHelper::createTextField('contact[lastname]','Last name or company', isset($this->contact->contact['lastname']) ? $this->contact->contact['lastname'] : '','edit-input');
        $cont .= HTMLHelper::createTextField('contact[firstname]','First name', isset($this->contact->contact['firstname']) ? $this->contact->contact['firstname'] : '','edit-input');
        $cont .= HTMLHelper::createTextField('contact[middlename]','Other name(s)', isset($this->contact->contact['middlename']) ? $this->contact->contact['middlename'] : '','edit-input');
        $cont .= HTMLHelper::createTextField("contact[namePrefix]",'Prefixes', isset($this->contact->contact['namePrefix']) ? $this->contact->contact['namePrefix'] : '','edit-input');
        $cont .= HTMLHelper::createTextField("contact[nameSuffix]",'Suffixes', isset($this->contact->contact['nameSuffix']) ? $this->contact->contact['nameSuffix'] : '','edit-input');
        $cont .= HTMLHelper::createTextField("contact[nickname]",'Other (nickname, company)', isset($this->contact->contact['nickname']) ? $this->contact->contact['nickname'] : '','edit-input');
        $cont .= HTMLHelper::createDropdown("contact[sex]",'Sex',array('blank'=>'N/A','female'=>'female','male'=>'male'),(isset($this->contact->contact['sex']) ? $this->contact->contact['sex'] : 'blank'),'edit-input');
        
        $cont .= '</fieldset>';
        
        return $cont;
        
    }
    
    /**
    * create the fieldset that allows user to the addresses of the contact
    * @global array list of country names and acronyms
    * @global Options used to determine the country default
    * @return string html-content
    */
function createAddressFieldset() {
        
        global $country, $options;
        
        $cont = '<fieldset class="edit-address">';
        $cont .= '<legend>Addresses</legend>';
        
        $cont .= '<div class="context-help" id="editHelp'.$this->htmlId.'"><a href="#" onclick="Element.hide(\'editHelp'.$this->htmlId.'\'); Effect.Appear(\'editHint'.$this->htmlId.'\',{duration:1.2}); return false;">?</a></div><div class="context-help" id="editHint'.($this->htmlId++).'" style="display: none;">All fields are optional. An address will be saved if either of the following are provided: type, address lines, city, state, zip code, or a phone number. If Primary Address is selected, the address will be displayed in the contact list. To obtain more than 2 additional blank address sections, save this entry and edit it again.</div>';
        
        $addr = $this->contact->getValueGroup('addresses');
        
        $n = max(count($addr),1); // initially 3 blank adresses
        
        // write out all existing addresses plus 2 blank address
        for($i=0; $i<$n+2; $i++) {
            
            $a = (array_key_exists($i,$addr) ? $addr[$i] : null); // generate additional blank entries 
            
            if($i>=$n) // hide the blank addresses by default
                $cont .= '<div class="edit-single-address" id="anotherAddress'.$i.'" style="display: none;"><br>';
            else
                $cont .= '<div class="edit-single-address">';
            
            if (!$this->add)
                $cont .= '<input type="hidden" name="address[' . $i . '][refid]" value="' . $a['refid'] . '" />';
            
            $cont .= '<div class="edit-line">';
            $cont .= HTMLHelper::createTextField("address[$i][type]",'Type',$a['type'],'edit-input');
            $cont .= HTMLHelper::createRadioButton("address_primary",'Set as primary address',$i,isset($this->contact->contact['primaryAddress']) && $a['refid'] == $this->contact->contact['primaryAddress'],'edit-input-radio');
            if(!$i>=$n)
                $cont .= '<div class="edit-input-link"><a href="javascript:deleteAddress(' . $i . ');">Delete this address</a></div>';
            $cont .= '</div>';
            
            $cont .= '<div class="edit-line">';
            $cont .= HTMLHelper::createTextField("address[$i][line1]",'Address (Line 1)',$a['line1'],'edit-input');
            $cont .= HTMLHelper::createTextField("address[$i][line2]",'Address (Line 2)',$a['line2'],'edit-input');
            $cont .= '</div>';
            
            $cont .= '<div class="edit-line">';
            $cont .= HTMLHelper::createTextField("address[$i][city]",'City',$a['city'],'edit-input');
            $cont .= HTMLHelper::createTextField("address[$i][state]",'State',$a['state'],'edit-input');
            $cont .= HTMLHelper::createTextField("address[$i][zip]",'Zip-code',$a['zip'],'edit-input');
            $cont .= '</div>';
            
            $cont .= '<div class="edit-line">';
            $cont .= HTMLHelper::createTextField("address[$i][phone1]",'Phone 1',$a['phone1'],'edit-input');
            $cont .= HTMLHelper::createTextField("address[$i][phone2]",'Phone 2',$a['phone2'],'edit-input');
            $cont .= HTMLHelper::createDropdown("address[$i][country]",'Country',$country,($a === null?$options->getOption('countryDefault'):$a['country']),'edit-input');
            $cont .= '</div>';
            
            if($i>=$n-1 && $i!=$n+1) // include the add more link in the previous div
                $cont .= "\n".'<div id="addAddressLink'.($i+1).'"><a href="#" onclick="Element.hide(\'addAddressLink'.($i+1).'\'); Effect.SlideDown(\'anotherAddress'.($i+1).'\',{duration:1.2}); return false;">add address</a></div>';
                            
            $cont .= '</div>';            
        }
                
        $cont .= '</fieldset>';
        
        return $cont;
    }
    
    /**
    * create a textarea for an arbitrary value group
    *
    * the values of the group are separated by |'s
    * @param string $type which value group of the contact is to be shown
    * @param integer $cols how many columns should the textarea have
    * @return string html-content
    */
function createValueGroupTextarea($type,$cols) {
        
        $v = '';
        $vals = $this->contact->getValueGroup($type);
        
        foreach ($vals as $m)
            $v .= "{$m['value']}|{$m['label']}" . ($m['visibility']=='hidden'?'|h':'') . ($m['visibility']=='admin-hidden'?'|a':'') ."\n";
        
        $cont .= HTMLHelper::createTextarea($type,$v,count($vals)+2,$cols,null,false);
        
        return $cont;
        
    }
        
    // private
function editHint($type='')
    {
        switch($type)
        {
            case 'date':
            return '<div class="context-help" id="editHelp'.$this->htmlId.'"><a href="#" onclick="Element.hide(\'editHelp'.$this->htmlId.'\'); Effect.Appear(\'editHint'.$this->htmlId.'\',{duration:1.2}); return false;">?</a></div><div class="context-help" id="editHint'.($this->htmlId++).'" style="display: none;">
            <div>Each entry is of the form: repeat, label, start date, end date, and view permissions. REPEAT selects how a date entry repeats. Weekly, mothly and yearly only is meaningful with one day events. Autoremove means the event will be automatically removed from the database after the end date. Then enter an arbitrary string as LABEL. START DATE equal to ? means already ongoing. An END DATE equal to ? is ongoing forever after the start date, a ONE DAY EVENT has a blank end date. Dates are entered in the format YYYY-MM-DD: 2007-04-31. Viewing permissions can be 
            public (everyone can see the item, users and managers can modify it), private (only the user and admin can see and modify the item), admin (user can view but only an admin can modify an item).</div></div>';        
        }
        
        return '<div class="context-help" id="editHelp'.$this->htmlId.'"><a href="#" onclick="Element.hide(\'editHelp'.$this->htmlId.'\'); Effect.Appear(\'editHint'.$this->htmlId.'\',{duration:1.2}); return false;">?</a></div><div class="context-help" id="editHint'.($this->htmlId++).'" style="display: none;">
        <div>Each entry is of the form: group, label, value, and view permissions. The group selects where information is placed on the output. For example all emails are placed together. Select the label to qualify an entry. For example for phonenumbers you could use fax or cell. Viewing permissions can be 
        public (everyone can see the item, users and managers can modify it), private (only the user and admin can see and modify the item), admin (user can view but only an admin can modify an item).</div></div>';        
    }
    
    /**
     * Returns true on success false on errors (contact NOT saved -> check errorHandler then)
     */
function saveContactFromPost(&$contact,&$post,$pictureFile=null,$adminsave=false)
    {
        $this->contact = &$contact; // force by reference
        $post['URLtoMugshot']=$pictureFile;
        return $this->contact->saveContactFromArray(StringHelper::cleanGPC($post),$adminsave);
    }
}

?>
