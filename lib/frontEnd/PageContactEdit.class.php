<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageContactEdit}
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
require_once('ContactImportExport.class.php');
require_once('Navigation.class.php');
require_once('RightsManager.class.php');
require_once('HTMLHelper.class.php');
require_once('XSLTUtility.class.php');
require_once('FileHelper.class.php');

/**
* the contact edit page
* 
* the contact edit page allows users to
* add, edit and remove contact entries
* @package frontEnd
* @subpackage pages
*/
class PageContactEdit extends Page {
    
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
    * @var boolean whether the contact is to be added or to be changed
    */
    var $enableXSLTProcessing;
    
    var $counters; // private (counters array for value group POST)
    
    /**
    * Constructor: ONLY TO BE CALLED like this: Page::newPage(classname,$id,$add) factory method!! 
    * 
    * @param $idOrContact integer|Contact the id of the contact, or the contact that is to be edited
    * @param $add boolean whether the contact is to be added or not (cannot be detected through {@link $id}, because a contact can be passed if an error occurs to preserve already inserted information)
    * @param $xsltProcessing boolean allows to deactivate XSLT processing if FALSE. default: TRUE
    * @global Options admin options
    */
function PageContactEdit($idOrContact,$add=false,$enableXSLTProcessing=TRUE) {
        global $options;
        
        $this->counters = array();
        
        $this->add = $add;
        $this->enableXSLTProcessing = $enableXSLTProcessing;
        
        if ($idOrContact === null)
        {
            $this->contact = Contact::newContact();
            $this->add = TRUE;
        }
        elseif (is_numeric($idOrContact))
            $this->contact = Contact::newContact($idOrContact);
        else
            $this->contact = &$idOrContact;
            
        // MANDATORY SECURITY CHECK IN CONSTRUCTOR OF EACH PAGE
        $rightsManager = RightsManager::getSingleton();
        if($add)
        {
            if(!$rightsManager->currentUserIsAllowedTo('create'))
                ErrorHandler::getSingleton()->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
                
            $this->Page('Add new entry');
        }
        else
        {
            if(!$rightsManager->currentUserIsAllowedTo('edit',$this->contact))
                ErrorHandler::getSingleton()->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));
                
            $this->Page($this->contact->contact['firstname'] . ' ' . $this->contact->contact['lastname']);
        }
        
        $this->menu = new Navigation('edit-menu');
        
        // disable save when XSLT will be processed. XSLT files MUST provide their own save button.
        if(!($this->enableXSLTProcessing && !empty($this->contact->contact['xsltDisplayType'])))
            $this->menu->addEntry('save','save','javascript:saveEntry();');
            
        if (isset($this->contact->contact['id']))
            $this->menu->addEntry('cancel','cancel','?id=' . $this->contact->contact['id']);
        else
            $this->menu->addEntry('cancel','cancel',Navigation::previousPageUrl());
            
        if (!$this->add) {
            $rightsManager = RightsManager::getSingleton();
            if($rightsManager->mayDeleteContact($this->contact)) {
                $this->menu->addEntry('delete','delete','javascript:deleteEntry(' . $this->contact->contact['id'] . ');');
                if ($_SESSION['user']->isAtLeast('admin') && $options->getOption('deleteTrashMode'))
                    $this->menu->addEntry('trash','trash','?mode=trash&amp;id=' . $this->contact->contact['id']);
            }
        }
            
        if($_SESSION['user']->isAtLeast('admin')) // no putting on changed list
            $this->menu->addEntry('adminsave','[adminsave]','javascript:adminsaveEntry();');
    }
    
    /**
    * creates the XSLT generated output (HTML) for editing the contact
    */
function createXSLTEditPage($transformationFile)
    {
        $xml = ContactImportExport::xmlExport($this->contact,$this->add);
        $x = new XSLTUtility($xml);        
        return $x->transform($transformationFile);
    }
    
    /**
    * create the content of admin panel
    * @return string html-content
    * @global ErrorHandler used for error handling
    * @global PluginManager used for plugin handling (i.e. additional editable fields)
    * @uses createXSLTContact()
    * @uses createMandatoryPropertyFields()
    * @uses createNameFieldset()
    * @uses createAddressFieldset()
    * @uses createProperties()
    * @uses createMugshotFieldset()
    * @uses createNotesFieldset()
    * @uses createGroupsFieldset()
    */
