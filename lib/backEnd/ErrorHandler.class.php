<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:

/**
* Contains class {@link ErrorHandler ErrorHandler}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('PageError.class.php');

/**
* Error Handler (global Singleton)
*
* Handles all errors that occur during usage of TAB,
* decides whether the error is fatal ('db','invArg','retVal','script','noLogin' [redirects to login panel], 
*'denied','adminLock','install','noFile','version','internal') or not ('nonFatal')
* and outputs an error page, or saves the error respectively. 
* In case of errors {@link Page} will print all error messages before any other output
* @package backEnd
*/

class ErrorHandler {
    
    /**
    * @var array Saves all errors that occured
    */
    var $errors;
    
    /**
    * @var array Constant to save error-types, which are considered fatal and lead to generation of an error page
    * WARNING: CHANGING EXISTING LABELS MAY HAVE SERVERE SECURITY IMPACT!
    */
    var $fatalErrors = array(
        'db',
        'invArg',
        'retVal',
        'script',
        'noLogin', // only for not logged in error, redirects to login panel
        'denied',
        'adminLock',
        'install',
        'noFile',
        'version',
        'internal'
    );
    
    /**
    * @var array of standard errors: PERMISSION_DENIED
    * WARNING: CHANGING EXISTING LABELS MAY HAVE SERVERE SECURITY IMPACT!
    */
    var $standardErrors = array(
            'STANDARD_ERROR' => array('internal','Internal Error: Incorrect call to ErrorHandler::standardError(). Please file a bug report!'),
            'PERMISSION_DENIED' => array('denied','You are not allowed to perform this action.'),
            'NOT_LOGGED_IN' => array('noLogin','You are currently not logged in. Please login with appropriate privileges to perform this action.'),
            'PARAMETER_MISSING' => array('invArg','Internal error: A required parameter for this function is missing.')
        );

    /**
    * Constructor, initializes {@link $this->errors}
    */
function ErrorHandler() {
        $this->clear();
    }
    
    /**
    * This method 'fixes' the global use of $errorHandler for future use.
    */
function getSingleton()
    {
        global $errorHandler;
        return $errorHandler;
    }
    
    /**
    * Clears all or specific errors ... use with caution.
    */
function clear($type=null) {
        if($type)
        {
            for($i=0;$i<count($this->errors);$i++)
                if($type==$this->errors[$i]['type'])
                    unset($this->errors[$i]);
                
            return;
        }
        
        $this->errors = array();
    }

    /** 
    * Registers a success message displayed in green. Alias: error('ok',...);
    *
    * @param string $originator object that caused the error; usually get_class($this)
    * @param string $cause type-specific error message (normally shown)
    */
function success($cause='',$originator='') {
        return $this->error('ok',$cause,$originator);
    }
    
    /** 
    * Registers a warning message displayed in red. Alias: error('warning',...);
    *
    * @param string $originator object that caused the error; usually get_class($this)
    * @param string $cause type-specific error message (normally shown)
    */
function warning($cause='',$originator='') {
        return $this->error('warning',$cause,$originator);
    }
    
    /** 
    * If a fatal error occurs generate PageError or redirect to LoginPanel
    */
function checkFatalError($type)
    {
        if (in_array($type,$this->fatalErrors)) 
        {
            if($type=='noLogin' && (strpos($_SERVER['QUERY_STRING'],'redirect') === FALSE)) // avoid infinite redirect recursion
            {
                global $CONFIG_TAB_ROOT;
                header('Location: ' .$CONFIG_TAB_ROOT.'user/login.php?redirect=' . urlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));
                exit();
            }
            
            $errorPage = new PageError($this);
            echo $errorPage->create();
            
            /* echo '<pre>';
            var_dump(debug_backtrace());
            echo '</pre>'; */
            
            exit();
        }
    }
    
    /** 
    * Registers a new error
    *
    * This function registers a new error
    * decides whether it is a fatal error or not
    * and shows error page or adds error to error list.
    * A special error is 'ok' which displays in green.
    * Non fatal errors are displayed in the Page class.
    * @param string $type error type
    * @param string $originator object that caused the error; usually get_class($this)
    * @param string $cause type-specific error message (normally shown)
    */
function error($type,$cause='',$originator='') {
        
        // store the error
        $this->errors[] = array(
            'type' => $type,
            'cause' => $cause,
            'originator' => $originator
        );
        
        $this->checkFatalError($type);
    }
    /** 
    * Registers a new error
    *
    * This function registers a new error
    * decides whether it is a fatal error or not
    * and shows error page or adds error to error list.
    * A special error is 'ok' which displays in green.
    * Non fatal errors are displayed in the Page class.
    * @param string $id of standard error
    * @param string $originator object that caused the error; usually get_class($this)
    */
function standardError($id,$originator='') {
        $err = $this->standardErrors[$id];
        
        if(empty($err))
            $err = $this->standardErrors['STANDARD_ERROR'];
        
        $type = $err[0];
        $cause = $err[1];
        
        // store the error
        $this->errors[] = array(
            'type' => $type,
            'cause' => $cause,
            'originator' => $originator
        );
        
        $this->checkFatalError($type);
    }
    
    /**
    * Get the last error
    *
    * Gets the last error that occured
    * if {@link $type} is specified
    * it gets the last error of that type
    * @param string $type error type to get errors
    * @return array associative array with indizes type and cause 
    */
function getLastError($type='') {
        
        for ($i=count($this->errors)-1;$i>=0;$i--)
            if ($type == '' || $this->errors[$i]['type'] == $type)
                return $this->errors[$i];
                
        return NULL;
        
    }
    
    /**
    * Get all errors
    * 
    * Gets all errors or all errors of a type
    * @param string $type error type to get errors
    * @return array array with errors, as returned from {@link getLastError()}
    */
function getErrors($type='') {
        if (!$type)
            return $this->errors;
            
        $er = array();
        
        for ($i=count($this->errors)-1;$i>=0;$i--)
            if ($this->errors[$i]['type'] == $type)
                $er[] = $this->errors[$i];
                
        return $er;
        
    }

    /**
    * Method that returns an HTML string for the error (or last error).
    * 
    * @param array $error array returned by getErrors() or last error if null.
    * @return array array with errors, as returned from {@link getLastError()}
    */
function errorString($error=null) {
        if($error==null) $error = $this->getLastError();
        return  !empty($error['originator']) ? '<span class="error-originator">' . $error['originator'] . ': </span>' . $error['cause'] : $error['cause'];   
    }
    
    /**
    * Method that returns all current errors as HTML string each in its own DIV.
    * CSS classes used: error-ok (green), error-warning (khaki), error-notice (red)
    * 
    * @return string HTML code
    */
function errorDIVs()
    {
        $cont ='';
        foreach ($this->getErrors() as $err)
        {
            // generate correct CSS tag
            switch($err['type'])
            {   case 'ok'     : $div = '<div class="error-ok">'; break;
                case 'warning': $div = '<div class="error-warning">'; break;
                default       : $div = '<div class="error-notice">'; break;
            }
            
            $cont .= $div . $this->errorString($err) . '</div>';
        }

        return $cont;
   }
}

/**
* @global ErrorHandler $errorHandler
*/
$errorHandler = new ErrorHandler();

?>
