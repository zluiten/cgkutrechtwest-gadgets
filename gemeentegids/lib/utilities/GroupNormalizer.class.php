<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link GroupNormalizer}
* @package utilities
* @author Tobias Schlatter
*/

/**
* Class to normalize charactes for grouping (i.e. convert ö to => o)
* @package utilities
*/
class GroupNormalizer {

    /**
    * @var array list of conversions
    */
    var $norm = array(
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'ā' => 'a',
        'ă' => 'a',
        'ą' => 'a',
        'ǎ' => 'a',
        'ǻ' => 'a',
        'ç' => 'c',
        'ć' => 'c',
        'ĉ' => 'c',
        'ċ' => 'c',
        'č' => 'c',
        'ď' => 'd',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ē' => 'e',
        'ĕ' => 'e',
        'ė' => 'e',
        'ę' => 'e',
        'ě' => 'e',
        'ĝ' => 'g',
        'ğ' => 'g',
        'ġ' => 'g',
        'ģ' => 'g',
        'ĥ' => 'h',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ĩ' => 'i',
        'ī' => 'i',
        'ĭ' => 'i',
        'į' => 'i',
        'ı' => 'i',
        'ǐ' => 'i',
        'ĵ' => 'j',
        'ķ' => 'k',
        'ĺ' => 'l',
        'ļ' => 'l',
        'ľ' => 'l',
        'ñ' => 'n',
        'ń' => 'n',
        'ņ' => 'n',
        'ň' => 'n',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'ō' => 'o',
        'ŏ' => 'o',
        'ő' => 'o',
        'ơ' => 'o',
        'ǒ' => 'o',
        'ŕ' => 'r',
        'ŗ' => 'r',
        'ř' => 'r',
        'ś' => 's',
        'ŝ' => 's',
        'ş' => 's',
        'š' => 's',
        'ſ' => 's',
        'ţ' => 't',
        'ť' => 't',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ũ' => 'u',
        'ū' => 'u',
        'ŭ' => 'u',
        'ů' => 'u',
        'ű' => 'u',
        'ų' => 'u',
        'ư' => 'u',
        'ǔ' => 'u',
        'ǖ' => 'u',
        'ǘ' => 'u',
        'ǚ' => 'u',
        'ǜ' => 'u',
        'ŵ' => 'w',
        'ý' => 'y',
        'ÿ' => 'y',
        'ŷ' => 'y',
        'ź' => 'z',
        'ż' => 'z',
        'ž' => 'z',
        'ǽ' => 'æ',
        'ǿ' => 'ø',
        'ά' => 'α',
        'έ' => 'ε',
        'ή' => 'η',
        'ί' => 'ι',
        'ϊ' => 'ι',
        'ό' => 'ο',
        'ΰ' => 'υ',
        'ϋ' => 'υ',
        'ύ' => 'υ',
        'ώ' => 'ω'
    );
    
    /**
    * convert character to normalized character, preserving case of char
    * @param string $char character to normalize
    * @return string normalized character
    */
function normalize($char) {
        if (!isset($this->norm[mb_strtolower($char)]))
            return $char;
        
        if (mb_strtolower($char) == $char)
            return mb_strtolower($this->norm[mb_strtolower($char)]);
        
        return mb_strtoupper($this->norm[mb_strtolower($char)]);
        
    }

}

/**
* @global GroupNormalizer $groupNormalizer
*/
$groupNormalizer = new GroupNormalizer();

?>
