<?php // jEdit :folding=indent: :collapseFolds=1: :noTabs=true:
/**
* THE ADDRESS BOOK RELOADED: user registration manager
* @author Tobias Schlatter
* @package frontEnd
*/

/** */

chdir('..');
require_once('lib/init.php');
require_once('PageRegister.class.php');
require_once('StringHelper.class.php');
require_once('User.class.php');
require_once('ErrorHandler.class.php');
require_once('Options.class.php');
require_once("lib/phpmailer/class.phpmailer.php");

if (!isset($_GET['mode']))
    $_GET['mode'] = 'register';

$flag = '';

switch($_GET['mode']) {
    case 'lostpasswd':
        if ($options->getOption('lostpassword') == 0)
            $errorHandler->error('register','The option to retrieve a lost password is disabled.');
            //$flag = 'error';
        	break;
        if (!isset($_POST['email']))
            break;
        $user = new User(StringHelper::cleanGPC($_POST['email']));
        if ($user->id === null) {
            $errorHandler->error('register','A user with this e-mail does not exist');
            $flag = 'error';
            break;
        }

        $pw = mb_substr(md5(time() . time() . $user->id . $_POST['email']),0,10);

        $mailer = new PHPMailer();

        $mailer->From = 'noreply@' . $_SERVER['SERVER_NAME'];
        $mailer->FromName = 'noreply@' . $_SERVER['SERVER_NAME'];
        $mailer->AddAddress($_POST['email']);

        $mailer->Subject = $options->getOption('adminEmailSubject') . ' - Lost Password';
        $mailer->Body    = 'This is an auto-generated message from The Address Book Reloaded at ' . $_SERVER['SERVER_NAME'] .
        ".\nYour new password is " . $pw . ".\nPlease change it, the next time you log in.\n\n" . $options->getOption('adminEmailFooter');

        if(!$mailer->Send()) {
            $errorHandler->error('mail',$mailer->ErrorInfo);
            $flag = 'error';
            break;
        }

        $user->setPassword($pw);

        $flag = 'changed';
    break;
    case 'confirm':

        if (!isset($_POST['userid'],$_POST['email'],$_POST['password'],$_POST['hash']))
            break;

        $arry = Contact::contactsWithEmail($_POST['email']);
        if(count($arry)>1) {
            $errorHandler->error('register','Cannot register. Multiple contacts with this email address exist.');
            $flag = 'error';
            break;
        }

        $user = new User(StringHelper::cleanGPC($_POST['email']));
        if ($user->id === null) {
            $flag = 'error';
            break;
        }

        if (!$user->login(StringHelper::cleanGPC($_POST['password']))) {
            $flag = 'error';
            break;
        }

        if ($user->id != StringHelper::cleanGPC($_POST['userid'])) {
            $flag = 'error';
            $errorHandler->error('register','Logged in user and passed user-id do not match. Please use the most recent confirmation email.');
            break;
        }

        if (!$user->confirm(StringHelper::cleanGPC($_POST['hash']))) {
            $flag = 'error';
            break;
        }

        $_SESSION['user'] = &$user;

        if (isset($user->contact['id'])) {
            $flag = 'ok';
            break;
        }

        $user->setType('user');

        if ($user->attachContact())
            $flag = 'found';
        else
            $flag = 'created';

        // prevent incorrect error message 'user not confirmed' (Bug# 1639466)
        $errorHandler->clear('login');

    break;
    case 'register':
        if ($options->getOption('allowUserReg') != 'no') {
            if (!isset($_POST['email'],$_POST['password1'],$_POST['password2']))
                break;

            if ($_POST['password1'] != $_POST['password2']) {
                $flag = 'error';
                $errorHandler->error('register','Passwords are not the same');
                break;
            }

            if (!$_POST['password1']) {
                $flag = 'error';
                $errorHandler->error('register','Please enter a password');
                break;
            }

            if ($options->getOption('allowUserReg') != 'everyone' && Contact::getContactFromEmail(StringHelper::cleanGPC($_POST['email']),$dummy) <= 0) {
                $flag = 'error';
                $errorHandler->error('register','This e-mail belongs to no contact that is registered, please try another of your e-mails, or contact an admin');
                break;
            }

            $user = new User(StringHelper::cleanGPC($_POST['email']),StringHelper::cleanGPC($_POST['password1']),$options->getOption('allowUserReg') != 'contactOnlyNoConfirm');

            if ($user->id === null) {
                $flag = 'error';
                break;
            }

            if ($options->getOption('allowUserReg') == 'contactOnlyNoConfirm') {
                $user->confirm();
                $user->setType('user');
                $user->attachContact();
                $user->loggedIn = true;
                $_SESSION['user'] = &$user;
                header('Location:'.Navigation::mainPageUrl());
            }

            $flag = 'ok';
        } else
            $errorHandler->error('noLogin','Registration is turned off');

    break;
    /* case 'attachContact': // CODE MOVED TO authorize.php
        $user = &$_SESSION['user'];
        if ($user->attachContact())
            $flag = 'found';
        else
            $flag = 'created';

        $_GET['mode'] = 'confirm';
    break; */
    case 'cuser':
        if (!$_SESSION['user']->isAtLeast('admin'))
            $errorHandler->standardError('PERMISSION_DENIED',basename($_SERVER['SCRIPT_NAME']));

        if (!isset($_GET['id']))
            $_GET['id'] = '';

        $cont = Contact::newContact(intval($_GET['id']));

        if ($cont->isUser()) {
            $errorHandler->error('register','This contact is already a user');
            require('../contact/contact.php');
            break;
        }

        // each user's email must be unique in the DB so we
        // check if no other contact exists that has the same email
        foreach ($cont->getValueGroup('email') as $eml)
            if (Contact::getContactFromEmail($eml['value'],$tmp) > 0 || ($tmp = User::getUserFromEmail($eml['value']))) {
                if ($tmp['id']==$cont->contact['id']) // skip the contact for which this request is
                    continue;

                if (!isset($tmp['id']))
                    $errorHandler->error('register','The e-mail address ' . $eml['value'] . ' also belongs to a user which is in the registration process.');
                else
                    $errorHandler->error('register','The e-mail address ' . $eml['value'] . ' also belongs to the contact <a href="../contact/contact.php?id=' . $tmp['id'] . '">' . $tmp['lastname'] . ', ' . $tmp['firstname'] . '</a>');
                $flag = 'error';
                break 2;
            }


        if (!isset($_POST['email'],$_POST['password1'],$_POST['password2']))
            break;

        if ($_POST['password1'] != $_POST['password2']) {
            $flag = 'error';
            $errorHandler->error('register','Passwords are not the same');
            break;
        }

        if ($_POST['password1'] == '') {
            $flag = 'error';
            $errorHandler->error('register','Please enter a password');
            break;
        }

        $user = new User(StringHelper::cleanGPC($_POST['email']),StringHelper::cleanGPC($_POST['password1']),false);

        if ($user->id === null) {
            $flag = 'error';
            break;
        }

        $user->confirm();

        $user->setType('user');

        if (!$user->attachContact() || !$user->contact['id'] == StringHelper::cleanGPC($_GET['id'])) {
            $errorHandler->error('register','This e-mail doesn\'t belong to this contact');
            $user->delete();
            $flag = 'error';
            break;
        }

        $flag = 'ok';
    break;
    case 'resend':
        if (!isset($_GET['email']))
            break;

        $user = new User(StringHelper::cleanGPC($_GET['email']));

        if ($user->id === null) {
            $errorHandler->error('register','A user with this e-mail does not exist');
            $flag = 'error';
            break;
        }

        if ($user->isConfirmed()) {
            $errorHandler->error('register','This user does not need to be confirmed');
            $flag = 'error';
            break;
        }

        $user->setEmail(StringHelper::cleanGPC($_GET['email']));
        $flag = 'ok';

    break;
}

$page = new PageRegister(StringHelper::cleanGPC($_GET['mode']),$flag,isset($_GET['redirect']) ? $_GET['redirect'] : '');
echo $page->create();

exit();

?>
