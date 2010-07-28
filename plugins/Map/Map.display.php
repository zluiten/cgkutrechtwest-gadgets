<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/*************************************************************
 *  Map PLUGIN for THE ADDRESS BOOK
 *************************************************************
* @package plugins
* @author Thomas Katzlberger
*/

    chdir('../../');
 
    require_once('lib/init.php');
    require_once('Contact.class.php');
    require_once('DB.class.php');
    require_once('StringHelper.class.php');
    require_once('ErrorHandler.class.php');

    if (isset($_GET['id']))
        $address_id = StringHelper::cleanGPC($_GET['id']);
        
    if (isset($_GET['cid']))
        $contact = Contact::newContact(intval(StringHelper::cleanGPC($_GET['cid']))); // use for the google-bubble?
    
    // search correct address in value group ... not very efficient
    $adds = $contact->getValueGroup('addresses');
    foreach ($adds as $a) {
        if ($a['refid'] == $address_id) {
            $add = &$a;
            break;
        }
    }

    if (!isset($add))
        $errorHandler->error('argVal','The address with id=' . $address_id . ' does not exist');
    
    $errorMessage = 'Unable to map this address. The address may not be included in any geocoder currently available here, or it is simply misspelled. Sorry!';
    
    // Cache Geocode ... currently not available, needs API key
    /* if(empty($add['latitude'])) {} */
    
    // Load GoogleMaps
    require_once('plugins/Map/Map.GoogleMaps.php');
    $gm = new GoogleMapsLink();
    if($gm->showMap($add)) // returns true if successful display (will use lat/long if avail)
        exit;

    // Not successful ... use other vendor to display?
    
    $ct = $add['country'];
    $errorHandler->error('invArg',$errorMessage);

?>
