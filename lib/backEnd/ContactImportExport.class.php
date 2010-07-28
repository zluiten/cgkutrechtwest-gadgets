<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link ContactImportExport}
* @package backEnd
* @author Thomas Katzlberger
*/

/** */
require_once('Contact.class.php');
require_once('ContactImage.class.php');
require_once('AddressFormatter.class.php');

/**
* Represents a contact
*
* Handles all import and export of a contact from and to other formats.
* @package backEnd
*/
class ContactImportExport
{
    /**
    * Encode a contact as vCard version 3.0.
    * @param Contact $contact
    * @param string $type 'WORK' (default) or 'HOME' is the default type for addresses and phonennumbers
    * @return string vCard
    * @global $CONFIG_TAB_ROOT, $country
    */
function vCardExport($contact,$defaultType='WORK')
    {
        global $CONFIG_TAB_ROOT, $country;
        
        /* SEE: http://vcardmaker.wackomenace.co.uk/ (incorrectly escapes ':'!!), http://tools.ietf.org/html/rfc2426
         * Please post improvements to: http://sourceforge.net/tracker/?group_id=172286&atid=861164 as attachment!!
         * Or to the developer forums at: http://sourceforge.net/forum/forum.php?forum_id=590644
        */
        
        $type = $defaultType;
        $output = '';
        if($contact->contact['nickname']) $output .= 'NICKNAME:' . $contact->contact['nickname'] . "\n";
        //if($contact->contact['birthday'] != '0000-00-00') $output .= 'BDAY:' . ?? . "\n";
        
        $vg = $contact->getValueGroup('addresses');
        foreach($vg as $adr)
        {
            $lbl = strtolower($adr['type']);
            if(substr($lbl,0,4)=='home' || substr($lbl,0,7)=='private') // \TODO TRANSLATION
                $type = 'HOME';
            else
                $type = $defaultType; // reset
            
            $output .= 'ADR;TYPE=DOM,'.$type.',POSTAL:;' . $adr['line2'] . ';' . $adr['line1'] . ';' . $adr['city'] . ';' . $adr['state'] . ';' . $adr['zip']  . ';' . $country[$adr['country']] . "\n"; // \TODO modifier ,PREF before : for primary
            if($adr['phone1'])
                $output .= 'TEL;TYPE='.$type.',VOICE:' . $adr['phone1'] . "\n";
            if($adr['phone2'])
                $output .= 'TEL;TYPE='.$type.',VOICE:' . $adr['phone2'] . "\n";
        }
                
        $type = $defaultType; // reset

        /* tel-type     = "HOME" / "WORK" / "PREF" / "VOICE" / "FAX" / "MSG"
                / "CELL" / "PAGER" / "BBS" / "MODEM" / "CAR" / "ISDN"
                / "VIDEO" / "PCS" / iana-token / x-name */
        
        $vg = $contact->getValueGroup('phone');
        foreach($vg as $v)
        {
            if($v['visibility'] != 'visible')
                continue;

            $t = $type;
            $n = $v['value'];
            $number = $n;
            $service = $v['label'];
            
            if(substr($service,0,4) == 'sips')
            {
                $t = 'PAGER';
                $number = ContactImportExport::vCardEscape("sips:$n");
            }
            else // See: http://www.voip-info.org/wiki/view/SIP+URI
            if(substr($service,0,3) == 'sip' || substr($service,0,4) == 'voip')
            {
                $t = 'PAGER'; // there is no SIP defined in vCard
                $number = ContactImportExport::vCardEscape("sip:$n");
            }
            else
            if(substr($service,0,3)=='fax')
                $t = 'FAX';
            else
            if(substr($service,0,4) == 'cell' || substr($service,0,6) == 'mobile')
                $t = 'CELL';
            else
            if(substr($service,0,5) == 'video')
                $t = 'VIDEO';
                
            $output .= 'TEL;TYPE='.$t.':' . ContactImportExport::vCardEscape($number) . "\n";
        }
            
        $department = '';
        $prefixes = ';' . $contact->contact['namePrefix'];
        $suffixes = ';' . $contact->contact['nameSuffix']; // CORRECT vCARD version does not work with M$: invisible postfix title
        //$suffixes = ',' . $contact->contact['nameSuffix']; // OUTLOOK version (this joins $prefixes and $postfixes)
        $vg = $contact->getValueGroup('other');
        foreach($vg as $v)
        {
            if($v['visibility'] != 'visible')
                continue;
                
            $n = ContactImportExport::vCardEscape($v['value']);
            $l = strtolower($v['label']);
            
            if($l == 'job title' || $l == 'occupation')
                $output .= 'TITLE:' . $n . "\n";
            else
            if($l == 'function' || $l == 'role')
                $output .= 'ROLE:' . $n . "\n";
            else
            if($l == 'department')
                $department = ";$n";
            else
            if($l == 'academic title') // alternate (incomplete way) to specify titles
            {
                if($n == 'BS' || $n == 'BA' || $n == 'MS' || $n == 'MA' || $n == 'MD' || $n == 'MBA' || $n == 'PhD')
                    $suffixes .= (strlen($prefixes)<=1) ? $n : ",$n";
                else
                    $prefixes .= (strlen($prefixes)<=1) ? $n : ",$n";
            }
        }
        
        $output .= 'ORG:' . $contact->groups(null,false,'groupname',false) . $department . "\n";
        
        $vg = $contact->getValueGroup('email');
        foreach($vg as $v)
            if($v['visibility'] == 'visible')
                $output .= 'EMAIL;TYPE=INTERNET,'.$type.':' . $v['value'] . "\n"; // could have ,PRIM modifier before :

        $vg = $contact->getValueGroup('www');
        foreach($vg as $v)
            if($v['visibility'] == 'visible')
                $output .= 'URL:' . ContactImportExport::vCardEscape($v['value']) . "\n";
        
        // URI pointing to this TAB entry
        $output .= 'URL:' . ContactImportExport::vCardEscape($CONFIG_TAB_ROOT . 'contact/contact.php?id=' . $_GET['id']) . "\n";

        // Attach picture base64
        if(!empty($contact->contact['pictureData']))
            $output .= 'PHOTO;ENCODING=BASE64;TYPE=JPEG:' . base64_encode($contact->contact['pictureData']) . "\n";
        
        // Attach picture URL
        if(!empty($contact->contact['pictureURL']))
            $output .= 'PHOTO;VALUE=URL:' . ContactImportExport::vCardEscape($contact->contact['pictureURL']) . "\n";     
        
        $output .= "END:VCARD\n";
        $output .= "\n";                

        $head = "BEGIN:VCARD\nVERSION:3.0\n";
        $head .= 'FN:' . $contact->contact['namePrefix'] . ' ' . $contact->contact['firstname'] . ' ' . $contact->contact['lastname'] . ' ' . $contact->contact['nameSuffix'] . "\n";
        $head .= 'N:' . $contact->contact['lastname'] . ';' . $contact->contact['firstname'] . ';' . $contact->contact['middlename'] . $prefixes . $suffixes . ";\n";

        return mb_convert_encoding($head . $output,'ISO-8859-1');
    }

