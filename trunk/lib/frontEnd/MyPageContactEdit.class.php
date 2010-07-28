<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

require_once('PageContactEdit.class.php');

class MyPageContactEdit extends PageContactEdit {
    /**
    * create the fieldset that allows user to the names of the contact
    * @return string html-content
    */
function createNameFieldset() {
        
        $cont = '<fieldset class="edit-names">';
        $cont .= '<legend>Names</legend>';
        
        $cont .= $this->contextHelp('name');
        
        $cont .= HTMLHelper::createTextField("contact[namePrefix]",'Prefixes', isset($this->contact->contact['namePrefix']) ? $this->contact->contact['namePrefix'] : '','edit-input');
        $cont .= HTMLHelper::createTextField("contact[nickname]",'Other (nickname, company)', isset($this->contact->contact['nickname']) ? $this->contact->contact['nickname'] : '','edit-input');
        $cont .= HTMLHelper::createTextField('contact[firstname]','First name', isset($this->contact->contact['firstname']) ? $this->contact->contact['firstname'] : '','edit-input');
        $cont .= HTMLHelper::createTextField('contact[middlename]','Other name(s)', isset($this->contact->contact['middlename']) ? $this->contact->contact['middlename'] : '','edit-input');
        $cont .= HTMLHelper::createTextField('contact[lastname]','Last name or company', isset($this->contact->contact['lastname']) ? $this->contact->contact['lastname'] : '','edit-input');
        $cont .= HTMLHelper::createTextField("contact[nameSuffix]",'Suffixes', isset($this->contact->contact['nameSuffix']) ? $this->contact->contact['nameSuffix'] : '','edit-input');
        $cont .= HTMLHelper::createDropdown("contact[sex]",'Sex',array('blank'=>'N/A','female'=>'female','male'=>'male'),(isset($this->contact->contact['sex']) ? $this->contact->contact['sex'] : 'blank'),'edit-input');
        $cont .= HTMLHelper::createTextField("contact[geboortedatum]",'Geboortedatum', isset($this->contact->contact['geboortedatum']) ? $this->contact->contact['geboortedatum'] : 'jjjj-mm-dd','edit-input');
                
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
            
            //$cont .= '<div class="edit-line">';
            //$cont .= HTMLHelper::createTextField("address[$i][type]",'Address type',$a['type'],'edit-input');
            //$cont .= HTMLHelper::createRadioButton("address_primary",'Set as primary address',$i,isset($this->contact->contact['primaryAddress']) && $a['refid'] == $this->contact->contact['primaryAddress'],'edit-input-radio');
            //$cont .= '</div>';
            
            $cont .= '<div class="edit-line">';
            $cont .= HTMLHelper::createTextField("address[$i][line1]",'Address (Line 1)',$a['line1'],'edit-input');
            if($i<$n)
                $cont .= '<div class="edit-input-link"><a href="javascript:deleteAddress(' . $i . ');">Delete this address</a></div>';
            $cont .= '</div>';
            
            $cont .= '<div class="edit-line">';
            $cont .= HTMLHelper::createTextField("address[$i][line2]",'Address (Line 2)',$a['line2'],'edit-input');
            $cont .= HTMLHelper::createTextField("address[$i][zip]",'Zip-code',$a['zip'],'edit-input');
            $cont .= HTMLHelper::createTextField("address[$i][city]",'City',$a['city'],'edit-input');
            $cont .= '</div>';
            
            $cont .= '<div class="edit-line">';
            //            $cont .= HTMLHelper::createTextField("address[$i][phone1]",'Phone 1',$a['phone1'],'edit-input');
            //            $cont .= HTMLHelper::createTextField("address[$i][phone2]",'Phone 2',$a['phone2'],'edit-input');
            $cont .= HTMLHelper::createTextField("address[$i][state]",'State',$a['state'],'edit-input');
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
    
}
?>
