<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link StringHelper}
* @package utilities
* @author Tobias Schlatter
*/

/**
* utility class to perform various string operations
* @package utilities
*/
class StringHelper {

    /**
    * cleanup values that are passed by GET, POST or COOKIE
    *
    * the php "feature" magic_quotes automatically escapes values passed from the
    * user to write them into the database. unfortunately it uses the wrong funtion
    * ({@link addslashes() addslashes()} instead of
    * {@link mysql_real_string_escape() mysql_real_string_escape()}) is used
    * and often, one does not write these infos to
    * the database. this function checks, whether magic_quotes is turned on or not
    * and strips the slashes if necessary. this function also handles cleaning of
    * arrays by cleaning them recursively. it should be called on every string passed
    * by GET, POST or COOKIE that is used.
    * @static
    * @param string|array $val string/array to clean up
    * @param boolean $htmlAllowed is html allowed in the strings?
    * @return string cleaned string
    */
function cleanGPC($val,$htmlAllowed = true) {
        if (is_array($val)) {
            $tmp = array();
            foreach($val as $k => $v)
                $tmp[(is_numeric($k) ? $k : stripslashes($k))] = StringHelper::cleanGPC($v,$htmlAllowed);
            return $tmp;
        }
            
        if (get_magic_quotes_gpc())
            $val = stripslashes($val);
            
        if ($htmlAllowed)
            return $val;
            
        return htmlentities($val,ENT_QUOTES,'UTF-8');
        
    }
    
    /**
    * trim whole array recursively if needed
    */
function recursiveTrimValues($array) 
    {
        $tmp = array();
        
        foreach($array as $k => $v)
        {
            if(is_array($v))
                $tmp[$k] = recursiveTrimValues($v);
            else
                $tmp[$k] = trim($v);
        }
                
        return $tmp;
    }
    
    /**
    * parses a European zip-line (A-1010 Wien, 613 30 Oslo M) into zip and city fields (currently unused)
    * @static
    * @param string $line line to parse
    * @param string $zip zip-code is stored in this variable
    * @param string $zip city is stored in this variable
    * @author Thomas Katzlberger
    */
function parseEuropeanZipCity($line,&$zip,&$city)
    {
        $a = explode(" ",$line);
        $i=0;
        $p1 = true;
        $zip = $a[$i++].' '; // first piece always D-20200
        $city='';
        while($a[$i])
        {
            if($p1 && is_numeric($a[$i]))
                $zip .= $a[$i].' ';
            else
            {       $p1 = false;
                $city .= $a[$i].' ';
            }
            $i++;
        }
    
        $zip = mb_substr($zip,0,mb_strlen($zip)-1);
        $city = mb_substr($city,0,mb_strlen($city)-1);
    
        // show the PROUD result
        //echo "Z:--$zip-- C:--$city--\n";
    }

    /**
    * determine whether a string is http (starts with /, http:// or https://)
    * @static
    * @param string $url string to check
    * @return boolean is http
    */
function isHTTP($url) {
        return strstr($url,"/")===$url ||
               strstr($url,"http://")===$url ||
               strstr($url,"https://")===$url;
    }
    
    /**
    * determine whether a string is an RFC822 compliant e-mail address
    * @static
    * @license http://creativecommons.org/licenses/by-sa/2.5/
    * @link http://iamcal.com/publish/articles/php/parsing_email
    * @author Cal Henderson
    * @copyright ©1993-2005 Cal Henderson
    * @param string $email e-mail to validate
    * @return boolean is valid e-mail
    */
function isEmail($email) {
        
        $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

        $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

        $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
            '\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

        $quoted_pair = '\\x5c[\\x00-\\x7f]';

        $domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";

        $quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";

        $domain_ref = $atom;

        $sub_domain = "($domain_ref|$domain_literal)";

        $word = "($atom|$quoted_string)";

        $domain = "$sub_domain(\\x2e$sub_domain)*";

        $local_part = "$word(\\x2e$word)*";

        $addr_spec = "$local_part\\x40$domain";

        return preg_match("!^$addr_spec$!", $email) ? 1 : 0;

    }
    
    /**
    * function to determine the number of characters which are the same at the beginning of two strings
    * @static
    * @param string $str1 first string
    * @param string $str2 secont string
    * @return integer length of common beginning
    */
function lengthSame($str1,$str2) {
    
        $i=0;
        while (mb_substr($str1,$i,1) == mb_substr($str2,$i,1) && $i<=strlen($str1) && $i<=strlen($str2))
            $i++;
            
        return $i;
    
    }
    
    /**
    * checks whether a string ends with the specified substring
    * @static
    * @param string $str string to check
    * @param string $sub ending to check
    * @return boolean are endings the same
    */
function strEndsWith($str,$sub) {
        return (mb_substr($str,mb_strlen($str)-mb_strlen($sub)) === $sub);
    }

    /**
    * replaces every character with the html equivalent of the form &#....;
    * 
    * used to obscure e-mail addresses, which prevents auto reading (of course it would be possible,
    * but most web-spiders can't handle them
    * @static
    * @param string $str string to obscure
    * @return string obscured string
    */
function obscureString($str) {
        return mb_encode_numericentity($str,array(
            0x00,0xffff,0,0xffff
        ));
    }
    
    /** 
    * Export array to .csv file. 
    * GERMAN WINDOWS (if $delimiter is ';'): Numeric values are processed: '.' is replaced by ',' as decimal sign.
    *
    * @param $fields array('column1' => 'output value', ...)
    * @param $selectedFields array('column1','column3') array of keys of $fields to determine output order or make a subselection of $fields
    * @param string $delimiter used for data separation in .csv default: ',' (Attention: ';' for GERMAN Windows, ',' for Unix)
    * @param string $enclosure string separation sign. default: '"'
    * @param string $outputEncoding default: 'Windows-1252'
    * @return string the CSV line
    **/
function csvLine($fields, $selectedFields, $delimiter = ',', $enclosure = '"',$outputEncoding = 'Windows-1252')
    {
        $str = '';
        $escape_char = '\\';
        
        foreach($selectedFields as $h)
        {
            $value = $fields[$h];
            
            // Change . to , if german windows is used (semicolon separated values)
            if($delimiter==';' && is_numeric($value))
                $value = str_replace('.', ',',$value);
                
            if (strpos($value, $delimiter) !== false ||
            strpos($value, $enclosure) !== false ||
            strpos($value, "\n") !== false ||
            strpos($value, "\r") !== false ||
            strpos($value, "\t") !== false ||
            strpos($value, ' ') !== false) {
                $str2 = $enclosure;
                $escaped = 0;
                $len = strlen($value);
                for ($i=0;$i<$len;$i++) 
                {
                    if ($value[$i] == $escape_char) {
                        $escaped = 1;
                    } else if (!$escaped && $value[$i] == $enclosure) {
                        $str2 .= $enclosure;
                    } else {
                        $escaped = 0;
                    }
                    $str2 .= $value[$i];
                }
                $str2 .= $enclosure;
                $str .= $str2.$delimiter;
            }
            else
            {
                $str .= $value.$delimiter;
            }
        }
        
        $str = substr($str,0,-1);
        $str .= "\n";
        
        return mb_convert_encoding($str,$outputEncoding,'UTF-8');
    }
}

?>
