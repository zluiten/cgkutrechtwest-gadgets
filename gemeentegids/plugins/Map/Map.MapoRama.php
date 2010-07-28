<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
    /**
    Map-o-Rama mapping and geocoding interface:
    
        function canGeocode($countryCode) returns true/false
        function geocode($tbl_address) returns array('latitude','longitute');
        function canMap($countryCode) returns always false
        function showMapAt($latitude,$longitude) returns always false
        function showMap($tbl_address) returns always false
        
    function maporama_curl()                standard function when curl library is installed
    function maporama_curl_without_curl()   alternate UNTESTED fetch function; rename this to maporama_curl() if you have no curl

    * @package plugins
    * @author various
    */

    class MapoRama
    {
        // Is there a geocoder to translate addresses?
        // This means that at least showMap($tbl_address) will work
        // However geocode() may still return null if translation to lat/long is not possible
        function canGeocode($countryCode)
        {
            return true;
            
            /*$valid_countries = array( 'ca'=>1, 'fr'=>1, 'de'=>1, 'it'=>1, 'es'=>1, 'us'=>1 );

            if(array_key_exists($countryCode, $valid_countries))
                return true;
            
            return false;*/
        }

        // returns an array on success an error string on failure
        function geocode(&$tbl_address)
        {
            
            $line1 = $tbl_address['line1'];
            $line2 = $tbl_address['line2'];
            $city = $tbl_address['city'];
            $state = $tbl_address['state'];
            $zip = $tbl_address['zip'];
            $countrycode = $tbl_address['country'];

            $ret = $this->get_lat_long($line1, $line2, $zip, $city, $state, $countrycode);
            
            // mapo-sucker may return 0,0 for an address!
            // I have made 0,0 a special value in the DB to avoid
            // polling mapo all over if it cannot resolve the address 
            
            return $ret;
        }
        
        // Are we able to display map data in this country?
        function canMap($countryCode)
        {                        
            return false;
        }

        // show by latitude longitude
        function showMapAt($latitude,$longitude)
        {
            return false;
        }

        // this is the generic doitall
        function showMap($tbl_address)
        {
            return false;
        }

        // returns an array on success an error string on failure
    function get_lat_long($address1, $address2, $zip, $city, $state = null, $countrycode) 
        {

            global $errorHandler;
            
            $html = $this->maporama_curl($address1, $address2, $zip, $city, $state, $countrycode);

            // grab code from web site
            if ($html)
            {
                    $i = preg_match_all('/MD_l.=[-0-9.]+/',$html,$lalo);
                    
                    if($i < 2)
                        return 'Address not found. Most likely Map-O-Rama changed their interface.<a href="http://world.maporama.com/">Try yourself on world.maporama.com!</a>';

                    $lo = explode("=",$lalo[0][0]);
                    $la = explode("=",$lalo[0][1]);
                    
                    return array('latitude'=>$la[1],'longitude'=>$lo[1]);
            }
            
            return 'Could not fetch the data. Maybe the interface has changed. <a href="http://world.maporama.com/">Try yourself on world.maporama.com!</a>';
        }

        // Fetch an URL with curl; below you can find an untested replacement that does not need curl installed
    function maporama_curl($address1, $address2, $zip, $city, $state = null, $countrycode)
        {
            // If zip and city are empty (the address is placed only in line1 and line2) THEN parse zip and city from line2
            if(empty($zip) && empty($city)) // parse line2
            {
                $a = explode(" ",$address2);
                $i=0;
                $p1 = true;
                $zip .= $a[$i++]." "; // first piece always D-20200
                while($a[$i])
                {
                    if($p1 && is_numeric($a[$i]))
                        $zip .= $a[$i]." ";
                    else
                    {    $p1 = false;
                        $city .= $a[$i]." ";
                    }
                    $i++;
                }
            }
        
           // fix any incompatibilities between addressbook and maporama country codes
           if (strcasecmp($countrycode, "GB")==0) {$countrycode="UK";}
           if (strcasecmp($countrycode, "VE")==0) {$countrycode="UE";}
           if (strcasecmp($countrycode, "AZ")==0) {$countrycode="AJ";}
           if (strcasecmp($countrycode, "AW")==0) {$countrycode="AB";}
           if (strcasecmp($countrycode, "PF")==0) {$countrycode="FP";}
           if (strcasecmp($countrycode, "SB")==0) {$countrycode="SS";}
           if (strcasecmp($countrycode, "SC")==0) {$countrycode="SH";}
           if (strcasecmp($countrycode, "SJ")==0) {$countrycode="NO";}
           if (strcasecmp($countrycode, "VG")==0) {$countrycode="VU";}
           if (strcasecmp($countrycode, "VI")==0) {$countrycode="VS";}
           if (strcasecmp($countrycode, "WF")==0) {$countrycode="WA";}
           if (strcasecmp($countrycode, "UM")==0) {$countrycode="US";}
           
           // Simplistic check to decide if Address1 or Address2 is the one to send to Maporama
           // We will base the decision on the presence of a housenumber at either the start or the end of the line
           
           if ($address1 AND $address2) {
              // check start of string for housenumber
              if ((is_numeric(mb_substr($address1, 0, mb_strpos($address1," ")))) OR (is_numeric(mb_substr($address1, -1)))) {
                 $address = $address1;
              } else {
                 if ((is_numeric(mb_substr($address2, 0, mb_strpos($address2," ")))) OR (is_numeric(mb_substr($address2, -1)))) {
                    $address = $address2;
                 }
              }
           } else {
              if ($address1) {
                 $address = $address1;
              }
           }
                
            $address = utf8_encode($address);
            $city = utf8_encode($city);
            $state = utf8_encode($state);
            $zip = utf8_encode($zip);
            $countrycode = mb_strtoupper($countrycode);
            $vars = "form-name=MapForm&xml=map&xsl=map&MD_scale=" . urlencode('0.0002') . "&MD_drawTraffic=0&MD_zoomToFit=1&MD_size=500x380&MD_mapTemplate=US&GC_country=$countrycode&GC_address=" . 
                    $address . "&GC_zip=" . $zip . "&GC_state=" . $state . "&GC_city=". $city ."&Go=Go";
                        
            $accept =  array( 'Accept-Encoding: gzip', 'Accept-Language: en' );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,'http://world.maporama.com/');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');  // pretend to be InternetExplorer!!
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $accept);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $html = curl_exec($ch); // Fetch the frontpage as if we were a normal user inclusive cookie
            sleep(1); // enter the data really fast ;-)
            curl_setopt($ch, CURLOPT_URL,'http://world.maporama.com/drawaddress.aspx');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);       
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $html = curl_exec($ch);
            curl_close($ch);
            
            return $html . $vars; // null on error
        }
    
        /**
         * returns Maporama URL
         *
         * @author      Jesper Nielsen <jesper> -- at -- <krusedulle.net>
         * @version     0.5.2
         *
         * This function is inspired by the Yahoo! widget Map-o-Rama!
         * http://widgets.yahoo.com/gallery/view.php?widget=37478
         *
         * 20060624: Added a function get_lat_long that theoretically can fetch the coordinate
         *         for any matched maporama address.
         */
        
    function maporama_curl_without_curl($address1, $address2, $zip, $city, $state = null, $countrycode)
        {   
            // Is the address placed in line1 and 2 completey >> empty zip and city then parse them
            switch($country)
            {
              case 'us': break;
              default:
                 if(empty($zip) && empty($city)) // parse line2
                 {
                    $a = explode(" ",$address2);
                    $i=0;
                    $p1 = true;
                    $zip .= $a[$i++]." "; // first piece always D-20200
                    while($a[$i])
                    {
                       if($p1 && is_numeric($a[$i]))
                          $zip .= $a[$i]." ";
                       else
                       {   $p1 = false;
                          $city .= $a[$i]." ";
                       }
                       $i++;
                    }
                 }
                 
                 break;
            }
            
            // fix any incompatibilities between addressbook and maporama country codes
            if (strcasecmp($countrycode, "GB")==0) {$countrycode="UK";}
            if (strcasecmp($countrycode, "VE")==0) {$countrycode="UE";}
            if (strcasecmp($countrycode, "AZ")==0) {$countrycode="AJ";}
            if (strcasecmp($countrycode, "AW")==0) {$countrycode="AB";}
            if (strcasecmp($countrycode, "PF")==0) {$countrycode="FP";}
            if (strcasecmp($countrycode, "SB")==0) {$countrycode="SS";}
            if (strcasecmp($countrycode, "SC")==0) {$countrycode="SH";}
            if (strcasecmp($countrycode, "SJ")==0) {$countrycode="NO";}
            if (strcasecmp($countrycode, "VG")==0) {$countrycode="VU";}
            if (strcasecmp($countrycode, "VI")==0) {$countrycode="VS";}
            if (strcasecmp($countrycode, "WF")==0) {$countrycode="WA";}
            if (strcasecmp($countrycode, "UM")==0) {$countrycode="US";}
            
            // Simplistic check to decide if Address1 or Address2 is the one to send to Maporama
            // We will base the decision on the presence of a housenumber at either the start or the end of the line
            
            if ($address1 AND $address2) {
              // check start of string for housenumber
              if ((is_numeric(substr($address1, 0, strpos($address1," ")))) OR (is_numeric(substr($address1, -1)))) {
                 $address = $address1;
              } else {
                 if ((is_numeric(substr($address2, 0, strpos($address2," ")))) OR (is_numeric(substr($address2, -1)))) {
                    $address = $address2;
                 }
              }
            } else {
              if ($address1) {
                 $address = $address1;
              }
            }
            
            $timeout = 100;  // Max time for stablish the conection
            $size    = 0;  // Bytes will be read (and display). 0 for read all
            $host    = 'world.maporama.com';            // Domain name
            $target  = '/idl/maporama/drawaddress.aspx';        // Specific program
            $referer = 'http://www.test.com/';    // Referer
            $port    = 80;
            
            // Setup an array of fields to post with then create the post string
            $posts = array (
                  'form-name' => 'MapForm',
                  'xml' => 'map',
                  'jsEnable' => '1',
                  'xsl' => 'map',
                  'MD_zoomToFit' => '0',
                  'MD_scale' => '0.0002',
                  'MD_drawTraffic' => '0',
                  'MD_zoomToFit' => '1',
                  'MD_size' => '500x380',
                  'MD_mapTemplate' => 'US',
                  'SESSIONID' => '',
                  'GC_country' => "$countrycode",
                  'GC_address' => "$address",
                  'GC_zip' => "$zip",
                  'GC_state' => "$state",
                  'GC_city' => "$city",
                  'Go' => "Go"
                      );
            
            // That's all.
            if ( is_array( $posts ) ) {
                foreach( $posts AS $name => $value ){
                   $postValues .= urlencode( $name ) . "=" . urlencode(utf8_encode($value)) . '&';
                }
                $postValues = substr( $postValues, 0, -1 );
                $method = "POST";
            } else {
                $postValues = '';
            }
            
            $request  = "$method $target$getValues HTTP/1.1\r\n";
            $request .= "Host: $host\r\n";
            $request .= 'User-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1) ';
            $request .= "Gecko/20021204\r\n";
            $request .= 'Accept: text/xml,application/xml,application/xhtml+xml,';
            $request .= 'text/html;q=0.9,text/plain;q=0.8,video/x-mng,image/png,';
            $request .= "image/jpeg,image/gif;q=0.2,text/css,*/*;q=0.1\r\n";
            $request .= "Accept-Language: en-us, en;q=0.50\r\n";
            $request .= "Accept-Encoding: gzip, deflate, compress;q=0.9\r\n";
            $request .= "Accept-Charset: ISO-8859-1, utf-8;q=0.66, *;q=0.66\r\n";
            $request .= "Referer: $referer\r\n";
            $request .= "Cache-Control: max-age=0\r\n";
            
            if ( $method == "POST" ) {
                $lenght = strlen( $postValues );
                $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $request .= "Content-Length: $lenght\r\n";
                $request .= "Connection: close\r\n\r\n";
                //$request .= "\r\n";
                $request .= $postValues;
            }
            
            
            $socket  = fsockopen( $host, $port, $errno, $errstr, $timeout );
            fputs( $socket, $request );
            if ( $size > 0 ) {
                $ret = fgets( $socket, $size );
            } else {
                $ret = '';
                while ( !feof( $socket ) ) {
                    $ret .= fgets( $socket, 4096 );
                }
            }
            fclose( $socket );
            
            return $ret . $postValues; // null on error
        }
    } // end class
?>
