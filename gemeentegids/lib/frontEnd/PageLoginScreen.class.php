<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageLoginScreen}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');
require_once('ErrorHandler.class.php');
require_once('Options.class.php');

/**
* the login screen
*
* the login screen is the first page that is displayed
* it shows links to register users and to recover lost passwords
* @package frontEnd
* @subpackage pages
*/
class PageLoginScreen extends Page {

    var $redirect;

    /**
    * Constructor
    *
    * init superclass
    */
function PageLoginScreen($redirect='') {
        $this->Page('Login Screen');

        $this->redirect = $redirect;
    }

    /**
    * create the content of login page
    * @return string html-content
    * @global Options used to determine login message and whether to display register link
    * @global ErrorHandler used for error handling
    */
function innerCreate() {

        global $options, $errorHandler, $CONFIG_TAB_SERVER_ROOT;

        //$cont ='<div class="login-form"><img src="'.$CONFIG_TAB_SERVER_ROOT.'images/banner.png" class="tab-title" alt="The Address Book" />';
        $cont ='<div class="login-form">';

        if ($options->getOption('msgLogin') != '')
            $cont .= '<div class="login-message">' . $options->getOption('msgLogin') . '</div>';

        $err = $errorHandler->getLastError('login');
        if ($err)
            $cont .= '<div class="login-error">' . $err['cause'] . '</div>';

        $redirect = !empty($this->redirect) ? '?redirect='.$this->redirect : '';
        $cont .= '<form method="post" action="'.$CONFIG_TAB_SERVER_ROOT.'user/authorize.php'.$redirect.'">';
        $cont .= <<<EOC
        <div><label for="user_email">E-Mail</label></div>
        <div><input type="text" name="user_email" id="user_email" size="40" /></div>
        <br/>
        <div><label for="user_password">Password</label></div>
        <div><input type="password" name="user_password" id="user_password" size="40" /></div>
        <br/>
        <div><button type="submit">login</button></div>
        </form>
        <br/>
EOC;
        $redirect = !empty($this->redirect) ? '&redirect='.$this->redirect : '';

        if ($options->getOption('lostpassword') != 0)
	        $cont .= '<div class="login-register"><a href="'.$CONFIG_TAB_SERVER_ROOT.'user/register.php?mode=lostpasswd'.$redirect.'">lost password</a></div>';

        if ($options->getOption('allowUserReg') != 'no')
            $cont .= '<br/><div class="login-register"><a href="'.$CONFIG_TAB_SERVER_ROOT.'user/register.php?mode=register">register</a></div>';

        if ($options->getOption('requireLogin') != 1)
            $cont .= '<br/><div class="login-guest"><a href="'.Navigation::mainPageUrl().'">enter as a guest</a></div>';

        $cont .= '</div>';

        return $cont;

    }

    /**
    * We overridde this to relocate standard error message output
    */
function errorCreate(&$errorHandler)
    {
        return '';
    }
}

?>
