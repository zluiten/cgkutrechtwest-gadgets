<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* THE ADDRESS BOOK RELOADED IMPORT CODE (ZULU!)
*
* @author Thomas Katzlberger
* @package plugins
*
* $_GET['format']   = vCard |
* $_GET['continue'] = contact | interface
*/

chdir('../../'); // goto main directory

require_once('lib/init.php');
require_once('ErrorHandler.class.php');
require_once('ContactImportExport.class.php');

$rightsManager = RightsManager::getSingleton();

if(!$rightsManager->currentUserIsAllowedTo('create'))
    $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));

if (!isset($_POST['format']))
    $_POST['format'] = '';

    /*function import_change_encoding(&$a)
    {
        foreach($a as $k => $v)
        {
            if(is_array($v))
                import_change_encoding(&$v);
            else
            if(is_string($v))
                $a[$k]=mb_convert_encoding($v,'UTF-8','ISO-8859-1');
        }        
    }*/
    
switch($_POST['format'])
{
    case 'vCard':
        if (!isset($_POST['text']))
           $errorHandler->error('invArg','No text posted.',basename($_SERVER['SCRIPT_NAME']));

        $vCards = explode('END:VCARD',$_POST['text']);

        $n=0;
        foreach($vCards as $cardText)
        {
            if(!strstr($cardText,'BEGIN:VCARD')) // invalid entry ... mostly just the tail
                continue;
                
            $contact=ContactImportExport::vCardImport($cardText."END:VCARD\n");
            if($contact!=null)
                $n++;
        }

        if($n!=1)
            $errorHandler->success('Imported cards: '.$n,basename($_SERVER['SCRIPT_NAME']));
        
        if(!isset($_POST['continue']) && $n==1) // interface (more input), card (display imported)
            $_POST['continue'] = 'contact';
        
        break;
    case 'csv': // NOT FINISHED!!
        if (!isset($_POST['text']))
           $errorHandler->error('invArg','No text posted.',basename($_SERVER['SCRIPT_NAME']));
           
        // write to file in tmp directory
        
        $row = 1;
        $handle = fopen("test.csv", "r");
        
        // Read the 1st row of the CSV. This is the mapping (see extras/importData.xls)
        // The column labels MUST match the $csvRow['xxx'] keys below
        // maybe we should do a strtolower here?
        $mapping = fgetcsv($handle, 1000, ",");
        
        while (($csvRow = fgetcsv($handle, 1000, ",")) !== FALSE) // read next row
        {
           $row++;
           $num = count($csvRow);
        
           // read data into associative array by names of first column
           $n=count($mapping);
           for($i=0;$i<$n;$i++)
               $hash[$mapping[$i]] = $csvRow[$i];
            
            // Check if we should skip this record (column #1 can start with a # (comment) then skip
            $x = strpos($csvRow[0],"#");
            if( $x !== false && $x === 0) // starts with #
                   continue; 
         
            // SEE: ContactImportExport.class.php for how it is done with vCards!
            $data['contact']['lastname'] = $hash['lastname']; 
            $data['contact']['firstname'] = $hash['firstname']; 
             
            $data['address'][0]['line1'] = $hash['line1']; 
            $data['address'][0]['phone1'] = $hash['phone1']; 
             
            // another address 
            //$data['address'][1]['line1'] = $csvRow['a2-line1']; 
             
            // other data 
            $data['blank'][0]['type'] = ''; // email OR www OR other OR chat OR phone; 
            $data['blank'][0]['label'] =  ''; // STRING; 
            $data['blank'][0]['value'] =  ''; // STRING; 
            $data['blank'][0]['visibility'] =  ''; // visible OR hidden OR admin; 
             
            $data['blank'][1]['type'] =  ''; // 'email'; 
            $data['blank'][1]['label'] =  ''; // 'private email'; 
            $data['blank'][1]['value'] =  ''; // foo@goo.com'; 
            $data['blank'][1]['visibility'] =  ''; // 'visible'; 
            
            // make duplicate checks
            
            // Attach picture ... 
            //if(0) // is a link
            //    $post['URLtoMugshot']=$pictureFile; // path must be local and accessible by file_get_contents
            
            // save it to the DB
            $contact = new Contact();  
            $contact->saveContactFromArray($data);
        }
        break;
}

if(!isset($_POST['continue'])) // interface (more input), card (display imported)
    $_GET['continue'] = 'interface';

// what next?
switch($_POST['continue'])
{
    case 'contact': // display imported (single entry only)
        header('Location: ../../contact/contact.php?id='.$contact->contact['id']);
        exit();
    
    default: // interface
        chdir('plugins/Import');
        include('interface.php');
        break;
}
?>