function innerCreate()
    {
        global $errorHandler;
        
        $outer = '<div class="edit-container">';
        $outer .= $this->menu->create();
        $outer .= '<div class="clear-both"></div>';
        
        // is there a XSLT stylesheet to display the contact?
        if($this->enableXSLTProcessing && !empty($this->contact->contact['xsltDisplayType']))
        {
            $transformationFile = 'lib/xslt/' . $this->contact->contact['xsltDisplayType'] . '-edit.xsl';
            
            if(file_exists($transformationFile))
                $cont = $this->createXSLTEditPage($transformationFile);
            else
                $errorHandler->warning('File not found: '.$transformationFile,basename($_SERVER['SCRIPT_NAME']));
        }
        
        if(!isset($cont))
            $cont = $this->createNormalEditPage();
        
        return $outer .  HTMLHelper::createNestedDivBoxModel('edit-content',$cont) . '</div><script type="text/javascript">widgInit();</script>'; // manual init; fails in FF if onload is already set
        
    }
    
function createNormalEditPage()
    {
        global $errorHandler,$pluginManager;
        
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
        $this->valueGroups['phone'][-1] = $this->contact->getValueGroup('phone',null);
        $this->valueGroups['email'][-1] = $this->contact->getValueGroup('email',null);
        $this->valueGroups['chat'][-1] = $this->contact->getValueGroup('chat',null);
        $this->valueGroups['www'][-1] = $this->contact->getValueGroup('www',null);
        $this->valueGroups['other'][-1] = $this->contact->getValueGroup('other',null);

        //================ MANDATORY ================
        // EXAMPLE for ADDING MANDATORY/SUGGESTED fields: uncomment the following code block
        // I suggest you put the class into MyPageContactEdit.class.php and replace this class (see config.php)
        $cont .= $this->createMandatoryPropertyFields();
        
        // ========== Communications ==========
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Communications</legend>';
        $cont .= $this->contextHelp('property');
        $opt = array('email'=>'email','phone'=>'phone','chat'=>'chat',' '=>'delete');
        $cont .= $this->createNormalProperties($opt,'email');
        $cont .= $this->createNormalProperties($opt,'phone');
        $cont .= $this->createNormalProperties($opt,'chat');
        $cont .= $this->createNormalProperties($opt,'blank');
        $cont .= '</fieldset>';
        
        // ========== Information ==========
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Information</legend>';
        $cont .= $this->contextHelp('property');
        $opt = array('other'=>'other','www'=>'url',' '=>'delete');
        $cont .= $this->createNormalProperties($opt,'www');
        $cont .= $this->createNormalProperties($opt,'other');
        $cont .= $this->createNormalProperties($opt,'blank');
        $cont .= '</fieldset>';
        
        // ========== Dates ==========
        $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Dates</legend>';
        $cont .= $this->contextHelp('date');
        $opt = array('yearly'=>'yearly','monthly'=>'monthly','weekly'=>'weekly','once'=>'once', 'autoremove'=>'autoremove', ' '=>'delete');
        $cont .= $this->createDates($opt,'date');
        $cont .= $this->createDates($opt,'blank_date');
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
            
        return $cont;
    }
    
    /**
    * create inputs that the user must fill out to be able to save the contact.
    * NOTE: Override this method in your own MyContactEdit class! See also class posing (hacking) in config.php
    */
