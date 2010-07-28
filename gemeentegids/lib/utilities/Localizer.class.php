<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link Localizer}
* @package utilities
* @author Thomas Katzlberger
*/

/**
* Page translation for TABR.
*/
class Localizer
{
    /**
    * Array of language codes the user understands: Notation: en-nz
    */
    var $languageCodes;
    
    /**
    * Array of locale object (GNU libc++) language codes the user understands: Notation: en_NZ
    * http://gcc.gnu.org/onlinedocs/libstdc++/22_locale/locale.html
    */
    var $localeIds;
        
    var $language;
    
    // private 
    var $translationHashTable;
    var $logFile; // null (off) or filename to append log
    
    /**
    * Static method to get the only instance of this object!
    * @static
    */
function getSingleton()
    {
        static $singleton=NULL;
        
        if(!isset($singleton))
            $singleton = new Localizer();
            
        return $singleton;
    }
    
    /**
    * detects language and loads language table.
    */
function Localizer() 
    {
        global $CONFIG_INSTALL_SUBDIR, $options;
        
        $this->logFile=null; // set to true to create a file localizer.log with strings for translation.
        
        $detect = $this->detectLanguages();

        if (isset($options)) { // are there options loaded at all?
            if (($val = $options->getOption('language','user')) !== null)
                $detect = array_merge(array(array(1 => $val)),$detect); // append user on top

            // append global default at the bottom
            $detect[] = array(1 => $options->getOption('language','global'));
        }

        $found=FALSE;
        foreach($detect as $l)
        {
            $lang = $l[1].'.lang.php';
            $this->language = $l[1];

            // Load translation table
            if(file_exists($CONFIG_INSTALL_SUBDIR.'lib/languages/'.$lang))
            {
                require_once($CONFIG_INSTALL_SUBDIR.'lib/languages/'.$lang);
                $found=TRUE;
                break;
            }
        }
        
        if($found==FALSE)
            require_once($CONFIG_INSTALL_SUBDIR.'lib/languages/en.lang.php');
        
        $this->translationHashTable = $LANG_TRANSLATION;
        
    }
    
     /**
     * Start logging missing vocabulary to localizer.log (must be writable!!). 
     * Example: Localizer::getSingleton()->logMissingVocabulary();
     */
function logMissingVocabulary($filename='/tmp/localizer.log')
    {
        $this->logFile = $filename;
    }

    /**
     * Returns language id array desired by user in en_NZ format. Defaults to "en" if nothing can be detected.
     * @return array Example: array('en_NZ','jp_JP');
     */
function getLocaleIdArray()
    {
        return $this->localeIds;
    }
    
    /**
     * Returns language id array desired by user in en_NZ format. Defaults to "en" if nothing can be detected.
     * @return array Example: array('en_NZ','jp_JP');
     */
function getLocaleId()
    {
        return $this->localeIds[0];
    }
    
    /**
     * Returns language id array desired by user in en_NZ format. Defaults to "en" if nothing can be detected.
     * @return array Example: array('en-nz','jp-jp');
     */
function getLanguageCodeArray()
    {
        return $this->languageCodes;
    }
    
    /**
     * Returns language id array desired by user in en_NZ format. Defaults to "en" if nothing can be detected.
     * @return array Example: array('en-nz','jp-jp');
     */
function getLanguageCode()
    {
        return $this->languageCodes[0];
    }
    
    /**
     * Returns all languages which have a corresponding language file
     * @return array list of type lang-code => lang-name
     */
function availableLanguages()
    {
        global $CONFIG_INSTALL_SUBDIR;

        $d = dir($CONFIG_INSTALL_SUBDIR.'lib/languages');
        
        $langs = array();

        $lang_names = $this->languages();
        
        while (false !== ($entry = $d->read()))
            if (substr($entry,-8) == 'lang.php') // all *lang.php files
                $langs[substr($entry,0,-9)] = $lang_names[substr($entry,0,-9)]; // $langs['en'] = $lang_names['en']

        $d->close();

        return $langs;
    }
    
