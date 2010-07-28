<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/** GoogleMapping and geocoding interface for client resolvable links without API key.
.
* @package plugins
* @author Thomas Katzlberger
*/

    require_once('AddressFormatter.class.php');
    
    class GoogleMapsLink
    {
        // Is there a geocoder to translate addresses?
        // This means that at least showMap($tbl_address) will work
        // However geocode() may still return null if translation to lat/long is not possible
        function canGeocode($countryCode)
        {
            return true;
        }

        // returns null on failure or array('latitude'=>$la,'longitute'=>$lo);
        function geocode($tbl_address,&$errorMessage)
        {
            $errorMessage='GoogleMaps geocoding not available.';
            return null;
        }
        
        // Are we able to display map data in this country?
        function canMap($countryCode)
        {
            return true;
        }

        // show by latitude longitude
        function showMapAt($latitude,$longitude,$countryCode = null)
        {
            $url = "http://maps.google.com/maps?ll=" .$latitude. "," .$longitude;
            //if($countryCode) $url .= "&t=".$countryCode; // interface language
            header("Location: ".$url); //redirect to google maps!
            exit;
        }

        // this is the generic doitall
        function showMap($tbl_address)
        {
            global $addressFormatter;
            
            if(isset($tbl_address['latitude']))
            {
                // sanity check
                sscanf($tbl_address['latitude'].' '.$tbl_address['longitude'], "%f %f", $nla, $nlo);
                if(!($nla==0 && $nlo==0))    // Code for failed lookup!
                    $this->showMapAt($tbl_address['latitude'],$tbl_address['longitude'],$tbl_address['country']);
            }
            
            if(!$this->canGeocode($tbl_address['country'],false))
                return false;

            $url = "http://maps.google.com/maps?q=" . $addressFormatter->formatAddress($tbl_address,',');
            
            //$url .= "&t=".$country; // interface language
            header("Location: ".$url); //redirect to google maps!
            exit;
        }
    }
?>
