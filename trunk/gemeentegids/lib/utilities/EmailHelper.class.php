<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link EmailHelper}
* @package utilities
* @author Thomas Katzlberger
*/

/**
* Utility class to generate mailto: links OR return a link to an internal mail script
* Any link for sending an emails should pass through this class to create a central 
* hook if someone wants to change sending emails through a serverside interface.
* @package utilities
*/
class EmailHelper {

    /**
    * generate a HTML link to send an email. Takes care of obscuring.
    * @static
    * @param string|array list of emails
    * @param string $text text between <a> and </a>
    * @return string 'mailto:-emails-'
    */
function sendEmailHref($email) 
    {        
        if(!is_array($email))
            return StringHelper::obscureString(rawurlencode($email));
        
        $ret = '';
        foreach($email as $e)
            $ret .= StringHelper::obscureString(rawurlencode($e)) . ",";
        
        return $ret;
    }
    
    /**
    * generate a HTML link to send an email. Takes care of obscuring.
    * @static
    * @param string|array list of emails
    * @param string $text text between <a> and </a>
    * @return string <a href="mailto:-emails-"> $text </a>
    */
function sendEmailLink($email, $text) 
    {
        return "<a class='email' href='mailto:" . EmailHelper::sendEmailHref($email) . "'>" . StringHelper::obscureString($text) . '</a>';
    }
    
}

?>