    /**
    * Method to include another language & translation file e.g. de.lang.php
    *   require_once('Localizer.class.php');
    *   $loc = Localizer::getSingleton();
    *   $loc->mergeLanguage('lib/languages/'); // wherever you put the translations - trailing slash needed 
    * @param $languageDirectory 'path/to/lang/' e.g. 'lib/languages/'
    */
function mergeLanguage($languageDirectory)
    {
        // Load second table
        if(file_exists($languageDirectory.$this->language . '.lang.php'))
        {
            require_once($languageDirectory.$this->language . '.lang.php');
            $this->translationHashTable = array_merge($this->translationHashTable,$LANG_TRANSLATION);
        }
    }
    
    /**
    * Method to translate a single string.
    * @param string $text any text
    */
function translate($text)
    {
        // isset is 400% faster than array_key_exists
        if(isset($this->translationHashTable[$text]))
            return $this->translationHashTable[$text];
        
        return $text;
    }
    
    /**
    * Method to translate a complete HTML page. Everything between > and < will be translated.
    * Does not translate simple strings! Writes a log file of missing translations if desired.
    * @param string $content HTML code
    */
function translateHTML($content)
    {
        global $CONFIG_TAB_SERVER_ROOT;
        
        $ret = '';
        $n = strlen($content);
        
        if($this->logFile)
            $handle = fopen($this->logFile,'a+'); // append to file in main app directory
                    
        for($i=0;$i<$n;)
        {
            $x = strpos($content,'>',$i);
            if($x===FALSE) // DONE
                break;
                
            $y = strpos($content,'<',$x); 
            if($y===FALSE) // DONE
                break;

            $x++; // skip >
            $translate = substr($content,$x,$y-$x);
            //echo $translate.'<br>';
            
            if(!isset($this->translationHashTable[$translate])) // log missing string
            {
                if($this->logFile && $this->language != 'en' && strlen($translate)>3)
                {
                    $q = trim($translate);
                    if(!empty($q))
                        fwrite($handle, "'".$translate."'=>'',\n");
                }
            }
            else
                $translate = $this->translationHashTable[$translate];
                
            $ret .= substr($content,$i,$x-$i).$translate;
            $i = $y;
        }
        
        if($this->logFile)
            fclose($handle);
        
        return $ret . substr($content,$i);
    }
    
    /*
    Script Name: Full Operating system language detection
    Author: Harald Hope, Website: http://techpatterns.com/
    Script Source URI: http://techpatterns.com/downloads/php_language_detection.php
    Version 0.3.3
    Copyright (C) 22 August 2005
    
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.
    
    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.
    
    Lesser GPL license text:
    http://www.gnu.org/licenses/lgpl.txt
    
    Coding conventions:
    http://cvs.sourceforge.net/viewcvs.py/phpbb/phpBB2/docs/codingstandards.htm?rev=1.3
    */
    /****************************************** 
    this will return an array composed of a 4 item array for each language the os supports
    1. full language abbreviation, like en-ca
    2. primary language, like en
    3. full language string, like English (Canada)
    4. primary language string, like English
    4. sets $this->localeIds and $this->languageCodes
    *******************************************/
    
