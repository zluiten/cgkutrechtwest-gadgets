<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link AddressFormatter}
* @package utilities
* @author Tobias Schlatter, Thomas Katzlberger
*/

/**
* Class to format addresses correct for printing according to a country's customs.
* @link http://www.bitboost.com/ref/international-address-formats.html
* @package utilities
*/
class AddressFormatter
{
    /**
    * @var array format of addresses by country acronym
    */
    private $addressOutputFormat =
    array (
            '0'  => array('line1', '%', 'line2', '%', 'zip', 'city', 'state', '%', 'country'),
            'ar' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'au' => array('line1', '%', 'line2', '%', 'city', 'state', 'zip', '%', 'country'),
            'at' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'be' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),

            /* I'm not sure about Brasil */
            'br' => array('line1', '%', 'line2', '%', 'city', ' - ', 'state', '%', 'country'),

            'ca' => array('line1', '%', 'line2', '%', 'city', 'state', 'zip', '%', 'country'),
            'cz' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),

            /* What's the M in Odense M?? */
            'dk' => array('line1', '%', 'line2', '%', 'zip', 'city', /* 'state' ?? ,*/ '%', 'country'),

            'fi' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'fr' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'de' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'gb' => array('line1', '%', 'line2', '%', 'city', '%', 'state', '%', 'zip', '%', 'country'),
            'uk' => array('line1', '%', 'line2', '%', 'city', '%', 'state', '%', 'zip', '%', 'country'),
            'hk' => array('line1', '%', 'line2', '%', 'city', '%', 'country'),
            'is' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'in' => array('line1', '%', 'line2', '%', 'city', '-', 'zip', '%', 'country'),
            'id' => array('line1', '%', 'line2', '%', 'city', 'zip', '%', 'country'),
            'ie' => array('line1', '%', 'line2', '%', 'city', 'zip', 'state', '%', 'country'),
            'il' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'it' => array('line1', '%', 'line2', '%', 'zip', '-', 'city', 'state', '%', 'country'),
            'jp' => array('line1', '%', 'line2', '%', 'city', 'zip', '%', 'country'),
            'lu' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'mx' => array('line1', '%', 'line2', '%', 'zip', 'city', ', ', 'state', '%', 'country'),
            'nl' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'nz' => array('line1', '%', 'line2', '%', 'city', 'zip', '%', 'country'),
            'no' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'cn' => array('line1', '%', 'line2', '%', 'city', 'zip', '%', 'country'),
            'pl' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'pt' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'ru' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'sg' => array('line1', '%', 'line2', '%', 'country', 'zip', '%', 'country'),
            'za' => array('line1', '%', 'line2', '%', 'city', '%', 'zip', 'country'),
            'kr' => array('line1', '%', 'line2', '%', 'city', 'zip', '%', 'country'),
            'es' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'se' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'ch' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),
            'hu' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'),

            /* Where comes state/county/or whatever?? */
            'tw' => array('line1', '%', 'line2', '%', 'city', ', ', 'zip', '%', 'country'),
            'cn' => array('line1', '%', 'line2', '%', 'zip', 'city', '%', 'country'), // China is confused ...
            //'cn' => array('line1', '%', 'line2', '%', 'zip', 'city', ', ', 'state', '%', 'country'),

            'us' => array('line1', '%', 'line2', '%', 'city', ', ', 'state', 'zip', '%', 'country'),
            'um' => array('line1', '%', 'line2', '%', 'city', 'state', 'zip', '%', 'country')
    );
   
   /**
   * format an address using a custom delimeter.
   *
   * $delimeter == 'HTML': html output is generated ($delimiter='</div><div class="adl">')
   * $delimeter == 'XML': ordered xml output is generated (<line><line1>bla</line1></line><line><zip>1234</zip><city>sin</city></line>)
   * @param array $addData associative array with address data array('line1'=>string, 'line2'=>string, 'city'=>string, 'state'=>string, 'zip'=>string, 'country'=>'ISO 2 digit code')
   * @param string $delimeter delimeter used to separate data which is on the same line, html output if not specified
   * @param boolean $hideCountry do not display country name (you must pass the country code to format the address correctly)
   * @return string html or plaintext output
   */
function formatAddress($addData,$delimeter='HTML',$hideCountry=FALSE)
   {
        global $country;        
                        
        if ($addData['country'] && isset($this->addressOutputFormat[$addData['country']])) {
                $format = $this->addressOutputFormat[$addData['country']];
        }
        else
            $format = null;

        if(!$format) // double check if the country code was not defined in $addressOutputFormat
                $format = $this->addressOutputFormat['0'];
        
        $html = ($delimeter=='HTML');
        $xml  = ($delimeter=='XML');
        
        $tmp = '';
        $del = $delimeter;
        
        foreach ( $format as $v ) {
            
                $data = '';
                
                switch ($v) {
                    case 'line1':
                        $data = $xml ? '<line1>'.$addData['line1'].'</line1>' : ($html ? '<span class="street-address">' : '') . $addData['line1'] . ($html ? '</span>' : '');
                    break;
                    case 'line2':
                        $data = $xml ? '<line2>'.$addData['line2'].'</line2>' : ($html ? '<span class="street-address">' : '') . $addData['line2'] . ($html ? '</span>' : '');
                    break;
                    case 'city':
                        if ($html && $addData['city'])
                            $data = '<span class="locality">'.$addData['city'].'</span>';
                        else
                            $data = $xml ? '<city>'.$addData['city'].'</city>' : $addData['city'];
                    break;
                    case 'state':
                        if($html && $addData['state'])
                            $data = '<span class="region">'.$addData['state'].'</span>';
                        else
                            $data = $xml ? '<state>'.$addData['state'].'</city>' : $addData['state'];
                    break;
                    case 'zip':
                        if($html && $addData['zip'])
                            $data = '<span class="postal-code">'.$addData['zip'].'</span>';
                        else
                            $data = $xml ? '<zip>'.$addData['zip'].'</zip>' : $addData['zip'];
                    break;
                    case 'country':
                        if ($hideCountry==false && $addData['country'] && $addData['country'] != '0') {
                            $c = isset($country[$addData['country']]) ? $country[$addData['country']] : $addData['country'];
                            if ($html)
                                $data = '<span class="country-name">'.$c.'</span>';
                            else
                                $data = $xml ? '<country>'.$c.'</country>' : $c;
                        }
                    break;
                    case '%': // substitute arbitrary delimiter
                        if ($html)
                            $del = '</div><div class="adl">';
                        else
                            $del = $xml ? '</line><line>' : $delimeter;
                    break;
                    default:
                        $del = $v;
                    break;
                }

                if ($data == '')
                        continue;

                if ($tmp == '') {
                    if ($html)
                        $tmp = '<div class="adl">' . $data;
                    else
                        $tmp = $xml ? '<line>' . $data : $data;
                    continue;
                }

                $tmp .= $del;
                $del = ' ';
                $tmp .= $data;
        }
        
        if ($html && $tmp)
            $tmp .= '</div>';
        if ($xml && $tmp)
            $tmp .= '</line>';
        
        if($html)
            return '<div class="adr">' . $tmp .'</div>'; // hCard
            
        return $tmp;
   }

}

/**
* holds Sigleton instance of AddressFormatter.
* @global AddressFormatter $GLOBALS['addressFormatter']
* @name $addressFormatter
*/
$GLOBALS['addressFormatter'] = new AddressFormatter();

?>
