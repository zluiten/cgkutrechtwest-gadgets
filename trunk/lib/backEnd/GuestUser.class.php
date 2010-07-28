<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* contains class {@link GuestUser}
* @package backEnd
* @author Tobias Schlatter
*/

/** */
require_once('User.class.php');

/**
* Dummy class, for guest users which aren't acutally in the database
*
* All methods return empty or dummy values
* @package backEnd
*/
class GuestUser extends User {
    
function GuestUser()
    {
        
        $this->id = '@noID';
        $this->type = 'guest';
        $this->loggedIn = true;
        
    }
    
function login($pw) {}
function setType($type) {}
function setPassword($pw) {}
function isUser() { return 0;}
function confirm($hash = NULL) {return true;}
function attachContact() {return true;}
function __sleep() {return array('type','loggedIn','id');}
function __wakeup() {}
function save() {}
function trash() {}
function delete() {}
function getValueGroup($type) {}
    
}