function createMandatoryPropertyFields()
    {
        $cont='';
        
        /* $cont .= '<fieldset class="edit-additionals">';
        $cont .= '<legend>Mandatory Information</legend>';
        $cont .= $this->createFixedPropertyInput('other','IQ','visible',FALSE);
        $cont .= $this->createFixedPropertyInput('other','Weight','visible',FALSE);
        $cont .= $this->createFixedPropertyInput('chat','ICQ','visible',FALSE);
        $cont .= '</fieldset>'; */
        
        return $cont;
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
function createNormalProperties($options,$type,$refid=null,$w2=90,$w3=280,$w4=80,$w5=80)
    {

        $rightsManager = RightsManager::getSingleton();
        
        if($type == 'blank')
        {
            $dummy = array('type'=>$type,'label'=>'','value'=>'','visibility'=>'visible','refid' => null);
            $vals = array($dummy,$dummy); // 2 blanks
        }
        else // use the array cached before
            $vals = $this->valueGroups[$type][($refid === null?-1:$refid)];

        $cont = '';
        if(is_array($vals)) // VG may be empty
            foreach($vals as $m)
            {
                if($m==null)
                    continue;
                
                if($m['visibility']=='admin-hidden' && !$_SESSION['user']->isAtLeast('admin') || $m['visibility']=='hidden' && !$rightsManager->mayViewPrivateInfo($this->contact)) // do not show
                    continue;
                
                $cont .= $this->createNormalPropertyInput($options,$type,$m['label'],$m['value'],$m['visibility'],(isset($m['refid']) ? $m['refid'] : null),TRUE,$w2,$w3,$w4,$w5);
            }
        
        return $cont;
    }
    
    /**
    * Creates a normal (not a date range) property input line.
    * @param array $options selections shown in dropdown
    * @param string $type type of the entry (shown in a dropdown)
    * @param string $label Contact property label
    * @param string $value Contact property value
    * @param string $visibility selected visibility
    * @param string $showVisibility displays dropdown selector if TRUE
    * @param integer css width of input field2 (input field 1 class='edit-property-select')
    * @param integer css width of input field3
    * @param integer css width of input field4
    * @return string html-content
    */
function createNormalPropertyInput($options,$type,$label,$value,$visibility,$refid,$showVisibility=TRUE)
    {
        if(!array_key_exists($type,$this->counters))
            $this->counters[$type]=0;
            
        $c = $this->counters[$type]++;
        
        $cont = "\n<div class='edit-propertyset'>" . HTMLHelper::createDropdown($type."[$c][type]",'',$options,$type,'edit-property-select',false);
        $cont .= "<input class='edit-property-label' type='text' name='".$type."[$c][label]' value='$label'/>\n";
        $cont .= "<input class='edit-property-value' type='text' name='".$type."[$c][value]' value='$value'/>\n";
        
        $vo = $this->visibilityOptions();
        if($showVisibility==TRUE && count($vo)>1)
            $cont .= HTMLHelper::createDropdown($type."[$c][visibility]",'',$vo,$visibility,'edit-property-visibility',false);
        else
            $cont .= "<input type='hidden' name='".$type."[$c][visibility]' value='$visibility'/>\n";
        
        $cont .= HTMLHelper::createDropdown($type."[$c][refid]",'',$this->addressDropdownOptions(),$refid,'edit-property-refid',false);
        
        $cont .= "<br></div>\n";                                                    
                    
        return $cont;
    }


    /**
     * Returns
     * refid => type (City)
     * or
     * refid => city
     * for all adresses
     * @return array data
     */
function addressDropdownOptions() {
        
        static $data = null;

        if ($data !== null)
            return $data;

        $data = array(null => 'none');
        $val = $this->contact->getValueGroup('addresses');

        if(isset($a['refid'])) // $a['refid'] is empty if save of contact fails
        {
            foreach ($val as $a) {
                if ($a['type'] && $a['city'])
                    $data[$a['refid']] = $a['type'] . ' (' . $a['city'] . ')';
                elseif ($a['type'])
                    $data[$a['refid']] = $a['type'];
                else
                    $data[$a['refid']] = $a['city'];
            }
        }
        
        return $data;
    }

    /**
    * Creates a fixed, normal (not a date range) property input line. This is used for mandatory/suggested properties.
    * The property is removed from $this->valueGroups so it does not show again in createNormalProperties()
    * field1 and field2 are readonly inputs, although one could manipulate the post, so there is no strong protection.
    * @param array $options selections shown in dropdown
    * @param string $type type of the entry (not necessarily shown in dropdown)
    * @param string $label Contact property label
    * @param string $value Contact property value
    * @param string $visibility selected visibility
    * @param string $showVisibility displays dropdown selector if TRUE
    * @param integer css width of input field2 (input field 1 class='edit-property-select')
    * @param integer css width of input field3
    * @param integer css width of input field4
    * @return string html-content
    * @todo Translate the type of the fixed property with Localizer.
    */
function createFixedPropertyInput($type,$label,$visibility,$showVisibility=TRUE)
    {
        if(!array_key_exists($type,$this->counters))
            $this->counters[$type]=0;
            
        $value='';
        
        foreach($this->valueGroups[$type][-1] as &$v)
            if($v['label']==$label) // search and remove if found from VG
            {
                $value=$v['value'];
                $v=null;
                break;
            }
        
        $c = $this->counters[$type]++;
        
        $cont = "\n<div class='edit-propertyset'>";
        $cont .= "<input type='text' readonly name='".$type."[$c][type]' value='$type' class='edit-property-select'/>\n";
        $cont .= "<input type='text' readonly name='".$type."[$c][label]' value='$label'/>\n";
        $cont .= "<input type='text' name='".$type."[$c][value]' value='$value'/>\n";
        
        $vo = $this->visibilityOptions();
        if($showVisibility==TRUE && count($vo)>1)
            $cont .= HTMLHelper::createDropdown($type."[$c][visibility]",'edit-property-visibility',$vo,$visibility,null,false);
        else
            $cont .= "<input type='hidden' name='".$type."[$c][visibility]' value='$visibility'/>\n";
            
        $cont .= "<input type='hidden' name='".$type."[$c][refid]' value='none'/>\n";
            
        $cont .= "<br></div>\n";                                                    
        
        return $cont;
    }

function visibilityOptions()
    {
        $visibilityOptions = array('visible'=>'public'); // default (actually used for manager)
        
        $rightsManager = RightsManager::getSingleton();
        if($rightsManager->mayViewPrivateInfo($this->contact)) // users and admins add private option
            $visibilityOptions['hidden'] = 'private';
        
        if($_SESSION['user']->isAtLeast('admin'))  // only admin can attach info private to user, changeable by admin, hidden from manager
            $visibilityOptions['admin-hidden'] = 'admin';
        
        return $visibilityOptions;
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
function createDates($options,$type) {
        
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
            $vals = $this->contact->getValueGroup($type,null);

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
            $cont .= "\n<div class='edit-propertyset'>" . HTMLHelper::createDropdown($type."[$c][type]",'',$options,$m['type'],'edit-date-select',false);
            $cont .= "\n<input class='edit-date-label' type='text' name='".$type."[$c][label]' value='{$m['label']}'/>\n";

            // generate value1 and value2 inputs
            for($i=1;$i<3;$i++) 
            {
                $value = htmlspecialchars($m["value$i"]);
                if($type == 'date') // special values of date
                {
                    if($m["value$i"] === null) // unlimited date
                        $value = '?';
                    else
                    if($m["value$i"] == '0000-00-00') // no date (one day)
                        $value = '';
                }
                
                $cont .= "<input class='edit-date-value' type='text' name='".$type."[$c][value$i]' value='" . $value . "'/>\n";
            }
            
            $cont .= HTMLHelper::createDropdown($type."[$c][visibility]",'',$this->visibilityOptions(),$m['visibility'],'edit-date-visibility',false);
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
        
        if($_SESSION['user']->isAtLeast('admin'))  // only admin can hide/change XSL (managers cannot see hidden contacts)
        {
            global $CONFIG_INSTALL_SUBDIR;
            // find .xls files without dash (type-edit.xsl)
            $func = create_function('$val','return strpos($val,"-")===FALSE;');
            $files = array_filter(FileHelper::scanDirectory($CONFIG_INSTALL_SUBDIR . 'lib/xslt','.xsl'),$func);
            
            if(count($files)>0) // no need to display dropdown if no XSL files exist
            {
                FileHelper::removeExtensions($files);
                $files = array_merge(array(''),$files);
                
                $xs = isset($this->contact->contact['xsltDisplayType']) ? $this->contact->contact['xsltDisplayType'] : '';
                
                $cont .= HTMLHelper::createDropdownValuesAreKeys('contact[xsltDisplayType]','XSLT stylesheet',$files,$xs,'edit-input',false);
            }
            
            $cont .= HTMLHelper::createCheckbox('contact[hidden]','Hide this entry',(isset($this->contact->contact['hidden']) ? $this->contact->contact['hidden'] : 0),'edit-input-checkbox-hide');
        }
        
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
        
        $cont .= $this->contextHelp('notes');
        
        $notes = isset($this->contact->contact['notes']) ? $this->contact->contact['notes'] : '';
        $n=count(explode("\n",$notes));
        //widgEditor cannot handle contact[notes] as ID -it submits also contact[notes]WidgEditor = true (confuses PHP)
        $cont .= HTMLHelper::createTextarea('contactNotes',$notes,$n+2,66,'widgEditor',false);
        
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

        $cont .= $this->contextHelp('mugshot');
        
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
        
        $cont .= $this->contextHelp('name');
        
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
    * @TODO If a contact gets bounced (missing mandatory value) 2 additional unhidden blank addresses show. This is because in the previous step 3 blank addresses were created and by reediting the same contact without storing it the blank addresses are not removed.
    */
function createAddressFieldset()
    {
        global $country, $options;
        
        $cont = '<fieldset class="edit-address">';
        $cont .= '<legend>Addresses</legend>';
        
        $cont .= $this->contextHelp('address');
        
        $addr = $this->contact->getValueGroup('addresses');
        
        $n = max(count($addr),1); // initially 3 blank adresses
        
        // write out all existing addresses plus 2 blank address
        for($i=0; $i<$n+2; $i++)
        {
            $a = (array_key_exists($i,$addr) ? $addr[$i] : null); // generate additional blank entries 
            
            if(!isset($a['refid'])) // if someone leaves the lastname blank we reedit the same contact - would cause tons of warnings
                $a = null;
            
            if ($a !== null) {
                $this->valueGroups['phone'][$a['refid']] = $this->contact->getValueGroup('phone',$a['refid']);
                $this->valueGroups['email'][$a['refid']] = $this->contact->getValueGroup('email',$a['refid']);
                $this->valueGroups['chat'][$a['refid']] = $this->contact->getValueGroup('chat',$a['refid']);
                $this->valueGroups['www'][$a['refid']] = $this->contact->getValueGroup('www',$a['refid']);
                $this->valueGroups['other'][$a['refid']] = $this->contact->getValueGroup('other',$a['refid']);
            }
            
            if($i>=$n) // hide the blank addresses by default
                $cont .= '<div class="edit-single-address" id="anotherAddress'.$i.'" style="display: none;"><br>';
            else
                $cont .= '<div class="edit-single-address">';
            
            if (!$this->add)
                $cont .= '<input type="hidden" name="address[' . $i . '][refid]" value="' . $a['refid'] . '" />';
            
            $cont .= '<div class="edit-line">';
            $cont .= HTMLHelper::createTextField("address[$i][type]",'Address type',$a['type'],'edit-input');
            $cont .= HTMLHelper::createRadioButton("address_primary",'Set as primary address',$i,isset($this->contact->contact['primaryAddress']) && $a['refid'] == $this->contact->contact['primaryAddress'],'edit-input-radio');
            if($i<$n)
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
            //            $cont .= HTMLHelper::createTextField("address[$i][phone1]",'Phone 1',$a['phone1'],'edit-input');
            //            $cont .= HTMLHelper::createTextField("address[$i][phone2]",'Phone 2',$a['phone2'],'edit-input');
            $cont .= HTMLHelper::createDropdown("address[$i][country]",'Country',$country,($a === null?$options->getOption('countryDefault'):$a['country']),'edit-input');
            $cont .= '</div>';

            if ($a !== null) {
                $cont .= '<div><label>Communications</label>';
                $opt = array('email'=>'email','phone'=>'phone','chat'=>'chat','other'=>'other','www'=>'url',' '=>'delete');
                $cont .= $this->createNormalProperties($opt,'email',$a['refid']);
                $cont .= $this->createNormalProperties($opt,'phone',$a['refid']);
                $cont .= $this->createNormalProperties($opt,'chat',$a['refid']);
                $cont .= $this->createNormalProperties($opt,'www',$a['refid']);
                $cont .= $this->createNormalProperties($opt,'other',$a['refid']);
                $cont .= '</div>';
            }
            
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
        $vals = $this->contact->getValueGroup($type,null);
        
        foreach ($vals as $m)
            $v .= "{$m['value']}|{$m['label']}" . ($m['visibility']=='hidden'?'|h':'') . ($m['visibility']=='admin-hidden'?'|a':'') ."\n";
        
        $cont .= HTMLHelper::createTextarea($type,$v,count($vals)+2,$cols,null,false);
        
        return $cont;
    }
    
    /**
     * Returns true on success false on errors (contact NOT saved -> check errorHandler then)
     */
function saveContactFromPost(&$contact,&$post,$pictureFile=null,$adminsave=false)
    {
        // interaction PHP/widgEditor
        $post['contact']['notes']=$post['contactNotes'];
        
        $this->contact = &$contact; // force by reference
        $post['URLtoMugshot']=$pictureFile;
        $p = StringHelper::cleanGPC($post);
        return $this->contact->saveContactFromArray($p,$adminsave);
    }
}

?>
