<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link PageAdminPanel}
* @author Tobias Schlatter
* @package frontEnd
* @subpackage pages
*/

/** */
require_once('Page.class.php');
require_once('Navigation.class.php');

/**
* the error page
* 
* this page is used to show fatal errors
* it is used ONLY by the {@link ErrorHandler}, if a fatal error occurs.
* This page is NOT to be used directly
* @package frontEnd
* @subpackage pages
*/
class PageError extends Page {

    /**
    * @var ErrorHandler
    */
    var $errorHandler;
    var $error; //cache
    
    /**
    * Constructor
    *
    * init superclass, according to error type, also init the two class vars
    * @param ErrorHandler global errorHandler
    * @param string $cause error type specific string
    */
function PageError($errorHandler) { // this should prevent people from using this class directly
        
        $this->errorHandler = $errorHandler;
        $this->error = $this->errorHandler->getLastError();
        switch ($this->error['type']) {
            case 'db':
                $this->Page('Database Error');
            break;
            case 'denied':
            case 'noLogin':
                $this->Page('Login Error');
            break;
            case 'adminLock':
                $this->Page('Administrative Lock');
            break;
            case 'install':
                $this->Page('Installation Error');
            break;
            case 'plugin':
                $this->Page('Plugin Error');
            break;
            case 'noFile':
                $this->Page('File not Found');
            break;
            case 'internal':
                $this->Page('Internal Assertion Error');
            break;
            default:
                $this->Page('Error');
            break;
        }
    }

    /**
    * create error page according to error that occured
    * @return string html-content
    */
function innerCreate() {
        global $CONFIG_TAB_ROOT;
        
        $cont = <<<EOC
        <div class="error-box">
EOC;

        switch ($this->error['type']) {
            case 'db':
                $cont .= '<div class="error-title">An error in the database occured:</div>';
                $cont .= '<div class="error-body">' . $this->errorHandler->errorString() . '</div>';
                $cont .= '<div class="error-body">NOTE: Developers (SVN repository users) will see development DB upgrades that run the same upgrade file multiple times. This may result in an error (Duplicate column name or similar) as soon as MySQL attempts a change that is already in the DB from the previous upgrade. This error should be ignored. The database should be at the newest version.</div>';
            break;
            case 'noLogin': // DISABLED - REDIRECTS IMMEDIATELY TO LOGIN PAGE IN ErrorHandler
            case 'denied':  // permission denied error
                $cont .= '<div class="error-title">An error with your login occured:</div>';
                $cont .= '<div class="error-body">' . $this->errorHandler->errorString() . '</div>';
                break;
            case 'adminLock':
                $cont .= '<div class="error-title">Administrative Lock Active</div>';
                $cont .= '<div class="error-body">This application is currently locked by an administrator because of database maintainance. You may not edit or delete any entries. Please retry later.</div>';
            break;
            case 'install':
                $cont .= '<div class="error-title">During installation the following error occurred:</div>';
                $cont .= '<div class="error-body">' . $this->errorHandler->errorString() . '</div>';
            break;
            case 'noFile':
                $cont .= '<div class="error-title">File not found:</div>';
                $cont .= '<div class="error-body">The file ' . $this->errorHandler->errorString() . ' could not be found.</div>';
            break;
            default:
                $cont .= '<div class="error-title">The following error occurred:</div>';
                $cont .= '<div class="error-body">' . $this->errorHandler->errorString() . '</div>';
            break;
        }

        $cont .= '<div class="error-footer">If necessary, please press the BACK button on your browser to return to the previous screen and correct any possible mistakes. You can also try the following actions that might solve your problem:<ul>';
        // not sure if we need this ...
        // $cont .= '<li><a style="font-size:larger;" href="'.$_SERVER['PHP_SELF'].'">go back</a></li>';
        $cont .= '<li><a style="font-size:larger;" href="'.Navigation::mainPageUrl().'">default page</a></li>';
        $cont .= '<li><a href="'.$CONFIG_TAB_ROOT.'user/login.php?redirect='.Navigation::mainPageUrl().'">login</a></li>';
        $cont .= '<li><a href="'.$CONFIG_TAB_ROOT.'user/logout.php">logout</a></li>';
        $cont .= '<li id="em0"><a href="#" onclick="Effect.SlideUp(\'em0\'); Effect.SlideDown(\'em1\'); Effect.SlideDown(\'em2\'); Effect.SlideDown(\'em3\'); Effect.SlideDown(\'em4\'); return false;">advanced</a></li>';
        
        $cont .= '<li style="display:none" id="em1"><a href="'.$CONFIG_TAB_ROOT.'lib/support/destroysession.php">destroy session (force logout)</a></li>';
        
        // Cannot be moved to admin section. Authozization does not work.
        $cont .= '<li style="display:none" id="em3"><a href="'.$CONFIG_TAB_ROOT.'admin/upgrade.php">[upgrade database]</a></li>';
        $cont .= '<li style="display:none" id="em2"><a href="'.$CONFIG_TAB_ROOT.'admin/install.php">[install database]</a></li>';
        
        // Avoid recursion from rightsManager if 'no user error' occurs!
        if(isset($_SESSION['user']))
        {
            $rightsManager = RightsManager::getSingleton();
            if($rightsManager->currentUserIsAllowedTo('administrate'))
            {
                $cont .= '<li style="display:none" id="em4"><a href="'.$CONFIG_TAB_ROOT.'lib/support/phpinfo.php">[php info]</a></li>';
            }
        }
            
        $cont .= '</ul><br/>If you still need help, or you believe this to be a bug, copy the calling URL from the browser <b>NOW</b> and then please notify ';
        global $CONFIG_BUG_TRACK_LINK;
        $cont .= isset($CONFIG_BUG_TRACK_LINK) ? $CONFIG_BUG_TRACK_LINK : '<a href="http://sourceforge.net/tracker/?atid=861161&group_id=172286&func=browse" target="_blank">Bug Tracker</a>.';
        $cont .= '</div></div>';

        return $cont;

    }
    
    /*
    * Overridde to suppress standard error message, but this would suppress other notices before...?
function errorCreate(&$errorHandler) { return ''; }
    */
}

?>