    /**
    * Decode a contact as vCard version 3.0.
    * @param string $vCardString string holding the vCard text
    * @return Contact $contact already stored in database or NULL on error
    */
function vCardImport($vCardString)
    {
        global $errorHandler;
        
        require_once('lib/vcard/vcardclass.inc');
        
        $vc = new VCARD('3.0');
        $vc->setvCard($vCardString);
        if($vc->lasterror_num != 0)
        {
            $errorHandler->error('import',$vc->lasterror_msg,basename($_SERVER['SCRIPT_NAME']));
            return null;
        }
        
        $data['contact']['lastname'] = $vc->getName('LAST'); 
        $data['contact']['firstname'] = $vc->getName('FIRST'); 
        $data['contact']['middlename'] = $vc->getName('MIDDLE'); 
        $data['contact']['namePrefix'] = $vc->getName('PREF'); 
        $data['contact']['nameSuffix'] = $vc->getName('SUFF'); 
        $data['contact']['nickname'] = $vc->getNickName(); 
        
        // ADDRESSES
        list($key,$a) = each($vc->getAdr('CITY','WORK','OR')); // this retrieval is strannnnge
        if($a!=null) {
            $data['address'][0]['type']  = 'work'; list($key,$a) = each($vc->getAdr('STREET','WORK','OR'));
            $data['address'][0]['line1'] = $a;     list($key,$a) = each($vc->getAdr('POBOX','WORK','OR'));
            $data['address'][0]['line2'] = $a;     list($key,$a) = each($vc->getAdr('CITY','WORK','OR'));
            $data['address'][0]['city']  = $a;     list($key,$a) = each($vc->getAdr('PROVINCE','WORK','OR'));
            $data['address'][0]['state'] = $a;     list($key,$a) = each($vc->getAdr('POSTAL','WORK','OR'));
            $data['address'][0]['zip']   = $a;/*     list($key,$a) = each($vc->getAdr('COUNTRY','WORK','OR'));*/
            //$data['address'][0]['country'] = $a['COUNTRY']; // decode how?
        }

        list($key,$a) = each($vc->getAdr('CITY','HOME','OR'));
        if($a!=null) {
            $data['address'][1]['type']  = 'home'; list($key,$a) = each($vc->getAdr('STREET','HOME','OR'));
            $data['address'][1]['line1'] = $a;     list($key,$a) = each($vc->getAdr('POBOX','HOME','OR'));
            $data['address'][1]['line2'] = $a;     list($key,$a) = each($vc->getAdr('CITY','HOME','OR'));
            $data['address'][1]['city']  = $a;     list($key,$a) = each($vc->getAdr('PROVINCE','HOME','OR'));
            $data['address'][1]['state'] = $a;     list($key,$a) = each($vc->getAdr('POSTAL','HOME','OR'));
            $data['address'][1]['zip']   = $a;/*     list($key,$a) = each($vc->getAdr('COUNTRY','HOME','OR'));*/
            //$data['address'][1]['country'] = $a['COUNTRY']; // decode how?
        }
         
        $i=0;
        // other data email OR www OR other OR chat OR phone;
        
        // 5. TELECOMMUNICATIONS ADDRESSING TYPES methods:
        $x = $vc->getTel('WORK'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'phone';
            $data['blank'][$i]['label'] =  'work';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getTel('HOME'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'phone';
            $data['blank'][$i]['label'] =  'home';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getTel('CELL'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'phone';
            $data['blank'][$i]['label'] =  'cell';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getTel('FAX'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'phone';
            $data['blank'][$i]['label'] =  'fax';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getTel('PAGER'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'phone';
            $data['blank'][$i]['label'] =  'pager';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getTel('VIDEO'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'phone';
            $data['blank'][$i]['label'] =  'video';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        // delivers only one result!!
        list($key,$x) = each($vc->getEmail('INTERNET','OR')); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'email';
            $data['blank'][$i]['label'] =  '';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        // 7. ORGANIZATIONAL TYPES methods:
        $x = $vc->getTitle(); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'other';
            $data['blank'][$i]['label'] =  'Job Title';
            $data['blank'][$i]['value'] =  $vc->getTitle();
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getRole(); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'other';
            $data['blank'][$i]['label'] =  'Role';
            $data['blank'][$i]['value'] =  $vc->getRole();
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getOrg(); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'other';
            $data['blank'][$i]['label'] =  'Organization';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        // cannot go into OU of SSL_CA bacause may not be admin importer here
        $x = $vc->getOrg('ORGUNIT'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'other';
            $data['blank'][$i]['label'] =  'Organizational Unit';
            $data['blank'][$i]['value'] =  $x; 
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        // what is ORGUNITS ???
        
        // 8. EXPLANATORY TYPES methods:
        $x = $vc->getUrl('WORK');
        $y = ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'www';
            $data['blank'][$i]['label'] =  'Work';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        $x = $vc->getUrl('HOME'); $x=ContactImportExport::vCardUnEscape($x);
        if(!empty($x)) {
            $data['blank'][$i]['type'] = 'www';
            $data['blank'][$i]['label'] =  'Home';
            $data['blank'][$i]['value'] =  $x;
            $data['blank'][$i++]['visibility'] =  'visible';
        }
        
        //$data['contact']['notes'] = $vc->getNote();
                
        // Attach picture ...         
        $url = $vc->getBinary('PHOTO','URL');
        if(!empty($url))
            $data['contact']['pictureURL'] = $url;
        
            
        //import_change_encoding(&$data);
        // save it to the DB
        $contact = Contact::newContact();
        
        // curretly this must go before save! When we have the Media class it may need to go after save (?)
        $pic = $vc->getBinary('PHOTO','JPEG');
        if(!empty($pic) && $options->getOption('picAllowUpload'))
        {
            $binaryPicture = base64_decode($pic); //  future:  $contact->setMedia('pictureData');
            $contact->setMedia('pictureData','image/jpeg',$binaryPicture);
        }
        
        $contact->saveContactFromArray($data);

        $errorHandler->success('Imported: ' . $data['contact']['firstname'] . ' ' . $data['contact']['lastname'],basename($_SERVER['SCRIPT_NAME']));
        
        return $contact;
    }

    // PRIVATE SECTION:
function vCardEscape($in) { return str_replace(";", "\;", str_replace(",", "\,", $in)); }
function vCardUnEscape($in) { return str_replace("\;", ";", str_replace("\,", ",", $in)); }    

    /**
    * Encode a contact as XML - similar to Jabber XML vCard
    * @param Contact $contact
    * @return string XML
    * @global $CONFIG_TAB_ROOT, $country
    */
function jabberXMLvCardExport($contact)
    {
        global $CONFIG_TAB_ROOT, $country;
        
        /* SEE Jabber XML vCard: http://www.xmpp.org/extensions/xep-0054.html
         * NOT COMPATIBLE TO THIS FORMAT: TEL inside ADR possible, TEL:TYPE not fixed as in vCard:
         * <TEL><TYPE>home</TYPE><NUMBER>12345</NUMBER></TEL>
         * Please post improvements to: http://sourceforge.net/forum/forum.php?forum_id=590644
        */
        
        $output = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
        $output = '<?xml-stylesheet href="vCard.css" type="text/css"?>'."\n";
        $output .= "<vCard xmlns='vcard-temp'>\n";
        $output .= '<FN>' . $contact->contact['namePrefix'] . ' ' . $contact->contact['firstname'] . ' ' . $contact->contact['lastname'] . ' ' . $contact->contact['nameSuffix'] . "</FN>\n";
        $output .= '<N><FAMILY>' . $contact->contact['lastname'] . '</FAMILY><GIVEN>' . $contact->contact['firstname'] . '</GIVEN><MIDDLE>' . $contact->contact['middlename'] .'</MIDDLE><PREFIX>'. $contact->contact['namePrefix'] .'</PREFIX><SUFFIX>'. $contact->contact['nameSuffix'] . "</SUFFIX></N>\n";
        
        if($contact->contact['nickname']) $output .= '<NICKNAME>' . $contact->contact['nickname'] . "</NICKNAME>\n";
        //if($contact->contact['birthday'] != '0000-00-00') $output .= 'BDAY:' . ?? . "\n";
        
        $vg = $contact->getValueGroup('addresses');
        foreach($vg as $adr)
        {
            $output .="<ADR>\n<TYPE>" . $adr['type'] ."</TYPE>\n";
            $output .='<EXTADD>' . $adr['line2'] ."</EXTADD>\n";
            $output .='<STREET>' . $adr['line1'] ."</STREET>\n";
            $output .='<LOCALITY>' . $adr['city'] ."</LOCALITY>\n";
            $output .='<REGION>' . $adr['state'] ."</REGION>\n";
            $output .='<PCODE>' . $adr['zip'] ."</PCODE>\n";
            $output .='<CTRY>' . $country[$adr['country']] ."</CTRY>\n";
            $output .='<CTRYCODE>' . $adr['country'] ."</CTRYCODE>\n";
            
            if($adr['phone1'])
                $output .='<TEL><TYPE>' . $adr['type'] .'</TYPE><NUMBER>' . $adr['phone1'] ."</NUMBER></TEL>\n";
            if($adr['phone2'])
                $output .='<TEL><TYPE>' . $adr['type'] .'</TYPE><NUMBER>' . $adr['phone2'] ."</NUMBER></TEL>\n";
                
            $output .="</ADR>\n";
        }
        
        $vg = $contact->getValueGroup('phone');
        foreach($vg as $v)
        {
            if($v['visibility'] != 'visible')
                continue;
                
            $output .='<TEL><TYPE>' . $v['label'] .'</TYPE><NUMBER>' . $v['value'] ."</NUMBER></TEL>\n";
        }
            
        /* $department = '';
        $prefixes = ';' . $contact->contact['namePrefix'];
        $suffixes = ';' . $contact->contact['nameSuffix']; // CORRECT vCARD version does not work with M$: invisible postfix title
        //$suffixes = ',' . $contact->contact['nameSuffix']; // OUTLOOK version (this joins $prefixes and $postfixes)
        $vg = $contact->getValueGroup('other');
        foreach($vg as $v)
        {
            if($v['visibility'] != 'visible')
                continue;
                
            $n = ContactImportExport::vCardEscape($v['value']);
            $l = strtolower($v['label']);
            
            if($l == 'job title' || $l == 'occupation')
                $output .= 'TITLE:' . $n . "\n";
            else
            if($l == 'function' || $l == 'role')
                $output .= 'ROLE:' . $n . "\n";
            else
            if($l == 'department')
                $department = ";$n";
            else
            if($l == 'academic title') // alternate (incomplete way) to specify titles
            {
                if($n == 'BS' || $n == 'BA' || $n == 'MS' || $n == 'MA' || $n == 'MD' || $n == 'MBA' || $n == 'PhD')
                    $suffixes .= (strlen($prefixes)<=1) ? $n : ",$n";
                else
                    $prefixes .= (strlen($prefixes)<=1) ? $n : ",$n";
            }
        }
        
        $output .= 'ORG:' . $contact->groups(null,false,'groupname',false) . $department . "\n"; */
        
        $vg = $contact->getValueGroup('email');
        foreach($vg as $v)
            if($v['visibility'] == 'visible')
                $output .='<EMAIL><LABEL>' . $v['label'] .'</LABEL><USERID>' . $v['value'] .'</USERID><VALUE>' . $v['value'] ."</VALUE></EMAIL>\n";

        $vg = $contact->getValueGroup('www');
        foreach($vg as $v)
            if($v['visibility'] == 'visible')
                $output .='<URL><LABEL>' . $v['label'] .'</LABEL><VALUE>' . $v['value'] ."</VALUE></URL>\n";
        
        // URI pointing to this TAB entry
        $output .='<URL><LABEL>TAB-R</LABEL><VALUE>' . ($CONFIG_TAB_ROOT . 'contact/contact.php?id=' . $_GET['id']) ."</VALUE></URL>\n";
        
        /* // Attach picture base64
        if(!empty($contact->contact['pictureData']))
            $output .= 'PHOTO;ENCODING=BASE64;TYPE=JPEG:' . base64_encode($contact->contact['pictureData']) . "\n";
        
        // Attach picture URL
        if(!empty($contact->contact['pictureURL']))
            $output .= 'PHOTO;VALUE=URL:' . ContactImportExport::vCardEscape($contact->contact['pictureURL']) . "\n";  */    
        
        $output .= "</vCard>\n";
        $output .= "\n";                

        return $output;
    }
    
    /**
    * Encode a contact as XML to be rendered by a stylesheet
    * @todo Check if this exports private/admin hidden values only if allowed.
    * @param Contact $contact
    * @return string XML
    * @global $CONFIG_TAB_ROOT, $country
    */
function xmlExport($contact)
    {
        global $CONFIG_TAB_ROOT, $country, $addressFormatter;
        
        $output = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
        $output .= "<contact>\n";
        $output .= "<id>".$contact->contact['id']."</id>\n";
        $output .= '<fullname>' . htmlspecialchars($contact->contact['namePrefix'] . ' ' . $contact->contact['firstname'] . ' ' . $contact->contact['lastname'] . ' ' . $contact->contact['nameSuffix'],ENT_NOQUOTES,'UTF-8') . "</fullname>\n";
        $output .= '<name><family>' . htmlspecialchars($contact->contact['lastname'],ENT_NOQUOTES,'UTF-8') . '</family><given>' . 
                htmlspecialchars($contact->contact['firstname'],ENT_NOQUOTES,'UTF-8') . '</given><middlename>' . 
                htmlspecialchars($contact->contact['middlename'],ENT_NOQUOTES,'UTF-8') .'</middlename><prefix>'. 
                htmlspecialchars($contact->contact['namePrefix'],ENT_NOQUOTES,'UTF-8') .'</prefix><suffix>'. 
                htmlspecialchars($contact->contact['nameSuffix'],ENT_NOQUOTES,'UTF-8') . "</suffix></name>\n";
        
        if($contact->contact['nickname']) $output .= '<nickname>' . $contact->contact['nickname'] . "</nickname>\n";
        //if($contact->contact['birthday'] != '0000-00-00') $output .= 'BDAY:' . ?? . "\n";
        
        $vg = $contact->getValueGroup('addresses');
        $output .="<address-list>\n";
        foreach($vg as $adr)
        {
            $output .="<address>\n";
            $output .="<dbid>" . $adr['refid'] ."</dbid>\n";
            $output .="<type>" . htmlspecialchars($adr['type'],ENT_NOQUOTES,'UTF-8') ."</type>\n";
            $output .='<line1>' . htmlspecialchars($adr['line1'],ENT_NOQUOTES,'UTF-8') ."</line1>\n";
            $output .='<line2>' . htmlspecialchars($adr['line2'],ENT_NOQUOTES,'UTF-8') ."</line2>\n";
            $output .='<city>' . htmlspecialchars($adr['city'],ENT_NOQUOTES,'UTF-8') ."</city>\n";
            $output .='<zip>' . htmlspecialchars($adr['zip'],ENT_NOQUOTES,'UTF-8') ."</zip>\n";
            $output .='<state>' . htmlspecialchars($adr['city'],ENT_NOQUOTES,'UTF-8') ."</state>\n";
            $output .='<countrycode>' . htmlspecialchars($adr['country'],ENT_NOQUOTES,'UTF-8') ."</countrycode>\n";
            $output .= '<formatted>'.$addressFormatter->formatAddress($adr).'</formatted>';
            
            
            global $VALUE_GROUP_TYPES_ARRAY;
        
            foreach($VALUE_GROUP_TYPES_ARRAY as $t)
            {
                $output .="<$t-list>\n";
                $vg = $contact->getValueGroup($t,$adr['refid']);
                foreach($vg as $v)
                {
                    if($v['visibility'] != 'visible')
                        continue;
                
                    if($t!='date')
                        $output .="<$t>".'<label>' . htmlspecialchars($v['label'],ENT_NOQUOTES,'UTF-8') .'</label><value>' . htmlspecialchars($v['value'],ENT_COMPAT,'UTF-8') ."</value></$t>\n";
                    else
                        $output .="<$t>".'<label>' . htmlspecialchars($v['label'],ENT_NOQUOTES,'UTF-8') .
                            '</label><value1>' . htmlspecialchars($v['value1'],ENT_NOQUOTES,'UTF-8') .
                            '</value1><value2>' . htmlspecialchars($v['value2'],ENT_NOQUOTES,'UTF-8') .
                            '</value2><type>' . htmlspecialchars($v['type'],ENT_NOQUOTES,'UTF-8') . "</type></$t>\n";
                }
                $output .="</$t-list>\n\n";
            }

                
            $output .="</address>\n";
        }
        $output .="</address-list>\n\n";
        
        global $VALUE_GROUP_TYPES_ARRAY;
        
        foreach($VALUE_GROUP_TYPES_ARRAY as $t)
        {
            $output .="<$t-list>\n";
            $vg = $contact->getValueGroup($t,null);
            foreach($vg as $v)
            {
                if($v['visibility'] != 'visible')
                    continue;
                
                if($t!='date')
                    $output .="<$t>".'<label>' . htmlspecialchars($v['label'],ENT_NOQUOTES,'UTF-8') .'</label><value>' . htmlspecialchars($v['value'],ENT_COMPAT,'UTF-8') ."</value></$t>\n";
                else
                    $output .="<$t>".'<label>' . htmlspecialchars($v['label'],ENT_NOQUOTES,'UTF-8') .
                        '</label><value1>' . htmlspecialchars($v['value1'],ENT_NOQUOTES,'UTF-8') .
                        '</value1><value2>' . htmlspecialchars($v['value2'],ENT_COMPAT,'UTF-8') .
                        '</value2><type>' . htmlspecialchars($v['type'],ENT_NOQUOTES,'UTF-8') . "</type></$t>\n";
            }
            $output .="</$t-list>\n\n";
        }
        
        $ci = new ContactImage($contact);
        $output .= "<pictureURL>" . $ci->uri() . "</pictureURL>\n";
        
        $output .= "<notes>\n";
        $output .= $contact->contact['notes']; // htmlspecialchars does not work here!! (XML processing!)
        $output .= "</notes>\n";
        
        global $pluginManager;
        $output .= $pluginManager->xmlExport($contact);
        
        $output .= "</contact>\n";
        $output .= "\n";                
        
        return $output;
    }
    
    /**
    * Encode a contact as XML to be rendered by a stylesheet
    * @param XML String
    * @return $contact to fill with xml data
    */
function xmlImport($xmlString,&$contact)
    {
    }
    
    /**
    * DIRECT EXPORT (stdout) of selected fields and values
    * @param Contact $contact
    * @param array $what ...?  'label' => 'return $contact->procedure();')
    * @return NOTHING
    */
/* function csvDirectExport($contact,$hearderLabels,$outProcedures)
    {
        $out = fopen('php://output', 'w');
        
        fputcsv($out, $hearderLabels)
        
        foreach($what as $k => $v)
            ;
            
        fclose($out);
    } */
}

?>