    // choice of redirection header or just getting language data
    // to call this you only need to use the $feature parameter
function detectLanguages()
    {
        // get the languages
        $a_languages = $this->languages();
        $index = '';
        $complete = '';
        $found = false;// set to default value
        //prepare user language array
        $user_languages = array();
    
        //check to see if language is set
        if ( isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) ) 
        {
            //explode languages into array
            $l = mb_strtolower( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
            $this->languageCodes = explode( ",", $l );
    
            foreach ($this->languageCodes as $code )
            {
                // pull out the language, place languages into array of full and primary
                // string structure: 
                $temp_array = array(); 
                // slice out the part before ; on first step, the part before - on second, place into array
                $temp_array[0] = mb_substr( $code, 0, strcspn( $code, ';' ) );//full language
                $temp_array[1] = mb_substr( $code, 0, 2 );// cut out primary language
                //place this array into main $user_languages language array
                $user_languages[] = $temp_array;
            }
    
            //start going through each one
            for ( $i = 0; $i < count( $user_languages ); $i++ )
            {
                foreach ( $a_languages as $index => $complete ) 
                {
                    if ( $index == $user_languages[$i][0] )
                    {
                        // complete language, like english (canada) 
                        $user_languages[$i][2] = $complete;
                        // extract working language, like english
                        $user_languages[$i][3] = mb_substr( $complete, 0, strcspn( $complete, ' (' ) );
                    }
                }
            }
        }
        else// if no languages found
        {
            $this->languageCodes = array('en');
            $user_languages[] = array( 'en','en','English','English' ); // English as default
        }
        
        $this->localeIds = array();
        foreach($this->languageCodes as $code)
            $this->localeIds[] = strlen($code)<=2 ? $code : substr($code,0,2).'_'.strtoupper(substr($code,-2)); // en-nz -> en_NZ
        
        return $user_languages;
    }
    
function languages()
    {
    // pack abbreviation/language array
    // important note: you must have the default language as the last item in each major language, after all the
    // en-ca type entries, so en would be last in that case
        $a_languages = array(
        'af' => 'Afrikaans',
        'sq' => 'Albanian',
        'ar-dz' => 'Arabic (Algeria)',
        'ar-bh' => 'Arabic (Bahrain)',
        'ar-eg' => 'Arabic (Egypt)',
        'ar-iq' => 'Arabic (Iraq)',
        'ar-jo' => 'Arabic (Jordan)',
        'ar-kw' => 'Arabic (Kuwait)',
        'ar-lb' => 'Arabic (Lebanon)',
        'ar-ly' => 'Arabic (libya)',
        'ar-ma' => 'Arabic (Morocco)',
        'ar-om' => 'Arabic (Oman)',
        'ar-qa' => 'Arabic (Qatar)',
        'ar-sa' => 'Arabic (Saudi Arabia)',
        'ar-sy' => 'Arabic (Syria)',
        'ar-tn' => 'Arabic (Tunisia)',
        'ar-ae' => 'Arabic (U.A.E.)',
        'ar-ye' => 'Arabic (Yemen)',
        'ar' => 'Arabic',
        'hy' => 'Armenian',
        'as' => 'Assamese',
        'az' => 'Azeri',
        'eu' => 'Basque',
        'be' => 'Belarusian',
        'bn' => 'Bengali',
        'bg' => 'Bulgarian',
        'ca' => 'Catalan',
        'zh-cn' => 'Chinese (China)',
        'zh-hk' => 'Chinese (Hong Kong SAR)',
        'zh-mo' => 'Chinese (Macau SAR)',
        'zh-sg' => 'Chinese (Singapore)',
        'zh-tw' => 'Chinese (Taiwan)',
        'zh' => 'Chinese',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'div' => 'Divehi',
        'nl-be' => 'Dutch (Belgium)',
        'nl' => 'Dutch (Netherlands)',
        'en-au' => 'English (Australia)',
        'en-bz' => 'English (Belize)',
        'en-ca' => 'English (Canada)',
        'en-ie' => 'English (Ireland)',
        'en-jm' => 'English (Jamaica)',
        'en-nz' => 'English (New Zealand)',
        'en-ph' => 'English (Philippines)',
        'en-za' => 'English (South Africa)',
        'en-tt' => 'English (Trinidad)',
        'en-gb' => 'English (United Kingdom)',
        'en-us' => 'English (United States)',
        'en-zw' => 'English (Zimbabwe)',
        'en' => 'English',
        'us' => 'English (United States)',
        'et' => 'Estonian',
        'fo' => 'Faeroese',
        'fa' => 'Farsi',
        'fi' => 'Finnish',
        'fr-be' => 'French (Belgium)',
        'fr-ca' => 'French (Canada)',
        'fr-lu' => 'French (Luxembourg)',
        'fr-mc' => 'French (Monaco)',
        'fr-ch' => 'French (Switzerland)',
        'fr' => 'French (France)',
        'mk' => 'FYRO Macedonian',
        'gd' => 'Gaelic',
        'ka' => 'Georgian',
        'de-at' => 'German (Austria)',
        'de-li' => 'German (Liechtenstein)',
        'de-lu' => 'German (lexumbourg)',
        'de-ch' => 'German (Switzerland)',
        'de' => 'German (Germany)',
        'el' => 'Greek',
        'gu' => 'Gujarati',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'id' => 'Indonesian',
        'it-ch' => 'Italian (Switzerland)',
        'it' => 'Italian (Italy)',
        'ja' => 'Japanese',
        'kn' => 'Kannada',
        'kk' => 'Kazakh',
        'kok' => 'Konkani',
        'ko' => 'Korean',
        'kz' => 'Kyrgyz',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mt' => 'Maltese',
        'mr' => 'Marathi',
        'mn' => 'Mongolian (Cyrillic)',
        'ne' => 'Nepali (India)',
        'nb-no' => 'Norwegian (Bokmal)',
        'nn-no' => 'Norwegian (Nynorsk)',
        'no' => 'Norwegian (Bokmal)',
        'or' => 'Oriya',
        'pl' => 'Polish',
        'pt-br' => 'Portuguese (Brazil)',
        'pt' => 'Portuguese (Portugal)',
        'pa' => 'Punjabi',
        'rm' => 'Rhaeto-Romanic',
        'ro-md' => 'Romanian (Moldova)',
        'ro' => 'Romanian',
        'ru-md' => 'Russian (Moldova)',
        'ru' => 'Russian',
        'sa' => 'Sanskrit',
        'sr' => 'Serbian',
        'sk' => 'Slovak',
        'ls' => 'Slovenian',
        'sb' => 'Sorbian',
        'es-ar' => 'Spanish (Argentina)',
        'es-bo' => 'Spanish (Bolivia)',
        'es-cl' => 'Spanish (Chile)',
        'es-co' => 'Spanish (Colombia)',
        'es-cr' => 'Spanish (Costa Rica)',
        'es-do' => 'Spanish (Dominican Republic)',
        'es-ec' => 'Spanish (Ecuador)',
        'es-sv' => 'Spanish (El Salvador)',
        'es-gt' => 'Spanish (Guatemala)',
        'es-hn' => 'Spanish (Honduras)',
        'es-mx' => 'Spanish (Mexico)',
        'es-ni' => 'Spanish (Nicaragua)',
        'es-pa' => 'Spanish (Panama)',
        'es-py' => 'Spanish (Paraguay)',
        'es-pe' => 'Spanish (Peru)',
        'es-pr' => 'Spanish (Puerto Rico)',
        'es-us' => 'Spanish (United States)',
        'es-uy' => 'Spanish (Uruguay)',
        'es-ve' => 'Spanish (Venezuela)',
        'es' => 'Spanish (Traditional Sort)',
        'sx' => 'Sutu',
        'sw' => 'Swahili',
        'sv-fi' => 'Swedish (Finland)',
        'sv' => 'Swedish',
        'syr' => 'Syriac',
        'ta' => 'Tamil',
        'tt' => 'Tatar',
        'te' => 'Telugu',
        'th' => 'Thai',
        'ts' => 'Tsonga',
        'tn' => 'Tswana',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        'vi' => 'Vietnamese',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'zu' => 'Zulu' );
        
        return $a_languages;
    }
}

?>
